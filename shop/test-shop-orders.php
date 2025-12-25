<?php
require_once __DIR__ . '/../includes/init.php';

$db = Database::getInstance();
$shopId = Auth::getShopId();

echo "<h2>Gỡ lỗi Đơn Hàng Shop</h2>";
echo "<p><strong>Shop ID:</strong> " . ($shopId ?? 'NULL') . "</p>";

if (!$shopId) {
    echo "<p style='color: red;'>❌ Không tìm thấy Shop ID!</p>";
    
    // Kiểm tra thông tin người dùng
    $user = Auth::user();
    echo "<pre>Thông tin người dùng:\n";
    print_r($user);
    echo "</pre>";
    
    // Kiểm tra bảng shops
    $shops = $db->query("SELECT * FROM shops WHERE user_id = :user_id", ['user_id' => Auth::id()]);
    echo "<pre>Shops cho người dùng này:\n";
    print_r($shops);
    echo "</pre>";
    exit;
}

// Kiểm tra order_items với shop_id
echo "<h3>Các Mục Order Items với shop_id = $shopId</h3>";
$orderItems = $db->query("SELECT * FROM order_items WHERE shop_id = :shop_id LIMIT 5", ['shop_id' => $shopId]);
echo "<pre>";
print_r($orderItems);
echo "</pre>";

// Kiểm tra các đơn hàng được kết nối với order_items
echo "<h3>Truy vấn Đơn Hàng (giống như index.php)</h3>";
$orders = $db->query(
    "SELECT o.id, o.order_number, o.status, o.payment_status, o.created_at, u.full_name,
            SUM(oi.subtotal) as shop_total
     FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     JOIN users u ON o.user_id = u.id
     WHERE oi.shop_id = :shop_id
     GROUP BY o.id, o.order_number, o.status, o.payment_status, o.created_at, u.full_name
     ORDER BY o.created_at DESC
     LIMIT 10",
    ['shop_id' => $shopId]
);
echo "<pre>";
print_r($orders);
echo "</pre>";

// Kiểm tra xem order_items có shop_id được điền không
echo "<h3>Tất cả Các Mục Order Items (10 đầu tiên)</h3>";
$allItems = $db->query("SELECT oi.*, p.name as product_name, p.shop_id as product_shop_id 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        LIMIT 10");
echo "<pre>";
print_r($allItems);
echo "</pre>";
