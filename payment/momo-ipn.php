<?php
/**
 * MoMo IPN Handler - Xử lý callback từ MoMo
 * 
 * IPN (Instant Payment Notification) được MoMo gửi lên server
 * để xác nhận kết quả giao dịch
 */

require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();
require_once __DIR__ . '/../includes/payment/MoMoGateway.php';

// Khởi tạo MoMoGateway
$momo = new MoMoGateway($db);

// Log IPN request
$ipnData = $_POST ?: $_GET;
logIPN('momo', $ipnData);

try {
    // Xác thực signature
    $verifyResult = $momo->verifyCallback($ipnData);
    
    if (!$verifyResult['success']) {
        http_response_code(400);
        echo json_encode(['resultCode' => 1004, 'message' => 'Invalid signature']);
        exit;
    }
    
    $resultCode = (int)($ipnData['resultCode'] ?? -1);
    $orderId = (int)($ipnData['orderId'] ?? 0);
    $transId = $ipnData['transId'] ?? '';
    $amount = (int)($ipnData['amount'] ?? 0);
    
    if ($orderId <= 0) {
        http_response_code(400);
        echo json_encode(['resultCode' => 1006, 'message' => 'Invalid order ID']);
        exit;
    }
    
    // Lấy thông tin đơn hàng
    $order = $db->queryOne(
        "SELECT id, status, payment_status, total_amount FROM orders WHERE id = :id",
        ['id' => $orderId]
    );
    
    if (!$order) {
        throw new Exception('Order not found: ' . $orderId);
    }
    
    // Kiểm tra số tiền
    if (abs((float)$order['total_amount'] - $amount) > 0.01) {
        throw new Exception('Amount mismatch: ' . $order['total_amount'] . ' != ' . $amount);
    }
    
    // Nếu đã thanh toán rồi, không cần xử lý lại
    if ($order['payment_status'] === 'paid') {
        http_response_code(200);
        echo json_encode(['resultCode' => 0, 'message' => 'Already processed']);
        exit;
    }
    
    // Xử lý kết quả
    $db->beginTransaction();
    
    if ($resultCode === 0) {
        // Thanh toán thành công
        $db->execute(
            "UPDATE orders 
             SET payment_status = 'paid',
                 payment_method = 'MOMO',
                 payment_transaction_id = :txn,
                 paid_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP,
                 status = CASE WHEN status = 'pending' THEN 'confirmed' ELSE status END
             WHERE id = :id",
            [
                'txn' => $transId,
                'id' => $orderId
            ]
        );
        
        // Ghi log thành công
        $db->insert(
            "INSERT INTO payment_transactions 
            (order_id, gateway, status, transaction_id, amount, message, ip_address, created_at)
            VALUES (:order_id, :gateway, :status, :txn_id, :amount, :message, :ip, NOW())",
            [
                'order_id' => $orderId,
                'gateway' => 'momo',
                'status' => 'success',
                'txn_id' => $transId,
                'amount' => $amount,
                'message' => 'IPN confirmation - Payment success',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]
        );
        
        $db->commit();
        
        // Gửi email xác nhận
        sendPaymentConfirmationEmail($orderId, 'momo');
        
        http_response_code(200);
        echo json_encode(['resultCode' => 0, 'message' => 'Confirm Success']);
    } else {
        // Thanh toán thất bại
        $errorMessage = MoMoGateway::getResultDescription($resultCode);
        
        $db->execute(
            "UPDATE orders 
             SET payment_status = 'failed',
                 payment_transaction_id = :txn,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id",
            [
                'txn' => $transId,
                'id' => $orderId
            ]
        );
        
        // Ghi log thất bại
        $db->insert(
            "INSERT INTO payment_transactions 
            (order_id, gateway, status, transaction_id, amount, message, ip_address, created_at)
            VALUES (:order_id, :gateway, :status, :txn_id, :amount, :message, :ip, NOW())",
            [
                'order_id' => $orderId,
                'gateway' => 'momo',
                'status' => 'failed',
                'txn_id' => $transId,
                'amount' => $amount,
                'message' => 'IPN confirmation - ' . $errorMessage,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]
        );
        
        $db->commit();
        
        http_response_code(200);
        echo json_encode(['resultCode' => 1, 'message' => 'Confirm Fail']);
    }
    
} catch (Exception $e) {
    $db->rollback();
    error_log('MoMo IPN Error: ' . $e->getMessage() . ' | Data: ' . json_encode($ipnData));
    
    http_response_code(500);
    echo json_encode(['resultCode' => 1, 'message' => 'System error']);
}

/**
 * Hàm ghi log IPN request
 */
function logIPN($gateway, $data) {
    $logFile = __DIR__ . '/../logs/ipn-' . $gateway . '-' . date('Y-m-d') . '.log';
    
    if (!is_dir(dirname($logFile))) {
        @mkdir(dirname($logFile), 0777, true);
    }
    
    $logEntry = "[" . date('Y-m-d H:i:s') . "] " . json_encode($data) . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Gửi email xác nhận thanh toán
 */
function sendPaymentConfirmationEmail($orderId, $gateway) {
    try {
        $db = Database::getInstance();
        $order = $db->queryOne(
            "SELECT o.*, u.email, u.name FROM orders o 
             JOIN users u ON o.user_id = u.id 
             WHERE o.id = :id",
            ['id' => $orderId]
        );
        
        if (!$order) return;
        
        $to = $order['email'];
        $subject = 'Xác nhận thanh toán đơn hàng ' . $order['order_number'];
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Xác nhận thanh toán</h2>
            <p>Chào " . htmlspecialchars($order['name']) . ",</p>
            <p>Cảm ơn bạn! Thanh toán đơn hàng <strong>" . htmlspecialchars($order['order_number']) . "</strong> thành công.</p>
            <p>
                <strong>Chi tiết đơn hàng:</strong><br/>
                Mã đơn hàng: " . htmlspecialchars($order['order_number']) . "<br/>
                Tổng tiền: " . formatPrice($order['total_amount']) . "<br/>
                Phương thức: " . (strtoupper($gateway)) . "<br/>
                Thời gian: " . date('d/m/Y H:i:s', strtotime($order['created_at'])) . "
            </p>
            <p>Đơn hàng sẽ được xác nhận và giao hàng sớm nhất.</p>
            <a href='" . SITE_URL . "/account/order-detail.php?id=" . $orderId . "'>Xem chi tiết đơn hàng</a>
        </body>
        </html>";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        
        @mail($to, $subject, $message, $headers);
    } catch (Exception $e) {
        error_log('Email sending error: ' . $e->getMessage());
    }
}

?>
