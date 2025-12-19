<?php
require_once __DIR__ . '/../includes/init.php';

if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để xem đơn hàng');
    redirect('/login.php?redirect=/account/orders.php');
}

$db = Database::getInstance();

// Lấy danh sách đơn hàng của user
$orders = $db->query(
    "SELECT id, order_number, total_amount, status, payment_status, created_at
     FROM orders
     WHERE user_id = :user_id
     ORDER BY created_at DESC",
    ['user_id' => Auth::id()]
);

$pageTitle = 'Đơn hàng của tôi';
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">
    <h3 class="mb-4"><i class="bi bi-bag-check"></i> Đơn hàng của tôi</h3>
    
    <?php if (empty($orders)): ?>
        <div class="alert alert-info">
            Bạn chưa có đơn hàng nào. <a href="<?= SITE_URL ?>/products.php" class="alert-link">Bắt đầu mua sắm</a>.
        </div>
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
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="fw-bold"><?= escape($order['order_number']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                        <td class="text-danger fw-bold"><?= formatPrice($order['total_amount']) ?></td>
                        <td>
                            <?php
                            $statusBadge = [
                                'pending' => ['bg-warning', 'Chờ xác nhận'],
                                'confirmed' => ['bg-info', 'Đã xác nhận'],
                                'processing' => ['bg-primary', 'Đang xử lý'],
                                'shipping' => ['bg-primary', 'Đang giao'],
                                'delivered' => ['bg-success', 'Đã giao'],
                                'cancelled' => ['bg-danger', 'Đã hủy']
                            ];
                            $status = $order['status'] ?? 'pending';
                            [$badgeClass, $badgeText] = $statusBadge[$status] ?? ['bg-secondary', 'Không xác định'];
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                        </td>
                        <td>
                            <?php
                            $paymentBadge = [
                                'pending' => ['bg-warning', 'Chờ thanh toán'],
                                'paid' => ['bg-success', 'Đã thanh toán'],
                                'failed' => ['bg-danger', 'Thất bại'],
                                'refunded' => ['bg-secondary', 'Hoàn tiền']
                            ];
                            $paymentStatus = $order['payment_status'] ?? 'pending';
                            [$pbClass, $pbText] = $paymentBadge[$paymentStatus] ?? ['bg-secondary', 'Không xác định'];
                            ?>
                            <span class="badge <?= $pbClass ?>"><?= $pbText ?></span>
                        </td>
                        <td>
                            <a href="<?= SITE_URL ?>/account/order-detail.php?id=<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline-primary">
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
