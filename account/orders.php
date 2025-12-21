<?php
require_once __DIR__ . '/../includes/init.php';

// Kiểm tra đăng nhập
if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để xem đơn hàng');
    redirect('/login.php?redirect=/account/orders.php');
}

// Khởi tạo service và lấy dữ liệu
$db = Database::getInstance();
require_once __DIR__ . '/../includes/services/OrderService.php';

$orderService = new OrderService($db, Auth::id());

// Lọc theo trạng thái (tùy chọn)
$currentStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$validStatuses = ['pending','confirmed','processing','shipping','delivered','cancelled'];
if ($currentStatus !== '' && !in_array($currentStatus, $validStatuses, true)) {
    $currentStatus = '';
}
$orders = $orderService->getUserOrders($currentStatus ?: null);
$counts = $orderService->getUserOrderCounts();

// Định nghĩa trạng thái đơn hàng
$orderStatuses = [
    'pending' => ['⏳', 'Chờ xác nhận', 'warning'],
    'confirmed' => ['✓', 'Đã xác nhận', 'info'],
    'processing' => ['⚙️', 'Đang xử lý', 'primary'],
    'shipping' => ['🚚', 'Đang giao', 'primary'],
    'delivered' => ['✅', 'Đã giao', 'success'],
    'cancelled' => ['❌', 'Đã hủy', 'danger']
];

$paymentStatuses = [
    'pending' => ['⏳', 'Chờ thanh toán', 'warning'],
    'paid' => ['💰', 'Đã thanh toán', 'success'],
    'failed' => ['❌', 'Thất bại', 'danger'],
    'refunded' => ['↩️', 'Hoàn tiền', 'secondary']
];

$pageTitle = 'Đơn hàng của tôi';
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <!-- Tiêu đề -->
    <div class="mb-4">
        <h2><i class="bi bi-bag-check"></i> Đơn hàng của tôi</h2>
        <hr>
    </div>

    <!-- Bộ lọc trạng thái -->
    <ul class="nav nav-pills mb-4">
        <?php
            $tabs = [
                'all' => 'Tất cả',
                'pending' => 'Chờ xác nhận',
                'confirmed' => 'Đã xác nhận',
                'processing' => 'Đang xử lý',
                'shipping' => 'Đang giao',
                'delivered' => 'Đã giao',
                'cancelled' => 'Đã hủy',
            ];
        ?>
        <?php foreach ($tabs as $key => $label):
            $active = ($key === 'all' && $currentStatus === '') || ($key !== 'all' && $currentStatus === $key);
            $url = SITE_URL . '/account/orders.php' . ($key === 'all' ? '' : ('?status=' . $key));
        ?>
        <li class="nav-item me-2 mb-2">
            <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= $url ?>">
                <?= $label ?>
                <span class="badge bg-light text-dark ms-1"><?= (int)($counts[$key] ?? 0) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <!-- Trường hợp: Không có đơn hàng -->
    <?php if (empty($orders)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Bạn chưa có đơn hàng nào.
        <a href="<?= SITE_URL ?>/products.php" class="alert-link fw-bold">Bắt đầu mua sắm →</a>
    </div>

    <!-- Trường hợp: Có đơn hàng -->
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Mã đơn hàng</th>
                    <th>Ngày đặt</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Thanh toán</th>
                    <th style="width: 180px;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order):
                    $status = $order['status'] ?? 'pending';
                    $paymentStatus = $order['payment_status'] ?? 'pending';
                    
                    [$statusEmoji, $statusText, $statusBadge] = $orderStatuses[$status] ?? ['❓', 'Không xác định', 'secondary'];
                    [$payEmoji, $payText, $payBadge] = $paymentStatuses[$paymentStatus] ?? ['❓', 'Không xác định', 'secondary'];
                ?>
                <tr>
                    <!-- Mã đơn hàng -->
                    <td>
                        <span class="badge bg-light text-dark">
                            <?= escape($order['order_number']) ?>
                        </span>
                    </td>

                    <!-- Ngày đặt -->
                    <td class="text-muted">
                        <small><?= formatDate($order['created_at']) ?></small>
                    </td>

                    <!-- Tổng tiền -->
                    <td>
                        <span class="fw-bold text-danger">
                            <?= formatPrice($order['total_amount']) ?>
                        </span>
                    </td>

                    <!-- Trạng thái đơn hàng -->
                    <td>
                        <span class="badge bg-<?= $statusBadge ?>">
                            <?= $statusEmoji ?> <?= $statusText ?>
                        </span>
                    </td>

                    <!-- Trạng thái thanh toán -->
                    <td>
                        <span class="badge bg-<?= $payBadge ?>">
                            <?= $payEmoji ?> <?= $payText ?>
                        </span>
                    </td>

                    <!-- Hành động -->
                    <td>
                        <a href="<?= SITE_URL ?>/account/order-detail.php?id=<?= (int)$order['id'] ?>" 
                           class="btn btn-sm btn-outline-primary me-2">
                            <i class="bi bi-eye"></i> Chi tiết
                        </a>
                        <?php if (in_array($status, ['pending','confirmed'], true) && $paymentStatus !== 'paid'): ?>
                        <?php $method = $order['payment_method'] ?? 'COD'; ?>
                        <div class="btn-group" role="group" aria-label="Pay again">
                            <?php if ($method === 'MOMO'): ?>
                            <a href="<?= SITE_URL ?>/payment/momo-return.php?id=<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-wallet2"></i> Thanh toán MoMo
                            </a>
                            <?php elseif ($method === 'VNPAY'): ?>
                            <a href="<?= SITE_URL ?>/payment/vnpay-return.php?id=<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-credit-card"></i> Thanh toán VNPay
                            </a>
                            <?php else: ?>
                            <a href="<?= SITE_URL ?>/payment/momo-return.php?id=<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-wallet2"></i> Thanh toán MoMo
                            </a>
                            <a href="<?= SITE_URL ?>/payment/vnpay-return.php?id=<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-credit-card"></i> Thanh toán VNPay
                            </a>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-cancel-order" data-order-id="<?= (int)$order['id'] ?>">
                                <i class="bi bi-x-circle"></i> Hủy đơn
                            </button>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
(function(){
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.btn-cancel-order');
        if (!btn) return;
        const id = btn.getAttribute('data-order-id');
        if (!id) return;
        if (!confirm('Bạn có chắc muốn hủy đơn hàng này?')) return;
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang hủy...';
        fetch('<?= SITE_URL ?>/ajax/order-cancel.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({order_id: id, csrf_token: csrf})
        }).then(r=>r.json()).then(data=>{
            if (data.success) {
                location.reload();
            } else {
                alert(data.message||'Không thể hủy đơn.');
            }
        }).catch(()=>{ alert('Có lỗi xảy ra.'); })
          .finally(()=>{ btn.disabled=false; btn.innerHTML='<i class="bi bi-x-circle"></i> Hủy đơn'; });
    });
})();
</script>
