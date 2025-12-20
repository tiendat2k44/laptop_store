<?php
/**
 * VNPay Payment Gateway
 */

class VNPayGateway {
    private $tmnCode;
    private $hashSecret;
    private $vnpUrl;
    private $returnUrl;
    
    public function __construct() {
        $this->tmnCode = VNPAY_TMN_CODE;
        $this->hashSecret = VNPAY_HASH_SECRET;
        $this->vnpUrl = VNPAY_URL;
        $this->returnUrl = SITE_URL . '/payment/vnpay-return.php';
    }
    
    /**
     * Tạo URL thanh toán VNPay
     * @param array $order ['id', 'order_number', 'total_amount']
     */
    public function createPaymentUrl($order) {
        $orderId = (int)$order['id'];
        $amount = (int)((float)$order['total_amount'] * 100); // VNPay yêu cầu × 100
        $orderCode = $order['order_number'];
        
        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $this->tmnCode,
            "vnp_Amount" => $amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => "Thanh toan don hang " . $orderCode,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $this->returnUrl,
            "vnp_TxnRef" => $orderId . substr(time(), -6), // Unique per transaction
        ];
        
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= "&" . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . "&";
        }
        
        $vnp_Url = $this->vnpUrl . "?" . $query;
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->hashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        
        return $vnp_Url;
    }
    
    /**
     * Xác thực IPN từ VNPay
     */
    public function verifyIPN($inputData) {
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);
        
        ksort($inputData);
        $hashdata = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= "&" . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        
        $secureHash = hash_hmac('sha512', $hashdata, $this->hashSecret);
        
        return $secureHash === $vnp_SecureHash;
    }
}
