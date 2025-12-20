<?php
require_once __DIR__ . '/../../includes/init.php';
Auth::requireLogin();

$db = Database::getInstance();
require_once __DIR__ . '/../../includes/services/AddressService.php';
$service = new AddressService($db, Auth::id());

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAjax()) {
    jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
}

if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'CSRF token invalid'], 403);
}

$action = trim($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);

if ($action === 'set_default' && $id > 0) {
    if ($service->updateAddress($id, ['is_default' => 1])) {
        jsonResponse(['success' => true, 'message' => 'Đặt địa chỉ mặc định']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Không thể cập nhật'], 500);
    }
} else if ($action === 'get_list') {
    $addrs = $service->getAddresses();
    jsonResponse(['success' => true, 'addresses' => $addrs]);
} else {
    jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}
