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

// Xác minh đơn hàng thuộc cửa hàng này
$order = $db->queryOne(
        "SELECT o.id, o.order_number, o.total_amount, o.status, o.payment_status, o.payment_method, o.created_at, o.delivery_address, 
                        u.full_name, u.phone, u.email
         FROM orders o
         JOIN users u ON o.user_id = u.id
         WHERE o.id = :id
             AND EXISTS (
                SELECT 1 FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = o.id AND COALESCE(oi.shop_id, p.shop_id, -1) = :sid
             )
         LIMIT 1",
        ['id' => $orderId, 'sid' => $shopId]
);

if (!$order) {
    Session::setFlash('error', 'Đơn hàng không tồn tại hoặc không thuộc cửa hàng của bạn');
    redirect(SITE_URL . '/shop/modules/orders/');
}

// Đảm bảo đơn chỉ thuộc riêng cửa hàng này (không chứa sản phẩm shop khác)
$shopScope = $db->queryOne(
    "SELECT COUNT(DISTINCT COALESCE(oi.shop_id, p.shop_id, -1)) as shop_count,
            MIN(COALESCE(oi.shop_id, p.shop_id, -1)) as any_shop
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = :oid",
    ['oid' => $orderId]
);

if (!$shopScope || (int)$shopScope['shop_count'] !== 1 || (int)$shopScope['any_shop'] !== (int)$shopId) {
    Session::setFlash('error', 'Đơn này chứa sản phẩm của cửa hàng khác, không thể chỉnh sửa');
    redirect(SITE_URL . '/shop/modules/orders/');
}

// Lấy các mục đơn hàng cho cửa hàng này
$orderItems = $db->query(
    "SELECT oi.id, oi.quantity, oi.subtotal, oi.product_id, oi.product_name, oi.price
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = :order_id AND COALESCE(oi.shop_id, p.shop_id, -1) = :shop_id",
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
                                <span id="order-status-badge"><?= getOrderStatusBadge($order['status']) ?></span>
                            </p>
                            <p><strong>Phương thức:</strong>
                                <?php 
                                $paymentMethods = [
                                    'COD' => ['COD', 'secondary'],
                                    'MOMO' => ['MoMo', 'success'],
                                    'VNPAY' => ['VNPay', 'primary'],
                                    'EASYPAY' => ['EasyPay', 'info']
                                ];
                                $pm = $order['payment_method'] ?? 'COD';
                                [$pmLabel, $pmClass] = $paymentMethods[$pm] ?? ['Không xác định', 'secondary'];
                                ?>
                                <span class="badge bg-<?= $pmClass ?>"><?= $pmLabel ?></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Thanh toán:</strong> <?= getPaymentStatusBadge($order['payment_status']) ?></p>
                            <p><strong>Tổng tiền:</strong> <span class="text-danger fw-bold"><?= formatPrice($order['total_amount']) ?></span></p>
                        </div>
                    </div>
                    
                    <!-- Change Status Section -->
                    <?php if (!in_array($order['status'], ['cancelled', 'delivered'])): ?>
                    <div class="alert alert-info">
                        <h6 class="mb-3"><i class="bi bi-arrow-repeat"></i> Cập nhật trạng thái đơn hàng</h6>
                        <p class="small text-muted mb-2">Chỉ thao tác được đơn chứa sản phẩm của cửa hàng bạn.</p>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <select class="form-select" id="new-status">
                                    <option value="">-- Chọn trạng thái mới --</option>
                                    <?php foreach (getOrderStatusMap() as $st => $info): ?>
                                        <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= $info['emoji'] ?> <?= $info['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-primary w-100" id="update-status-btn">
                                    <i class="bi bi-check-circle"></i> Cập nhật
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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
                                    <?php if ($item['product_id']): ?>
                                        <a href="<?php echo SITE_URL; ?>/product-detail.php?id=<?= (int)$item['product_id'] ?>" target="_blank">
                                            <?= escape($item['product_name']) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= escape($item['product_name']) ?>
                                    <?php endif; ?>
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

<script>
// Cập nhật trạng thái đơn hàng
document.getElementById('update-status-btn')?.addEventListener('click', function() {
    const newStatus = document.getElementById('new-status').value;
    
    if (!newStatus) {
        alert('Vui lòng chọn trạng thái mới');
        return;
    }
    
    if (!confirm('Bạn có chắc chắn muốn cập nhật trạng thái đơn hàng?')) {
        return;
    }
    
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
    
    // Dùng jQuery cho AJAX nếu có, nếu không dùng fetch
    if (typeof $ !== 'undefined') {
        $.ajax({
            url: '<?php echo SITE_URL; ?>/shop/ajax/update-order-status.php',
            method: 'POST',
            data: {
                order_id: <?= (int)$orderId ?>,
                status: newStatus
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('✓ ' + response.message);
                    location.reload();
                } else {
                    alert('✗ ' + response.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-circle"></i> Cập nhật';
                }
            },
            error: function() {
                alert('Có lỗi xảy ra, vui lòng thử lại');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Cập nhật';
            }
        });
    } else {
        // Fallback về fetch API
        fetch('<?php echo SITE_URL; ?>/shop/ajax/update-order-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                order_id: <?= (int)$orderId ?>,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ ' + data.message);
                location.reload();
            } else {
                alert('✗ ' + data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Cập nhật';
            }
        })
        .catch(() => {
            alert('Có lỗi xảy ra, vui lòng thử lại');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Cập nhật';
        });
    }
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
