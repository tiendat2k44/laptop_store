<?php
require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();
require_once __DIR__ . '/../../../includes/services/AdminOrderService.php';
$service = new AdminOrderService($db);

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$filters = [
    'status' => $status,
    'keyword' => $keyword,
    'date_from' => $date_from,
    'date_to' => $date_to,
];

// Tải tối đa 5000 dòng cho export
$rows = $service->listOrders($filters, 5000, 0);

// Header CSV
$filename = 'orders_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=' . $filename);

// BOM cho Excel
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
// Tiêu đề cột
fputcsv($out, ['ID','Mã đơn','Khách hàng','Email','Tổng tiền','Trạng thái','Thanh toán','Ngày tạo']);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'],
        $r['order_number'],
        $r['customer_name'],
        $r['customer_email'],
        $r['total_amount'],
        $r['status'],
        $r['payment_status'],
        date('Y-m-d H:i:s', strtotime($r['created_at'])),
    ]);
}

fclose($out);
exit;
