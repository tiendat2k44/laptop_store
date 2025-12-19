<?php
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "=== QUICK CHECK: PHP & DATABASE ===\n\n";

// PHP info
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Loaded ini: " . (PHP_SAPI === 'cli' ? php_ini_loaded_file() : ini_get('cfg_file_path')) . "\n";
echo "Extensions: pdo=" . (extension_loaded('pdo') ? 'YES' : 'NO') . ", pdo_pgsql=" . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . ", pgsql=" . (extension_loaded('pgsql') ? 'YES' : 'NO') . "\n\n";

// DB config
echo "DB Config -> HOST=" . DB_HOST . ", PORT=" . DB_PORT . ", NAME=" . DB_NAME . ", USER=" . DB_USER . "\n\n";

// Try DB connection
try {
    $db = Database::getInstance();
    echo "DB Connection: OK\n\n";
} catch (Throwable $e) {
    echo "DB Connection: FAILED -> " . $e->getMessage() . "\n";
    exit(1);
}

// Check roles
$roles = $db->query("SELECT id, name FROM roles ORDER BY id");
echo "Roles: " . json_encode($roles) . "\n\n";

// Check admin user
$admin = $db->queryOne("SELECT id, email, role_id, email_verified, status, password_hash FROM users WHERE email = :email", ['email' => 'admin@laptopstore.com']);
if (!$admin) {
    echo "Admin user: NOT FOUND\n";
} else {
    echo "Admin user: FOUND (id=" . $admin['id'] . ", role_id=" . $admin['role_id'] . ", verified=" . ($admin['email_verified'] ? 'YES' : 'NO') . ", status=" . $admin['status'] . ")\n";
    $ok = password_verify('123456', $admin['password_hash'] ?? '');
    echo "Password '123456' verify: " . ($ok ? 'OK' : 'FAIL') . "\n";
}

echo "\n";

// Featured products
$countFeatured = $db->queryOne("SELECT COUNT(*) AS c FROM products p JOIN shops s ON p.shop_id = s.id WHERE p.status='active' AND s.status='active' AND p.featured = true");
$countActive = $db->queryOne("SELECT COUNT(*) AS c FROM products p JOIN shops s ON p.shop_id = s.id WHERE p.status='active' AND s.status='active'");
echo "Products (featured/active): " . ($countFeatured['c'] ?? 0) . " / " . ($countActive['c'] ?? 0) . "\n";

$some = $db->query("SELECT p.id, p.name, p.featured, p.status, s.status AS shop_status FROM products p JOIN shops s ON p.shop_id = s.id ORDER BY p.id DESC LIMIT 5");
echo "Last 5 products: " . json_encode($some) . "\n\n";

// Banners
$bn = $db->queryOne("SELECT COUNT(*) AS c FROM banners WHERE status='active'");
echo "Banners active: " . ($bn['c'] ?? 0) . "\n\n";

echo "=== RECOMMENDATIONS ===\n";
echo "- Nếu Password verify FAIL: chạy lệnh UPDATE mật khẩu admin -> 123456:\n";
echo "  UPDATE users SET password_hash = '\$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', email_verified = TRUE, status = 'active', role_id = 1 WHERE email = 'admin@laptopstore.com';\n\n";

echo "- Nếu Products featured = 0 nhưng active > 0: bật featured cho vài sản phẩm:\n";
echo "  UPDATE products SET featured = TRUE WHERE status='active' LIMIT 8;\n\n";

echo "- Nếu Products active = 0: hãy nạp database/sample_data.sql.\n";
