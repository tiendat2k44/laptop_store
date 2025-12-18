<?php
/**
 * Configuration File
 * All system constants and configurations
 */

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'laptop_store');
define('DB_USER', 'postgres');
define('DB_PASS', 'your_password_here');

// Site Configuration
define('SITE_NAME', 'Laptop Store');
define('SITE_URL', 'http://localhost');
define('SITE_EMAIL', 'support@laptopstore.com');

// Path Configuration
define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads');
define('UPLOAD_URL', SITE_URL . '/assets/uploads');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('REMEMBER_ME_DURATION', 2592000); // 30 days in seconds

// Pagination
define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 20);

// Email Configuration (PHPMailer)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
define('MAIL_FROM_EMAIL', 'noreply@laptopstore.com');
define('MAIL_FROM_NAME', SITE_NAME);

// Payment Gateway Configuration
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

// Image Upload Configuration
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);
define('PRODUCT_IMAGE_WIDTH', 800);
define('PRODUCT_IMAGE_HEIGHT', 800);
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 300);

// Security
define('HASH_ALGORITHM', PASSWORD_BCRYPT);
define('HASH_COST', 10);
define('TOKEN_LENGTH', 64);

// Order Configuration
define('ORDER_PREFIX', 'LS'); // Laptop Store
define('DEFAULT_SHIPPING_FEE', 30000); // 30,000 VND

// Status Constants
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');
define('STATUS_LOCKED', 'locked');
define('STATUS_SUSPENDED', 'suspended');

// Order Status
define('ORDER_STATUS_PENDING', 'pending');
define('ORDER_STATUS_CONFIRMED', 'confirmed');
define('ORDER_STATUS_PROCESSING', 'processing');
define('ORDER_STATUS_SHIPPING', 'shipping');
define('ORDER_STATUS_DELIVERED', 'delivered');
define('ORDER_STATUS_CANCELLED', 'cancelled');

// Payment Status
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_PAID', 'paid');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_REFUNDED', 'refunded');

// User Roles
define('ROLE_ADMIN', 1);
define('ROLE_SHOP', 2);
define('ROLE_CUSTOMER', 3);
