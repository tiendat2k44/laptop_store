<?php
/**
 * Admin - Quản Lý Thanh Toán
 * Quản lý cấu hình cổng thanh toán (MoMo, VNPay, EasyPay) và lịch sử giao dịch
 */

require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();

$tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'config';

// Xử lý form cập nhật cấu hình thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'CSRF token không hợp lệ');
        redirect(SITE_URL . '/admin/modules/payments/');
    }
    
    if (isset($_POST['update_config'])) {
        $configName = $_POST['config_name'] ?? '';
        $configKey = $_POST['config_key'] ?? '';
        $configValue = $_POST['config_value'] ?? '';
        
        if ($configName && $configKey && strlen($configValue) > 0) {
            try {
                // Kiểm tra config đã tồn tại chưa, nếu có thì UPDATE, chưa có thì INSERT
                $existing = $db->queryOne(
                    "SELECT id FROM payment_config WHERE config_key = :key",
                    ['key' => $configKey]
                );
                
                if ($existing) {
                    // Cập nhật giá trị config cũ
                    $db->execute(
                        "UPDATE payment_config SET config_value = :value, updated_at = NOW() 
                         WHERE config_key = :key",
                        ['value' => $configValue, 'key' => $configKey]
                    );
                } else {
                    // Thêm config mới
                    $db->insert(
                        "INSERT INTO payment_config (config_name, config_key, config_value, created_at) 
                         VALUES (:name, :key, :value, NOW())",
                        ['name' => $configName, 'key' => $configKey, 'value' => $configValue]
                    );
                }
                
                Session::setFlash('success', 'Cấu hình được cập nhật thành công');
            } catch (Exception $e) {
                Session::setFlash('error', 'Lỗi: ' . $e->getMessage());
            }
        } else {
            Session::setFlash('error', 'Vui lòng điền đầy đủ thông tin');
        }
        redirect(SITE_URL . '/admin/modules/payments/?tab=' . $tab);
    }
}

// Lấy tất cả cấu hình thanh toán hiện tại
$configs = $db->query(
    "SELECT * FROM payment_config ORDER BY config_key ASC"
);

// Chuyển thành mảng key-value để dễ truy xuất
foreach ($configs as $cfg) {
    $configArray[$cfg['config_key']] = $cfg['config_value'];
}

// Lấy lịch sử giao dịch thanh toán với bộ lọc
$txnKeyword = isset($_GET['txn_keyword']) ? trim($_GET['txn_keyword']) : '';
$txnGateway = isset($_GET['txn_gateway']) ? trim($_GET['txn_gateway']) : '';
$txnStatus = isset($_GET['txn_status']) ? trim($_GET['txn_status']) : '';

$where = "1=1";
$params = [];
if ($txnKeyword) {
    $where .= " AND (t.transaction_id LIKE :keyword OR t.message LIKE :keyword2)";
    $params['keyword'] = '%' . $txnKeyword . '%';
    $params['keyword2'] = '%' . $txnKeyword . '%';
}
if ($txnGateway) {
    $where .= " AND t.gateway = :gateway";
    $params['gateway'] = $txnGateway;
}
if ($txnStatus) {
    $where .= " AND t.status = :status";
    $params['status'] = $txnStatus;
}

$transactions = $db->query(
    "SELECT t.*, o.order_number, o.user_id FROM payment_transactions t 
     LEFT JOIN orders o ON t.order_id = o.id 
     WHERE $where 
     ORDER BY t.created_at DESC 
     LIMIT 100",
    $params
);

