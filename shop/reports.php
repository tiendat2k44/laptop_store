<?php
require_once __DIR__ . '/../includes/init.php';

// Yêu cầu quyền truy cập cửa hàng
Auth::requireRole(ROLE_SHOP, '/login.php');

$pageTitle = 'Báo cáo doanh thu';
$db = Database::getInstance();
$shopId = Auth::getShopId();

if (!$shopId) {
    Session::setFlash('error', 'Cửa hàng chưa được kích hoạt');
    redirect('/');
}

// Lấy khoảng thời gian từ bộ lọc
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Ngày đầu tiên của tháng hiện tại
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Hôm nay
$period = $_GET['period'] ?? 'day'; // day, week, month

// Lấy driver cơ sở dữ liệu
$driver = $db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);

// Thống kê doanh thu
if ($driver === 'pgsql') {
    $totalRevenue = $db->queryOne("
        SELECT COALESCE(SUM(oi.subtotal), 0) as revenue 
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.shop_id = :shop_id 
        AND o.payment_status = 'paid'
        AND oi.created_at BETWEEN :start_date AND :end_date
    ", ['shop_id' => $shopId, 'start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59'])['revenue'] ?? 0;
    
    $pendingRevenue = $db->queryOne("
        SELECT COALESCE(SUM(oi.subtotal), 0) as revenue 
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.shop_id = :shop_id 
        AND o.payment_status = 'pending'
        AND oi.created_at BETWEEN :start_date AND :end_date
    ", ['shop_id' => $shopId, 'start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59'])['revenue'] ?? 0;
    
    // Doanh thu theo kỳ (PostgreSQL)
    if ($period === 'day') {
        $groupBy = "DATE(oi.created_at)";
        $dateFormat = "TO_CHAR(oi.created_at, 'DD/MM/YYYY')";
    } elseif ($period === 'week') {
        $groupBy = "DATE_TRUNC('week', oi.created_at)";
        $dateFormat = "TO_CHAR(DATE_TRUNC('week', oi.created_at), 'DD/MM/YYYY')";
    } else {
        $groupBy = "DATE_TRUNC('month', oi.created_at)";
        $dateFormat = "TO_CHAR(DATE_TRUNC('month', oi.created_at), 'MM/YYYY')";
    }
    
    $revenueByPeriod = $db->query("
        SELECT $dateFormat as period_label,
               COALESCE(SUM(oi.subtotal), 0) as revenue,
               COUNT(DISTINCT oi.order_id) as order_count
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.shop_id = :shop_id 
        AND o.payment_status = 'paid'
        AND oi.created_at BETWEEN :start_date AND :end_date
        GROUP BY $groupBy
        ORDER BY $groupBy DESC
        LIMIT 30
    ", ['shop_id' => $shopId, 'start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59']);
    
} else {
    // MySQL
    $totalRevenue = $db->queryOne("
        SELECT COALESCE(SUM(oi.subtotal), 0) as revenue 
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.shop_id = :shop_id 
        AND o.payment_status = 'paid'
        AND oi.created_at BETWEEN :start_date AND :end_date
    ", ['shop_id' => $shopId, 'start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59'])['revenue'] ?? 0;
    
    $pendingRevenue = $db->queryOne("
        SELECT COALESCE(SUM(oi.subtotal), 0) as revenue 
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.shop_id = :shop_id 
        AND o.payment_status = 'pending'
        AND oi.created_at BETWEEN :start_date AND :end_date
    ", ['shop_id' => $shopId, 'start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59'])['revenue'] ?? 0;
    
    // Doanh thu theo kỳ (MySQL)
    if ($period === 'day') {
        $groupBy = "DATE(oi.created_at)";
        $dateFormat = "DATE_FORMAT(oi.created_at, '%d/%m/%Y')";
    } elseif ($period === 'week') {
        $groupBy = "YEARWEEK(oi.created_at)";
        $dateFormat = "CONCAT('Tuần ', WEEK(oi.created_at), '/', YEAR(oi.created_at))";
    } else {
        $groupBy = "DATE_FORMAT(oi.created_at, '%Y-%m')";
        $dateFormat = "DATE_FORMAT(oi.created_at, '%m/%Y')";
    }
    
    $revenueByPeriod = $db->query("
        SELECT $dateFormat as period_label,
               COALESCE(SUM(oi.subtotal), 0) as revenue,
               COUNT(DISTINCT oi.order_id) as order_count
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.shop_id = :shop_id 
        AND o.payment_status = 'paid'
        AND oi.created_at BETWEEN :start_date AND :end_date
        GROUP BY $groupBy
        ORDER BY $groupBy DESC
        LIMIT 30
    ", ['shop_id' => $shopId, 'start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59']);
}

// Sản phẩm bán chạy nhất
$topProducts = $db->query("
    SELECT p.id, p.name, p.image, 
           COUNT(oi.id) as sold_count,
           SUM(oi.quantity) as total_quantity,
           SUM(oi.subtotal) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE p.shop_id = :shop_id 
    AND o.payment_status = 'paid'
    AND oi.created_at BETWEEN :start_date AND :end_date
    GROUP BY p.id, p.name, p.image
    ORDER BY total_revenue DESC
    LIMIT 10
", ['shop_id' => $shopId, 'start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59']);

// Thống kê đơn hàng
$orderStats = $db->queryOne("
    SELECT 
        COUNT(DISTINCT CASE WHEN o.payment_status = 'paid' THEN oi.order_id END) as paid_orders,
        COUNT(DISTINCT CASE WHEN o.payment_status = 'pending' THEN oi.order_id END) as pending_orders,
        COUNT(DISTINCT CASE WHEN o.status = 'cancelled' THEN oi.order_id END) as cancelled_orders
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.shop_id = :shop_id
    AND oi.created_at BETWEEN :start_date AND :end_date
", ['shop_id' => $shopId, 'start_date' => $startDate, 'end_date' => $endDate . ' 23:59:59']);

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-graph-up"></i> Báo cáo doanh thu</h2>
        <p class="text-muted mb-0">Thống kê và phân tích doanh thu cửa hàng</p>
    </div>
</div>

<!-- Filter -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="start_date" class="form-control" value="<?= escape($startDate) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="end_date" class="form-control" value="<?= escape($endDate) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Nhóm theo</label>
                <select name="period" class="form-select">
                    <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>Ngày</option>
                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Tuần</option>
                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Tháng</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label d-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Lọc
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Revenue Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card stat-card success shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Doanh thu đã nhận</h6>
                        <h3 class="mb-0 text-success"><?php echo formatPrice($totalRevenue); ?></h3>
                        <small class="text-muted">Từ <?= date('d/m/Y', strtotime($startDate)) ?></small>
                    </div>
                    <div class="fs-1 text-success">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card stat-card warning shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Doanh thu chờ thanh toán</h6>
                        <h3 class="mb-0 text-warning"><?php echo formatPrice($pendingRevenue); ?></h3>
                        <small class="text-muted">Chưa thanh toán</small>
                    </div>
                    <div class="fs-1 text-warning">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card stat-card info shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Tổng doanh thu</h6>
                        <h3 class="mb-0 text-info"><?php echo formatPrice($totalRevenue + $pendingRevenue); ?></h3>
                        <small class="text-muted">Trong kỳ</small>
                    </div>
                    <div class="fs-1 text-info">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Statistics -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <div class="fs-1 text-success mb-2">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h4 class="mb-1"><?= number_format($orderStats['paid_orders'] ?? 0) ?></h4>
                <p class="text-muted mb-0">Đơn đã thanh toán</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <div class="fs-1 text-warning mb-2">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <h4 class="mb-1"><?= number_format($orderStats['pending_orders'] ?? 0) ?></h4>
                <p class="text-muted mb-0">Đơn chờ thanh toán</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <div class="fs-1 text-danger mb-2">
                    <i class="bi bi-x-circle"></i>
                </div>
                <h4 class="mb-1"><?= number_format($orderStats['cancelled_orders'] ?? 0) ?></h4>
                <p class="text-muted mb-0">Đơn đã hủy</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Revenue by Period -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart-line"></i> Doanh thu theo <?= $period === 'day' ? 'ngày' : ($period === 'week' ? 'tuần' : 'tháng') ?></h5>
            </div>
            <div class="card-body">
                <?php if (empty($revenueByPeriod)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        Chưa có dữ liệu trong khoảng thời gian này
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Thời gian</th>
                                    <th class="text-end">Số đơn</th>
                                    <th class="text-end">Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxRevenue = max(array_column($revenueByPeriod, 'revenue'));
                                foreach ($revenueByPeriod as $row): 
                                    $percentage = $maxRevenue > 0 ? ($row['revenue'] / $maxRevenue * 100) : 0;
                                ?>
                                <tr>
                                    <td class="fw-bold"><?= escape($row['period_label']) ?></td>
                                    <td class="text-end"><?= number_format($row['order_count']) ?></td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <div class="progress me-3" style="width: 100px; height: 6px;">
                                                <div class="progress-bar bg-success" style="width: <?= $percentage ?>%"></div>
                                            </div>
                                            <strong class="text-success"><?= formatPrice($row['revenue']) ?></strong>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Top Products -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-trophy"></i> Sản phẩm bán chạy</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topProducts)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        Chưa có dữ liệu
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($topProducts as $index => $product): ?>
                        <div class="list-group-item">
                            <div class="d-flex align-items-start">
                                <div class="badge bg-<?= $index < 3 ? 'warning' : 'secondary' ?> me-2 mt-1">
                                    #<?= $index + 1 ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= escape($product['name']) ?></h6>
                                    <small class="text-muted">
                                        <?= number_format($product['total_quantity']) ?> sản phẩm
                                    </small>
                                    <div class="mt-1">
                                        <strong class="text-success"><?= formatPrice($product['total_revenue']) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
