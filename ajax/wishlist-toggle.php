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

$productId = intval($_POST['product_id'] ?? 0);

if ($productId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
}

$db = Database::getInstance();

try {
    // Check if product exists in wishlist
    $existing = $db->queryOne(
        "SELECT * FROM wishlist WHERE user_id = :user_id AND product_id = :product_id",
        ['user_id' => Auth::id(), 'product_id' => $productId]
    );
    
    if ($existing) {
        // Remove from wishlist
        $sql = "DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id";
        $db->execute($sql, ['user_id' => Auth::id(), 'product_id' => $productId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Đã xóa khỏi danh sách yêu thích',
            'added' => false
        ]);
    } else {
        // Add to wishlist
        $sql = "INSERT INTO wishlist (user_id, product_id, created_at) 
                VALUES (:user_id, :product_id, CURRENT_TIMESTAMP)";
        $db->insert($sql, ['user_id' => Auth::id(), 'product_id' => $productId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Đã thêm vào danh sách yêu thích',
            'added' => true
        ]);
    }
    
} catch (Exception $e) {
    error_log("Wishlist toggle error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
}
