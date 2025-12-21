<?php
require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_SHOP, '/login.php');

$db = Database::getInstance();
$shopId = Auth::getShopId();

if (!$shopId) {
    Session::setFlash('error', 'Cửa hàng không tồn tại');
    redirect(SITE_URL . '/shop/');
}

$orderId = intval($_GET['id'] ?? 0);

if ($orderId <= 0) {
    Session::setFlash('error', 'Đơn hàng không tồn tại');
    redirect(SITE_URL . '/shop/modules/orders/');
}

// Verify order belongs to shop
$order = $db->queryOne(
    "SELECT DISTINCT o.id, o.order_number, o.total_amount, o.status, o.payment_status, o.created_at, o.delivery_address, u.full_name, u.phone, u.email
     FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     JOIN products p ON oi.product_id = p.id
     JOIN users u ON o.user_id = u.id
     WHERE o.id = :id AND p.shop_id = :sid",
    ['id' => $orderId, 'sid' => $shopId]
);

if (!$order) {
    Session::setFlash('error', 'Đơn hàng không tồn tại');
    redirect(SITE_URL . '/shop/modules/orders/');
}

// Get order items for this shop
$orderItems = $db->query(
    "SELECT oi.id, oi.quantity, oi.subtotal, p.id as product_id, p.name, p.price
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = :order_id AND p.shop_id = :shop_id",
    ['order_id' => $orderId, 'shop_id' => $shopId]
);

$pageTitle = 'Chi tiết đơn hàng #' . escape($order['order_number']);
include __DIR__ . '/../../../includes/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-receipt"></i> Đơn hàng #<?= escape($order['order_number']) ?></h2>
        <a href="<?php echo SITE_URL; ?>/shop/modules/orders/" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="row">
        <!-- Order Info -->
        <div class="col-lg-8">
            <!-- Order Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Thông tin đơn hàng</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Ngày đặt:</strong> <?= formatDate($order['created_at']) ?></p>
                            <p><strong>Trạng thái:</strong> 
                                <span class="badge bg-<?= 
                                    $order['status'] === 'pending' ? 'warning' :
                                    ($order['status'] === 'delivered' ? 'success' :
                                    ($order['status'] === 'cancelled' ? 'danger' : 'info'))
                                ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Thanh toán:</strong> 
                                <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                    <?= $order['payment_status'] === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán' ?>
                                </span>
                            </p>
                            <p><strong>Tổng tiền:</strong> <span class="text-danger fw-bold"><?= formatPrice($order['total_amount']) ?></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Sản phẩm</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?= (int)$item['product_id'] ?>" target="_blank">
                                        <?= escape($item['name']) ?>
                                    </a>
                                </td>
                                <td><?= formatPrice($item['price']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td class="fw-bold"><?= formatPrice($item['subtotal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Khách hàng</h5>
                </div>
                <div class="card-body">
                    <p><strong>Tên:</strong> <?= escape($order['full_name']) ?></p>
                    <p><strong>Email:</strong> <?= escape($order['email']) ?></p>
                    <p><strong>Điện thoại:</strong> <?= escape($order['phone']) ?></p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Địa chỉ giao hàng</h5>
                </div>
                <div class="card-body">
                    <p><?= escape($order['delivery_address'] ?? 'Chưa có thông tin') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
