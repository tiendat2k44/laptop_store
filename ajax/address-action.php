<?php
require_once __DIR__ . '/../includes/init.php';

// AJAX only
if (!isAjax()) {
    http_response_code(400);
    exit('Bad request');
}

// Require login
if (!Auth::check()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();

// Check if addresses table exists
try {
    $tableExists = $db->queryOne("
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'addresses'
        ) as exists
    ");
    
    if (!$tableExists || !$tableExists['exists']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'addresses' => [], 'message' => 'Table not exists']);
        exit;
    }
} catch (Exception $e) {
    error_log('Address table check error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'addresses' => []]);
    exit;
}

require_once __DIR__ . '/../includes/services/AddressService.php';
$service = new AddressService($db, Auth::id());

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'CSRF token invalid']);
    exit;
}

$action = trim($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);

try {
    if ($action === 'set_default' && $id > 0) {
        if ($service->updateAddress($id, ['is_default' => 1])) {
            echo json_encode(['success' => true, 'message' => 'Đặt địa chỉ mặc định']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật']);
        }
    } else if ($action === 'get_list') {
        $addrs = $service->getAddresses();
        echo json_encode(['success' => true, 'addresses' => $addrs ?? []]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    error_log('AddressService error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'addresses' => [], 'message' => $e->getMessage()]);
}
exit;