// Thống kê giao dịch
$stats = [
    'total_transactions' => $db->queryOne("SELECT COUNT(*) as count FROM payment_transactions")['count'] ?? 0,
    'success_txns' => $db->queryOne("SELECT COUNT(*) as count FROM payment_transactions WHERE status = 'success'")['count'] ?? 0,
    'pending_txns' => $db->queryOne("SELECT COUNT(*) as count FROM payment_transactions WHERE status = 'pending'")['count'] ?? 0,
    'total_amount' => $db->queryOne("SELECT COALESCE(SUM(amount), 0) as total FROM payment_transactions WHERE status = 'success'")['total'] ?? 0,
    'vnpay_count' => $db->queryOne("SELECT COUNT(*) as count FROM payment_transactions WHERE gateway = 'vnpay' AND status = 'success'")['count'] ?? 0,
    'momo_count' => $db->queryOne("SELECT COUNT(*) as count FROM payment_transactions WHERE gateway = 'momo' AND status = 'success'")['count'] ?? 0,
];

$pageTitle = 'Quản lý thanh toán';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-credit-card"></i> Quản lý thanh toán</h2>
        <p class="text-muted mb-0">Cấu hình gateway & lịch sử giao dịch</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card stat-card primary shadow-sm">
            <div class="card-body p-3">
                <div>
                    <h6 class="text-muted mb-1 small">Tổng giao dịch</h6>
                    <h3 class="mb-0"><?= number_format($stats['total_transactions']) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card success shadow-sm">
            <div class="card-body p-3">
                <div>
                    <h6 class="text-muted mb-1 small">Thành công</h6>
                    <h3 class="mb-0"><?= number_format($stats['success_txns']) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card warning shadow-sm">
            <div class="card-body p-3">
                <div>
                    <h6 class="text-muted mb-1 small">Chờ xử lý</h6>
                    <h3 class="mb-0"><?= number_format($stats['pending_txns']) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info shadow-sm">
            <div class="card-body p-3">
                <div>
                    <h6 class="text-muted mb-1 small">Tổng doanh thu</h6>
                    <h3 class="mb-0 text-success"><?= formatPrice($stats['total_amount']) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="row g-2">
            <div class="col-6">
                <div class="card shadow-sm">
                    <div class="card-body p-2 text-center">
                        <small class="text-muted">VNPay</small>
                        <h5 class="mb-0 text-info"><?= number_format($stats['vnpay_count']) ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card shadow-sm">
                    <div class="card-body p-2 text-center">
                        <small class="text-muted">MoMo</small>
                        <h5 class="mb-0 text-success"><?= number_format($stats['momo_count']) ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4 border-bottom" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $tab === 'config' ? 'active' : '' ?>" 
                onclick="location.href='<?= SITE_URL ?>/admin/modules/payments/?tab=config'" 
                type="button" role="tab">
            <i class="bi bi-gear"></i> Cấu hình gateway
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $tab === 'transactions' ? 'active' : '' ?>" 
                onclick="location.href='<?= SITE_URL ?>/admin/modules/payments/?tab=transactions'" 
                type="button" role="tab">
            <i class="bi bi-clock-history"></i> Lịch sử giao dịch
        </button>
    </li>
</ul>

