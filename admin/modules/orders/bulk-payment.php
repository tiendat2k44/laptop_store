<?php
/**
 * Admin - Cập Nhật Thanh Toán Hàng Loạt
 * Cập nhật trạng thái thanh toán cho nhiều đơn hàng cùng lúc
 */

require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/orders/');
}

// Xác thực CSRF token
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
require_once __DIR__ . '/../../../includes/services/AdminOrderService.php';
$service = new AdminOrderService($db);

// Xác định trạng thái thanh toán tương ứng với hành động
if ($action === 'mark_paid') $status = 'paid';
if ($action === 'mark_refunded') $status = 'refunded';

if (!$status) {
    Session::setFlash('error', 'Thao tác không hợp lệ');
    redirect('/admin/modules/orders/');
}

// Thực hiện cập nhật hàng loạt trong transaction
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
