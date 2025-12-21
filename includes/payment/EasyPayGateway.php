<?php
/**
 * EasyPay/Sepay Payment Gateway - Tích hợp cổng thanh toán EasyPay
 * 
 * Tài liệu: https://sepay.vn/lap-trinh-cong-thanh-toan.html
 * 
 * Các phương thức chính:
 * - createPaymentUrl(): Tạo URL thanh toán
 * - verifyWebhook(): Xác thực webhook từ EasyPay
 * - verifyReturn(): Xác thực kết quả trả về
 * - logTransaction(): Ghi log giao dịch
 */

class EasyPayGateway {
    private $partnerCode;
    private $apiKey;
    private $endpoint;
    private $returnUrl;
    private $webhookUrl;
    private $db;
    
    // EasyPay endpoints
    const EASYPAY_ENDPOINT = 'https://easypay.vn/api/openapi/pay-url';
    const EASYPAY_QUERY_ENDPOINT = 'https://easypay.vn/api/openapi/order-status';
    
    // Status codes
    const STATUS_CODES = [
        0 => 'Chờ thanh toán',
        1 => 'Thành công',
        2 => 'Thất bại',
        3 => 'Hủy',
    ];
    
    public function __construct($database = null) {
        $this->partnerCode = EASYPAY_PARTNER_CODE ?? '';
        $this->apiKey = EASYPAY_API_KEY ?? '';
        $this->endpoint = EASYPAY_ENDPOINT;
        $this->returnUrl = SITE_URL . '/payment/easy-pay-return.php';
        $this->webhookUrl = SITE_URL . '/payment/easy-pay-ipn.php';
        $this->db = $database ?? Database::getInstance();
    }
    
    /**
     * Tạo URL thanh toán EasyPay
     * 
     * @param array $order Thông tin đơn hàng ['id', 'order_number', 'total_amount']
     * @return array ['success' => bool, 'url' => string, 'error' => string]
     * @throws Exception
     */
    public function createPaymentUrl($order) {
        if (empty($this->partnerCode) || empty($this->apiKey)) {
            throw new Exception('Cấu hình EasyPay không đầy đủ');
        }
        
        $orderId = (int)$order['id'];
        $amount = (int)((float)$order['total_amount']);
        $orderCode = $order['order_number'];
        // Request ID: order_id + timestamp (10 digits)
        $requestId = $orderId . time();
        
        // Tạo chữ ký MD5
        // Format: md5(partner_code + request_id + amount + api_key)
        $signature = md5($this->partnerCode . $requestId . $amount . $this->apiKey);
        
        // Dữ liệu request
        $data = [
            'partner_code' => $this->partnerCode,
            'partner_name' => SITE_NAME ?? 'Laptop Store',
            'request_id' => $requestId,
            'amount' => $amount,
            'order_code' => $orderCode,
            'order_description' => 'Thanh toan don hang ' . $orderCode,
            'customer_name' => 'Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '0000000000',
            'return_url' => $this->returnUrl . '?id=' . $orderId,
            'webhook_url' => $this->webhookUrl,
            'signature' => $signature,
        ];
        
        // Gọi API
        try {
            $response = $this->callAPI($this->endpoint, $data);
            
            if ($response['status'] === 'success') {
                // Ghi log
                $this->logTransaction($orderId, 'easypay', 'pending', $requestId, $amount, 'Tạo URL thanh toán');
                
                return [
                    'success' => true,
                    'url' => $response['pay_url'] ?? '',
                    'request_id' => $requestId
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['message'] ?? 'Không thể tạo URL thanh toán'
                ];
            }
        } catch (Exception $e) {
            error_log('EasyPay API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Lỗi kết nối đến EasyPay: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Xác thực webhook từ EasyPay
     * 
     * @param array $data Dữ liệu webhook từ EasyPay
     * @return array ['success' => bool, 'message' => string, 'status' => int]
     */
    public function verifyWebhook($data) {
        // Xác thực chữ ký
        $signature = $data['signature'] ?? '';
        $requestId = $data['request_id'] ?? '';
        $status = (int)($data['status'] ?? -1);
        $amount = (int)($data['amount'] ?? 0);
        
        // Tạo lại chữ ký
        // Format: md5(partner_code + request_id + amount + status + api_key)
        $calculatedSignature = md5(
            $this->partnerCode . 
            $requestId . 
            $amount . 
            $status . 
            $this->apiKey
        );
        
        if ($calculatedSignature !== $signature) {
            error_log('EasyPay webhook signature mismatch: ' . $signature . ' vs ' . $calculatedSignature);
            return [
                'success' => false,
                'message' => 'Chữ ký không hợp lệ',
                'status' => -1
            ];
        }
        
        // Status: 0=chờ, 1=thành công, 2=thất bại, 3=hủy
        if ($status === 1) {
            return [
                'success' => true,
                'message' => 'Giao dịch thành công',
                'status' => $status,
                'request_id' => $requestId,
                'transaction_id' => $data['transaction_id'] ?? ''
            ];
        } else {
            return [
                'success' => false,
                'message' => self::STATUS_CODES[$status] ?? 'Giao dịch thất bại',
                'status' => $status
            ];
        }
    }
    
    /**
     * Xác thực return từ EasyPay (khi người dùng quay lại)
     * 
     * @param array $data Dữ liệu return từ EasyPay
     * @return array ['success' => bool, 'message' => string, 'status' => int]
     */
    public function verifyReturn($data) {
        $status = (int)($data['status'] ?? -1);
        
        if ($status === 1) {
            return [
                'success' => true,
                'message' => 'Giao dịch thành công',
                'status' => $status,
                'request_id' => $data['request_id'] ?? ''
            ];
        } else {
            return [
                'success' => false,
                'message' => self::STATUS_CODES[$status] ?? 'Giao dịch thất bại',
                'status' => $status
            ];
        }
    }
    
    /**
     * Query trạng thái giao dịch
     * 
     * @param string $requestId Request ID từ tạo thanh toán
     * @return array Kết quả query
     */
    public function queryTransactionStatus($requestId) {
        $signature = md5($this->partnerCode . $requestId . $this->apiKey);
        
        $data = [
            'partner_code' => $this->partnerCode,
            'request_id' => $requestId,
            'signature' => $signature,
        ];
        
        try {
            $response = $this->callAPI(self::EASYPAY_QUERY_ENDPOINT, $data);
            return $response;
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Gọi API EasyPay
     * 
     * @param string $url URL endpoint
     * @param array $data Dữ liệu gửi
     * @return array Kết quả trả về
     * @throws Exception
     */
    private function callAPI($url, $data) {
        $postData = json_encode($data);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('CURL Error: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP Error: ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        
        return $result ?? [];
    }
    
    /**
     * Ghi log giao dịch
     */
    private function logTransaction($orderId, $gateway, $status, $transactionRef, $amount, $message) {
        try {
            $this->db->insert('payment_transactions', [
                'order_id' => $orderId,
                'gateway' => $gateway,
                'status' => $status,
                'transaction_ref' => $transactionRef,
                'amount' => $amount,
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Failed to log transaction: ' . $e->getMessage());
        }
    }
}
?>
