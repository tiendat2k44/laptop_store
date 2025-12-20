<?php

class CouponService {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Xác thực và lấy thông tin mã giảm giá
     * @return array|null với discount info hoặc null nếu không hợp lệ
     */
    public function validateCoupon($code, $orderTotal = 0) {
        $code = strtoupper(trim((string)$code));
        if ($code === '') return null;
        
        $coupon = $this->db->queryOne(
            "SELECT * FROM coupons WHERE code = :code AND status = 'active'",
            ['code' => $code]
        );
        
        if (!$coupon) return null;
        
        // Kiểm tra ngày hết hạn
        if (strtotime($coupon['end_date']) < time()) {
            return null;
        }
        if (strtotime($coupon['start_date']) > time()) {
            return null;
        }
        
        // Kiểm tra đã hết lượt sử dụng
        if (!empty($coupon['usage_limit']) && (int)$coupon['used_count'] >= (int)$coupon['usage_limit']) {
            return null;
        }
        
        // Kiểm tra đơn tối thiểu
        if (!empty($coupon['min_order_value']) && (float)$orderTotal < (float)$coupon['min_order_value']) {
            return null;
        }
        
        // Tính toán discount
        $discount = 0;
        if ($coupon['discount_type'] === 'percentage') {
            $discount = ($orderTotal * (float)$coupon['discount_value']) / 100;
            if (!empty($coupon['max_discount'])) {
                $discount = min($discount, (float)$coupon['max_discount']);
            }
        } else {
            $discount = (float)$coupon['discount_value'];
        }
        
        $discount = max(0, min($discount, $orderTotal)); // Không vượt quá tổng đơn
        
        return [
            'id' => (int)$coupon['id'],
            'code' => $coupon['code'],
            'discount' => $discount,
            'description' => $coupon['description']
        ];
    }
    
    /**
     * Tăng số lần sử dụng mã
     */
    public function incrementUsage($couponId) {
        return $this->db->execute(
            "UPDATE coupons SET used_count = used_count + 1 WHERE id = :id",
            ['id' => (int)$couponId]
        );
    }
}
