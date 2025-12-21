<?php
/**
 * Test Order Creation - Kiểm tra chi tiết việc tạo order
 * Truy cập: /diagnostics/test_order.php
 */
require_once __DIR__ . '/../includes/init.php';

if (!Auth::check()) {
    die('Please login first. <a href="' . SITE_URL . '/login.php">Login</a>');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Order Creation</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .section { margin: 20px 0; padding: 15px; background: #252526; border-left: 4px solid #007acc; }
        .ok { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        h2 { color: #569cd6; }
        pre { background: #1e1e1e; padding: 10px; overflow-x: auto; border: 1px solid #3e3e42; }
        .step { margin: 10px 0; padding: 10px; background: #2d2d30; }
    </style>
</head>
<body>
<h1>🧪 Test Order Creation</h1>

<?php
$db = Database::getInstance();
require_once __DIR__ . '/../includes/services/CartService.php';
require_once __DIR__ . '/../includes/services/OrderService.php';

$cart = new CartService($db, Auth::id());
$items = $cart->getItems();

echo '<div class="section">';
echo '<h2>📦 Current Cart</h2>';
if (empty($items)) {
    echo '<p class="error">❌ Cart is empty! <a href="' . SITE_URL . '/products.php">Add products</a></p>';
    echo '</div></body></html>';
    exit;
}

echo '<p class="ok">✅ Cart has ' . count($items) . ' items</p>';
echo '<ul>';
foreach ($items as $item) {
    echo '<li>' . htmlspecialchars($item['name']) . ' (x' . $item['quantity'] . ') - Stock: ' . $item['stock_quantity'] . '</li>';
}
echo '</ul>';
echo '</div>';

// Test data
$testShipping = [
    'name' => 'Test User',
    'phone' => '0987654321',
    'address' => '123 Test Street',
    'city' => 'Hà Nội',
    'district' => 'Ba Đình',
    'ward' => 'Phường 1',
    'payment_method' => 'COD',
    'notes' => 'Test order from diagnostics'
];

$subtotal = 0;
foreach ($items as $item) {
    $price = getDisplayPrice($item['price'], $item['sale_price']);
    $subtotal += $price * $item['quantity'];
}

$testAmounts = [
    'subtotal' => $subtotal,
    'shipping_fee' => 0,
    'discount_amount' => 0,
    'total_amount' => $subtotal
];

echo '<div class="section">';
echo '<h2>📋 Test Data</h2>';
echo '<pre>' . json_encode($testShipping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
echo '<pre>' . json_encode($testAmounts, JSON_PRETTY_PRINT) . '</pre>';
echo '</div>';

// Actual test
if (isset($_GET['run']) && $_GET['run'] === 'yes') {
    echo '<div class="section">';
    echo '<h2>🚀 Running Order Creation Test...</h2>';
    
    $orderService = new OrderService($db, Auth::id());
    
    try {
        // Enable detailed logging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        echo '<div class="step"><strong>Step 1:</strong> Begin transaction...</div>';
        $db->beginTransaction();
        echo '<div class="step ok">✅ Transaction started</div>';
        
        echo '<div class="step"><strong>Step 2:</strong> Generate order number...</div>';
        $orderNumber = 'LS' . date('YmdHis') . rand(1000, 9999);
        echo '<div class="step ok">✅ Order number: ' . $orderNumber . '</div>';
        
        echo '<div class="step"><strong>Step 3:</strong> Insert order...</div>';
        $driver = $db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        echo '<div class="step">Database driver: ' . $driver . '</div>';
        
        $params = [
            'order_number' => $orderNumber,
            'user_id' => Auth::id(),
            'recipient_name' => $testShipping['name'],
            'recipient_phone' => $testShipping['phone'],
            'shipping_address' => $testShipping['address'],
            'city' => $testShipping['city'],
            'district' => $testShipping['district'],
            'ward' => $testShipping['ward'],
            'subtotal' => $testAmounts['subtotal'],
            'shipping_fee' => $testAmounts['shipping_fee'],
            'discount_amount' => $testAmounts['discount_amount'],
            'total_amount' => $testAmounts['total_amount'],
            'payment_method' => $testShipping['payment_method'],
            'notes' => $testShipping['notes']
        ];
        
        if ($driver === 'pgsql') {
            $orderRow = $db->queryOne(
                "INSERT INTO orders (
                    order_number, user_id,
                    recipient_name, recipient_phone, shipping_address, city, district, ward,
                    subtotal, shipping_fee, discount_amount, total_amount,
                    payment_method, payment_status, status, notes, created_at
                ) VALUES (
                    :order_number, :user_id,
                    :recipient_name, :recipient_phone, :shipping_address, :city, :district, :ward,
                    :subtotal, :shipping_fee, :discount_amount, :total_amount,
                    :payment_method, 'pending', 'pending', :notes, CURRENT_TIMESTAMP
                ) RETURNING id",
                $params
            );
            $orderId = $orderRow['id'] ?? null;
        } else {
            $orderId = $db->insert(
                "INSERT INTO orders (
                    order_number, user_id,
                    recipient_name, recipient_phone, shipping_address, city, district, ward,
                    subtotal, shipping_fee, discount_amount, total_amount,
                    payment_method, payment_status, status, notes, created_at
                ) VALUES (
                    :order_number, :user_id,
                    :recipient_name, :recipient_phone, :shipping_address, :city, :district, :ward,
                    :subtotal, :shipping_fee, :discount_amount, :total_amount,
                    :payment_method, 'pending', 'pending', :notes, NOW()
                )",
                $params
            );
        }
        
        if (!$orderId) {
            throw new Exception('Failed to get order ID');
        }
        
        echo '<div class="step ok">✅ Order inserted with ID: ' . $orderId . '</div>';
        
        echo '<div class="step"><strong>Step 4:</strong> Insert order items...</div>';
        $itemCount = 0;
        foreach ($items as $item) {
            $price = getDisplayPrice($item['price'], $item['sale_price']);
            
            echo '<div class="step">Inserting item: ' . htmlspecialchars($item['name']) . '</div>';
            
            $itemResult = $db->insert(
                "INSERT INTO order_items (
                    order_id, product_id, shop_id, product_name, product_thumbnail,
                    price, quantity, subtotal, status, created_at
                ) VALUES (
                    :order_id, :product_id, :shop_id, :product_name, :product_thumbnail,
                    :price, :quantity, :subtotal, 'pending', CURRENT_TIMESTAMP
                )",
                [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'shop_id' => $item['shop_id'],
                    'product_name' => $item['name'],
                    'product_thumbnail' => $item['main_image'],
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $price * $item['quantity']
                ]
            );
            
            echo '<div class="step ok">✅ Item inserted (result: ' . var_export($itemResult, true) . ')</div>';
            $itemCount++;
            
            // Update stock
            $updateResult = $db->execute(
                "UPDATE products 
                 SET stock_quantity = stock_quantity - :qty, 
                     sold_count = sold_count + :qty 
                 WHERE id = :pid AND stock_quantity >= :qty",
                ['qty' => $item['quantity'], 'pid' => $item['product_id']]
            );
            
            echo '<div class="step ok">✅ Stock updated (affected rows: ' . $updateResult . ')</div>';
        }
        
        echo '<div class="step ok">✅ Total items inserted: ' . $itemCount . '</div>';
        
        echo '<div class="step"><strong>Step 5:</strong> Commit transaction...</div>';
        $db->commit();
        echo '<div class="step ok">✅ Transaction committed successfully!</div>';
        
        echo '<div class="section ok">';
        echo '<h2>✅ SUCCESS!</h2>';
        echo '<p>Order created successfully!</p>';
        echo '<p><strong>Order ID:</strong> ' . $orderId . '</p>';
        echo '<p><strong>Order Number:</strong> ' . $orderNumber . '</p>';
        echo '<p><a href="' . SITE_URL . '/account/order-detail.php?id=' . $orderId . '">View Order</a> | ';
        echo '<a href="' . SITE_URL . '/account/orders.php">All Orders</a></p>';
        echo '</div>';
        
    } catch (Exception $e) {
        $db->rollback();
        echo '<div class="section error">';
        echo '<h2>❌ ERROR!</h2>';
        echo '<p><strong>Error Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Error Code:</strong> ' . $e->getCode() . '</p>';
        echo '<p><strong>File:</strong> ' . $e->getFile() . '</p>';
        echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    }
    
    echo '</div>';
    
} else {
    echo '<div class="section warning">';
    echo '<h2>⚠️ Ready to Test</h2>';
    echo '<p>This will create a REAL order in the database.</p>';
    echo '<p><a href="?run=yes" style="color: #4ec9b0; font-size: 1.2em; font-weight: bold;">▶ Click here to run the test</a></p>';
    echo '</div>';
}
?>

<div class="section">
<h2>🔙 Navigation</h2>
<p><a href="<?= SITE_URL ?>/diagnostics/debug_checkout.php" style="color: #4ec9b0;">← Back to Debug Page</a></p>
<p><a href="<?= SITE_URL ?>/checkout.php" style="color: #4ec9b0;">→ Go to Checkout</a></p>
</div>

</body>
</html>
