<?php
/**
 * Dịch Vụ Giỏ Hàng (CartService)
 * Quản lý giỏ hàng của người dùng - thêm, xóa, cập nhật sản phẩm
 */

class CartService {
    private $db;
    private $userId;
    
    public function __construct($database, $userId) {
        $this->db = $database;
        $this->userId = $userId;
    }
    
    /**
     * Lấy tất cả sản phẩm trong giỏ của người dùng
     */
    public function getItems() {
        return $this->db->query(
            "SELECT ci.id as item_id, ci.quantity, ci.created_at,
                    p.id as product_id, p.name, p.price, p.sale_price, p.stock_quantity, p.shop_id,
                    (SELECT image_url FROM product_images 
                     WHERE product_id = p.id ORDER BY display_order LIMIT 1) AS main_image
             FROM cart_items ci
             JOIN products p ON ci.product_id = p.id
             WHERE ci.user_id = :user_id
             ORDER BY ci.created_at DESC",
            ['user_id' => $this->userId]
        );
    }
    
    /**
     * Tính tổng giá trị giỏ hàng
     */
    public function getTotal() {
        $items = $this->getItems();
        $total = 0;
        
        foreach ($items as $item) {
            $price = getDisplayPrice($item['price'], $item['sale_price']);
            $total += $price * $item['quantity'];
        }
        
        return $total;
    }
    
    /**
     * Lấy số lượng item trong giỏ
     */
    public function getCount() {
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM cart_items WHERE user_id = :user_id",
            ['user_id' => $this->userId]
        );
        return $result['count'] ?? 0;
    }
    
    /**
     * Xóa toàn bộ giỏ hàng (sau khi đặt hàng)
     */
    public function clear() {
        $this->db->execute(
            "DELETE FROM cart_items WHERE user_id = :user_id",
            ['user_id' => $this->userId]
        );
    }
    
    /**
     * Xóa các sản phẩm đã chọn (sau khi thanh toán một phần)
     * @param array $itemIds Danh sách item_id cần xóa
     */
    public function clearSelectedItems($itemIds) {
        if (empty($itemIds)) {
            return;
        }
        
        // Tạo placeholders cho câu query (?, ?, ?...)
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $params = array_merge([$this->userId], $itemIds);
        
        $this->db->execute(
            "DELETE FROM cart_items WHERE user_id = ? AND id IN ($placeholders)",
            $params
        );
    }
}

?>
