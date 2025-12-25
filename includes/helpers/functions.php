<?php
/**
 * Hàm Tiện Ích (Helper Functions)
 * Các hàm dùng chung trong toàn bộ ứng dụng
 */

/**
 * Làm sạch dữ liệu đầu ra để phòng chống XSS
 * @param string $string Chuỗi cần làm sạch
 * @return string Chuỗi đã được escape
 */
function escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Chuyển hướng đến URL
 * Tự động thêm SITE_URL nếu $url bắt đầu bằng /
 * @param string $url URL cần chuyển hướng
 * @param int $statusCode Mã trạng thái HTTP (mặc định 302)
 */
function redirect($url, $statusCode = 302) {
    // Nếu URL bắt đầu bằng / và không phải là URL đầy đủ, thêm SITE_URL
    if (strpos($url, '/') === 0 && strpos($url, 'http') !== 0) {
        $url = SITE_URL . $url;
    }
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Tạo slug từ chuỗi tiếng Việt (dùng cho URL thân thiện)
 * @param string $string Chuỗi cần chuyển thành slug
 * @return string Slug đã được tạo
 */
function generateSlug($string) {
    $string = mb_strtolower($string, 'UTF-8');
    
    // Chuyển đổi ký tự tiếng Việt có dấu sang không dấu
    $vietnameseMap = [
        'á' => 'a', 'à' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
        'ă' => 'a', 'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
        'â' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
        'đ' => 'd',
        'é' => 'e', 'è' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
        'ê' => 'e', 'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
        'í' => 'i', 'ì' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
        'ô' => 'o', 'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
        'ơ' => 'o', 'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
        'ư' => 'u', 'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
        'ý' => 'y', 'ỳ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
    ];
    
    $string = strtr($string, $vietnameseMap);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    
    return $string;
}

/**
 * Định dạng giá tiền sang định dạng VNĐ
 * @param float $price Giá cần định dạng
 * @return string Giá đã định dạng (VD: 10.000.000 ₫)
 */
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' ₫';
}

/**
 * Định dạng ngày tháng theo kiểu Việt Nam
 * @param string $date Ngày cần định dạng
 * @param string $format Định dạng mong muốn (mặc định: d/m/Y H:i)
 * @return string Ngày đã định dạng
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

// image_url() đã có ở cuối file với logic nâng cao; không định nghĩa lại ở đây

/**
 * Tạo mã đơn hàng tự động
 * @return string Mã đơn hàng (VD: ORD20231224153045789)
 */
function generateOrderNumber() {
    return ORDER_PREFIX . date('YmdHis') . rand(100, 999);
}

/**
 * Kiểm tra tính hợp lệ của email
 * @param string $email Email cần kiểm tra
 * @return bool true nếu hợp lệ, false nếu không
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hàm alias: Kiểm tra email hợp lệ (dùng trong code mới)
 */
function isValidEmail($email) {
    return validateEmail($email);
}

/**
 * Kiểm tra số điện thoại Việt Nam hợp lệ
 * @param string $phone Số điện thoại cần kiểm tra
 * @return bool true nếu hợp lệ (0/+84 + 10 số)
 */
function validatePhone($phone) {
    $pattern = '/^(0|\+84)(3|5|7|8|9)[0-9]{8}$/';
    return preg_match($pattern, $phone);
}

/**
 * Hàm alias: Kiểm tra số điện thoại (10-11 số, bắt đầu 0)
 */
function isValidPhone($phone) {
    return preg_match('/^0\d{9,10}$/', (string)$phone) === 1;
}

/**
 * Lấy giá hiển thị - ưu tiên giá khuyến mại nếu hợp lệ
 * @param float $price Giá gốc
 * @param float|null $salePrice Giá khuyến mại
 * @return float Giá hiển thị cuối cùng
 */
function getDisplayPrice($price, $salePrice = null) {
    if (!empty($salePrice) && (float)$salePrice > 0 && (float)$salePrice < (float)$price) {
        return (float)$salePrice;
    }
    return (float)$price;
}

