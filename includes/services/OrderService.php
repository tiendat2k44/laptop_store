<?php
/**
 * Dịch Vụ Đơn Hàng (OrderService)
 * Quản lý đơn hàng - tạo, cập nhật, hủy, lấy thông tin đơn hàng
 */

class OrderService {
    private $db;
    private $userId;
    
    public function __construct($database, $userId) {
        $this->db = $database;
        $this->userId = $userId;
    }
    
    /**
     * Tạo đơn hàng mới từ giỏ hàng
     * @param array $shipping Thông tin giao hàng (tên, sdt, địa chỉ...)
     * @param array $items Danh sách sản phẩm đặt hàng
     * @param array $amounts Số tiền (tạm tính, ship, giảm giá, tổng)
     * @return int|null order ID hoặc null nếu thất bại
     */
    public function createOrder($shipping, $items, $amounts) {
        try {
            $this->db->beginTransaction();
            
            // Bước 1: Tạo đơn hàng chính
            $orderNumber = $this->generateOrderNumber();
            // Chèn đơn hàng, tương thích cả PostgreSQL (RETURNING) và MySQL (lastInsertId)
            $driver = $this->db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
            $params = [
                'order_number' => $orderNumber,
                'user_id' => $this->userId,
                'recipient_name' => $shipping['name'],
                'recipient_phone' => $shipping['phone'],
                'shipping_address' => $shipping['address'],
                'city' => $shipping['city'],
                'district' => $shipping['district'] ?? '',
                'ward' => $shipping['ward'] ?? '',
                'subtotal' => $amounts['subtotal'],
                'shipping_fee' => $amounts['shipping_fee'],
                'discount_amount' => $amounts['discount_amount'],
                'total_amount' => $amounts['total_amount'],
                'payment_method' => $shipping['payment_method'],
                'notes' => $shipping['notes'] ?? ''
            ];

            if ($driver === 'pgsql') {
                $orderRow = $this->db->queryOne(
                    "INSERT INTO orders (
                        order_number, user_id,
                        recipient_name, recipient_phone, shipping_address, city, district, ward,
                        subtotal, shipping_fee, discount_amount, total_amount,
                        payment_method, payment_status, status, notes, created_at
                    ) VALUES (
                        :order_number, :user_id,
                        :recipient_name, :recipient_phone, :shipping_address, :city, :district, :ward,
                        :subtotal, :shipping_fee, :discount_amount, :total_amount,
                        :payment_method, 'pending', 'pending', :notes, CURRENT_TIMESTAMP
                    ) RETURNING id",
                    $params
                );
                $orderId = $orderRow['id'] ?? null;
            } else {
                $this->db->insert(
                    "INSERT INTO orders (
                        order_number, user_id,
                        recipient_name, recipient_phone, shipping_address, city, district, ward,
                        subtotal, shipping_fee, discount_amount, total_amount,
                        payment_method, payment_status, status, notes, created_at
                    ) VALUES (
                        :order_number, :user_id,
                        :recipient_name, :recipient_phone, :shipping_address, :city, :district, :ward,
                        :subtotal, :shipping_fee, :discount_amount, :total_amount,
                        :payment_method, 'pending', 'pending', :notes, NOW()
                    )",
                    $params
                );
                // Lấy ID theo cách MySQL
                $orderId = (int)$this->db->getConnection()->lastInsertId();
            }
            if (!$orderId) {
                throw new Exception('Không thể tạo đơn hàng');
            }
            
            // Bước 2: Thêm sản phẩm vào đơn hàng + cập nhật tồn kho
            foreach ($items as $item) {
                $price = getDisplayPrice($item['price'], $item['sale_price']);
                
                // Xử lý shop_id: nếu không có, dùng 1 (shop mặc định)
                $shopId = !empty($item['shop_id']) ? (int)$item['shop_id'] : 1;
                
                // Thêm order item
                $itemInsertResult = $this->db->insert(
                    "INSERT INTO order_items (
                        order_id, product_id, shop_id, product_name, product_thumbnail,
                        price, quantity, subtotal, status, created_at
                    ) VALUES (
                        :order_id, :product_id, :shop_id, :product_name, :product_thumbnail,
                        :price, :quantity, :subtotal, 'pending', CURRENT_TIMESTAMP
                    )",
                    [
                        'order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'shop_id' => $shopId,
                        'product_name' => $item['name'],
                        'product_thumbnail' => $item['main_image'],
                        'price' => $price,
                        'quantity' => $item['quantity'],
                        'subtotal' => $price * $item['quantity']
                    ]
                );
                
                if ($itemInsertResult === false) {
                    throw new Exception('Không thể thêm sản phẩm vào đơn hàng: ' . $item['name']);
                }
                
