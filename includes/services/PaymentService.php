<?php
/**
 * Payment Service - Service xử lý thanh toán
 * 
 * Các phương thức:
 * - getPaymentMethod()
 * - initializePayment()
 * - confirmPayment()
 * - getTransactionLog()
 */

class PaymentService {
    private $db;
    private $vnpay;
    private $momo;
    
    public function __construct($database) {
        $this->db = $database;
        
        // Load gateway classes
        require_once __DIR__ . '/../payment/VNPayGateway.php';
        require_once __DIR__ . '/../payment/MoMoGateway.php';
        
        $this->vnpay = new VNPayGateway($this->db);
        $this->momo = new MoMoGateway($this->db);
    }
    
    /**
     * Lấy phương thức thanh toán của đơn hàng
     * 
     * @param int $orderId ID đơn hàng
     * @return array|null
     */
    public function getPaymentMethod($orderId) {
        return $this->db->queryOne(
            "SELECT payment_method FROM orders WHERE id = :id",
            ['id' => $orderId]
        );
    }
    
    /**
     * Khởi tạo thanh toán cho một đơn hàng
     * 
     * @param int $orderId ID đơn hàng
     * @param string $method Phương thức (VNPAY, MOMO, COD)
     * @return array ['success' => bool, 'url' => string, 'error' => string]
     */
    public function initializePayment($orderId, $method = 'VNPAY') {
        try {
            // Lấy thông tin đơn hàng
            $order = $this->db->queryOne(
                "SELECT id, order_number, total_amount FROM orders WHERE id = :id",
                ['id' => $orderId]
            );
            
            if (!$order) {
                return ['success' => false, 'error' => 'Không tìm thấy đơn hàng'];
            }
            
            // Tạo URL thanh toán theo phương thức
            if ($method === 'VNPAY') {
                $url = $this->vnpay->createPaymentUrl($order);
                return ['success' => true, 'url' => $url];
            } elseif ($method === 'MOMO') {
                $result = $this->momo->createPayment($order);
                if ($result['success']) {
                    return [
                        'success' => true,
                        'data' => $result['data'],
                        'endpoint' => $result['endpoint']
                    ];
                }
                return ['success' => false, 'error' => 'Tạo request MoMo thất bại'];
            } elseif ($method === 'COD') {
                // Thanh toán khi nhận hàng
                $this->db->execute(
                    "UPDATE orders SET status = 'confirmed' WHERE id = :id",
                    ['id' => $orderId]
                );
                return ['success' => true, 'method' => 'COD'];
            }
            
            return ['success' => false, 'error' => 'Phương thức thanh toán không hỗ trợ'];
            
        } catch (Exception $e) {
            error_log('Payment initialization error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Lỗi hệ thống'];
        }
    }
    
    /**
     * Xác nhận thanh toán từ gateway
     * 
     * @param string $gateway Cổng thanh toán (vnpay, momo)
     * @param array $data Dữ liệu từ gateway
     * @return array ['success' => bool, 'message' => string]
     */
    public function confirmPayment($gateway, $data) {
        try {
            if ($gateway === 'vnpay') {
                return $this->vnpay->verifyReturn($data);
            } elseif ($gateway === 'momo') {
                return $this->momo->verifyReturn($data);
            }
            
            return ['success' => false, 'message' => 'Gateway không hỗ trợ'];
        } catch (Exception $e) {
            error_log('Payment confirmation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi khi xác nhận thanh toán'];
        }
    }
    
    /**
     * Lấy lịch sử giao dịch của một đơn hàng
     * 
     * @param int $orderId ID đơn hàng
     * @return array
     */
    public function getTransactionLog($orderId) {
        return $this->db->query(
            "SELECT * FROM payment_transactions 
             WHERE order_id = :id 
             ORDER BY created_at DESC",
            ['id' => $orderId]
        );
    }
    
