<?php
require_once __DIR__ . '/includes/init.php';

if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để xem đơn hàng');
    redirect('/login.php?redirect=/order-success.php');
}

$db = Database::getInstance();
require_once __DIR__ . '/includes/services/OrderService.php';
$service = new OrderService($db, Auth::id());

$orderId = intval($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    $orderId = intval(Session::get('last_order_id') ?? 0);
}
if ($orderId <= 0) {
    Session::setFlash('error', 'Không tìm thấy đơn hàng');
    redirect('/account/orders.php');
}

$order = $service->getOrderDetail($orderId);
if (!$order) {
    Session::setFlash('error', 'Không tìm thấy đơn hàng');
    redirect('/account/orders.php');
}

$pageTitle = 'Đặt hàng thành công';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-success shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="mb-3">Đặt hàng thành công!</h3>
                    <p class="text-muted mb-4">
                        Cảm ơn bạn đã mua hàng. Vui lòng kiểm tra email hoặc theo dõi đơn hàng.
                    </p>
                    <p class="mb-4">
                        <strong>Mã đơn hàng:</strong><br>
                        <span class="fs-5 badge bg-primary"><?= escape($order['order_number']) ?></span>
                    </p>

                    <a href="<?= SITE_URL ?>/account/order-detail.php?id=<?= (int)$orderId ?>" class="btn btn-outline-primary mb-2 w-100">
                        <i class="bi bi-eye"></i> Xem chi tiết đơn hàng
                    </a>
                    <a href="<?= SITE_URL ?>/account/orders.php" class="btn btn-success mb-2 w-100">
                        <i class="bi bi-list-check"></i> Xem đơn hàng của tôi
                    </a>
                    <a href="<?= SITE_URL ?>/products.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-shop"></i> Tiếp tục mua sắm
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