                // Cập nhật tồn kho & số lượng bán
                $this->db->execute(
                    "UPDATE products 
                     SET stock_quantity = stock_quantity - :qty, 
                         sold_count = sold_count + :qty 
                     WHERE id = :pid AND stock_quantity >= :qty",
                    ['qty' => $item['quantity'], 'pid' => $item['product_id']]
                );
            }
            
            $this->db->commit();
            
            // Gửi email xác nhận đơn hàng (không chặn luồng nếu lỗi)
            try {
                $user = $this->db->queryOne("SELECT id, email, full_name FROM users WHERE id = :id", ['id' => $this->userId]);
                if ($user && !empty($user['email'])) {
                    $order = $this->db->queryOne("SELECT * FROM orders WHERE id = :id", ['id' => $orderId]);
                    $items = $this->db->query("SELECT product_name, quantity, subtotal FROM order_items WHERE order_id = :oid", ['oid' => $orderId]);
                    $body = tpl_order_created($order, $items);
                    @send_mail($user['email'], '[".SITE_NAME."] Xác nhận đơn '. $order['order_number'], $body);
                }
            } catch (Throwable $mailEx) {
                error_log('OrderService::mail createOrder - ' . $mailEx->getMessage());
            }

            return ['id' => $orderId, 'order_number' => $orderNumber];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('OrderService::createOrder - ' . $e->getMessage());
                return null;
        }
    }
    
    /**
     * Lấy danh sách đơn hàng của người dùng (có thể lọc theo trạng thái)
     * @param string|null $status
     */
    public function getUserOrders($status = null) {
        $params = ['user_id' => $this->userId];
        $where = 'user_id = :user_id';
        if ($status) {
            $where .= ' AND status = :status';
            $params['status'] = $status;
        }
        return $this->db->query(
            "SELECT id, order_number, total_amount, status, payment_status, created_at
             FROM orders
             WHERE $where
             ORDER BY created_at DESC",
            $params
        );
    }

    /**
     * Đếm số lượng đơn theo trạng thái cho người dùng
     */
    public function getUserOrderCounts() {
        $rows = $this->db->query(
            "SELECT status, COUNT(*) AS cnt
             FROM orders
             WHERE user_id = :user_id
             GROUP BY status",
            ['user_id' => $this->userId]
        );
        $counts = [
            'all' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'processing' => 0,
            'shipping' => 0,
            'delivered' => 0,
            'cancelled' => 0,
        ];
        foreach ($rows as $r) {
            $counts[$r['status']] = (int)$r['cnt'];
            $counts['all'] += (int)$r['cnt'];
        }
        return $counts;
    }

    /**
     * Hủy đơn hàng của người dùng (chỉ cho phép khi pending/confirmed và chưa thanh toán)
     */
    public function cancelOrder($orderId) {
        try {
            // Kiểm tra quyền và trạng thái
            $order = $this->db->queryOne(
                "SELECT id, status, payment_status FROM orders WHERE id = :id AND user_id = :uid",
                ['id' => $orderId, 'uid' => $this->userId]
            );
            if (!$order) {
                throw new Exception('Đơn hàng không tồn tại');
            }
            if (!in_array($order['status'], ['pending', 'confirmed'], true)) {
                throw new Exception('Không thể hủy đơn ở trạng thái hiện tại');
            }
            if ($order['payment_status'] === 'paid') {
                throw new Exception('Đơn đã thanh toán, vui lòng liên hệ hỗ trợ');
            }

            $this->db->beginTransaction();

            // Hoàn kho và trừ sold_count
            $items = $this->db->query(
                "SELECT product_id, quantity FROM order_items WHERE order_id = :oid",
                ['oid' => $orderId]
            );
            foreach ($items as $it) {
                $this->db->execute(
                    "UPDATE products
                     SET stock_quantity = stock_quantity + :qty,
                         sold_count = GREATEST(sold_count - :qty, 0)
                     WHERE id = :pid",
                    ['qty' => (int)$it['quantity'], 'pid' => (int)$it['product_id']]
                );
            }

            // Cập nhật trạng thái items và đơn
            $this->db->execute(
                "UPDATE order_items SET status = 'cancelled' WHERE order_id = :oid",
                ['oid' => $orderId]
            );
            $this->db->execute(
                "UPDATE orders SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = :oid",
                ['oid' => $orderId]
            );

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('OrderService::cancelOrder - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy chi tiết đơn hàng
     */
    public function getOrderDetail($orderId) {
        return $this->db->queryOne(
            "SELECT * FROM orders WHERE id = :id AND user_id = :user_id",
            ['id' => $orderId, 'user_id' => $this->userId]
        );
    }
    
    /**
     * Lấy danh sách sản phẩm trong đơn hàng
     */
    public function getOrderItems($orderId) {
        return $this->db->query(
            "SELECT oi.*, p.id as product_id FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = :order_id",
            ['order_id' => $orderId]
        );
    }
    
    /**
     * Tạo mã đơn hàng unique
     */
    private function generateOrderNumber() {
        return ORDER_PREFIX . date('YmdHis') . substr(strval(rand(1000, 9999)), -4);
    }
}

?>
