<?php
require_once __DIR__ . '/../includes/init.php';

// Kiểm tra quyền admin
if (!Auth::check() || !Auth::isAdmin()) {
    Session::setFlash('error', 'Bạn không có quyền truy cập trang này');
    redirect('/login.php');
}

$db = Database::getInstance();

// Lấy thống kê tổng quan
$stats = [
    'total_users' => $db->queryOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
    'total_shops' => $db->queryOne("SELECT COUNT(*) as count FROM shops WHERE status = 'active'")['count'] ?? 0,
    'total_products' => $db->queryOne("SELECT COUNT(*) as count FROM products WHERE status = 'active'")['count'] ?? 0,
    'total_orders' => $db->queryOne("SELECT COUNT(*) as count FROM orders")['count'] ?? 0,
    'pending_shops' => $db->queryOne("SELECT COUNT(*) as count FROM shops WHERE status = 'pending'")['count'] ?? 0,
    'total_revenue' => $db->queryOne("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'")['total'] ?? 0
];

// Lấy đơn hàng mới nhất
$recentOrders = $db->query(
    "SELECT o.id, o.order_number, o.total_amount, o.status, o.payment_status, o.created_at,
            u.full_name AS customer_name
     FROM orders o
     JOIN users u ON o.user_id = u.id
     ORDER BY o.created_at DESC
     LIMIT 10"
);

// Lấy shop đang chờ duyệt
$pendingShops = $db->query(
    "SELECT s.id, s.shop_name, s.created_at, u.full_name, u.email
     FROM shops s
     JOIN users u ON s.user_id = u.id
     WHERE s.status = 'pending'
     ORDER BY s.created_at DESC
     LIMIT 5"
);

$pageTitle = 'Admin Dashboard';

// Doanh thu tổng hợp - Compatible với cả PostgreSQL và MySQL
$driver = $db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === 'pgsql') {
    $revenueToday = $db->queryOne(
        "SELECT COALESCE(SUM(total_amount),0) AS total FROM orders 
         WHERE payment_status = 'paid' AND created_at::date = CURRENT_DATE"
    );
    $revenueThisMonth = $db->queryOne(
        "SELECT COALESCE(SUM(total_amount),0) AS total FROM orders 
         WHERE payment_status = 'paid' AND date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE)"
    );
    $revenueThisYear = $db->queryOne(
        "SELECT COALESCE(SUM(total_amount),0) AS total FROM orders 
         WHERE payment_status = 'paid' AND date_trunc('year', created_at) = date_trunc('year', CURRENT_DATE)"
    );
    $chartData = $db->query(
        "SELECT to_char(created_at::date, 'YYYY-MM-DD') AS date,
                COALESCE(SUM(total_amount),0) AS revenue
         FROM orders 
         WHERE created_at >= CURRENT_DATE - INTERVAL '6 days' AND payment_status = 'paid'
         GROUP BY created_at::date
         ORDER BY date"
    );
} else {
    // MySQL
    $revenueToday = $db->queryOne(
        "SELECT COALESCE(SUM(total_amount),0) AS total FROM orders 
         WHERE payment_status = 'paid' AND DATE(created_at) = CURDATE()"
    );
    $revenueThisMonth = $db->queryOne(
        "SELECT COALESCE(SUM(total_amount),0) AS total FROM orders 
         WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
    );
    $revenueThisYear = $db->queryOne(
        "SELECT COALESCE(SUM(total_amount),0) AS total FROM orders 
         WHERE payment_status = 'paid' AND YEAR(created_at) = YEAR(NOW())"
    );
    $chartData = $db->query(
        "SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS date,
                COALESCE(SUM(total_amount),0) AS revenue
         FROM orders 
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND payment_status = 'paid'
         GROUP BY DATE(created_at)
         ORDER BY date"
    );
}

$revenueToday = $revenueToday ? (float)$revenueToday['total'] : 0;
$revenueThisMonth = $revenueThisMonth ? (float)$revenueThisMonth['total'] : 0;
$revenueThisYear = $revenueThisYear ? (float)$revenueThisYear['total'] : 0;

$chartLabels = [];
$chartValues = [];
if ($chartData) {
    foreach ($chartData as $data) {
        $chartLabels[] = date('d/m', strtotime($data['date']));
        $chartValues[] = (float)$data['revenue'];
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
    <div class="text-muted">
        <i class="bi bi-calendar"></i> <?php echo formatDate(date('Y-m-d H:i:s'), 'd/m/Y H:i'); ?>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card primary shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Khách hàng</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_users']); ?></h3>
                    </div>
                    <div class="fs-1 text-primary">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card success shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Cửa hàng</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_shops']); ?></h3>
                        <?php if ($stats['pending_shops'] > 0): ?>
                            <small class="text-warning"><i class="bi bi-clock"></i> <?php echo $stats['pending_shops']; ?> chờ duyệt</small>
                        <?php endif; ?>
                    </div>
                    <div class="fs-1 text-success">
                        <i class="bi bi-shop"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card warning shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Sản phẩm</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_products']); ?></h3>
                    </div>
                    <div class="fs-1 text-warning">
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
                        <?php endif; ?>
                    </div>
                    <div class="fs-1 text-info">
                        <i class="bi bi-cart-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revenue Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-1">Doanh thu hôm nay</h6>
                <h3 class="text-success mb-0"><?php echo formatPrice($revenueToday); ?></h3>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-1">Doanh thu tháng này</h6>
                <h3 class="text-success mb-0"><?php echo formatPrice($revenueThisMonth); ?></h3>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-1">Doanh thu năm nay</h6>
                <h3 class="text-success mb-0"><?php echo formatPrice($revenueThisYear); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Doanh thu 7 ngày gần đây</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Đơn hàng gần đây</h5>
                <a href="/admin/modules/orders/" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
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
                                <th>Thanh toán</th>
                                <th>Thời gian</th>
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
                                        <td>
                                            <a href="/admin/modules/orders/view.php?id=<?php echo $order['id']; ?>" class="text-decoration-none fw-bold">
                                                <?php echo escape($order['order_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo escape($order['customer_name']); ?></td>
                                        <td class="text-danger fw-bold"><?php echo formatPrice($order['total_amount']); ?></td>
                                        <td><?php echo getOrderStatusBadge($order['status']); ?></td>
                                        <td><?php echo getPaymentStatusBadge($order['payment_status']); ?></td>
                                        <td><?php echo timeAgo($order['created_at']); ?></td>
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

<script>
// Revenue Chart
const ctx = document.getElementById('revenueChart');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [{
            label: 'Doanh thu (₫)',
            data: <?php echo json_encode($chartValues); ?>,
            borderColor: 'rgb(13, 110, 253)',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('vi-VN').format(value) + ' ₫';
                    }
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
