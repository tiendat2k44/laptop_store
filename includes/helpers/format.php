<?php
// Các hàm tiện ích chung

// XSS PROTECTION
// Chuyển đổi đầu vào an toàn để hiển thị trên HTML (ngăn chặn XSS)
function escape($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');

// Chuyển hướng trang - tự động thêm SITE_URL cho đường dẫn tương đối
function redirect($url, $status = 302) {
    if (strpos($url, '/') === 0 && strpos($url, 'http') !== 0) {
        $url = SITE_URL . $url;
    }
    header('Location: ' . $url, true, $status);
    exit;
}

// Định dạng giá thành tiền Việt (VND)
function formatPrice($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

/**
 * Định dạng ngày theo chuẩn Việt (d/m/Y H:i)
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    return !$date ? '' : date($format, strtotime($date));
}

/**
 * Định dạng số lượng bán với dấu chấm phân cách
 */
function formatNumber($num) {
    return number_format($num, 0, ',', '.');
}

// Chuyển đổi đường dẫn ảnh thành URL đầy đủ (xử lý cả URL tuyệt đối, assets/, uploads/)
function image_url($path) {
    if (empty($path)) {
        return SITE_URL . '/assets/images/no-image.svg';
    }
    
    // Nếu đã là URL đầy đủ, trả về ngay
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    // Nếu bắt đầu bằng /, thêm SITE_URL vào
    if (strpos($path, '/') === 0) {
        return SITE_URL . $path;
    }
    
    // Nếu là assets/* hoặc uploads/*, thêm SITE_URL vào
    if (strpos($path, 'assets/') === 0 || strpos($path, 'uploads/') === 0) {
        return SITE_URL . '/' . $path;
    }
    
    // Mặc định: coi là path tương đối, thêm SITE_URL
    return SITE_URL . '/' . $path;
}

// Tạo slug từ chuỗi (chuyển về lowercase, bỏ diacritics, thay khoảng trắng bằng -)
function generateSlug($text) {
    // Chuyển thường
    $text = mb_strtolower($text, 'UTF-8');
    
    // Bỏ diacritics tiếng Việt
    $map = [
        'á'=>'a','à'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
        'â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','đ'=>'d','é'=>'e','è'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e',
        'ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e','í'=>'i','ì'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
        'ó'=>'o','ò'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
        'ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o','ú'=>'u','ù'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u',
        'ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u','ý'=>'y','ỳ'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
    ];
    $text = strtr($text, $map);
    
    // Chỉ giữ lại chữ cái, số, dấu gạch ngang
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    
    // Thay khoảng trắng/gạch ngang bằng gạch ngang đơn
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // Bỏ gạch ngang ở đầu/cuối
    return trim($text, '-');
}

// ============ VALIDATION ============
/**
 * Kiểm tra email hợp lệ
 */Kiểm tra email hợp lệ

/**
 * Kiểm tra số điện thoại Việt (10-11 số)
 */
function isValidPhone($phone) {
    return preg_match('/^0\d{9,10}$/', $phone) === 1;
}

// ============ PRICING CALCULATION ============
/**
 * Tính giá bán (nếu có sale_price, lấy sale_price, ngược lại lấy price)
 */Tính giá bán (nếu có sale_price, lấy sale_price, ngược lại lấy price)

/**
 * Tính phần trăm giảm giá
 */
function calculateDiscount($originalPrice, $salePrice) {
    if (empty($salePrice) || $salePrice >= $originalPrice) {
        return 0;
    }
    return max(0, min(100, round((($originalPrice - $salePrice) / $originalPrice) * 100)));
}

// Hằng số
if (!defined('ORDER_PREFIX')) {
    define('ORDER_PREFIX', 'ORD');
}
?>
