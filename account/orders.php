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

// Debug: log để kiểm tra
error_log('OrderService userId: ' . Auth::id());

$orders = $orderService->getUserOrders($currentStatus ?: null);
error_log('Orders found: ' . count($orders));

// DEBUG: Hiển thị thông tin debug (XÓA SAU KHI TEST)
if (isset($_GET['debug'])) {
    echo '<div class="alert alert-warning">';
    echo '<strong>DEBUG INFO:</strong><br>';
    echo 'Current User ID: ' . Auth::id() . '<br>';
    echo 'Orders count: ' . count($orders) . '<br>';
    echo 'Current Status Filter: ' . ($currentStatus ?: 'all') . '<br>';
    
    // Test query trực tiếp
    $testOrders = $db->query("SELECT id, order_number, user_id, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
    echo 'Total orders in DB (last 5): <br>';
    foreach ($testOrders as $o) {
        echo sprintf('- Order #%s (user_id=%d, status=%s, created=%s)<br>', 
            $o['order_number'], $o['user_id'], $o['status'], $o['created_at']);
    }
    echo '</div>';
}

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
    <ul class="nav nav-pills mb-4" role="tablist">
        <?php
            $tabs = [
                'all' => ['Tất cả', 'bi-list'],
                'pending' => ['⏳ Chờ xác nhận', 'bi-hourglass-split'],
                'confirmed' => ['✓ Đã xác nhận', 'bi-check-circle'],
                'processing' => ['⚙️ Đang xử lý', 'bi-gear'],
                'shipping' => ['🚚 Đang giao', 'bi-truck'],
                'delivered' => ['✅ Đã giao', 'bi-check2-circle'],
                'cancelled' => ['❌ Đã hủy', 'bi-x-circle'],
            ];
        ?>
        <?php foreach ($tabs as $key => $data):
            list($label, $icon) = $data;
            $active = ($key === 'all' && $currentStatus === '') || ($key !== 'all' && $currentStatus === $key);
            $url = SITE_URL . '/account/orders.php' . ($key === 'all' ? '' : ('?status=' . $key));
        ?>
        <li class="nav-item me-2 mb-2">
            <a class="nav-link <?= $active ? 'active bg-primary' : 'bg-light' ?>" href="<?= $url ?>">
                <i class="bi <?= $icon ?>"></i> <?= $label ?>
                <span class="badge <?= $active ? 'bg-light text-dark' : 'bg-secondary text-white' ?> ms-2"><?= (int)($counts[$key] ?? 0) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <!-- Trường hợp: Không có đơn hàng -->
    <?php if (empty($orders)): ?>
    <div class="alert alert-info rounded-3" role="alert">
        <i class="bi bi-info-circle me-2 fs-5"></i>
        <strong>Chưa có đơn hàng</strong><br>
        Bạn chưa có đơn hàng nào. <a href="<?= SITE_URL ?>/products.php" class="alert-link fw-bold">Bắt đầu mua sắm →</a>
    </div>

    <!-- Trường hợp: Có đơn hàng -->
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($orders as $order):
            $status = $order['status'] ?? 'pending';
            $paymentStatus = $order['payment_status'] ?? 'pending';
            
            [$statusEmoji, $statusText, $statusBadge] = $orderStatuses[$status] ?? ['❓', 'Không xác định', 'secondary'];
            [$payEmoji, $payText, $payBadge] = $paymentStatuses[$paymentStatus] ?? ['❓', 'Không xác định', 'secondary'];
        ?>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 border-0 order-card" style="transition: all 0.3s ease;">
                <div class="card-body">
                    <!-- Header: Mã đơn hàng + Trạng thái -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="card-title mb-1">
                                <span class="badge bg-light text-dark me-2"><?= escape($order['order_number']) ?></span>
                            </h5>
                            <small class="text-muted">
                                <i class="bi bi-calendar-event"></i> <?= formatDate($order['created_at']) ?>
                            </small>
                        </div>
                        <span class="badge bg-<?= $statusBadge ?> fs-6">
                            <?= $statusEmoji ?> <?= $statusText ?>
                        </span>
                    </div>

                    <!-- Thanh toán status -->
                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-2">Trạng thái thanh toán:</small>
                        <span class="badge bg-<?= $payBadge ?>">
                            <?= $payEmoji ?> <?= $payText ?>
                        </span>
                    </div>

                    <!-- Tổng tiền -->
                    <div class="mb-3">
                        <small class="text-muted d-block">Tổng giá trị:</small>
                        <h4 class="text-danger mb-0">
                            <?= formatPrice($order['total_amount']) ?>
                        </h4>
                    </div>

                    <!-- Hành động -->
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= SITE_URL ?>/account/order-detail.php?id=<?= (int)$order['id'] ?>" 
                           class="btn btn-sm btn-outline-primary flex-grow-1">
                            <i class="bi bi-eye"></i> Chi tiết
                        </a>
                        
                        <?php if (in_array($status, ['pending','confirmed'], true) && $paymentStatus !== 'paid'): ?>
                            <?php $method = $order['payment_method'] ?? 'COD'; ?>
                            
                            <?php if ($method === 'MOMO'): ?>
                            <a href="<?= SITE_URL ?>/payment/momo-return.php?id=<?= (int)$order['id'] ?>" 
                               class="btn btn-sm btn-success flex-grow-1" title="Thanh toán MoMo">
                                <i class="bi bi-wallet2"></i> Thanh toán
                            </a>
                            <?php elseif ($method === 'VNPAY'): ?>
                            <a href="<?= SITE_URL ?>/payment/vnpay-return.php?id=<?= (int)$order['id'] ?>" 
                               class="btn btn-sm btn-primary flex-grow-1" title="Thanh toán VNPay">
                                <i class="bi bi-credit-card"></i> Thanh toán
                            </a>
                            <?php else: ?>
                            <div class="btn-group btn-group-sm flex-grow-1" role="group">
                                <a href="<?= SITE_URL ?>/payment/momo-return.php?id=<?= (int)$order['id'] ?>" 
                                   class="btn btn-success" title="Thanh toán MoMo">
                                    <i class="bi bi-wallet2"></i>
                                </a>
                                <a href="<?= SITE_URL ?>/payment/vnpay-return.php?id=<?= (int)$order['id'] ?>" 
                                   class="btn btn-primary" title="Thanh toán VNPay">
                                    <i class="bi bi-credit-card"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-sm btn-outline-danger btn-cancel-order" 
                                    data-order-id="<?= (int)$order['id'] ?>" title="Hủy đơn hàng">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
    .order-card {
        border-radius: 12px;
        overflow: hidden;
    }
    .order-card:hover {
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
        transform: translateY(-4px);
    }
</style>

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