<!-- TAB 1: CONFIGURATION -->
<?php if ($tab === 'config'): ?>
<div class="row">
    <!-- VNPay Config -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-bank"></i> Cấu hình VNPay</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                    
                    <!-- TMN Code -->
                    <div class="mb-3">
                        <label class="form-label"><strong>TMN Code</strong></label>
                        <input type="text" name="config_name" value="VNPay Terminal Code" style="display:none;">
                        <input type="hidden" name="config_key" value="VNPAY_TMN_CODE">
                        <input type="text" class="form-control form-control-lg" name="config_value" 
                               value="<?= escape($configArray['VNPAY_TMN_CODE'] ?? '') ?>" 
                               placeholder="VD: 1XXXXXX" required>
                        <small class="text-muted d-block mt-1"><i class="bi bi-info-circle"></i> Mã Terminal từ VNPay</small>
                    </div>
                    
                    <!-- Hash Secret -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Hash Secret</strong></label>
                        <input type="text" name="config_name" value="VNPay Hash Secret" style="display:none;">
                        <input type="text" class="form-control form-control-lg font-monospace" name="config_value" 
                               value="<?= escape($configArray['VNPAY_HASH_SECRET'] ?? '') ?>" 
                               placeholder="XXXXXXXXXXXXX" required>
                        <small class="text-muted d-block mt-1"><i class="bi bi-shield-lock"></i> Khóa bí mật từ VNPay</small>
                    </div>
                    
                    <!-- URL -->
                    <div class="mb-4">
                        <label class="form-label"><strong>VNPay URL</strong></label>
                        <input type="text" name="config_name" value="VNPay URL" style="display:none;">
                        <input type="text" class="form-control" name="config_value" 
                               value="<?= escape($configArray['VNPAY_URL'] ?? 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html') ?>" 
                               placeholder="VNPay endpoint">
                        <small class="text-muted d-block mt-2">
                            <strong>Sandbox:</strong> https://sandbox.vnpayment.vn/paymentv2/vpcpay.html<br>
                            <strong>Production:</strong> https://payment.vnpayment.vn/paymentv2/vpcpay.html
                        </small>
                    </div>
                    
                    <button type="submit" name="update_config" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-save"></i> Lưu cấu hình VNPay
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- MoMo Config -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-wallet2"></i> Cấu hình MoMo</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                    
                    <!-- Partner Code -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Partner Code</strong></label>
                        <input type="text" name="config_name" value="MoMo Partner Code" style="display:none;">
                        <input type="hidden" name="config_key" value="MOMO_PARTNER_CODE">
                        <input type="text" class="form-control form-control-lg" name="config_value" 
                               value="<?= escape($configArray['MOMO_PARTNER_CODE'] ?? '') ?>" 
                               placeholder="MXXXXXXXX" required>
                        <small class="text-muted d-block mt-1"><i class="bi bi-info-circle"></i> Partner Code từ MoMo</small>
                    </div>
                    
                    <!-- Access Key -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Access Key</strong></label>
                        <input type="text" name="config_name" value="MoMo Access Key" style="display:none;">
                        <input type="hidden" name="config_key" value="MOMO_ACCESS_KEY">
                        <input type="text" class="form-control form-control-lg font-monospace" name="config_value" 
                               value="<?= escape($configArray['MOMO_ACCESS_KEY'] ?? '') ?>" 
                               placeholder="XXXXXXXXXXXXX" required>
                        <small class="text-muted d-block mt-1"><i class="bi bi-key"></i> Access Key từ MoMo</small>
                    </div>
                    
                    <!-- Secret Key -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Secret Key</strong></label>
                        <input type="text" name="config_name" value="MoMo Secret Key" style="display:none;">
                        <input type="hidden" name="config_key" value="MOMO_SECRET_KEY">
                        <input type="text" class="form-control form-control-lg font-monospace" name="config_value" 
                               value="<?= escape($configArray['MOMO_SECRET_KEY'] ?? '') ?>" 
                               placeholder="XXXXXXXXXXXXX" required>
                        <small class="text-muted d-block mt-1"><i class="bi bi-shield-lock"></i> Secret Key từ MoMo (giữ bí mật)</small>
                    </div>
                    
                    <!-- MoMo Endpoint -->
                    <div class="mb-4">
                        <label class="form-label"><strong>Endpoint</strong></label>
                        <input type="text" name="config_name" value="MoMo Endpoint" style="display:none;">
                        <input type="hidden" name="config_key" value="MOMO_ENDPOINT">
                        <input type="text" class="form-control" name="config_value" 
                               value="<?= escape($configArray['MOMO_ENDPOINT'] ?? 'https://test-payment.momo.vn/v2/gateway/api/create') ?>" 
                               placeholder="MoMo endpoint">
                        <small class="text-muted d-block mt-2">
                            <strong>Test:</strong> https://test-payment.momo.vn/v2/gateway/api/create<br>
                            <strong>Prod:</strong> https://payment.momo.vn/v2/gateway/api/create
                        </small>
                    </div>
                    
                    <button type="submit" name="update_config" class="btn btn-success w-100 btn-lg">
                        <i class="bi bi-save"></i> Lưu cấu hình MoMo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tất cả cấu hình -->
