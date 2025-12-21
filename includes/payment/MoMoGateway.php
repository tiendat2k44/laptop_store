<?php
/**
 * MoMo Payment Gateway - Tích hợp cổng thanh toán MoMo
 * 
 * Các phương thức chính:
 * - createPayment(): Tạo request thanh toán
 * - verifySignature(): Xác thực chữ ký
 * - processCallback(): Xử lý callback
 */

class MoMoGateway {
    private $partnerCode;
    private $accessKey;
    private $secretKey;
    private $endpoint;
    private $returnUrl;
    private $ipnUrl;
    private $db;
    
    // Sandbox endpoints
    const MOMO_TEST_ENDPOINT = 'https://test-payment.momo.vn/v2/gateway/api/create';
    const MOMO_PROD_ENDPOINT = 'https://payment.momo.vn/v2/gateway/api/create';
    const MOMO_TEST_QUERY = 'https://test-payment.momo.vn/v2/gateway/api/query';
    const MOMO_PROD_QUERY = 'https://payment.momo.vn/v2/gateway/api/query';
    
    // Result codes
    const RESULT_CODES = [
        0 => 'Giao dịch thành công',
        1001 => 'Lỗi token không hợp lệ',
        1002 => 'Lỗi không tìm thấy yêu cầu',
        1003 => 'Lỗi requestId trùng',
        1004 => 'Lỗi xác thực chữ ký',
        1005 => 'Lỗi server nội bộ',
        1006 => 'Lỗi request không hợp lệ',
        1007 => 'Lỗi cũ',
        9000 => 'Giao dịch được khởi tạo thành công',
        9001 => 'Request thất bại',
    ];
    
    public function __construct($database = null) {
        $this->partnerCode = MOMO_PARTNER_CODE ?? '';
        $this->accessKey = MOMO_ACCESS_KEY ?? '';
        $this->secretKey = MOMO_SECRET_KEY ?? '';
        $this->endpoint = MOMO_ENDPOINT ?? self::MOMO_TEST_ENDPOINT;
        $this->returnUrl = SITE_URL . '/payment/momo-return.php';
        $this->ipnUrl = SITE_URL . '/payment/momo-ipn.php';
        $this->db = $database ?? Database::getInstance();
    }
    
    /**
     * Tạo request thanh toán MoMo
     * 
     * @param array $order Thông tin đơn hàng ['id', 'order_number', 'total_amount']
     * @return array ['success' => bool, 'data' => array, 'error' => string]
     * @throws Exception
     */
    public function createPayment($order) {
        if (empty($this->partnerCode) || empty($this->accessKey) || empty($this->secretKey)) {
            throw new Exception('Cấu hình MoMo không đầy đủ');
        }
        
        $orderId = (int)$order['id'];
        $amount = (int)((float)$order['total_amount']);
        $orderInfo = 'Thanh toan don hang ' . $order['order_number'];
        
        // Tạo requestId duy nhất
        $requestId = $orderId . time();
        
        // Dữ liệu mã hóa
        $extraData = base64_encode(json_encode([
            'order_id' => $orderId,
            'order_number' => $order['order_number']
        ]));
        
        // Tạo signature
        $rawHash = "accessKey=" . $this->accessKey
                 . "&amount=" . $amount
                 . "&extraData=" . $extraData
                 . "&ipnUrl=" . urlencode($this->ipnUrl)
                 . "&orderId=" . $requestId
                 . "&orderInfo=" . urlencode($orderInfo)
                 . "&partnerCode=" . $this->partnerCode
                 . "&redirectUrl=" . urlencode($this->returnUrl . "?id=" . $orderId)
                 . "&requestId=" . $requestId
                 . "&requestType=captureWallet";
        
        $signature = hash_hmac("sha256", $rawHash, $this->secretKey);
        
        // Dữ liệu request
        $data = [
            'partnerCode' => $this->partnerCode,
            'partnerName' => SITE_NAME ?? 'Laptop Store',
            'partnerTransId' => $requestId,
            'accessKey' => $this->accessKey,
            'amount' => $amount,
            'orderId' => $requestId,
            'orderInfo' => $orderInfo,
            'requestType' => 'captureWallet',
            'ipnUrl' => $this->ipnUrl,
            'redirectUrl' => $this->returnUrl . "?id=" . $orderId,
            'signature' => $signature,
            'extraData' => $extraData,
            'requestId' => $requestId,
            'lang' => 'vi',
        ];
        
        // Ghi log
        $this->logTransaction($orderId, 'momo', 'pending', $requestId, $amount, 'Tạo request thanh toán');
        
        return [
            'success' => true,
            'data' => $data,
            'endpoint' => $this->endpoint
        ];
    }
    
