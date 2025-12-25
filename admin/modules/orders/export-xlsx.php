<?php
/**
 * Admin - Xuất Đơn Hàng Ra File Excel (XLSX)
 * Xuất danh sách đơn hàng theo bộ lọc ra file Excel XLSX
 */

require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();
require_once __DIR__ . '/../../../includes/services/AdminOrderService.php';
require_once __DIR__ . '/../../../includes/helpers/SimpleXLSX.php';

// Lấy các tham số bộ lọc từ URL
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Kiểm tra trạng thái hợp lệ
$validStatuses = ['pending','confirmed','processing','shipping','delivered','cancelled'];
if ($status !== '' && !in_array($status, $validStatuses, true)) $status = '';

$filters = ['status' => $status, 'keyword' => $keyword, 'date_from' => $date_from, 'date_to' => $date_to];

$service = new AdminOrderService($db);
// Lấy tối đa 10.000 đơn hàng để xuất
$orders = $service->listOrders($filters, 10000, 0);

// Tạo file Excel và thêm sheet
$xlsx = new SimpleXLSX();
$sheet = $xlsx->addSheet('Đơn hàng');

// Thêm dòng tiêu đề
$xlsx->addRow($sheet, ['Mã đơn', 'Khách hàng', 'Email', 'Số điện thoại', 'Tổng tiền', 'Trạng thái', 'Thanh toán', 'Ngày tạo']);

// Thêm các dòng dữ liệu đơn hàng
foreach ($orders as $order) {
    $xlsx->addRow($sheet, [
        $order['order_number'],
        $order['customer_name'],
        $order['customer_email'],
        $order['recipient_phone'] ?? '',
        formatPrice($order['total_amount']),
        $order['status'],
        $order['payment_status'],
        formatDate($order['created_at'])
    ]);
}

// Tạo tên file và xuất ra trình duyệt
$filename = 'don-hang-' . date('Y-m-d-His') . '.xlsx';
$xlsx->output($filename);
