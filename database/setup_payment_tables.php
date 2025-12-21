<?php
/**
 * Setup Payment Tables
 * Tự động tạo bảng payment_transactions và payment_config
 */

require_once __DIR__ . '/../includes/init.php';

// Chỉ admin mới được chạy
if (!Auth::isAdmin()) {
    die('Access denied. Admin only.');
}

$db = Database::getInstance();
$results = [];

// Kiểm tra xem bảng đã tồn tại chưa
try {
    $exists = $db->queryOne("
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'payment_transactions'
        ) as exists
    ");
    
    if ($exists['exists']) {
        $results[] = ['status' => 'info', 'message' => 'Bảng payment_transactions đã tồn tại'];
    } else {
        // Tạo bảng payment_transactions
        $db->execute("
            CREATE TABLE IF NOT EXISTS payment_transactions (
              id SERIAL PRIMARY KEY,
              order_id INTEGER NOT NULL,
              gateway VARCHAR(20) NOT NULL,
              status VARCHAR(20) NOT NULL,
              transaction_id VARCHAR(255) NOT NULL,
              amount DECIMAL(12, 2) NOT NULL,
              message TEXT,
              ip_address VARCHAR(50),
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            )
        ");
        
        // Tạo indexes
        $db->execute("CREATE INDEX IF NOT EXISTS idx_payment_txn_order ON payment_transactions(order_id)");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_payment_txn_gateway ON payment_transactions(gateway)");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_payment_txn_status ON payment_transactions(status)");
        $db->execute("CREATE INDEX IF NOT EXISTS idx_payment_txn_created_at ON payment_transactions(created_at)");
        
        $results[] = ['status' => 'success', 'message' => 'Đã tạo bảng payment_transactions thành công'];
    }
} catch (Exception $e) {
    $results[] = ['status' => 'error', 'message' => 'Lỗi payment_transactions: ' . $e->getMessage()];
}

// Kiểm tra bảng payment_config
try {
    $exists = $db->queryOne("
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'payment_config'
        ) as exists
    ");
    
    if ($exists['exists']) {
        $results[] = ['status' => 'info', 'message' => 'Bảng payment_config đã tồn tại'];
    } else {
        // Tạo bảng payment_config
        $db->execute("
            CREATE TABLE IF NOT EXISTS payment_config (
              id SERIAL PRIMARY KEY,
              config_name VARCHAR(100) NOT NULL,
              config_key VARCHAR(100) NOT NULL UNIQUE,
              config_value TEXT NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $db->execute("CREATE INDEX IF NOT EXISTS idx_payment_config_key ON payment_config(config_key)");
        
        // Insert dữ liệu mẫu
        $db->execute("
            INSERT INTO payment_config (config_name, config_key, config_value) VALUES
            ('VNPay TMN Code', 'VNPAY_TMN_CODE', 'your_tmn_code_here'),
            ('VNPay Hash Secret', 'VNPAY_HASH_SECRET', 'your_hash_secret_here'),
            ('MoMo Partner Code', 'MOMO_PARTNER_CODE', 'your_partner_code_here'),
            ('MoMo Access Key', 'MOMO_ACCESS_KEY', 'your_access_key_here'),
            ('MoMo Secret Key', 'MOMO_SECRET_KEY', 'your_secret_key_here')
            ON CONFLICT (config_key) DO NOTHING
        ");
        
        $results[] = ['status' => 'success', 'message' => 'Đã tạo bảng payment_config thành công'];
    }
} catch (Exception $e) {
    $results[] = ['status' => 'error', 'message' => 'Lỗi payment_config: ' . $e->getMessage()];
}

// Hiển thị kết quả
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Payment Tables</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="bi bi-database-check"></i> Setup Payment Tables</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($results as $result): ?>
                        <?php
                        $alertClass = $result['status'] === 'success' ? 'success' : 
                                     ($result['status'] === 'error' ? 'danger' : 'info');
                        $icon = $result['status'] === 'success' ? 'check-circle' : 
                               ($result['status'] === 'error' ? 'x-circle' : 'info-circle');
                        ?>
                        <div class="alert alert-<?= $alertClass ?>">
                            <i class="bi bi-<?= $icon ?>-fill"></i> <?= escape($result['message']) ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-4">
                        <h5>Kiểm tra kết quả:</h5>
                        <?php
                        try {
                            $count = $db->queryOne("SELECT COUNT(*) as count FROM payment_transactions")['count'];
                            echo '<p>✅ Bảng <code>payment_transactions</code> có ' . $count . ' record(s)</p>';
                            
                            $count = $db->queryOne("SELECT COUNT(*) as count FROM payment_config")['count'];
                            echo '<p>✅ Bảng <code>payment_config</code> có ' . $count . ' record(s)</p>';
                        } catch (Exception $e) {
                            echo '<p class="text-danger">❌ Lỗi: ' . escape($e->getMessage()) . '</p>';
                        }
                        ?>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?= SITE_URL ?>/payment/test-payment.php" class="btn btn-primary">
                        <i class="bi bi-play-circle"></i> Test Payment
                    </a>
                    <a href="<?= SITE_URL ?>/admin/modules/payments/" class="btn btn-secondary">
                        <i class="bi bi-gear"></i> Payment Config
                    </a>
                    <a href="<?= SITE_URL ?>/checkout.php" class="btn btn-success">
                        <i class="bi bi-cart"></i> Go to Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