/**
 * Tính phần trăm giảm giá
 * @param float $originalPrice Giá gốc
 * @param float $salePrice Giá khuyến mại
 * @return int Phần trăm giảm (0-100)
 */
function calculateDiscount($originalPrice, $salePrice) {
    if (empty($salePrice) || $salePrice >= $originalPrice) {
        return 0;
    }
    return max(0, min(100, (int)round((($originalPrice - $salePrice) / $originalPrice) * 100)));
}

/**
 * Get file extension
 * @param string $filename
 * @return string
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Generate unique filename
 * @param string $originalName
 * @return string
 */
function generateUniqueFilename($originalName) {
    $extension = getFileExtension($originalName);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Upload image with resize
 * @param array $file $_FILES array element
 * @param string $folder Folder name inside uploads directory
 * @param int $maxWidth Maximum width
 * @param int $maxHeight Maximum height
 * @return array ['success' => bool, 'filename' => string, 'message' => string]
 */
function uploadImage($file, $folder, $maxWidth = null, $maxHeight = null) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Không có file được tải lên'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Kích thước file vượt quá ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, WEBP)'];
    }
    
    // Create folder if not exists
    $uploadPath = UPLOAD_PATH . '/' . $folder;
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // Generate unique filename
    $filename = generateUniqueFilename($file['name']);
    $filepath = $uploadPath . '/' . $filename;
    
    // Resize image if dimensions provided
    if ($maxWidth && $maxHeight) {
        $result = resizeImage($file['tmp_name'], $filepath, $maxWidth, $maxHeight);
        if (!$result) {
            return ['success' => false, 'message' => 'Không thể xử lý ảnh'];
        }
    } else {
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'message' => 'Không thể lưu file'];
        }
    }
    
    return ['success' => true, 'filename' => $folder . '/' . $filename, 'message' => 'Tải lên thành công'];
}

/**
 * Resize image
 * @param string $source Source file path
 * @param string $destination Destination file path
 * @param int $maxWidth Maximum width
 * @param int $maxHeight Maximum height
 * @return bool
 */