<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Tất cả cấu hình hiện tại</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tên cấu hình</th>
                        <th>Khóa</th>
                        <th>Giá trị</th>
                        <th width="150">Cập nhật lần cuối</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($configs)): ?>
                        <?php foreach ($configs as $cfg): ?>
                        <tr>
                            <td><strong><?= escape($cfg['config_name']) ?></strong></td>
                            <td><code class="text-primary"><?= escape($cfg['config_key']) ?></code></td>
                            <td>
                                <code class="text-danger font-monospace small">
                                    <?= strlen($cfg['config_value']) > 30 ? 
                                        substr($cfg['config_value'], 0, 30) . '...' : 
                                        escape($cfg['config_value']) ?>
                                </code>
                            </td>
                            <td><small class="text-muted"><?= formatDate($cfg['updated_at'] ?? $cfg['created_at']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="bi bi-inbox"></i> Chưa có cấu hình nào
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 2: TRANSACTIONS -->
<?php else: ?>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small">Tìm kiếm giao dịch</label>
                <input type="hidden" name="tab" value="transactions">
                <input type="text" name="txn_keyword" class="form-control" 
                       placeholder="ID hoặc tin nhắn..." 
                       value="<?= escape($txnKeyword) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Cổng thanh toán</label>
                <select name="txn_gateway" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="vnpay" <?= $txnGateway === 'vnpay' ? 'selected' : '' ?>>VNPay</option>
                    <option value="momo" <?= $txnGateway === 'momo' ? 'selected' : '' ?>>MoMo</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Trạng thái</label>
                <select name="txn_status" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="pending" <?= $txnStatus === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                    <option value="success" <?= $txnStatus === 'success' ? 'selected' : '' ?>>Thành công</option>
                    <option value="failed" <?= $txnStatus === 'failed' ? 'selected' : '' ?>>Thất bại</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i> Tìm kiếm</button>
                <a href="<?= SITE_URL ?>/admin/modules/payments/?tab=transactions" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Payment Transactions Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th width="150">ID giao dịch</th>
                        <th>Đơn hàng</th>
                        <th width="90">Cổng</th>
                        <th width="120">Số tiền</th>
                        <th width="90">Trạng thái</th>
                        <th width="150">Thời gian</th>
                        <th>Thông điệp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions): ?>
                        <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td>
                                <code class="text-primary small"><?= escape(substr($txn['transaction_id'], -15)) ?></code>
                            </td>
                            <td>
                                <?php if ($txn['order_number']): ?>
                                    <a href="<?= SITE_URL ?>/admin/modules/orders/view.php?id=<?= (int)$txn['order_id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-link-45deg"></i> <?= escape($txn['order_number']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Không liên kết</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $txn['gateway'] === 'vnpay' ? 'info' : 'success' ?> fs-6">
                                    <?= strtoupper($txn['gateway']) ?>
                                </span>
                            </td>
                            <td>
                                <strong class="text-success"><?= formatPrice($txn['amount']) ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-<?= $txn['status'] === 'success' ? 'success' : ($txn['status'] === 'pending' ? 'warning' : 'danger') ?> fs-6">
                                    <i class="bi bi-<?= $txn['status'] === 'success' ? 'check-circle' : ($txn['status'] === 'pending' ? 'clock' : 'x-circle') ?>"></i>
                                    <?= ucfirst($txn['status']) ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted"><?= formatDate($txn['created_at']) ?></small>
                            </td>
                            <td>
                                <small class="text-muted"><?= escape(substr($txn['message'], 0, 40)) ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3"></i>
                            <p class="mt-2">Không có giao dịch nào</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
