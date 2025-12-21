<?php
/**
 * Debug Checkout - Kiểm tra chi tiết hệ thống checkout
 * Truy cập: /diagnostics/debug_checkout.php
 */
require_once __DIR__ . '/../includes/init.php';

// Chỉ cho phép admin hoặc khi đang dev
if (!Auth::check() || (!Auth::isAdmin() && !defined('DEBUG_MODE'))) {
    die('Access denied. Login as admin or enable DEBUG_MODE in config.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .section { margin: 20px 0; padding: 15px; background: #252526; border-left: 4px solid #007acc; }
        .ok { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        h2 { color: #569cd6; margin-top: 30px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #3e3e42; padding: 8px; text-align: left; }
        th { background: #2d2d30; }
        pre { background: #1e1e1e; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
<h1>🔍 Checkout System Debug</h1>

<?php
$db = Database::getInstance();

echo '<div class="section">';
echo '<h2>1️⃣ Database Connection</h2>';
try {
    $testQuery = $db->query("SELECT 1 as test");
    if ($testQuery) {
        echo '<p class="ok">✅ Database connection: OK</p>';
    }
} catch (Exception $e) {
    echo '<p class="error">❌ Database connection: FAILED - ' . $e->getMessage() . '</p>';
}
echo '</div>';

echo '<div class="section">';
echo '<h2>2️⃣ Required Tables</h2>';
$requiredTables = ['users', 'products', 'cart_items', 'orders', 'order_items'];
foreach ($requiredTables as $table) {
    try {
        $exists = $db->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '$table')");
        if ($exists && $exists[0]['exists']) {
            $count = $db->queryOne("SELECT COUNT(*) as cnt FROM $table");
            echo '<p class="ok">✅ Table `' . $table . '`: OK (' . $count['cnt'] . ' rows)</p>';
        } else {
            echo '<p class="error">❌ Table `' . $table . '`: NOT FOUND</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Table `' . $table . '`: ERROR - ' . $e->getMessage() . '</p>';
    }
}
echo '</div>';

echo '<div class="section">';
echo '<h2>3️⃣ Current User Session</h2>';
if (Auth::check()) {
    echo '<p class="ok">✅ User logged in</p>';
    echo '<table>';
    echo '<tr><th>Key</th><th>Value</th></tr>';
    echo '<tr><td>User ID</td><td>' . Auth::id() . '</td></tr>';
    $user = Auth::user();
    if ($user) {
        echo '<tr><td>Email</td><td>' . htmlspecialchars($user['email']) . '</td></tr>';
        echo '<tr><td>Name</td><td>' . htmlspecialchars($user['full_name']) . '</td></tr>';
        echo '<tr><td>Role</td><td>' . htmlspecialchars($user['role_name']) . '</td></tr>';
    }
    echo '</table>';
} else {
    echo '<p class="error">❌ User NOT logged in</p>';
}
echo '</div>';

echo '<div class="section">';
echo '<h2>4️⃣ Cart Status</h2>';
if (Auth::check()) {
    require_once __DIR__ . '/../includes/services/CartService.php';
    $cart = new CartService($db, Auth::id());
    $items = $cart->getItems();
    echo '<p>Cart items count: <strong>' . count($items) . '</strong></p>';
    if (!empty($items)) {
        echo '<table>';
        echo '<tr><th>Product</th><th>Qty</th><th>Price</th><th>Stock</th></tr>';
        foreach ($items as $item) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($item['name']) . '</td>';
            echo '<td>' . $item['quantity'] . '</td>';
            echo '<td>' . number_format($item['price']) . 'đ</td>';
            echo '<td>' . ($item['stock_quantity'] ?? 0) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="warning">⚠️ Cart is empty</p>';
    }
} else {
    echo '<p class="warning">⚠️ User not logged in - cannot check cart</p>';
}
echo '</div>';

echo '<div class="section">';
echo '<h2>5️⃣ Recent Orders (Last 10)</h2>';
$orders = $db->query("SELECT id, order_number, user_id, status, payment_status, total_amount, created_at 
                      FROM orders 
                      ORDER BY created_at DESC 
                      LIMIT 10");
if ($orders) {
    echo '<table>';
    echo '<tr><th>ID</th><th>Order #</th><th>User ID</th><th>Status</th><th>Payment</th><th>Amount</th><th>Created</th></tr>';
    foreach ($orders as $order) {
        echo '<tr>';
        echo '<td>' . $order['id'] . '</td>';
        echo '<td>' . htmlspecialchars($order['order_number']) . '</td>';
        echo '<td>' . $order['user_id'] . '</td>';
        echo '<td>' . htmlspecialchars($order['status']) . '</td>';
        echo '<td>' . htmlspecialchars($order['payment_status']) . '</td>';
        echo '<td>' . number_format($order['total_amount']) . 'đ</td>';
        echo '<td>' . $order['created_at'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p class="warning">⚠️ No orders found</p>';
}
echo '</div>';

if (Auth::check()) {
    echo '<div class="section">';
    echo '<h2>6️⃣ My Orders (Current User)</h2>';
    $myOrders = $db->query("SELECT id, order_number, status, payment_status, total_amount, created_at 
                            FROM orders 
                            WHERE user_id = :uid
                            ORDER BY created_at DESC 
                            LIMIT 10", 
                            ['uid' => Auth::id()]);
    if ($myOrders && count($myOrders) > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Order #</th><th>Status</th><th>Payment</th><th>Amount</th><th>Created</th></tr>';
        foreach ($myOrders as $order) {
            echo '<tr>';
            echo '<td>' . $order['id'] . '</td>';
            echo '<td>' . htmlspecialchars($order['order_number']) . '</td>';
            echo '<td>' . htmlspecialchars($order['status']) . '</td>';
            echo '<td>' . htmlspecialchars($order['payment_status']) . '</td>';
            echo '<td>' . number_format($order['total_amount']) . 'đ</td>';
            echo '<td>' . $order['created_at'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="warning">⚠️ Current user has no orders</p>';
    }
    echo '</div>';
}

echo '<div class="section">';
echo '<h2>7️⃣ Configuration Check</h2>';
$configs = [
    'SITE_URL' => SITE_URL,
    'DB_HOST' => DB_HOST,
    'DB_NAME' => DB_NAME,
    'DB_USER' => DB_USER,
    'ORDER_PREFIX' => ORDER_PREFIX,
];
echo '<table>';
echo '<tr><th>Config</th><th>Value</th></tr>';
foreach ($configs as $key => $val) {
    echo '<tr><td>' . $key . '</td><td>' . htmlspecialchars($val) . '</td></tr>';
}
echo '</table>';
echo '</div>';

echo '<div class="section">';
echo '<h2>8️⃣ Payment Config Check</h2>';
$paymentConfigs = [
    'VNPAY_TMN_CODE' => defined('VNPAY_TMN_CODE') ? (strpos(VNPAY_TMN_CODE, 'your_') === 0 ? '❌ NOT CONFIGURED' : '✅ Configured') : '❌ Not defined',
    'VNPAY_HASH_SECRET' => defined('VNPAY_HASH_SECRET') ? (strpos(VNPAY_HASH_SECRET, 'your_') === 0 ? '❌ NOT CONFIGURED' : '✅ Configured') : '❌ Not defined',
    'MOMO_PARTNER_CODE' => defined('MOMO_PARTNER_CODE') ? (strpos(MOMO_PARTNER_CODE, 'your_') === 0 ? '❌ NOT CONFIGURED' : '✅ Configured') : '❌ Not defined',
];
echo '<table>';
echo '<tr><th>Payment Gateway</th><th>Status</th></tr>';
foreach ($paymentConfigs as $key => $status) {
    echo '<tr><td>' . $key . '</td><td>' . $status . '</td></tr>';
}
echo '</table>';
echo '</div>';

echo '<div class="section">';
echo '<h2>9️⃣ Test Order Creation (DRY RUN)</h2>';
echo '<p class="warning">⚠️ This is a simulation - no actual order will be created</p>';

if (Auth::check()) {
    require_once __DIR__ . '/../includes/services/CartService.php';
    $cart = new CartService($db, Auth::id());
    $items = $cart->getItems();
    
    if (!empty($items)) {
        echo '<p class="ok">✅ Cart has items - order creation would proceed</p>';
        
        $testShipping = [
            'name' => 'Test User',
            'phone' => '0123456789',
            'address' => '123 Test Street',
            'city' => 'Hà Nội',
            'district' => 'Ba Đình',
            'ward' => 'Phường 1',
            'payment_method' => 'COD',
            'notes' => 'Test order'
        ];
        
        $subtotal = 0;
        foreach ($items as $item) {
            $price = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
            $subtotal += $price * $item['quantity'];
        }
        
        $testAmounts = [
            'subtotal' => $subtotal,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'total_amount' => $subtotal
        ];
        
        echo '<pre>Test shipping data: ' . json_encode($testShipping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        echo '<pre>Test amounts: ' . json_encode($testAmounts, JSON_PRETTY_PRINT) . '</pre>';
        
        // Kiểm tra stock
        $stockOK = true;
        foreach ($items as $it) {
            if ($it['stock_quantity'] < $it['quantity']) {
                echo '<p class="error">❌ Stock issue: ' . htmlspecialchars($it['name']) . ' - need ' . $it['quantity'] . ' but only ' . $it['stock_quantity'] . ' available</p>';
                $stockOK = false;
            }
        }
        
        if ($stockOK) {
            echo '<p class="ok">✅ Stock check: All items available</p>';
            echo '<p class="ok">✅ Order would be created successfully</p>';
        }
        
    } else {
        echo '<p class="error">❌ Cart is empty - cannot create order</p>';
    }
} else {
    echo '<p class="error">❌ User not logged in</p>';
}
echo '</div>';

echo '<div class="section">';
echo '<h2>🔟 Error Logs (Last 50 lines)</h2>';
$logFile = '/tmp/php_errors.log';
if (!file_exists($logFile)) {
    $logFile = ini_get('error_log');
}

if ($logFile && file_exists($logFile)) {
    $logs = shell_exec("tail -n 50 '$logFile' 2>/dev/null");
    if ($logs) {
        echo '<pre style="max-height: 400px; overflow-y: auto;">' . htmlspecialchars($logs) . '</pre>';
    } else {
        echo '<p class="warning">⚠️ Could not read log file</p>';
    }
} else {
    echo '<p class="warning">⚠️ Error log file not found. Check: ' . htmlspecialchars($logFile ?: 'not configured') . '</p>';
}
echo '</div>';

?>

<div class="section">
<h2>🔧 Quick Actions</h2>
<p><a href="<?= SITE_URL ?>/checkout.php" style="color: #4ec9b0;">→ Go to Checkout</a></p>
<p><a href="<?= SITE_URL ?>/account/orders.php?debug=1" style="color: #4ec9b0;">→ Debug Orders Page</a></p>
<p><a href="<?= SITE_URL ?>/cart.php" style="color: #4ec9b0;">→ View Cart</a></p>
<p><a href="<?= SITE_URL ?>/products.php" style="color: #4ec9b0;">→ Browse Products</a></p>
</div>

<p style="margin-top: 40px; color: #858585; font-size: 0.9em;">
    Generated: <?= date('Y-m-d H:i:s') ?> | 
    PHP Version: <?= PHP_VERSION ?> | 
    User Agent: <?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') ?>
</p>

</body>
</html>
