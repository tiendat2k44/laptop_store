<?php
require_once __DIR__ . '/../includes/init.php';

// Kiểm tra quyền shop
if (!Auth::check() || !Auth::isShop()) {
    Session::setFlash('error', 'Bạn không có quyền truy cập trang này');
    redirect('/login.php');
}

$db = Database::getInstance();

// Lấy thông tin shop
$shop = $db->queryOne(
    "SELECT id, name, description, logo, banner, status, rating, user_id FROM shops WHERE user_id = :user_id",
    [':user_id' => Auth::id()]
);

if (!$shop) {
    Session::setFlash('error', 'Không tìm thấy thông tin cửa hàng');
    redirect('/');
}

if ($shop['status'] === 'pending') {
    Session::setFlash('warning', 'Cửa hàng của bạn đang chờ admin phê duyệt');
}

$shopId = $shop['id'];

// Lấy thống kê
$stats = [
    'total_products' => $db->queryOne("SELECT COUNT(*) as count FROM products WHERE shop_id = :sid", [':sid' => $shopId])['count'] ?? 0,
    'active_products' => $db->queryOne("SELECT COUNT(*) as count FROM products WHERE shop_id = :sid AND status = 'active'", [':sid' => $shopId])['count'] ?? 0,
    'total_orders' => $db->queryOne("SELECT COUNT(DISTINCT o.id) as count FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.shop_id = :sid", [':sid' => $shopId])['count'] ?? 0,
    'total_revenue' => $db->queryOne("SELECT SUM(oi.subtotal) as total FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE p.shop_id = :sid", [':sid' => $shopId])['total'] ?? 0
];

// Lấy đơn hàng mới
$recentOrders = $db->query(
    "SELECT DISTINCT o.id, o.order_number, o.total_amount, o.status, o.created_at, u.full_name
     FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     JOIN products p ON oi.product_id = p.id
     JOIN users u ON o.user_id = u.id
     WHERE p.shop_id = :sid
     ORDER BY o.created_at DESC
     LIMIT 10",
    [':sid' => $shopId]
);

// Lấy sản phẩm bán chạy
$topProducts = $db->query(
    "SELECT p.id, p.name, p.price, p.stock_quantity, p.sold_count,
            (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY display_order LIMIT 1) as image
     FROM products p
     WHERE p.shop_id = :sid AND p.status = 'active'
     ORDER BY p.sold_count DESC
     LIMIT 5",
    [':sid' => $shopId]
);

$pageTitle = 'Shop Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-shop"></i> <?= escape($shop['shop_name']) ?></h2>
            <p class="text-muted">Quản lý cửa hàng của bạn</p>
            <?php if ($shop['status'] === 'pending'): ?>
            <div class="alert alert-warning">
                <i class="bi bi-clock"></i> Cửa hàng đang chờ admin phê duyệt
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Thống kê -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Sản phẩm</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_products']) ?></h3>
                            <small class="text-success">
                                <?= $stats['active_products'] ?> đang bán
                            </small>
                        </div>
                        <div class="fs-1 text-primary">
                            <i class="bi bi-laptop"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Đơn hàng</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_orders']) ?></h3>
                        </div>
                        <div class="fs-1 text-warning">
                            <i class="bi bi-cart"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Doanh thu</h6>
                            <h3 class="mb-0 text-success"><?= formatPrice($stats['total_revenue']) ?></h3>
                        </div>
                        <div class="fs-1 text-success">
                            <i class="bi bi-cash"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Đánh giá</h6>
                            <h3 class="mb-0"><?= number_format($shop['rating'] ?? 0, 1) ?>/5</h3>
                        </div>
                        <div class="fs-1 text-warning">
                            <i class="bi bi-star-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Đơn hàng -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Đơn hàng mới nhất</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Chưa có đơn hàng</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?= escape($order['order_number']) ?>
                                        </span>
                                    </td>
                                    <td><?= escape($order['full_name']) ?></td>
                                    <td class="fw-bold text-danger">
                                        <?= formatPrice($order['total_amount']) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= escape($order['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= formatDate($order['created_at']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sản phẩm bán chạy -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Sản phẩm bán chạy</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($topProducts)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i> Chưa có sản phẩm
                    </div>
                    <?php else: ?>
                    <?php foreach ($topProducts as $prod): ?>
                    <div class="d-flex align-items-center gap-3 mb-3 border-bottom pb-3">
                        <img src="<?= image_url($prod['image']) ?>" 
                             alt="<?= escape($prod['name']) ?>"
                             class="rounded"
                             style="width: 60px; height: 60px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?= escape($prod['name']) ?></h6>
                            <small class="text-muted">
                                Đã bán: <strong><?= (int)$prod['sold_count'] ?></strong> | 
                                Kho: <?= (int)$prod['stock_quantity'] ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <strong class="text-danger"><?= formatPrice($prod['price']) ?></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Quản lý nhanh</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= SITE_URL ?>/shop/products.php" class="btn btn-primary">
                            <i class="bi bi-laptop"></i> Quản lý sản phẩm
                        </a>
                        <a href="<?= SITE_URL ?>/shop/orders.php" class="btn btn-warning">
                            <i class="bi bi-cart"></i> Quản lý đơn hàng
                        </a>
                        <a href="<?= SITE_URL ?>/shop/profile.php" class="btn btn-outline-secondary">
                            <i class="bi bi-gear"></i> Cài đặt cửa hàng
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
