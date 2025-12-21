<?php
/**
 * Hệ Thống Chẩn Đoán Toàn Diện
 * Kiểm tra: Database, Auth, Orders, Admin, Payment
 */

require_once __DIR__ . '/../includes/init.php';

echo "<pre style='background:#1e1e1e;color:#00ff00;padding:20px;font-family:monospace;white-space:pre-wrap;'>";
echo "=== LAPTOP STORE DIAGNOSTIC SYSTEM ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// 1. CONFIG & DATABASE
echo "📋 1. CONFIGURATION & DATABASE\n";
echo "─────────────────────────────────\n";
echo "SITE_URL: " . SITE_URL . "\n";
echo "DB_HOST: " . DB_HOST . ":" . DB_PORT . "\n";
echo "DB_NAME: " . DB_NAME . "\n";

try {
    $db = Database::getInstance();
    echo "✅ Database Connection: OK\n";
    
    // Check tables
    $tables = ['users', 'products', 'orders', 'order_items', 'cart_items', 'coupons', 'addresses'];
    echo "\nTable Existence:\n";
    foreach ($tables as $t) {
        try {
            $result = $db->queryOne("SELECT 1 FROM $t LIMIT 1");
            echo "  ✅ $t\n";
        } catch (Exception $e) {
            echo "  ❌ $t\n";
        }
    }
    
    // Check orders count
    $orderCount = $db->queryOne("SELECT COUNT(*) as cnt FROM orders")['cnt'] ?? 0;
    echo "\nOrders count: " . $orderCount . "\n";
    
    // Check if orders table has required columns
    echo "\nOrder table columns:\n";
    $columns = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='orders' AND table_schema='public' ORDER BY ordinal_position");
    if ($columns) {
        foreach ($columns as $col) {
            echo "  - " . $col['column_name'] . " (" . $col['data_type'] . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database Connection Failed: " . $e->getMessage() . "\n";
}

// 2. AUTHENTICATION
echo "\n\n📋 2. AUTHENTICATION\n";
echo "─────────────────────────────────\n";
if (Auth::check()) {
    $user = Auth::user();
    echo "✅ Authenticated as: " . $user['full_name'] . " (" . $user['email'] . ")\n";
    echo "   User ID: " . Auth::id() . "\n";
    echo "   Is Admin: " . (Auth::isAdmin() ? 'YES' : 'NO') . "\n";
    
    // Check user's orders
    $orders = $db->query(
        "SELECT id, order_number, status, payment_status, created_at FROM orders WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5",
        ['uid' => Auth::id()]
    );
    echo "\n   Recent Orders (last 5):\n";
    if ($orders) {
        foreach ($orders as $o) {
            echo "     - #" . $o['order_number'] . " (" . $o['status'] . "/" . $o['payment_status'] . ") on " . $o['created_at'] . "\n";
        }
    } else {
        echo "     ⚠️  No orders found\n";
    }
} else {
    echo "⚠️  Not authenticated (view as guest)\n";
}

// 3. ADMIN ACCESS
echo "\n\n📋 3. ADMIN ACCESS\n";
echo "─────────────────────────────────\n";
try {
    // Try to count admin users
    $adminCount = $db->queryOne("SELECT COUNT(*) as cnt FROM users WHERE is_admin = TRUE")['cnt'] ?? 0;
    echo "Admin users in system: " . $adminCount . "\n";
    
    if (Auth::check() && Auth::isAdmin()) {
        echo "✅ Current user is admin\n";
        
        // Try admin queries
        $stats = $db->queryOne("SELECT COUNT(*) as cnt FROM orders");
        echo "   Can query orders: ✅ (" . $stats['cnt'] . " total)\n";
    } else {
        echo "⚠️  Current user is NOT admin - cannot access admin panel\n";
        echo "   To make a user admin, run: UPDATE users SET is_admin = TRUE WHERE id = <user_id>;\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// 4. PAYMENT CONFIGURATION
echo "\n\n📋 4. PAYMENT CONFIGURATION\n";
echo "─────────────────────────────────\n";
echo "MOMO_PARTNER_CODE: " . (defined('MOMO_PARTNER_CODE') ? MOMO_PARTNER_CODE : 'NOT DEFINED') . "\n";
echo "MOMO_ACCESS_KEY: " . (defined('MOMO_ACCESS_KEY') ? MOMO_ACCESS_KEY : 'NOT DEFINED') . "\n";
echo "MOMO_SECRET_KEY: " . (defined('MOMO_SECRET_KEY') ? MOMO_SECRET_KEY : 'NOT DEFINED') . "\n";
echo "VNPAY_TMNCODE: " . (defined('VNPAY_TMNCODE') ? VNPAY_TMNCODE : 'NOT DEFINED') . "\n";
echo "VNPAY_HASHSECRET: " . (defined('VNPAY_HASHSECRET') ? VNPAY_HASHSECRET : 'NOT DEFINED') . "\n";

$hasMoMoConfig = defined('MOMO_PARTNER_CODE') && MOMO_PARTNER_CODE !== 'your_partner_code' && !empty(MOMO_PARTNER_CODE);
$hasVNPayConfig = defined('VNPAY_TMNCODE') && VNPAY_TMNCODE !== 'your_tmn_code' && !empty(VNPAY_TMNCODE);

echo "\nPayment Gateway Status:\n";
echo "  MoMo: " . ($hasMoMoConfig ? "✅ Configured" : "⚠️  Placeholder/Missing") . "\n";
echo "  VNPay: " . ($hasVNPayConfig ? "✅ Configured" : "⚠️  Placeholder/Missing") . "\n";

// 5. FILES & PATHS
echo "\n\n📋 5. FILES & PATHS\n";
echo "─────────────────────────────────\n";
$requiredFiles = [
    'checkout.php',
    'account/orders.php',
    'payment/test-payment.php',
    'payment/vnpay-return.php',
    'payment/momo-return.php',
    'admin/index.php'
];

foreach ($requiredFiles as $f) {
    $path = __DIR__ . '/../' . $f;
    echo ($file_exists($path) ? "✅" : "❌") . " " . $f . "\n";
}

// 6. RECOMMENDATIONS
echo "\n\n📋 6. RECOMMENDATIONS\n";
echo "─────────────────────────────────\n";

if ($orderCount == 0) {
    echo "⚠️  No orders in database!\n";
    echo "   Next step: Create a test order via checkout.php\n";
}

if (!Auth::check()) {
    echo "⚠️  Not logged in\n";
    echo "   Next step: Register or login at /register.php\n";
}

if (!Auth::isAdmin()) {
    echo "⚠️  Not an admin\n";
    echo "   Next step: Register as user, then run:\n";
    echo "             UPDATE users SET is_admin = TRUE WHERE id = <your_id>;\n";
}

if (!$hasMoMoConfig || !$hasVNPayConfig) {
    echo "⚠️  Payment gateways not configured (using placeholders)\n";
    echo "   To test: Use /payment/test-payment.php to simulate payments\n";
    echo "   Or update config with real sandbox credentials\n";
}

echo "\n\n=== END DIAGNOSTIC ===\n";
echo "</pre>";

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<ul>";
echo "<li><a href='" . SITE_URL . "/'>Back to Homepage</a></li>";
echo "<li><a href='" . SITE_URL . "/checkout.php'>Test Checkout</a></li>";
echo "<li><a href='" . SITE_URL . "/account/orders.php'>View My Orders</a></li>";
echo "<li><a href='" . SITE_URL . "/admin/'>Admin Dashboard</a></li>";
if (!Auth::check()) {
    echo "<li><a href='" . SITE_URL . "/login.php'>Login</a></li>";
}
echo "</ul>";
?>
