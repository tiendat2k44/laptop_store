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

$validStatuses = ['pending','confirmed','processing','shipping','delivered','cancelled'];
if ($status !== '' && !in_array($status, $validStatuses, true)) $status = '';

$filters = [
    'status' => $status,
    'keyword' => $keyword,
    'date_from' => $date_from,
    'date_to' => $date_to,
];
$counts = $service->getCountsByStatus();

// Phân trang
$perPage = 20;
$page = max(1, intval($_GET['p'] ?? 1));
$total = $service->countOrders($filters);
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $perPage;
$orders = $service->listOrders($filters, $perPage, $offset);

$pageTitle = 'Quản lý đơn hàng';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cart-check"></i> Đơn hàng</h2>
    <div class="gap-2" style="display:flex">
        <?php 
            $qs = $_GET; unset($qs['p']); 
            $csvUrl = '/admin/modules/orders/export.php' . (empty($qs) ? '' : ('?' . http_build_query($qs)));
            $xlsxUrl = '/admin/modules/orders/export-xlsx.php' . (empty($qs) ? '' : ('?' . http_build_query($qs)));
        ?>
        <a href="<?= $csvUrl ?>" class="btn btn-outline-success"><i class="bi bi-download"></i> CSV</a>
        <a href="<?= $xlsxUrl ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel"></i> XLSX</a>
    </div>
</div>

<!-- Bộ lọc -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form class="row g-3" method="GET">
            <div class="col-md-3">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả (<?= (int)($counts['all'] ?? 0) ?>)</option>
                    <?php foreach ($validStatuses as $st): ?>
                        <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= ucfirst($st) ?> (<?= (int)($counts[$st] ?? 0) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Từ ngày</label>
                <input type="date" class="form-control" name="date_from" value="<?= escape($date_from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Đến ngày</label>
                <input type="date" class="form-control" name="date_to" value="<?= escape($date_to) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" class="form-control" name="keyword" placeholder="Mã đơn / Tên / SĐT" value="<?= escape($keyword) ?>">
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-search"></i> Lọc</button>
                <a href="<?php echo SITE_URL; ?>/admin/modules/orders/" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Xóa lọc</a>
            </div>
        </form>
    </div>
</div>

<!-- Danh sách + Bulk -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <form id="bulkForm" method="POST" action="/admin/modules/orders/bulk-payment.php">
            <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
            <div class="p-3 d-flex flex-wrap gap-2 align-items-center">
                <div class="input-group" style="max-width:480px;">
                    <select name="action" class="form-select" required>
                        <option value="">-- Chọn thao tác --</option>
                        <option value="mark_paid">Đánh dấu đã thanh toán</option>
                        <option value="mark_refunded">Đánh dấu đã hoàn tiền</option>
                    </select>
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-check2-square"></i> Áp dụng</button>
                </div>
                <small class="text-muted">Chọn các đơn bằng ô checkbox bên trái mỗi dòng</small>
            </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="ordersTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px"><input type="checkbox" id="chkAll"></th>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thanh toán</th>
                        <th>Ngày tạo</th>
                        <th style="width:120px">Hành động</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Không có đơn phù hợp</td></tr>
                    <?php else: foreach ($orders as $o): ?>
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
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<div class="container my-3" id="ordersPagination">
        <nav>
                <ul class="pagination justify-content-center" id="paginationList">
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
    </nav>
</div>

<script>
(function(){
    const form = document.querySelector('form.row.g-3');
    const tableBody = document.getElementById('ordersTableBody');
    const paginationWrap = document.getElementById('ordersPagination');
    const chkAll = document.getElementById('chkAll');

    function serializeFilters(page){
        const data = new URLSearchParams(new FormData(form));
        if (page) data.set('p', page);
        return data.toString();
    }

    function loadPage(page){
        const qs = serializeFilters(page);
        fetch('/admin/modules/orders/list.php?' + qs)
            .then(r=>r.json())
            .then(res=>{
                if (!res.success) return;
                tableBody.innerHTML = res.tbody || '';
                paginationWrap.innerHTML = '<nav>' + (res.pagination||'') + '</nav>';
            });
    }

    if (form) {
        form.addEventListener('submit', function(e){
            e.preventDefault();
            loadPage(1);
        });
    }

    document.addEventListener('click', function(e){
        const a = e.target.closest('.page-link-nav');
        if (!a) return;
        e.preventDefault();
        const page = parseInt(a.getAttribute('data-page')||'1', 10);
        loadPage(page);
    });

    if (chkAll) {
        chkAll.addEventListener('change', function(){
            document.querySelectorAll('.row-check').forEach(cb=>{ cb.checked = chkAll.checked; });
        });
    }
})();
</script>
