<?php
/**
 * Admin - AJAX Lấy Danh Sách Đơn Hàng
 * API trả về HTML để render danh sách đơn hàng với phân trang
 */

require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

header('Content-Type: application/json');

$db = Database::getInstance();
require_once __DIR__ . '/../../../includes/services/AdminOrderService.php';
$service = new AdminOrderService($db);

// Lấy các tham số bộ lọc và phân trang
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

// Tính toán phân trang
$perPage = max(1, intval($_GET['perPage'] ?? 20));
$page = max(1, intval($_GET['p'] ?? 1));
$total = $service->countOrders($filters);
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $perPage;
$orders = $service->listOrders($filters, $perPage, $offset);

// Tạo HTML cho tbody (danh sách đơn hàng)
foreach ($orders as $o): ?>
<tr>
    <td><input type="checkbox" class="row-check" name="ids[]" value="<?= (int)$o['id'] ?>"></td>
    <td><span class="badge bg-light text-dark"><?= escape($o['order_number']) ?></span></td>
    <td>
        <div class="fw-bold"><?= escape($o['customer_name']) ?></div>
        <div class="small text-muted"><?= escape($o['customer_email']) ?></div>
    </td>
    <td class="text-danger fw-bold"><?= formatPrice($o['total_amount']) ?></td>
    <td><?= getOrderStatusBadge($o['status']) ?></td>
    <td><?= getPaymentStatusBadge($o['payment_status']) ?></td>
    <td><small class="text-muted"><?= formatDate($o['created_at']) ?></small></td>
    <td>
        <a href="<?php echo SITE_URL; ?>/admin/modules/orders/view.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-eye"></i>
        </a>
    </td>
</tr>
<?php endforeach; 
$tbodyHtml = ob_get_clean();

// Tạo HTML cho pagination (phân trang)
ob_start();
?>
<ul class="pagination justify-content-center">
    <?php 
        $qs = $_GET; 
        $qs['p'] = max(1, $page-1);
        $prevUrl = '/admin/modules/orders/?' . http_build_query($qs);
        $qs['p'] = min($pages, $page+1);
        $nextUrl = '/admin/modules/orders/?' . http_build_query($qs);
    ?>
    <li class="page-item <?= $page<=1?'disabled':'' ?>">
        <a class="page-link page-link-nav" href="<?= $page<=1 ? '#' : $prevUrl ?>" data-page="<?= max(1, $page-1) ?>">«</a>
    </li>
    <?php for ($i=1;$i<=$pages;$i++): $qs['p']=$i; $url='/admin/modules/orders/?'.http_build_query($qs); ?>
    <li class="page-item <?= $i===$page?'active':'' ?>">
        <a class="page-link page-link-nav" href="<?= $url ?>" data-page="<?= $i ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
    <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
        <a class="page-link page-link-nav" href="<?= $page>=$pages ? '#' : $nextUrl ?>" data-page="<?= min($pages, $page+1) ?>">»</a>
    </li>
</ul>
<div class="text-center text-muted small">Trang <?= $page ?> / <?= $pages ?> — Tổng <?= number_format($total) ?> đơn</div>
<?php
$paginationHtml = ob_get_clean();

// Trả về JSON chứa HTML đã render
echo json_encode([
    'success' => true,
    'tbody' => $tbodyHtml,
    'pagination' => $paginationHtml,
    'page' => $page,
    'pages' => $pages,
    'total' => $total,
]);
exit;
