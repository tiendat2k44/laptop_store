<?php
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!Auth::check()) {
    jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập'], 401);
}

// Check CSRF token
if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
}

$itemId = intval($_POST['item_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

if ($itemId <= 0 || $quantity <= 0) {
    jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
}

$db = Database::getInstance();

try {
    // Verify item belongs to user
    $item = $db->queryOne(
        "SELECT ci.*, p.stock_quantity FROM cart_items ci 
         JOIN products p ON ci.product_id = p.id 
         WHERE ci.id = :id AND ci.user_id = :user_id",
        ['id' => $itemId, 'user_id' => Auth::id()]
    );
    
    if (!$item) {
        jsonResponse(['success' => false, 'message' => 'Sản phẩm không tồn tại trong giỏ hàng']);
    }
    
    if ($item['stock_quantity'] < $quantity) {
        jsonResponse(['success' => false, 'message' => 'Vượt quá số lượng có sẵn']);
    }
    
    // Update quantity
    $sql = "UPDATE cart_items SET quantity = :quantity, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
    $db->execute($sql, ['quantity' => $quantity, 'id' => $itemId]);
    
    jsonResponse(['success' => true, 'message' => 'Đã cập nhật giỏ hàng']);
    
} catch (Exception $e) {
    error_log("Update cart error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
}
