<?php
require_once __DIR__ . '/../includes/init.php';

// Kiểm tra đăng nhập
if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để xem chi tiết đơn hàng');
    redirect('/login.php?redirect=/account/orders.php');
}

// Khởi tạo service
$db = Database::getInstance();
require_once __DIR__ . '/../includes/services/OrderService.php';

// Lấy ID đơn hàng từ URL
$orderId = intval($_GET['id'] ?? 0);
if ($orderId <= 0) {
    Session::setFlash('error', 'Đơn hàng không hợp lệ');
    redirect('/account/orders.php');
}

// Lấy thông tin đơn hàng
$orderService = new OrderService($db, Auth::id());
$order = $orderService->getOrderDetail($orderId);

if (!$order) {
    Session::setFlash('error', 'Không tìm thấy đơn hàng');
    redirect('/account/orders.php');
}

// Lấy danh sách sản phẩm trong đơn
$items = $orderService->getOrderItems($orderId);

// Định nghĩa trạng thái đơn hàng
$orderStatuses = [
    'pending' => ['⏳', 'Chờ xác nhận', 'warning'],
    'confirmed' => ['✓', 'Đã xác nhận', 'info'],
    'processing' => ['⚙️', 'Đang xử lý', 'primary'],
    'shipping' => ['🚚', 'Đang giao', 'primary'],
    'delivered' => ['✅', 'Đã giao', 'success'],
    'cancelled' => ['❌', 'Đã hủy', 'danger']
];

$paymentMethods = ['COD' => 'Thanh toán khi nhận', 'MOMO' => 'MoMo', 'VNPAY' => 'VNPAY'];

$pageTitle = 'Đơn hàng ' . escape($order['order_number']);
include __DIR__ . '/../includes/header.php';

$status = $order['status'] ?? 'pending';
[$statusEmoji, $statusText, $statusBadge] = $orderStatuses[$status] ?? ['❓', 'Không xác định', 'secondary'];
?>

<div class="container my-5">
    <!-- Tiêu đề & Nút quay lại -->
    <div class="mb-4 d-flex align-items-center justify-content-between">
        <h2><i class="bi bi-bag-check"></i> Đơn hàng <?= escape($order['order_number']) ?></h2>
        <a href="<?= SITE_URL ?>/account/orders.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>
    <hr>

    <div class="row">
        <!-- Cột trái: Chi tiết đơn hàng & Sản phẩm -->
        <div class="col-lg-8 mb-4">
            <!-- 📦 Thông tin đơn hàng -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">📋 Thông tin đơn hàng</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Trạng thái -->
                        <div class="col-md-6">
                            <h6 class="text-muted small">Trạng thái</h6>
                            <p class="mb-3">
                                <span class="badge bg-<?= $statusBadge ?> fs-6">
                                    <?= $statusEmoji ?> <?= $statusText ?>
                                </span>
                            </p>
                        </div>

                        <!-- Phương thức thanh toán -->
                        <div class="col-md-6">
                            <h6 class="text-muted small">Phương thức</h6>
                            <p class="mb-3">
                                <strong><?= $paymentMethods[$order['payment_method']] ?? 'Không xác định' ?></strong>
                            </p>
                        </div>

                        <!-- Ngày đặt -->
                        <div class="col-md-6">
                            <h6 class="text-muted small">Ngày đặt</h6>
                            <p class="mb-0">
                                <strong><?= formatDate($order['created_at']) ?></strong>
                            </p>
                        </div>

                        <!-- Thanh toán -->
                        <div class="col-md-6">
                            <h6 class="text-muted small">Thanh toán</h6>
                            <p class="mb-0">
                                <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                    <?= $order['payment_status'] === 'paid' ? '✅ Đã thanh toán' : '⏳ Chờ thanh toán' ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Ghi chú -->
                    <?php if ($order['notes']): ?>
                    <hr class="my-3">
                    <h6 class="text-muted small">📝 Ghi chú</h6>
                    <p class="mb-0"><?= escape($order['notes']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 📍 Địa chỉ giao hàng -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">📍 Địa chỉ giao hàng</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1">
                        <strong><?= escape($order['recipient_name']) ?></strong><br>
                        Điện thoại: <span class="text-monospace"><?= escape($order['recipient_phone']) ?></span>
                    </p>
                    <p class="mb-0 text-muted">
                        <?= escape($order['shipping_address']) ?><br>
                        <?= escape($order['city'] . ($order['district'] ? ', ' . $order['district'] : '') . ($order['ward'] ? ', ' . $order['ward'] : '')) ?>
                    </p>
                </div>
            </div>

            <!-- 📦 Sản phẩm trong đơn -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">📦 Sản phẩm (<?= count($items) ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($items as $item): ?>
                    <div class="border-bottom p-3 d-flex gap-3 align-items-start">
                        <!-- Ảnh -->
                        <?php if ($item['product_thumbnail']): ?>
                        <img src="<?= image_url($item['product_thumbnail']) ?>" alt="" 
                             class="rounded flex-shrink-0" 
                             style="width: 80px; height: 80px; object-fit: cover;">
                        <?php endif; ?>

                        <!-- Thông tin -->
                        <div class="flex-grow-1">
                            <h6><?= escape($item['product_name']) ?></h6>
                            <small class="text-muted">Số lượng: <strong><?= (int)$item['quantity'] ?></strong></small>
                        </div>

                        <!-- Giá -->
                        <div class="text-end flex-shrink-0">
                            <p class="mb-1 text-muted small">
                                <?= formatPrice($item['price']) ?> /cái
                            </p>
                            <p class="mb-0 fw-bold text-danger fs-5">
                                <?= formatPrice($item['subtotal']) ?>
                            </p>

                            <!-- Nút đánh giá (nếu đã giao) -->
                            <?php if ($status === 'delivered' && $item['product_id']): ?>
                            <a href="<?= SITE_URL ?>/account/review.php?product_id=<?= (int)$item['product_id'] ?>&order_id=<?= (int)$orderId ?>" 
                               class="btn btn-sm btn-outline-primary mt-2">
                                <i class="bi bi-star"></i> Đánh giá
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Cột phải: Tóm tắt tiền -->
        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h5 class="card-title mb-4">💰 Tóm tắt tiền</h5>

                    <!-- Chi tiết -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <strong><?= formatPrice($order['subtotal']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Vận chuyển:</span>
                            <strong><?= formatPrice($order['shipping_fee']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between text-success">
                            <span>Giảm giá:</span>
                            <strong>-<?= formatPrice($order['discount_amount']) ?></strong>
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Tổng cộng -->
                    <div class="d-flex justify-content-between fs-5 fw-bold">
                        <span>Tổng cộng</span>
                        <span class="text-danger"><?= formatPrice($order['total_amount']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
