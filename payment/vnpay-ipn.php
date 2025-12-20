<?php
require_once __DIR__ . '/../../includes/init.php';

$db = Database::getInstance();
require_once __DIR__ . '/../../includes/payment/VNPayGateway.php';

$vnpay = new VNPayGateway();

// Xác thực IPN từ VNPay
if (!$vnpay->verifyIPN($_GET)) {
    http_response_code(400);
    jsonResponse(['success' => false, 'message' => 'Invalid signature'], 400);
}

$responseCode = $_GET['vnp_ResponseCode'] ?? '00';
$txnRef = $_GET['vnp_TxnRef'] ?? '';

// responseCode 00 = thành công
if ($responseCode === '00' && $txnRef) {
    // Lấy order_id từ txn_ref
    $orderId = (int)substr($txnRef, 0, -6);
    
    try {
        $db->beginTransaction();
        
        // Cập nhật trạng thái thanh toán
        $result = $db->execute(
            "UPDATE orders 
             SET payment_status = 'paid', 
                 payment_transaction_id = :txn,
                 paid_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id",
            ['txn' => $txnRef, 'id' => $orderId]
        );
        
        if ($result) {
            $db->commit();
            http_response_code(200);
            jsonResponse(['RespCode' => '00', 'Message' => 'Confirm Success']);
        } else {
            throw new Exception('Update failed');
        }
    } catch (Exception $e) {
        $db->rollback();
        error_log('VNPay IPN error: ' . $e->getMessage());
        http_response_code(500);
        jsonResponse(['RespCode' => '01', 'Message' => 'Confirm Failed']);
    }
} else {
    http_response_code(400);
    jsonResponse(['RespCode' => '01', 'Message' => 'Payment failed']);
}