    /**
     * Lấy tất cả giao dịch
     * 
     * @param array $filters Bộ lọc
     * @return array
     */
    public function getAllTransactions($filters = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filters['gateway'])) {
            $where .= " AND gateway = :gateway";
            $params['gateway'] = $filters['gateway'];
        }
        
        if (!empty($filters['status'])) {
            $where .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where .= " AND DATE(created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where .= " AND DATE(created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        return $this->db->query(
            "SELECT * FROM payment_transactions 
             WHERE $where 
             ORDER BY created_at DESC 
             LIMIT 1000",
            $params
        );
    }
    
    /**
     * Lấy thống kê giao dịch
     * 
     * @param string $period Khoảng thời gian (day, week, month, year)
     * @return array
     */
    public function getStatistics($period = 'month') {
        $dateFormat = 'DATE(created_at)';
        $where = "1=1";
        
        switch ($period) {
            case 'day':
                $where = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $where = "WEEK(created_at) = WEEK(CURDATE())";
                break;
            case 'month':
                $where = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
                break;
            case 'year':
                $where = "YEAR(created_at) = YEAR(CURDATE())";
                break;
        }
        
        $stats = $this->db->queryOne(
            "SELECT 
                COUNT(*) as total_transactions,
                COUNT(CASE WHEN status = 'success' THEN 1 END) as successful,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END) as total_amount,
                AVG(amount) as avg_amount
            FROM payment_transactions 
            WHERE $where"
        );
        
        return $stats ?? [];
    }
    
    /**
     * Lấy cấu hình thanh toán từ database
     * 
     * @param string $key Khóa cấu hình
     * @return string|null
     */
    public function getConfig($key) {
        $config = $this->db->queryOne(
            "SELECT config_value FROM payment_config WHERE config_key = :key",
            ['key' => $key]
        );
        return $config ? $config['config_value'] : null;
    }
    
    /**
     * Cập nhật cấu hình
     * 
     * @param string $key Khóa cấu hình
     * @param string $value Giá trị
     * @return bool
     */
    public function updateConfig($key, $value) {
        try {
            $existing = $this->getConfig($key);
            
            if ($existing) {
                return (bool)$this->db->execute(
                    "UPDATE payment_config SET config_value = :value WHERE config_key = :key",
                    ['value' => $value, 'key' => $key]
                );
            } else {
                return (bool)$this->db->insert(
                    "INSERT INTO payment_config (config_key, config_value, created_at) 
                     VALUES (:key, :value, NOW())",
                    ['key' => $key, 'value' => $value]
                );
            }
        } catch (Exception $e) {
            error_log('Config update error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Query trạng thái giao dịch từ gateway
     * 
     * @param string $gateway Cổng (vnpay, momo)
     * @param int $orderId ID đơn hàng
     * @param string $txnRef Reference từ gateway
     * @return array
     */
    public function queryTransactionStatus($gateway, $orderId, $txnRef) {
        try {
            if ($gateway === 'vnpay') {
                return $this->vnpay->queryTransaction($orderId, $txnRef);
            } elseif ($gateway === 'momo') {
                return $this->momo->queryTransactionStatus($txnRef);
            }
            
            return [];
        } catch (Exception $e) {
            error_log('Query status error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Cron job: Cập nhật trạng thái đơn hàng chờ thanh toán
     * Chạy mỗi 5 phút để kiểm tra các đơn hàng có timeout
     */
    public function processExpiredPendingOrders() {
        try {
            // Lấy các đơn hàng chờ thanh toán quá 24 giờ
            $expiredOrders = $this->db->query(
                "SELECT id, order_number FROM orders 
                 WHERE payment_status = 'pending' 
                 AND status = 'pending' 
                 AND DATE(created_at) < DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                 LIMIT 100"
            );
            
            foreach ($expiredOrders as $order) {
                // Cập nhật trạng thái thành cancelled
                $this->db->execute(
                    "UPDATE orders SET status = 'cancelled' WHERE id = :id",
                    ['id' => $order['id']]
                );
            }
            
            return ['success' => true, 'expired_count' => count($expiredOrders)];
        } catch (Exception $e) {
            error_log('Process expired orders error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
