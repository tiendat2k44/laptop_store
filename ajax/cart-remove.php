<?php
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Kiểm tra người dùng đã đăng nhập chưa
if (!Auth::check()) {
    jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập'], 401);
}

// Kiểm tra CSRF token
if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
}

// Xóa nhiều items (comma-separated IDs)
if (isset($_POST['item_ids']) && !empty($_POST['item_ids'])) {
    $itemIds = explode(',', $_POST['item_ids']);
    $itemIds = array_map('intval', $itemIds);
    $itemIds = array_filter($itemIds);
    
    if (empty($itemIds)) {
        jsonResponse(['success' => false, 'message' => 'No items to delete']);
    }
    
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $params = array_merge([Auth::id()], $itemIds);
    
    $db = Database::getInstance();
    $result = $db->execute(
        "DELETE FROM cart_items WHERE user_id = ? AND id IN ($placeholders)",
        $params
    );
    
    if ($result !== false) {
        jsonResponse(['success' => true, 'message' => 'Đã xóa ' . count($itemIds) . ' sản phẩm']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Không thể xóa sản phẩm']);
    }
}

// Xóa 1 item (legacy)
$itemId = intval($_POST['item_id'] ?? 0);

if ($itemId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
}

$db = Database::getInstance();

try {
    // Verify item belongs to user and delete
    $sql = "DELETE FROM cart_items WHERE id = :id AND user_id = :user_id";
    $result = $db->execute($sql, ['id' => $itemId, 'user_id' => Auth::id()]);
    
    if ($result) {
        jsonResponse(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Sản phẩm không tồn tại trong giỏ hàng']);
    }
    
} catch (Exception $e) {
    error_log("Remove cart error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
}
