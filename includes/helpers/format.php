<?php
// Các hàm tiện ích chung

// XSS PROTECTION: Chuyển đổi đầu vào an toàn để hiển thị trên HTML (ngăn chặn XSS)
function escape($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

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
    return number_format((float)$amount, 0, ',', '.') . ' ₫';
}

// Định dạng ngày theo chuẩn Việt (d/m/Y H:i)
function formatDate($date, $format = 'd/m/Y H:i') {
    return !$date ? '' : date($format, strtotime($date));
}

// Định dạng số với dấu chấm phân cách
function formatNumber($num) {
    return number_format((int)$num, 0, ',', '.');
}

// Chuyển đổi đường dẫn ảnh thành URL đầy đủ (xử lý cả URL tuyệt đối, assets/, uploads/)
function image_url($path) {
    if (empty($path)) {
        return SITE_URL . '/assets/images/no-image.svg';
    }
    // Đã là URL tuyệt đối
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    // Bắt đầu bằng /
    if (strpos($path, '/') === 0) {
        return SITE_URL . $path;
    }
    // Tiền tố assets/ hoặc uploads/
    if (strpos($path, 'assets/') === 0 || strpos($path, 'uploads/') === 0 || strpos($path, 'products/') === 0 || strpos($path, 'banners/') === 0) {
        return SITE_URL . '/' . $path;
    }
    // Mặc định: path tương đối
    return SITE_URL . '/' . $path;
}

// Tạo slug từ chuỗi (bỏ dấu tiếng Việt, giữ a-z0-9-)
function generateSlug($text) {
    $text = mb_strtolower($text ?? '', 'UTF-8');
    $map = [
        'á'=>'a','à'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
        'â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','đ'=>'d','é'=>'e','è'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e',
        'ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e','í'=>'i','ì'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
        'ó'=>'o','ò'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
        'ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o','ú'=>'u','ù'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u',
        'ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u','ý'=>'y','ỳ'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ============ VALIDATION ============
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Kiểm tra số điện thoại Việt (10-11 số, bắt đầu bằng 0)
function isValidPhone($phone) {
    return preg_match('/^0\d{9,10}$/', (string)$phone) === 1;
}

// ============ PRICING CALCULATION ============
// Lấy giá hiển thị (ưu tiên sale_price nếu hợp lệ)
function getDisplayPrice($price, $salePrice = null) {
    if (!empty($salePrice) && (float)$salePrice > 0 && (float)$salePrice < (float)$price) {
        return (float)$salePrice;
    }
    return (float)$price;
}

// Tính phần trăm giảm giá
function calculateDiscount($originalPrice, $salePrice) {
    if (empty($salePrice) || $salePrice >= $originalPrice) {
        return 0;
    }
    return max(0, min(100, (int)round((($originalPrice - $salePrice) / $originalPrice) * 100)));
}

// Hằng số
if (!defined('ORDER_PREFIX')) {
    define('ORDER_PREFIX', 'ORD');
}

