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
$orders = $orderService->getUserOrders();

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
                    <th style="width: 100px;">Hành động</th>
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

                    <!-- Chi tiết -->
                    <td>
                        <a href="<?= SITE_URL ?>/account/order-detail.php?id=<?= (int)$order['id'] ?>" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Chi tiết
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
