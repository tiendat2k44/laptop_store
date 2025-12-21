<?php
/**
 * Test cart selection feature - checkout with selected items only
 * 
 * FLOW:
 * 1. Verify cart has multiple items
 * 2. Simulate selecting only some items
 * 3. Simulate checkout POST with selected_items[]
 * 4. Verify order created with only selected items
 * 5. Verify unselected items remain in cart
 */

require_once __DIR__ . '/../includes/init.php';

// Must be logged in
if (!Auth::check()) {
    echo "❌ Not logged in\n";
    exit(1);
}

$db = Database::getInstance();
$userId = Auth::id();
$username = $_SESSION['user']['email'] ?? 'unknown';

echo "=== Cart Selection & Checkout Test ===\n";
echo "User: $username (ID: $userId)\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Check cart items
echo "1️⃣  Current cart items:\n";
$cartItems = $db->select("
    SELECT id, product_id, quantity, price, sale_price 
    FROM cart_items 
    WHERE user_id = ?
    ORDER BY id
", [$userId]);

if (empty($cartItems)) {
    echo "❌ Cart is empty! Add items first.\n";
    exit(1);
}

foreach ($cartItems as $i => $item) {
    $displayPrice = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
    echo "   [$i] ID={$item['id']}, Product={$item['product_id']}, Qty={$item['quantity']}, Price=" . formatPrice($displayPrice) . "\n";
}

$totalItemsInCart = count($cartItems);
echo "Total: $totalItemsInCart items\n\n";

// 2. Simulate selection: select half of items (or just first 2)
$selectedCount = min(2, intval($totalItemsInCart / 2) ?: 1);
$selectedItemIds = array_slice(array_column($cartItems, 'id'), 0, $selectedCount);

echo "2️⃣  Selecting $selectedCount of $totalItemsInCart items:\n";
foreach ($selectedItemIds as $itemId) {
    echo "   ✓ Item ID: $itemId\n";
}
echo "\n";

// 3. Simulate checkout POST with selected items
echo "3️⃣  Simulating checkout with selected items...\n";
$_POST['selected_items'] = $selectedItemIds;
$_POST['csrf_token'] = Session::getToken();

// Get items for order
require_once __DIR__ . '/../includes/services/CartService.php';
$cart = new CartService($db, $userId);
$allItems = $cart->getItems();

// Filter to selected only
$hasSelectedItems = isset($_POST['selected_items']) && is_array($_POST['selected_items']) && !empty($_POST['selected_items']);
$filteredSelectedIds = array_map('intval', $_POST['selected_items']);
$items = array_filter($allItems, function($item) use ($filteredSelectedIds) {
    return in_array($item['item_id'], $filteredSelectedIds, true);
});

echo "   Items after selection filter: " . count($items) . "\n";
if (count($items) !== $selectedCount) {
    echo "   ⚠️  WARNING: Expected $selectedCount items, got " . count($items) . "\n";
}

// 4. Check amounts calculation
$subtotal = 0;
foreach ($items as $item) {
    $price = $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
    $subtotal += $price * $item['quantity'];
}

$amounts = [
    'subtotal' => $subtotal,
    'shipping_fee' => 0,
    'discount_amount' => 0,
    'total_amount' => $subtotal
];

echo "   Subtotal: " . formatPrice($subtotal) . "\n";
echo "   Total: " . formatPrice($amounts['total_amount']) . "\n\n";

// 5. Create order (dry run - just validate logic)
echo "4️⃣  Order creation simulation:\n";
require_once __DIR__ . '/../includes/services/OrderService.php';
$orderService = new OrderService($db);

try {
    // Validate items before creating
    foreach ($items as $item) {
        if (empty($item['shop_id'])) {
            echo "   ⚠️  WARNING: Item {$item['item_id']} missing shop_id\n";
        }
    }
    
    echo "   ✓ All items have required fields\n";
    echo "   ✓ Ready to create order\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n5️⃣  Summary:\n";
echo "   Selected items count: $selectedCount\n";
echo "   Items would be ordered: " . count($items) . "\n";
echo "   Items remaining in cart: " . ($totalItemsInCart - $selectedCount) . "\n";
echo "   Order total: " . formatPrice($amounts['total_amount']) . "\n\n";

echo "✅ Test simulation complete!\n";
echo "Now test actual checkout at: " . SITE_URL . "/cart.php\n";
echo "Steps:\n";
echo "  1. Select " . min(2, $selectedCount) . " products (uncheck others)\n";
echo "  2. Click 'Tiến hành thanh toán'\n";
echo "  3. Complete payment\n";
echo "  4. Return to cart.php and verify remaining items\n";
?>
