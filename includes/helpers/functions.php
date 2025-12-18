<?php
/**
 * Helper Functions
 * Common utility functions used throughout the application
 */

/**
 * Sanitize output to prevent XSS
 * @param string $string
 * @return string
 */
function escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to URL
 * @param string $url
 * @param int $statusCode
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Generate slug from string
 * @param string $string
 * @return string
 */
function generateSlug($string) {
    $string = mb_strtolower($string, 'UTF-8');
    
    // Vietnamese character replacement
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
 * Format price to VND currency
 * @param float $price
 * @return string
 */
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' ₫';
}

/**
 * Format date to Vietnamese format
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Generate order number
 * @return string
 */
function generateOrderNumber() {
    return ORDER_PREFIX . date('YmdHis') . rand(100, 999);
}

/**
 * Validate email
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Vietnamese format)
 * @param string $phone
 * @return bool
 */
function validatePhone($phone) {
    $pattern = '/^(0|\+84)(3|5|7|8|9)[0-9]{8}$/';
    return preg_match($pattern, $phone);
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
    $badges = [
        'pending' => '<span class="badge bg-warning">Chờ xác nhận</span>',
        'confirmed' => '<span class="badge bg-info">Đã xác nhận</span>',
        'processing' => '<span class="badge bg-primary">Đang xử lý</span>',
        'shipping' => '<span class="badge bg-primary">Đang giao</span>',
        'delivered' => '<span class="badge bg-success">Đã giao</span>',
        'cancelled' => '<span class="badge bg-danger">Đã hủy</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . escape($status) . '</span>';
}

/**
 * Get payment status badge HTML
 * @param string $status
 * @return string
 */
function getPaymentStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Chờ thanh toán</span>',
        'paid' => '<span class="badge bg-success">Đã thanh toán</span>',
        'failed' => '<span class="badge bg-danger">Thất bại</span>',
        'refunded' => '<span class="badge bg-secondary">Đã hoàn tiền</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . escape($status) . '</span>';
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
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if (strpos($path, 'assets/') === 0) {
        return SITE_URL . '/' . $path;
    }
    // Mặc định: coi như đường dẫn trong uploads
    return UPLOAD_URL . '/' . ltrim($path, '/');
}
