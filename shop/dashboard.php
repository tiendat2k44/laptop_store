<?php
require_once __DIR__ . '/../includes/init.php';

// Yêu cầu quyền truy cập cửa hàng
Auth::requireRole(ROLE_SHOP, '/login.php');

$pageTitle = 'Dashboard Shop';
$db = Database::getInstance();
$shopId = Auth::getShopId();

if (!$shopId) {
    Session::setFlash('error', 'Cửa hàng chưa được kích hoạt');
    redirect('/');
}

// Lấy thông tin cửa hàng
$shop = $db->queryOne("SELECT * FROM shops WHERE id = :id", ['id' => $shopId]);

if (!$shop || $shop['status'] !== 'active') {
    Session::setFlash('error', 'Cửa hàng chưa được phê duyệt hoặc đã bị tạm ngừng');
    redirect('/');
}

// Lấy thống kê
$stats = [
    'total_products' => $db->queryOne("SELECT COUNT(*) as count FROM products WHERE shop_id = :shop_id", ['shop_id' => $shopId])['count'] ?? 0,
    'active_products' => $db->queryOne("SELECT COUNT(*) as count FROM products WHERE shop_id = :shop_id AND status = 'active'", ['shop_id' => $shopId])['count'] ?? 0,
    'total_orders' => $db->queryOne("SELECT COUNT(DISTINCT order_id) as count FROM order_items WHERE shop_id = :shop_id", ['shop_id' => $shopId])['count'] ?? 0,
    'pending_orders' => $db->queryOne("SELECT COUNT(*) as count FROM order_items WHERE shop_id = :shop_id AND status = 'pending'", ['shop_id' => $shopId])['count'] ?? 0,
];

// Lấy doanh thu - Tương thích với PostgreSQL và MySQL
$driver = $db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === 'pgsql') {
    $revenueThisMonth = $db->queryOne("
        SELECT COALESCE(SUM(oi.subtotal), 0) as revenue 
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.shop_id = :shop_id 
        AND DATE_TRUNC('month', oi.created_at) = DATE_TRUNC('month', CURRENT_DATE)
        AND o.payment_status = 'paid'
    ", ['shop_id' => $shopId])['revenue'] ?? 0;
    
    $recentOrders = $db->query("
        SELECT DISTINCT ON (o.id) o.*, u.full_name as customer_name,
               (SELECT SUM(subtotal) FROM order_items WHERE order_id = o.id AND shop_id = :shop_id) as shop_total
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN users u ON o.user_id = u.id
        WHERE oi.shop_id = :shop_id
        ORDER BY o.id, o.created_at DESC
        LIMIT 10
    ", ['shop_id' => $shopId]);
} else {
    // MySQL
    $revenueThisMonth = $db->queryOne("
        SELECT COALESCE(SUM(oi.subtotal), 0) as revenue 
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.shop_id = :shop_id 
        AND MONTH(oi.created_at) = MONTH(CURDATE())
        AND YEAR(oi.created_at) = YEAR(CURDATE())
        AND o.payment_status = 'paid'
    ", ['shop_id' => $shopId])['revenue'] ?? 0;
    
    $recentOrders = $db->query("
        SELECT o.*, u.full_name as customer_name,
               (SELECT SUM(subtotal) FROM order_items WHERE order_id = o.id AND shop_id = :shop_id) as shop_total
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN users u ON o.user_id = u.id
        WHERE oi.shop_id = :shop_id
        GROUP BY o.id, u.full_name
        ORDER BY o.created_at DESC
        LIMIT 10
    ", ['shop_id' => $shopId]);
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-shop"></i> <?php echo escape($shop['shop_name']); ?></h2>
        <p class="text-muted mb-0">Quản lý cửa hàng của bạn</p>
    </div>
    <a href="/shop/settings.php" class="btn btn-outline-primary">
        <i class="bi bi-gear"></i> Cài đặt cửa hàng
    </a>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card primary shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Sản phẩm</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_products']); ?></h3>
                        <small class="text-success"><i class="bi bi-check-circle"></i> <?php echo $stats['active_products']; ?> đang bán</small>
                    </div>
                    <div class="fs-1 text-primary opacity-75">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card info shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Đơn hàng</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_orders']); ?></h3>
                        <?php if ($stats['pending_orders'] > 0): ?>
                            <small class="text-warning"><i class="bi bi-clock"></i> <?php echo $stats['pending_orders']; ?> chờ xử lý</small>
                        <?php else: ?>
                            <small class="text-muted">Tất cả đã xử lý</small>
                        <?php endif; ?>
                    </div>
                    <div class="fs-1 text-info opacity-75">
                        <i class="bi bi-cart-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card stat-card success shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Doanh thu tháng này</h6>
                        <h3 class="text-success mb-0"><?php echo formatPrice($revenueThisMonth); ?></h3>
                        <small class="text-muted"><?php echo date('F Y'); ?></small>
                    </div>
                    <div class="fs-1 text-success opacity-75">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <a href="/shop/modules/products/create.php" class="btn btn-lg btn-primary w-100 py-3 shadow-sm">
            <i class="bi bi-plus-circle fs-3 d-block mb-2"></i>
            <strong>Thêm sản phẩm</strong>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/shop/modules/products/" class="btn btn-lg btn-outline-primary w-100 py-3 shadow-sm">
            <i class="bi bi-box-seam fs-3 d-block mb-2"></i>
            <strong>Quản lý sản phẩm</strong>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/shop/modules/orders/" class="btn btn-lg btn-outline-info w-100 py-3 shadow-sm">
            <i class="bi bi-cart-check fs-3 d-block mb-2"></i>
            <strong>Quản lý đơn hàng</strong>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/shop/reports.php" class="btn btn-lg btn-outline-success w-100 py-3 shadow-sm">
            <i class="bi bi-graph-up fs-3 d-block mb-2"></i>
            <strong>Báo cáo doanh thu</strong>
        </a>
    </div>
</div>

<!-- Recent Orders -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Đơn hàng gần đây</h5>
                <a href="/shop/modules/orders/" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                                <th>Thời gian</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Chưa có đơn hàng nào</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo escape($order['order_number']); ?></td>
                                        <td><?php echo escape($order['customer_name']); ?></td>
                                        <td class="text-danger fw-bold"><?php echo formatPrice($order['shop_total']); ?></td>
                                        <td><?php echo getOrderStatusBadge($order['status']); ?></td>
                                        <td><?php echo timeAgo($order['created_at']); ?></td>
                                        <td>
                                            <a href="/shop/modules/orders/view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> Xem
                                            </a>
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
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
