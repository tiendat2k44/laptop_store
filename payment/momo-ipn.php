<?php
require_once __DIR__ . '/../../includes/init.php';

$db = Database::getInstance();
require_once __DIR__ . '/../../includes/payment/MoMoGateway.php';

$momo = new MoMoGateway();

$signature = $_GET['signature'] ?? '';
$unsubscribe = (int)($_GET['unsubscribe'] ?? 0);

// Xác thực signature
if (!$momo->verifySignature($signature, $_GET)) {
    http_response_code(400);
    jsonResponse(['success' => false, 'message' => 'Invalid signature'], 400);
}

$resultCode = (int)($_GET['resultCode'] ?? -1);
$orderId = (int)($_GET['orderId'] ?? 0);
$transId = $_GET['transId'] ?? '';

// resultCode 0 = thành công
if ($resultCode === 0 && $orderId > 0) {
    try {
        $db->beginTransaction();
        
        $result = $db->execute(
            "UPDATE orders
             SET payment_status = 'paid',
                 payment_transaction_id = :txn,
                 paid_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id",
            ['txn' => $transId, 'id' => $orderId]
        );
        
        if ($result) {
            $db->commit();
            http_response_code(200);
            jsonResponse(['resultCode' => 0, 'message' => 'Thành công']);
        } else {
            throw new Exception('Update failed');
        }
    } catch (Exception $e) {
        $db->rollback();
        error_log('MoMo IPN error: ' . $e->getMessage());
        http_response_code(500);
        jsonResponse(['resultCode' => 1, 'message' => 'Thất bại']);
    }
} else {
    http_response_code(400);
    jsonResponse(['resultCode' => 1, 'message' => 'Payment failed']);
}
