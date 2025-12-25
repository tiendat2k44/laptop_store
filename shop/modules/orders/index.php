<?php
/**
 * Shop - Quản lý Đơn Hàng Của Hàng
 * Hiển thị các đơn hàng liên quan đến sản phẩm của cửa hàng
 */

require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_SHOP, '/login.php');

$db = Database::getInstance();
$shopId = Auth::getShopId();

if (!$shopId) {
    Session::setFlash('error', 'Cửa hàng không tồn tại');
    redirect('/shop/');
}

// Lấy tham số bộ lọc từ URL
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Tạo điều kiện WHERE để lọc đơn hàng của shop
$where = "COALESCE(oi.shop_id, p.shop_id, -1) = :shop_id";
$params = ['shop_id' => $shopId];

if ($status) {
    $where .= " AND o.status = :status";
    $params['status'] = $status;
}
if ($keyword) {
    $where .= " AND o.order_number LIKE :keyword";
    $params['keyword'] = '%' . $keyword . '%';
}

$orders = $db->query(
    "SELECT o.id, o.order_number, o.status, o.payment_status, o.created_at, u.full_name,
            SUM(oi.subtotal) as shop_total
     FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     LEFT JOIN products p ON oi.product_id = p.id
     JOIN users u ON o.user_id = u.id
     WHERE $where
     GROUP BY o.id, o.order_number, o.status, o.payment_status, o.created_at, u.full_name
     HAVING COUNT(DISTINCT COALESCE(oi.shop_id, p.shop_id, -1)) = 1 AND MIN(COALESCE(oi.shop_id, p.shop_id, -1)) = :shop_id
     ORDER BY o.created_at DESC
     LIMIT 100",
    $params
);

$pageTitle = 'Quản lý đơn hàng';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-cart-check"></i> Đơn hàng</h2>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="keyword" class="form-control" placeholder="Tìm mã đơn..." value="<?= escape($keyword) ?>">
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                        <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                        <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Đang xử lý</option>
                        <option value="shipping" <?= $status === 'shipping' ? 'selected' : '' ?>>Đang giao</option>
                        <option value="delivered" <?= $status === 'delivered' ? 'selected' : '' ?>>Đã giao</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Số tiền</th>
                    <th>Trạng thái</th>
                    <th>Thanh toán</th>
                    <th>Ngày đặt</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders): ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="fw-bold">#<?= escape($order['order_number']) ?></td>
                        <td><?= escape($order['full_name']) ?></td>
                        <td class="text-danger fw-bold"><?= formatPrice($order['shop_total']) ?></td>
                        <td><?= getOrderStatusBadge($order['status']) ?></td>
                        <td><?= getPaymentStatusBadge($order['payment_status']) ?></td>
                        <td><small><?= formatDate($order['created_at']) ?></small></td>
                        <td>
                            <a href="<?php echo SITE_URL; ?>/shop/modules/orders/view.php?id=<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Xem</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        Không có đơn hàng nào
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