    /**
     * Xác thực callback từ MoMo
     * 
     * @param array $data Dữ liệu callback
     * @return array ['success' => bool, 'message' => string, 'code' => int]
     */
    public function verifyCallback($data) {
        // Xác thực signature
        $signature = $data['signature'] ?? '';
        
        // Tạo raw hash
        $rawHash = "accessKey=" . $this->accessKey
                 . "&amount=" . ($data['amount'] ?? '')
                 . "&extraData=" . ($data['extraData'] ?? '')
                 . "&message=" . ($data['message'] ?? '')
                 . "&orderId=" . ($data['orderId'] ?? '')
                 . "&orderInfo=" . ($data['orderInfo'] ?? '')
                 . "&partnerCode=" . $this->partnerCode
                 . "&partnerTransId=" . ($data['partnerTransId'] ?? '')
                 . "&requestId=" . ($data['requestId'] ?? '')
                 . "&responseTime=" . ($data['responseTime'] ?? '')
                 . "&resultCode=" . ($data['resultCode'] ?? '')
                 . "&transId=" . ($data['transId'] ?? '');
        
        $calculatedSignature = hash_hmac("sha256", $rawHash, $this->secretKey);
        
        if ($calculatedSignature !== $signature) {
            return [
                'success' => false,
                'message' => 'Chữ ký không hợp lệ',
                'code' => 1004
            ];
        }
        
        $resultCode = (int)($data['resultCode'] ?? -1);
        
        if ($resultCode === 0) {
            return [
                'success' => true,
                'message' => 'Giao dịch thành công',
                'code' => $resultCode,
                'transId' => $data['transId'] ?? '',
                'orderId' => $data['orderId'] ?? ''
            ];
        } else {
            return [
                'success' => false,
                'message' => self::RESULT_CODES[$resultCode] ?? 'Giao dịch thất bại',
                'code' => $resultCode
            ];
        }
    }
    
    /**
     * Xác thực return từ MoMo (khi người dùng quay lại từ app)
     * 
     * @param array $data Dữ liệu return từ MoMo
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function verifyReturn($data) {
        $resultCode = (int)($data['resultCode'] ?? -1);
        
        if ($resultCode === 0) {
            return [
                'success' => true,
                'message' => 'Giao dịch thành công',
                'data' => [
                    'orderId' => $data['orderId'] ?? '',
                    'transId' => $data['transId'] ?? '',
                    'amount' => $data['amount'] ?? ''
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => self::RESULT_CODES[$resultCode] ?? 'Giao dịch thất bại',
                'code' => $resultCode
            ];
        }
    }
    
    /**
     * Query trạng thái giao dịch
     * 
     * @param string $orderId ID giao dịch từ MoMo
     * @return array Kết quả query
     */
    public function queryTransactionStatus($orderId) {
        $requestId = time() . rand(100000, 999999);
        
        $rawHash = "accessKey=" . $this->accessKey
                 . "&orderId=" . $orderId
                 . "&partnerCode=" . $this->partnerCode
                 . "&requestId=" . $requestId;
        
        $signature = hash_hmac("sha256", $rawHash, $this->secretKey);
        
        $data = [
            'partnerCode' => $this->partnerCode,
            'accessKey' => $this->accessKey,
            'requestId' => $requestId,
            'orderId' => $orderId,
            'signature' => $signature,
            'lang' => 'vi'
        ];
        
        // Thực hiện POST request
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return ['resultCode' => 1005, 'message' => 'Lỗi kết nối'];
    }
    
    /**
     * Ghi log giao dịch
     * 
     * @param int $orderId ID đơn hàng
     * @param string $gateway Cổng thanh toán
     * @param string $status Trạng thái
     * @param string $transactionId Mã giao dịch
     * @param float $amount Số tiền
     * @param string $message Thông điệp
     */
    private function logTransaction($orderId, $gateway, $status, $transactionId, $amount, $message = '') {
        try {
            $this->db->insert(
                "INSERT INTO payment_transactions 
                (order_id, gateway, status, transaction_id, amount, message, ip_address, created_at) 
                VALUES (:order_id, :gateway, :status, :txn_id, :amount, :message, :ip, NOW())",
                [
                    'order_id' => $orderId,
                    'gateway' => $gateway,
                    'status' => $status,
                    'txn_id' => $transactionId,
                    'amount' => $amount,
                    'message' => $message,
                    'ip' => $this->getClientIp()
                ]
            );
        } catch (Exception $e) {
            error_log('Payment log error: ' . $e->getMessage());
        }
    }
    
    /**
     * Lấy IP client
     */
    private function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
    
    /**
     * Lấy mô tả result code
     */
    public static function getResultDescription($code) {
        return self::RESULT_CODES[$code] ?? 'Mã lỗi không xác định';
    }
}
        $computedSignature = hash_hmac("sha256", $rawHash, $this->secretKey);
        
        return $computedSignature === $rawSignature;
    }
}
