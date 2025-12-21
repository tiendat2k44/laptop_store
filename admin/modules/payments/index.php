<?php
/**
 * Admin Payments Management - Quản lý cấu hình thanh toán & lịch sử giao dịch
 */

require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();

$action = isset($_GET['action']) ? trim($_GET['action']) : 'config';
$tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'vnpay';

// Xử lý cập nhật cấu hình
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'CSRF token không hợp lệ');
        redirect('/admin/modules/payments/');
    }
    
    if (isset($_POST['update_config'])) {
        $configName = $_POST['config_name'] ?? '';
        $configKey = $_POST['config_key'] ?? '';
        $configValue = $_POST['config_value'] ?? '';
        
        if ($configName && $configKey && strlen($configValue) > 0) {
            try {
                // Kiểm tra xem config đã tồn tại chưa
                $existing = $db->queryOne(
                    "SELECT id FROM payment_config WHERE config_key = :key",
                    ['key' => $configKey]
                );
                
                if ($existing) {
                    // Update
                    $db->execute(
                        "UPDATE payment_config SET config_value = :value, updated_at = NOW() 
                         WHERE config_key = :key",
                        ['value' => $configValue, 'key' => $configKey]
                    );
                } else {
                    // Insert
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
        redirect('/admin/modules/payments/?tab=' . $tab);
    }
}

// Lấy cấu hình hiện tại
$configs = $db->query(
    "SELECT * FROM payment_config ORDER BY config_key ASC"
);

$configArray = [];
foreach ($configs as $cfg) {
    $configArray[$cfg['config_key']] = $cfg['config_value'];
}

// Lấy lịch sử giao dịch (trang payment transactions)
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

$pageTitle = 'Quản lý thanh toán';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-credit-card"></i> Quản lý thanh toán</h2>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $tab === 'config' ? 'active' : '' ?>" 
                onclick="location.href='?tab=config'" 
                type="button" role="tab">
            <i class="bi bi-gear"></i> Cấu hình
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $tab === 'transactions' ? 'active' : '' ?>" 
                onclick="location.href='?tab=transactions'" 
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
                        <label class="form-label">TMN Code</label>
                        <input type="text" name="config_name" value="VNPay Terminal Code" style="display:none;">
                        <input type="hidden" name="config_key" value="VNPAY_TMN_CODE">
                        <input type="text" class="form-control" name="config_value" 
                               value="<?= escape($configArray['VNPAY_TMN_CODE'] ?? '') ?>" 
                               placeholder="VD: 1XXXXXX" required>
                        <small class="text-muted">Mã Terminal từ VNPay</small>
                    </div>
                    
                    <!-- Hash Secret -->
                    <div class="mb-3">
                        <label class="form-label">Hash Secret</label>
                        <input type="text" name="config_name" value="VNPay Hash Secret" style="display:none;">
                        <input type="text" class="form-control font-monospace" name="config_value" 
                               value="<?= escape($configArray['VNPAY_HASH_SECRET'] ?? '') ?>" 
                               placeholder="XXXXXXXXXXXXX" required>
                        <small class="text-muted">Khóa bí mật từ VNPay (chứa trong tài khoản của bạn)</small>
                    </div>
                    
                    <!-- URL -->
                    <div class="mb-3">
                        <label class="form-label">VNPay URL</label>
                        <input type="text" name="config_name" value="VNPay URL" style="display:none;">
                        <input type="text" class="form-control" name="config_value" 
                               value="<?= escape($configArray['VNPAY_URL'] ?? 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html') ?>" 
                               placeholder="VNPay endpoint">
                        <small class="text-muted">
                            Sandbox: https://sandbox.vnpayment.vn/paymentv2/vpcpay.html<br>
                            Production: https://payment.vnpayment.vn/paymentv2/vpcpay.html
                        </small>
                    </div>
                    
                    <button type="submit" name="update_config" class="btn btn-primary w-100">
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
                        <label class="form-label">Partner Code</label>
                        <input type="text" name="config_name" value="MoMo Partner Code" style="display:none;">
                        <input type="text" class="form-control" name="config_key" value="MOMO_PARTNER_CODE" style="display:none;">
                        <input type="text" class="form-control" name="config_value" 
                               value="<?= escape($configArray['MOMO_PARTNER_CODE'] ?? '') ?>" 
                               placeholder="MXXXXXXXX" required>
                        <small class="text-muted">Partner Code từ MoMo</small>
                    </div>
                    
                    <!-- Access Key -->
                    <div class="mb-3">
                        <label class="form-label">Access Key</label>
                        <input type="text" name="config_name" value="MoMo Access Key" style="display:none;">
                        <input type="text" class="form-control font-monospace" name="config_key" value="MOMO_ACCESS_KEY" style="display:none;">
                        <input type="text" class="form-control font-monospace" name="config_value" 
                               value="<?= escape($configArray['MOMO_ACCESS_KEY'] ?? '') ?>" 
                               placeholder="XXXXXXXXXXXXX" required>
                        <small class="text-muted">Access Key từ MoMo</small>
                    </div>
                    
                    <!-- Secret Key -->
                    <div class="mb-3">
                        <label class="form-label">Secret Key</label>
                        <input type="text" name="config_name" value="MoMo Secret Key" style="display:none;">
                        <input type="text" class="form-control font-monospace" name="config_key" value="MOMO_SECRET_KEY" style="display:none;">
                        <input type="text" class="form-control font-monospace" name="config_value" 
                               value="<?= escape($configArray['MOMO_SECRET_KEY'] ?? '') ?>" 
                               placeholder="XXXXXXXXXXXXX" required>
                        <small class="text-muted">Secret Key từ MoMo (giữ bí mật)</small>
                    </div>
                    
                    <!-- MoMo Endpoint -->
                    <div class="mb-3">
                        <label class="form-label">Endpoint</label>
                        <input type="text" name="config_name" value="MoMo Endpoint" style="display:none;">
                        <input type="text" class="form-control" name="config_key" value="MOMO_ENDPOINT" style="display:none;">
                        <input type="text" class="form-control" name="config_value" 
                               value="<?= escape($configArray['MOMO_ENDPOINT'] ?? 'https://test-payment.momo.vn/v2/gateway/api/create') ?>" 
                               placeholder="MoMo endpoint">
                        <small class="text-muted">
                            Test: https://test-payment.momo.vn/v2/gateway/api/create<br>
                            Prod: https://payment.momo.vn/v2/gateway/api/create
                        </small>
                    </div>
                    
                    <button type="submit" name="update_config" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> Lưu cấu hình MoMo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Thông tin cấu hình hiện tại -->
<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Tất cả cấu hình</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Tên cấu hình</th>
                        <th>Khóa</th>
                        <th>Giá trị</th>
                        <th>Cập nhật</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($configs)): ?>
                        <?php foreach ($configs as $cfg): ?>
                        <tr>
                            <td><?= escape($cfg['config_name']) ?></td>
                            <td><code><?= escape($cfg['config_key']) ?></code></td>
                            <td>
                                <code class="text-danger font-monospace">
                                    <?= strlen($cfg['config_value']) > 20 ? 
                                        substr($cfg['config_value'], 0, 20) . '...' : 
                                        escape($cfg['config_value']) ?>
                                </code>
                            </td>
                            <td><small class="text-muted"><?= formatDate($cfg['updated_at'] ?? $cfg['created_at']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">Chưa có cấu hình nào</td>
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
                <input type="hidden" name="tab" value="transactions">
                <input type="text" name="txn_keyword" class="form-control" 
                       placeholder="Tìm ID giao dịch..." 
                       value="<?= escape($txnKeyword) ?>">
            </div>
            <div class="col-md-2">
                <select name="txn_gateway" class="form-select">
                    <option value="">Tất cả cổng</option>
                    <option value="vnpay" <?= $txnGateway === 'vnpay' ? 'selected' : '' ?>>VNPay</option>
                    <option value="momo" <?= $txnGateway === 'momo' ? 'selected' : '' ?>>MoMo</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="txn_status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" <?= $txnStatus === 'pending' ? 'selected' : '' ?>>Chờ</option>
                    <option value="success" <?= $txnStatus === 'success' ? 'selected' : '' ?>>Thành công</option>
                    <option value="failed" <?= $txnStatus === 'failed' ? 'selected' : '' ?>>Thất bại</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Tìm</button>
            </div>
            <div class="col-md-3">
                <a href="<?php echo SITE_URL; ?>/admin/modules/payments/?tab=transactions" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x"></i> Xóa bộ lọc
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Payment Transactions Table -->
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>ID giao dịch</th>
                <th>Đơn hàng</th>
                <th>Cổng</th>
                <th>Số tiền</th>
                <th>Trạng thái</th>
                <th>Thời gian</th>
                <th>Thông điệp</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($transactions): ?>
                <?php foreach ($transactions as $txn): ?>
                <tr>
                    <td>
                        <code class="text-primary"><?= escape(substr($txn['transaction_id'], -20)) ?></code>
                    </td>
                    <td>
                        <?php if ($txn['order_number']): ?>
                            <a href="<?php echo SITE_URL; ?>/admin/modules/orders/view.php?id=<?= (int)$txn['order_id'] ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <?= escape($txn['order_number']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $txn['gateway'] === 'vnpay' ? 'info' : 'success' ?>">
                            <?= strtoupper($txn['gateway']) ?>
                        </span>
                    </td>
                    <td>
                        <strong><?= formatPrice($txn['amount']) ?></strong>
                    </td>
                    <td>
                        <span class="badge bg-<?= $txn['status'] === 'success' ? 'success' : ($txn['status'] === 'pending' ? 'warning' : 'danger') ?>">
                            <?= ucfirst($txn['status']) ?>
                        </span>
                    </td>
                    <td><small><?= formatDate($txn['created_at']) ?></small></td>
                    <td>
                        <small class="text-muted"><?= escape(substr($txn['message'], 0, 50)) ?></small>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="bi bi-inbox"></i> Không có giao dịch nào
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
