<?php
require_once __DIR__ . '/../includes/init.php';

if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để xem chi tiết đơn hàng');
    redirect('/login.php?redirect=/account/orders.php');
}

$db = Database::getInstance();
$orderId = intval($_GET['id'] ?? 0);

if ($orderId <= 0) {
    Session::setFlash('error', 'Đơn hàng không hợp lệ');
    redirect('/account/orders.php');
}

// Lấy thông tin đơn hàng
$order = $db->queryOne(
    "SELECT * FROM orders WHERE id = :id AND user_id = :user_id",
    ['id' => $orderId, 'user_id' => Auth::id()]
);

if (!$order) {
    Session::setFlash('error', 'Không tìm thấy đơn hàng');
    redirect('/account/orders.php');
}

// Lấy danh sách sản phẩm trong đơn
$items = $db->query(
    "SELECT oi.*, p.id as product_id FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = :order_id",
    ['order_id' => $orderId]
);

$pageTitle = 'Chi tiết đơn hàng ' . escape($order['order_number']);
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">
    <div class="row mb-4">
        <div class="col">
            <a href="<?= SITE_URL ?>/account/orders.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Thông tin đơn hàng -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Mã đơn hàng: <strong><?= escape($order['order_number']) ?></strong></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Thông tin giao hàng</h6>
                            <p class="mb-1"><strong><?= escape($order['recipient_name']) ?></strong></p>
                            <p class="mb-1">Điện thoại: <?= escape($order['recipient_phone']) ?></p>
                            <p class="mb-1"><?= escape($order['shipping_address']) ?></p>
                            <p class="mb-0"><?= escape($order['city'] . ($order['district'] ? ', ' . $order['district'] : '') . ($order['ward'] ? ', ' . $order['ward'] : '')) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Trạng thái</h6>
                            <p class="mb-2">
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
                            </p>
                            <p class="mb-1"><strong>Phương thức thanh toán:</strong> <?= $order['payment_method'] === 'COD' ? 'Thanh toán khi nhận hàng' : escape($order['payment_method']) ?></p>
                            <p class="mb-0">
                                <strong>Thanh toán:</strong>
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
                            </p>
                        </div>
                    </div>
                    <?php if ($order['notes']): ?>
                    <hr>
                    <p><strong>Ghi chú:</strong> <?= escape($order['notes']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sản phẩm trong đơn -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Sản phẩm trong đơn hàng</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($items as $item): ?>
                        <div class="list-group-item d-flex align-items-center py-3">
                            <?php if ($item['product_thumbnail']): ?>
                            <img src="<?= image_url($item['product_thumbnail']) ?>" alt="" class="me-3" style="width:80px;height:80px;object-fit:cover;border-radius:8px;">
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= escape($item['product_name']) ?></h6>
                                <small class="text-muted">Số lượng: <?= (int)$item['quantity'] ?></small>
                            </div>
                            <div class="text-end">
                                <p class="mb-1"><strong><?= formatPrice($item['price']) ?>/cái</strong></p>
                                <p class="text-danger fw-bold"><?= formatPrice($item['subtotal']) ?></p>
                                <?php if ($item['status'] === 'delivered' && $item['product_id']): ?>
                                <a href="<?= SITE_URL ?>/account/review.php?product_id=<?= (int)$item['product_id'] ?>&order_id=<?= (int)$orderId ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-star"></i> Đánh giá
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Tóm tắt tiền -->
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h5 class="card-title">Tóm tắt tiền</h5>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Tạm tính</span>
                        <span><?= formatPrice($order['subtotal']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Phí vận chuyển</span>
                        <span><?= formatPrice($order['shipping_fee']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Giảm giá</span>
                        <span>-<?= formatPrice($order['discount_amount']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between fs-5 fw-bold mt-3">
                        <span>Tổng cộng</span>
                        <span class="text-danger"><?= formatPrice($order['total_amount']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
