<?php

class OrderService {
    private $db;
    private $userId;
    
    public function __construct($database, $userId) {
        $this->db = $database;
        $this->userId = $userId;
    }
    
    /**
     * Tạo đơn hàng mới từ giỏ hàng
     * Trả về: order ID hoặc null nếu thất bại
     */
    public function createOrder($shipping, $items, $amounts) {
        try {
            $this->db->beginTransaction();
            
            // 1️⃣ Tạo đơn hàng chính
            $orderNumber = $this->generateOrderNumber();
            $orderSql = "INSERT INTO orders (
                order_number, user_id,
                recipient_name, recipient_phone, shipping_address, city, district, ward,
                subtotal, shipping_fee, discount_amount, total_amount,
                payment_method, payment_status, status, notes, created_at
            ) VALUES (
                :order_number, :user_id,
                :recipient_name, :recipient_phone, :shipping_address, :city, :district, :ward,
                :subtotal, :shipping_fee, :discount_amount, :total_amount,
                :payment_method, 'pending', 'pending', :notes, CURRENT_TIMESTAMP
            ) RETURNING id";
            
            $orderRow = $this->db->queryOne($orderSql, [
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
            ]);
            
            $orderId = $orderRow['id'] ?? null;
            if (!$orderId) {
                throw new Exception('Không thể tạo đơn hàng');
            }
            
            // 2️⃣ Thêm items vào đơn hàng + cập nhật tồn kho
            foreach ($items as $item) {
                $price = getDisplayPrice($item['price'], $item['sale_price']);
                
                // Thêm order item
                $this->db->insert(
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
                        'shop_id' => $item['shop_id'],
                        'product_name' => $item['name'],
                        'product_thumbnail' => $item['main_image'],
                        'price' => $price,
                        'quantity' => $item['quantity'],
                        'subtotal' => $price * $item['quantity']
                    ]
                );
                
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
            return $orderId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('OrderService::createOrder - ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Lấy danh sách đơn hàng của người dùng
     */
    public function getUserOrders() {
        return $this->db->query(
            "SELECT id, order_number, total_amount, status, payment_status, created_at
             FROM orders
             WHERE user_id = :user_id
             ORDER BY created_at DESC",
            ['user_id' => $this->userId]
        );
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
