<?php
/**
 * VNPay Payment Gateway - Tích hợp cổng thanh toán VNPay
 * 
 * Các phương thức chính:
 * - createPaymentUrl(): Tạo URL thanh toán
 * - verifyReturn(): Xác thực kết quả trả về
 * - verifyIPN(): Xác thực IPN callback
 * - logTransaction(): Ghi log giao dịch
 */

class VNPayGateway {
    private $tmnCode;
    private $hashSecret;
    private $vnpUrl;
    private $returnUrl;
    private $ipnUrl;
    private $db;
    
    const VNPAY_API_URL = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
    const VNPAY_QUERYDR_URL = 'https://sandbox.vnpayment.vn/merchant_weblogon/querydr';
    const VNPAY_REFUND_URL = 'https://sandbox.vnpayment.vn/merchant_weblogon/refund';
    
    // Mã response codes
    const RESPONSE_CODES = [
        '00' => 'Giao dịch thành công',
        '01' => 'Ngân hàng từ chối',
        '02' => 'Ngân hàng từ chối - Chủ tài khoản liên hệ ngân hàng',
        '03' => 'URL không hợp lệ',
        '04' => 'Khóa HMAC không hợp lệ',
        '05' => 'Dữ liệu giao dịch không hợp lệ',
        '07' => 'Trùng lặp giao dịch',
        '09' => 'Tài khoản người bán chưa được khớp nối',
        '10' => 'Chứng thực không thành công',
        '11' => 'Hết thời gian chờ',
    ];
    
    public function __construct($database = null) {
        $this->tmnCode = VNPAY_TMN_CODE ?? '';
        $this->hashSecret = VNPAY_HASH_SECRET ?? '';
        $this->vnpUrl = VNPAY_URL ?? self::VNPAY_API_URL;
        $this->returnUrl = SITE_URL . '/payment/vnpay-return.php';
        $this->ipnUrl = SITE_URL . '/payment/vnpay-ipn.php';
        $this->db = $database ?? Database::getInstance();
    }
    
    /**
     * Tạo URL thanh toán VNPay
     * 
     * @param array $order Thông tin đơn hàng ['id', 'order_number', 'total_amount']
     * @return string URL thanh toán VNPay
     * @throws Exception
     */
    public function createPaymentUrl($order) {
        if (empty($this->tmnCode) || empty($this->hashSecret)) {
            throw new Exception('Cấu hình VNPay không đầy đủ');
        }
        
        $orderId = (int)$order['id'];
        $amount = (int)((float)$order['total_amount'] * 100); // VNPay yêu cầu × 100
        $orderCode = $order['order_number'];
        
        // Tạo mã giao dịch duy nhất
        $txnRef = $orderId . time();
        
        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $this->tmnCode,
            "vnp_Amount" => $amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $this->getClientIp(),
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => $this->encodeOrderInfo("Thanh toan don hang " . $orderCode),
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $this->returnUrl,
            "vnp_TxnRef" => $txnRef,
        ];
        
        // Sắp xếp theo thứ tự chữ cái
        ksort($inputData);
        
