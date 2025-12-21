<?php
/**
 * VNPay IPN Handler - Xử lý callback từ VNPay
 * 
 * IPN (Instant Payment Notification) được VNPay gửi lên server
 * để xác nhận kết quả giao dịch
 */

require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();
require_once __DIR__ . '/../includes/payment/VNPayGateway.php';

// Khởi tạo VNPayGateway
$vnpay = new VNPayGateway($db);

// Log IPN request
$ipnData = $_GET;
logIPN('vnpay', $ipnData);

// Xác thực chữ ký từ VNPay
if (!$vnpay->verifyIPN($ipnData)) {
    http_response_code(400);
    echo json_encode(['RespCode' => '97', 'Message' => 'Invalid signature']);
    exit;
}

$responseCode = $ipnData['vnp_ResponseCode'] ?? '';
$transactionNo = $ipnData['vnp_TransactionNo'] ?? '';
$txnRef = $ipnData['vnp_TxnRef'] ?? '';
$amount = intval($ipnData['vnp_Amount'] ?? 0) / 100;

try {
    // Trích xuất order_id từ txnRef
    // Format: {orderId}{timestamp}
    $orderId = (int)substr($txnRef, 0, -10);
    
    if ($orderId <= 0) {
        throw new Exception('Invalid order ID in txnRef');
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
        throw new Exception('Amount mismatch');
    }
    
    // Nếu đã thanh toán rồi, không cần xử lý lại
    if ($order['payment_status'] === 'paid') {
        http_response_code(200);
        echo json_encode(['RespCode' => '00', 'Message' => 'Already processed']);
        exit;
    }
    
    // Xử lý kết quả thanh toán
    $db->beginTransaction();
    
    if ($responseCode === '00') {
        // Thanh toán thành công
        $db->execute(
            "UPDATE orders 
             SET payment_status = 'paid',
                 payment_method = 'VNPAY',
                 payment_transaction_id = :txn,
                 paid_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP,
                 status = CASE WHEN status = 'pending' THEN 'confirmed' ELSE status END
             WHERE id = :id",
            [
                'txn' => $transactionNo,
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
                'gateway' => 'vnpay',
                'status' => 'success',
                'txn_id' => $transactionNo,
                'amount' => $amount,
                'message' => 'IPN confirmation - Payment success',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]
        );
        
        $db->commit();
        
        // Gửi email xác nhận
        sendPaymentConfirmationEmail($orderId, 'vnpay');
        
        // Trả về phản hồi thành công
        http_response_code(200);
        echo json_encode(['RespCode' => '00', 'Message' => 'Confirm Success']);
    } else {
        // Thanh toán thất bại
        $errorMessage = VNPayGateway::getResponseDescription($responseCode);
        
        $db->execute(
            "UPDATE orders 
             SET payment_status = 'failed',
                 payment_transaction_id = :txn,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id",
            [
                'txn' => $transactionNo,
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
                'gateway' => 'vnpay',
                'status' => 'failed',
                'txn_id' => $transactionNo,
                'amount' => $amount,
                'message' => 'IPN confirmation - ' . $errorMessage,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]
        );
        
        $db->commit();
        
        http_response_code(200);
        echo json_encode(['RespCode' => '01', 'Message' => 'Confirm Fail']);
    }
    
} catch (Exception $e) {
    $db->rollback();
    error_log('VNPay IPN Error: ' . $e->getMessage() . ' | Data: ' . json_encode($ipnData));
    
    http_response_code(500);
    echo json_encode(['RespCode' => '99', 'Message' => 'System error']);
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
