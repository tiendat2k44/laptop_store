<?php
/**
 * Easy Pay IPN Handler
 * Xử lý webhook từ EasyPay
 * Phản hồi với JSON để EasyPay biết là đã nhận
 */

header('Content-Type: application/json');

require_once '../includes/init.php';

try {
    // Lấy dữ liệu từ webhook
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        error_log('EasyPay IPN: Invalid JSON - ' . $rawInput);
        exit;
    }
    
    // Các trường bắt buộc
    $requestId = $data['request_id'] ?? '';
    $status = (int)($data['status'] ?? -1);
    $amount = (int)($data['amount'] ?? 0);
    $signature = $data['signature'] ?? '';
    
    if (empty($requestId) || empty($signature)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        error_log('EasyPay IPN: Missing fields - request_id=' . $requestId);
        exit;
    }
    
    // Xác thực webhook
    require_once '../includes/payment/EasyPayGateway.php';
    $easypay = new EasyPayGateway();
    
    $result = $easypay->verifyWebhook($data);
    
    if (!$result['success']) {
        http_response_code(401);
        echo json_encode(['error' => $result['message']]);
        error_log('EasyPay IPN: Signature verification failed - ' . $signature);
        exit;
    }
    
    // Extract order_id từ request_id
    $orderId = intval(substr($requestId, 0, -10)); // Remove 10-digit timestamp
    
    if (empty($orderId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid order ID']);
        exit;
    }
    
    // Lấy database
    $db = Database::getInstance();
    
    // Kiểm tra order tồn tại
    $order = $db->query(
        'SELECT id, status, total_amount FROM orders WHERE id = ?',
        [$orderId]
    )->fetch();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        error_log('EasyPay IPN: Order not found - order_id=' . $orderId);
        exit;
    }
    
    // Cập nhật status đơn hàng nếu thanh toán thành công
    if ($status === 1 && $order['status'] === 'pending') {
        $db->update('orders', ['status' => 'confirmed'], ['id' => $orderId]);
        
        error_log('EasyPay IPN: Order confirmed - order_id=' . $orderId . ', transaction_id=' . $result['transaction_id']);
    }
    
    // Ghi log giao dịch
    $db->insert('payment_transactions', [
        'order_id' => $orderId,
        'gateway' => 'easypay',
        'status' => ($status === 1) ? 'success' : 'failed',
        'transaction_ref' => $result['transaction_id'] ?? $requestId,
        'amount' => $amount,
        'message' => 'EasyPay IPN: ' . ($result['message'] ?? 'Webhook processed'),
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Response thành công
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'IPN processed successfully',
        'order_id' => $orderId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    error_log('EasyPay IPN Handler Error: ' . $e->getMessage());
}
?>
