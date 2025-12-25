<?php
/**
 * AJAX Handler - Thêm Sản Phẩm Vào Giỏ Hàng
 * Xử lý yêu cầu thêm sản phẩm vào giỏ qua AJAX
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!Auth::check()) {
    jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm vào giỏ hàng'], 401);
}

// Check CSRF token
if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
}

$productId = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

if ($productId <= 0 || $quantity <= 0) {
    jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
}

$db = Database::getInstance();

try {
    // Check if product exists and has stock
    $product = $db->queryOne("SELECT * FROM products WHERE id = :id AND status = 'active'", ['id' => $productId]);
    
    if (!$product) {
        jsonResponse(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
    }
    
    if ($product['stock_quantity'] < $quantity) {
        jsonResponse(['success' => false, 'message' => 'Sản phẩm không đủ số lượng trong kho']);
    }
    
    // Kiểm tra sản phẩm đã có trong giỏ chưa
    $existingItem = $db->queryOne(
        "SELECT * FROM cart_items WHERE user_id = :user_id AND product_id = :product_id",
        ['user_id' => Auth::id(), 'product_id' => $productId]
    );
    
    if ($existingItem) {
        // Cập nhật số lượng nếu sản phẩm đã có trong giỏ
        $newQuantity = $existingItem['quantity'] + $quantity;
        
        if ($product['stock_quantity'] < $newQuantity) {
            jsonResponse(['success' => false, 'message' => 'Vượt quá số lượng có sẵn']);
        }
        
        $sql = "UPDATE cart_items SET quantity = :quantity, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id";
        
        $db->execute($sql, [
            'quantity' => $newQuantity,
            'id' => $existingItem['id']
        ]);
    } else {
        // Add new item
        $sql = "INSERT INTO cart_items (user_id, product_id, quantity, created_at) 
                VALUES (:user_id, :product_id, :quantity, CURRENT_TIMESTAMP)";
        
        $db->insert($sql, [
            'user_id' => Auth::id(),
            'product_id' => $productId,
            'quantity' => $quantity
        ]);
    }
    
    // Get updated cart count
    $cartCount = $db->queryOne(
        "SELECT COUNT(*) as count FROM cart_items WHERE user_id = :user_id",
        ['user_id' => Auth::id()]
    );
    
    jsonResponse([
        'success' => true,
        'message' => 'Đã thêm vào giỏ hàng',
        'cart_count' => $cartCount['count']
    ]);
    
} catch (Exception $e) {
    error_log("Add to cart error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
}