function resizeImage($source, $destination, $maxWidth, $maxHeight) {
    try {
        list($origWidth, $origHeight, $type) = getimagesize($source);
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
        $newWidth = round($origWidth * $ratio);
        $newHeight = round($origHeight * $ratio);
        
        // Create image from source
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($source);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($source);
                break;
            default:
                return false;
        }
        
        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }
        
        // Resize
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        
        // Save resized image
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $destination, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $destination, 9);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($newImage, $destination, 90);
                break;
        }
        
        // Free memory
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        return true;
    } catch (Exception $e) {
        error_log("Image resize error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete file
 * @param string $filename Relative path from uploads directory
 * @return bool
 */
function deleteFile($filename) {
    if (empty($filename)) {
        return false;
    }
    
    $filepath = UPLOAD_PATH . '/' . $filename;
    
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    
    return false;
}

/**
 * Get paginated results
 * @param int $total Total records
 * @param int $page Current page
 * @param int $perPage Items per page
 * @return array ['offset' => int, 'limit' => int, 'total_pages' => int]
 */
function paginate($total, $page = 1, $perPage = ITEMS_PER_PAGE) {
    $page = max(1, intval($page));
    $totalPages = ceil($total / $perPage);
    $page = min($page, $totalPages);
    
    $offset = ($page - 1) * $perPage;
    
    return [
        'offset' => $offset,
        'limit' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_items' => $total
    ];
}

/**
 * Truncate text
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Get order status badge HTML
 * @param string $status
 * @return string
 */
function getOrderStatusBadge($status) {
    $map = getOrderStatusMap();
    if (isset($map[$status])) {
        $cls = $map[$status]['badge'];
        $label = $map[$status]['label'];
        return '<span class="badge bg-' . $cls . '">' . $label . '</span>';
    }
    return '<span class="badge bg-secondary">' . escape($status) . '</span>';
}

/**
 * Get payment status badge HTML
 * @param string $status
 * @return string
 */
function getPaymentStatusBadge($status) {
    $map = getPaymentStatusMap();
    if (isset($map[$status])) {
        $cls = $map[$status]['badge'];
        $label = $map[$status]['label'];
        return '<span class="badge bg-' . $cls . '">' . $label . '</span>';
    }
    return '<span class="badge bg-secondary">' . escape($status) . '</span>';
}

/**
 * Bản đồ trạng thái đơn hàng: nhãn + lớp badge + emoji
 */
function getOrderStatusMap() {
    return [
        'pending' => ['label' => 'Chờ xác nhận', 'badge' => 'warning', 'emoji' => '⏳'],
        'confirmed' => ['label' => 'Đã xác nhận', 'badge' => 'info', 'emoji' => '✓'],
        'processing' => ['label' => 'Đang xử lý', 'badge' => 'primary', 'emoji' => '⚙️'],
        'shipping' => ['label' => 'Đang giao', 'badge' => 'primary', 'emoji' => '🚚'],
        'delivered' => ['label' => 'Đã giao', 'badge' => 'success', 'emoji' => '✅'],
        'cancelled' => ['label' => 'Đã hủy', 'badge' => 'danger', 'emoji' => '❌'],
    ];
}

function getOrderStatusKeys() {
    return array_keys(getOrderStatusMap());
}

function getOrderStatusLabel($status) {
    $map = getOrderStatusMap();
    return $map[$status]['label'] ?? $status;
}

/**
 * Bản đồ trạng thái thanh toán
 */
function getPaymentStatusMap() {
    return [
        'pending' => ['label' => 'Chờ thanh toán', 'badge' => 'warning', 'emoji' => '⏳'],
        'paid' => ['label' => 'Đã thanh toán', 'badge' => 'success', 'emoji' => '💰'],
        'failed' => ['label' => 'Thất bại', 'badge' => 'danger', 'emoji' => '❌'],
        'refunded' => ['label' => 'Đã hoàn tiền', 'badge' => 'secondary', 'emoji' => '↩️'],
    ];
}

function getPaymentStatusKeys() {
    return array_keys(getPaymentStatusMap());
}

function getPaymentStatusLabel($status) {
    $map = getPaymentStatusMap();
    return $map[$status]['label'] ?? $status;
}

/**
 * Send JSON response
 * @param array $data
 * @param int $statusCode
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Check if request is AJAX
 * @return bool
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get current URL
 * @return string
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Time ago format
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Vừa xong';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' phút trước';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' giờ trước';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' ngày trước';
    } else {
        return formatDate($datetime);
    }
}

/**
 * Chuẩn hóa đường dẫn ảnh thành URL đầy đủ
 * Hỗ trợ các trường hợp:
 * - Đã là URL tuyệt đối (http/https)
 * - Đường dẫn bắt đầu bằng assets/...
 * - Đường dẫn tương đối trong uploads: products/..., banners/...
 * - Rỗng: trả về ảnh placeholder
 * @param string $path
 * @return string
 */
function image_url($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return SITE_URL . '/assets/images/no-image.svg';
    }
    // URL tuyệt đối
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    // Trường hợp chỉ định trong assets tĩnh
    if (strpos($path, 'assets/') === 0) {
        $url = SITE_URL . '/' . $path;
        $fs = ROOT_PATH . '/' . $path;
        return file_exists($fs) ? $url : (SITE_URL . '/assets/images/no-image.svg');
    }

    // Chuẩn hóa đường dẫn uploads
    // Hỗ trợ: products/..., banners/..., uploads/...
    $relative = ltrim($path, '/');
    if (strpos($relative, 'uploads/') === 0) {
        $relative = substr($relative, strlen('uploads/'));
    }

    // Xây URL trong thư mục uploads
    $url = UPLOAD_URL . '/' . $relative; // => /assets/uploads/{relative}
    $fs  = UPLOAD_PATH . '/' . $relative; // => {ROOT}/assets/uploads/{relative}

    // Nếu file không tồn tại, trả về ảnh mặc định
    return file_exists($fs) ? $url : (SITE_URL . '/assets/images/no-image.svg');
}