        // Tạo chuỗi hash
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            $hashdata .= urlencode($key) . "=" . urlencode($value) . "&";
        }
        $hashdata = rtrim($hashdata, "&");
        
        // Tính toán secure hash
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->hashSecret);
        
        // Tạo query string
        $query = "";
        foreach ($inputData as $key => $value) {
            $query .= urlencode($key) . "=" . urlencode($value) . "&";
        }
        
        $paymentUrl = $this->vnpUrl . "?" . $query . "vnp_SecureHash=" . $vnpSecureHash;
        
        // Ghi log
        $this->logTransaction($orderId, 'vnpay', 'pending', $txnRef, $amount / 100, 'Tạo URL thanh toán');
        
        return $paymentUrl;
    }
    
    /**
     * Xác thực kết quả trả về từ VNPay
     * 
     * @param array $responseData Dữ liệu trả về từ VNPay
     * @return array ['success' => bool, 'message' => string, 'code' => string]
     */
    public function verifyReturn($responseData) {
        $vnpSecureHash = $responseData['vnp_SecureHash'] ?? '';
        
        // Copy dữ liệu và xóa secure hash
        $inputData = $responseData;
        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);
        
        // Sắp xếp
        ksort($inputData);
        
        // Tạo chuỗi hash
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            $hashdata .= urlencode($key) . "=" . urlencode($value) . "&";
        }
        $hashdata = rtrim($hashdata, "&");
        
        // Kiểm tra secure hash
        $secureHash = hash_hmac('sha512', $hashdata, $this->hashSecret);
        
        if ($secureHash !== $vnpSecureHash) {
            return [
                'success' => false,
                'message' => 'Chữ ký không hợp lệ',
                'code' => 'invalid_signature'
            ];
        }
        
        $responseCode = $responseData['vnp_ResponseCode'] ?? '';
        $transactionNo = $responseData['vnp_TransactionNo'] ?? '';
        
        if ($responseCode === '00') {
            return [
                'success' => true,
                'message' => 'Giao dịch thành công',
                'code' => $responseCode,
                'transactionNo' => $transactionNo
            ];
        } else {
            return [
                'success' => false,
                'message' => self::RESPONSE_CODES[$responseCode] ?? 'Giao dịch thất bại',
                'code' => $responseCode,
                'transactionNo' => $transactionNo
            ];
        }
    }
    
    /**
     * Xác thực IPN callback từ VNPay
     * 
     * @param array $inputData Dữ liệu IPN từ VNPay
     * @return array ['RespCode' => '00', 'Message' => 'Confirm Success']
     */
    public function verifyIPN($inputData) {
        $vnpSecureHash = $inputData['vnp_SecureHash'] ?? '';
        
        // Copy và xóa secure hash
        $data = $inputData;
        unset($data['vnp_SecureHash']);
        unset($data['vnp_SecureHashType']);
        
        ksort($data);
        
        // Tính hash
        $hashdata = "";
        foreach ($data as $key => $value) {
            $hashdata .= urlencode($key) . "=" . urlencode($value) . "&";
        }
        $hashdata = rtrim($hashdata, "&");
        
        $secureHash = hash_hmac('sha512', $hashdata, $this->hashSecret);
        
        // Kiểm tra signature
        if ($secureHash !== $vnpSecureHash) {
            return ['RespCode' => '97', 'Message' => 'Invalid signature'];
        }
        
        // Lấy thông tin giao dịch
        $responseCode = $inputData['vnp_ResponseCode'] ?? '';
        $transactionNo = $inputData['vnp_TransactionNo'] ?? '';
        
        if ($responseCode === '00') {
            return [
                'RespCode' => '00',
                'Message' => 'Confirm Success',
                'transactionNo' => $transactionNo
            ];
        } else {
            return [
                'RespCode' => '01',
                'Message' => 'Confirm Fail'
            ];
        }
    }
    
    /**
     * Lấy trạng thái giao dịch từ VNPay (Query DR)
     * 
     * @param string $orderId Mã đơn hàng
     * @param string $txnRef Mã giao dịch
     * @return array Kết quả query
     */
    public function queryTransaction($orderId, $txnRef) {
        $inputData = [
            "vnp_TmnCode" => $this->tmnCode,
            "vnp_TxnRef" => $txnRef,
            "vnp_OrderInfo" => "Check status for order " . $orderId,
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_IpAddr" => $this->getClientIp(),
        ];
        
        ksort($inputData);
        
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            $hashdata .= urlencode($key) . "=" . urlencode($value) . "&";
        }
        $hashdata = rtrim($hashdata, "&");
        
        $secureHash = hash_hmac('sha512', $hashdata, $this->hashSecret);
        
        $inputData['vnp_SecureHash'] = $secureHash;
        
        // Thực hiện POST request
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::VNPAY_QUERYDR_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($inputData),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return ['isSucess' => false];
    }
    
    /**
     * Ghi log giao dịch
     * 
     * @param int $orderId ID đơn hàng
     * @param string $gateway Cổng thanh toán (vnpay, momo)
     * @param string $status Trạng thái (pending, success, failed)
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
            // Log error nếu cần
            error_log('Payment log error: ' . $e->getMessage());
        }
    }
    
    /**
     * Mã hóa OrderInfo để bảo mật
     */
    private function encodeOrderInfo($info) {
        return base64_encode($info);
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
     * Lấy mô tả response code
     */
    public static function getResponseDescription($code) {
        return self::RESPONSE_CODES[$code] ?? 'Mã lỗi không xác định';
    }
}
