<?php
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/orders/');
}

if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
    Session::setFlash('error', 'CSRF token không hợp lệ');
    redirect('/admin/modules/orders/');
}

$action = $_POST['action'] ?? '';
$ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
if (empty($ids)) {
    Session::setFlash('error', 'Vui lòng chọn ít nhất một đơn');
    redirect('/admin/modules/orders/');
}

$db = Database::getInstance();
require_once __DIR__ . '/../../includes/services/AdminOrderService.php';
$service = new AdminOrderService($db);

$status = null;
if ($action === 'mark_paid') $status = 'paid';
if ($action === 'mark_refunded') $status = 'refunded';

if (!$status) {
    Session::setFlash('error', 'Thao tác không hợp lệ');
    redirect('/admin/modules/orders/');
}

try {
    $db->beginTransaction();
    $updated = 0;
    foreach ($ids as $id) {
        if ($service->updatePaymentStatus($id, $status)) {
            $updated++;
        }
    }
    $db->commit();
    Session::setFlash('success', "Đã cập nhật trạng thái thanh toán cho {$updated} đơn.");
} catch (Exception $e) {
    $db->rollback();
    error_log('Bulk payment update failed: ' . $e->getMessage());
    Session::setFlash('error', 'Có lỗi xảy ra khi cập nhật hàng loạt');
}

redirect('/admin/modules/orders/');
