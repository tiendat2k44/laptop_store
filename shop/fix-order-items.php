<?php
/**
 * Sửa lỗi shop_id bị thiếu trong bảng order_items
 * Chạy một lần để điền shop_id từ bảng products
 */

require_once __DIR__ . '/../includes/init.php';

// Yêu cầu admin trên web, cho phép CLI để bảo trì
$isCli = PHP_SAPI === 'cli';
if (!$isCli && (!Auth::check() || !Auth::isAdmin())) {
    die('Access denied. Admin only.');
}

$db = Database::getInstance();

echo "<h2>Sửa Order Items - Chuyển đổi Shop ID</h2>";
echo "<hr>";

try {
    // Kiểm tra trạng thái hiện tại
    $stats = $db->queryOne("
        SELECT 
            COUNT(*) as total_items,
            COUNT(CASE WHEN shop_id IS NOT NULL AND shop_id > 0 THEN 1 END) as items_with_shop,
            COUNT(CASE WHEN shop_id IS NULL OR shop_id = 0 THEN 1 END) as items_without_shop
        FROM order_items
    ");
    
    echo "<h3>Trước khi cập nhật:</h3>";
    echo "<ul>";
    echo "<li>Tổng mục order items: <strong>" . $stats['total_items'] . "</strong></li>";
    echo "<li>Mục với shop_id: <strong>" . $stats['items_with_shop'] . "</strong></li>";
    echo "<li>Mục không có shop_id: <strong style='color: red;'>" . $stats['items_without_shop'] . "</strong></li>";
    echo "</ul>";
    
    if ($stats['items_without_shop'] > 0) {
        echo "<p><em>Đang cập nhật order_items...</em></p>";
        
        // Cập nhật shop_id từ products
        $updated = $db->execute("
            UPDATE order_items oi
            SET shop_id = p.shop_id
            FROM products p
            WHERE oi.product_id = p.id
            AND (oi.shop_id IS NULL OR oi.shop_id = 0)
        ");
        
        echo "<p style='color: green;'>✓ Cập nhật thành công!</p>";
        
        // Kiểm tra sau khi cập nhật
        $statsAfter = $db->queryOne("
            SELECT 
                COUNT(*) as total_items,
                COUNT(CASE WHEN shop_id IS NOT NULL AND shop_id > 0 THEN 1 END) as items_with_shop,
                COUNT(CASE WHEN shop_id IS NULL OR shop_id = 0 THEN 1 END) as items_without_shop
            FROM order_items
        ");
        
        echo "<h3>Sau khi cập nhật:</h3>";
        echo "<ul>";
        echo "<li>Tổng mục order items: <strong>" . $statsAfter['total_items'] . "</strong></li>";
        echo "<li>Mục với shop_id: <strong style='color: green;'>" . $statsAfter['items_with_shop'] . "</strong></li>";
        echo "<li>Mục không có shop_id: <strong>" . $statsAfter['items_without_shop'] . "</strong></li>";
        echo "</ul>";
        
        if ($statsAfter['items_without_shop'] > 0) {
            echo "<p style='color: orange;'>⚠ Cảnh báo: Vẫn còn " . $statsAfter['items_without_shop'] . " mục không có shop_id. Các sản phẩm này có thể đã bị xóa.</p>";
            
            // Hiển thị các mục không thể sửa
            $orphaned = $db->query("
                SELECT oi.id, oi.product_id, oi.product_name, oi.order_id
                FROM order_items oi
                WHERE (oi.shop_id IS NULL OR oi.shop_id = 0)
                LIMIT 20
            ");
            
            if ($orphaned) {
                echo "<h4>Các mục mồ côi (sản phẩm đã xóa):</h4>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID Mục</th><th>ID Sản phẩm</th><th>Tên sản phẩm</th><th>ID Đơn hàng</th></tr>";
                foreach ($orphaned as $item) {
                    echo "<tr>";
                    echo "<td>" . $item['id'] . "</td>";
                    echo "<td>" . $item['product_id'] . "</td>";
                    echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
                    echo "<td>" . $item['order_id'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<p><em>Đề xuất: Gán các mục này vào shop_id = 1 (cửa hàng mặc định)</em></p>";
                echo '<form method="post">';
                echo '<input type="hidden" name="fix_orphaned" value="1">';
                echo '<button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">Sửa Các Mục Mồ Côi (Đặt thành Shop 1)</button>';
                echo '</form>';
            }
        }
    } else {
        echo "<p style='color: green;'>✓ Tất cả các mục order items đã có shop_id được gán!</p>";
    }
    
    // Xử lý sửa các mục mồ côi
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_orphaned'])) {
        echo "<hr><h3>Đang sửa các mục mồ côi...</h3>";
        $db->execute("
            UPDATE order_items
            SET shop_id = 1
            WHERE (shop_id IS NULL OR shop_id = 0)
        ");
        echo "<p style='color: green;'>✓ Đã sửa! Gán tất cả các mục mồ côi vào shop_id = 1</p>";
        echo '<p><a href="">Làm mới trang</a></p>';
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='/shop/'>← Quay lại Bảng Điều Khiển Shop</a> | <a href='/admin/'>Bảng Điều Khiển Admin</a></p>";
