<?php
/**
 * File Cấu Hình Hệ Thống
 * Chứa tất cả các hằng số và cấu hình của ứng dụng
 */

// Báo cáo lỗi (Đặt thành 0 khi lên production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình Database
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'laptop_store');
define('DB_USER', 'postgres');
define('DB_PASS', 'your_password_here');

// Cấu hình Website (tự động nhận thư mục triển khai để tránh sai base URL)
define('SITE_NAME', 'Laptop Store');
$defaultSiteUrl = 'http://localhost/laptop_store';
if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
	define('SITE_URL', $defaultSiteUrl);
} else {
	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	$host = $_SERVER['HTTP_HOST'];
	$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
	$scriptDir = $scriptDir === '/' ? '' : $scriptDir;
	define('SITE_URL', $scheme . '://' . $host . $scriptDir);
}
define('SITE_EMAIL', 'support@laptopstore.com');

// Cấu hình Đường dẫn
define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads');
define('UPLOAD_URL', SITE_URL . '/assets/uploads');

// Cấu hình Session
define('SESSION_TIMEOUT', 3600); // 1 giờ tính bằng giây
define('REMEMBER_ME_DURATION', 2592000); // 30 ngày tính bằng giây

// Phân trang
define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 20);

// Cấu hình Email (PHPMailer)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
define('MAIL_FROM_EMAIL', 'noreply@laptopstore.com');
define('MAIL_FROM_NAME', SITE_NAME);

// Cấu hình Cổng Thanh Toán
// MoMo
define('MOMO_PARTNER_CODE', 'your_partner_code');
define('MOMO_ACCESS_KEY', 'your_access_key');
define('MOMO_SECRET_KEY', 'your_secret_key');
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');
define('MOMO_RETURN_URL', SITE_URL . '/payment/momo-return.php');
define('MOMO_IPN_URL', SITE_URL . '/payment/momo-ipn.php');

// VNPay
define('VNPAY_TMN_CODE', 'your_tmn_code');
define('VNPAY_HASH_SECRET', 'your_hash_secret');
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNPAY_RETURN_URL', SITE_URL . '/payment/vnpay-return.php');

// Cấu hình Upload Hình Ảnh
define('MAX_FILE_SIZE', 5242880); // 5MB tính bằng bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);
define('PRODUCT_IMAGE_WIDTH', 800);
define('PRODUCT_IMAGE_HEIGHT', 800);
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 300);

// Bảo mật
define('HASH_ALGORITHM', PASSWORD_BCRYPT);
define('HASH_COST', 10);
define('TOKEN_LENGTH', 64);

// Cấu hình Đơn Hàng
define('ORDER_PREFIX', 'LS'); // Laptop Store
define('DEFAULT_SHIPPING_FEE', 30000); // 30,000 VND

// Hằng số Trạng thái
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');
define('STATUS_LOCKED', 'locked');
define('STATUS_SUSPENDED', 'suspended');

// Trạng thái Đơn Hàng
define('ORDER_STATUS_PENDING', 'pending');
define('ORDER_STATUS_CONFIRMED', 'confirmed');
define('ORDER_STATUS_PROCESSING', 'processing');
define('ORDER_STATUS_SHIPPING', 'shipping');
define('ORDER_STATUS_DELIVERED', 'delivered');
define('ORDER_STATUS_CANCELLED', 'cancelled');

// Trạng thái Thanh Toán
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_PAID', 'paid');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_REFUNDED', 'refunded');

// Vai trò Người dùng
define('ROLE_ADMIN', 1);
define('ROLE_SHOP', 2);
define('ROLE_CUSTOMER', 3);
