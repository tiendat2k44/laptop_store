<?php
/**
 * MoMo Payment Gateway
 */

class MoMoGateway {
    private $partnerCode;
    private $accessKey;
    private $secretKey;
    private $endpoint;
    private $returnUrl;
    private $ipnUrl;
    
    public function __construct() {
        $this->partnerCode = MOMO_PARTNER_CODE;
        $this->accessKey = MOMO_ACCESS_KEY;
        $this->secretKey = MOMO_SECRET_KEY;
        $this->endpoint = MOMO_ENDPOINT;
        $this->returnUrl = SITE_URL . '/payment/momo-return.php';
        $this->ipnUrl = SITE_URL . '/payment/momo-ipn.php';
    }
    
    /**
     * Tạo request thanh toán MoMo
     * Trả về redirect URL hoặc error
     */
    public function createPaymentRequest($order) {
        $orderId = (int)$order['id'];
        $amount = (int)((float)$order['total_amount']);
        $orderInfo = 'Thanh toan don ' . $order['order_number'];
        
        $requestId = $orderId . substr(time(), -6);
        $extraData = base64_encode(json_encode(['order_id' => $orderId]));
        
        $rawHash = "accessKey=" . $this->accessKey
                 . "&amount=" . $amount
                 . "&extraData=" . $extraData
                 . "&ipnUrl=" . urlencode($this->ipnUrl)
                 . "&orderId=" . $requestId
                 . "&orderInfo=" . urlencode($orderInfo)
                 . "&partnerCode=" . $this->partnerCode
                 . "&redirectUrl=" . urlencode($this->returnUrl)
                 . "&requestId=" . $requestId
                 . "&requestType=captureWallet";
        
        $signature = hash_hmac("sha256", $rawHash, $this->secretKey);
        
        $data = [
            'partnerCode' => $this->partnerCode,
            'partnerName' => SITE_NAME,
            'partnerTransId' => $requestId,
            'accessKey' => $this->accessKey,
            'amount' => $amount,
            'orderId' => $requestId,
            'orderInfo' => $orderInfo,
            'requestType' => 'captureWallet',
            'ipnUrl' => $this->ipnUrl,
            'redirectUrl' => $this->returnUrl,
            'signature' => $signature,
            'extraData' => $extraData,
            'requestId' => $requestId,
        ];
        
        return ['success' => true, 'data' => $data, 'url' => 'https://test-payment.momo.vn/v2/gateway/api/create'];
    }
    
    /**
     * Xác thực signature của MoMo
     */
    public function verifySignature($rawSignature, $data) {
        // Tính toán signature từ data
        ksort($data);
        $rawHash = "";
        foreach ($data as $key => $value) {
            if ($key !== 'signature' && $key !== 'partnerCode') {
                $rawHash .= $key . "=" . $value . "&";
            }
        }
        $rawHash = rtrim($rawHash, "&");
        
        $computedSignature = hash_hmac("sha256", $rawHash, $this->secretKey);
        
        return $computedSignature === $rawSignature;
    }
}
