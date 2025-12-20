<?php
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Yêu cầu đăng nhập
if (!Auth::check()) {
    jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập'], 401);
}

// Kiểm tra CSRF token
if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
}

$orderId = intval($_POST['order_id'] ?? 0);
if ($orderId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
}

$db = Database::getInstance();
require_once __DIR__ . '/../includes/services/OrderService.php';
$service = new OrderService($db, Auth::id());

try {
    $ok = $service->cancelOrder($orderId);
    if ($ok) {
        jsonResponse(['success' => true, 'message' => 'Đã hủy đơn hàng thành công']);
    }
    jsonResponse(['success' => false, 'message' => 'Không thể hủy đơn hàng']);
} catch (Exception $e) {
    error_log('ajax/order-cancel: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
}
