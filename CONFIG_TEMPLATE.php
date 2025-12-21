<?php
/**
 * CONFIG TEMPLATE - LAPTOP STORE
 * 
 * INSTRUCTIONS:
 * 1. Copy this file as config.php in /includes/config/
 * 2. Update YOUR_* placeholders with your actual values
 * 3. Test with /diagnostics/full_diagnostic.php
 * 
 * DATABASE SETUP:
 * - PostgreSQL: psql -U postgres -d laptop_store
 * - MySQL: mysql -u root -p laptop_store
 * 
 * EMAIL SETUP:
 * - Use Gmail App Password (not regular password)
 * - Enable 2FA first: https://myaccount.google.com/
 * 
 * PAYMENT SETUP:
 * - MoMo: https://developers.momo.vn/ (Sandbox)
 * - VNPay: https://sandbox.vnpayment.vn/ (Sandbox)
 */

// ═════════════════════════════════════════════════════════════════════════════════
// 🔧 SYSTEM CONFIG
// ═════════════════════════════════════════════════════════════════════════════════

// Display errors in development (set 0 in production!)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// ═════════════════════════════════════════════════════════════════════════════════
// 🗄️ DATABASE CONFIG
// ═════════════════════════════════════════════════════════════════════════════════

/**
 * PostgreSQL Setup (Default)
 * 
 * Installation:
 *   Linux: sudo apt install postgresql
 *   macOS: brew install postgresql
 *   Windows: Download from postgresql.org
 * 
 * Create Database:
 *   psql -U postgres
 *   CREATE DATABASE laptop_store;
 *   
 * Default user: postgres
 * Default password: (set during installation)
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'laptop_store');
define('DB_USER', 'postgres');
define('DB_PASS', 'YOUR_DATABASE_PASSWORD');  // ← Update this

// Alternative: MySQL Setup
// define('DB_HOST', 'localhost');
// define('DB_PORT', '3306');
// define('DB_NAME', 'laptop_store');
// define('DB_USER', 'root');
// define('DB_PASS', 'YOUR_MYSQL_PASSWORD');

// ═════════════════════════════════════════════════════════════════════════════════
// 🌐 WEBSITE CONFIG
// ═════════════════════════════════════════════════════════════════════════════════

/**
 * Set SITE_URL to your application's base URL
 * 
 * Examples:
 *   - Local XAMPP: http://localhost/TienDat123/laptop_store-main
 *   - Local WAMP: http://localhost/laptop_store
 *   - Local Docker: http://localhost:8000
 *   - Production: https://yourdomain.com
 *   - Production subdomain: https://shop.yourdomain.com
 * 
 * Important: NO trailing slash
 */
define('SITE_NAME', 'Laptop Store');
define('SITE_URL', 'http://localhost/TienDat123/laptop_store-main');  // ← Update this
define('SITE_EMAIL', 'support@laptopstore.com');

// Paths
define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads');
define('UPLOAD_URL', SITE_URL . '/assets/uploads');

// ═════════════════════════════════════════════════════════════════════════════════
// 🔐 SESSION & SECURITY
// ═════════════════════════════════════════════════════════════════════════════════

define('SESSION_TIMEOUT', 3600);           // 1 hour (seconds)
define('REMEMBER_ME_DURATION', 2592000);   // 30 days (seconds)

// ═════════════════════════════════════════════════════════════════════════════════
// 📧 EMAIL CONFIG (PHPMailer/SMTP)
// ═════════════════════════════════════════════════════════════════════════════════

/**
 * Setup Instructions:
 * 
 * 1. CREATE GMAIL APP PASSWORD:
 *    - Go to: https://myaccount.google.com/
 *    - Enable 2-Factor Authentication first
 *    - Go to Security → App passwords
 *    - Generate password for "Mail"
 *    - Use the 16-character password below
 * 
 * 2. ALTERNATIVE EMAIL SERVICES:
 *    - Mailgun: https://www.mailgun.com/
 *    - SendGrid: https://sendgrid.com/
 *    - AWS SES: https://aws.amazon.com/ses/
 * 
 * 3. DISABLE EMAIL (for testing):
 *    - Set MAIL_HOST = '' to skip email sending
 */

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'YOUR_GMAIL@gmail.com');           // ← Update this
define('MAIL_PASSWORD', 'YOUR_16_CHAR_APP_PASSWORD');     // ← Update this
define('MAIL_FROM_EMAIL', 'noreply@laptopstore.com');
define('MAIL_FROM_NAME', SITE_NAME);

// ═════════════════════════════════════════════════════════════════════════════════
// 💳 PAYMENT GATEWAY CONFIG
// ═════════════════════════════════════════════════════════════════════════════════

/**
 * PAYMENT METHOD 1: COD (Cash on Delivery)
 * - No setup required
 * - Enabled by default
 * - Status: Ready to use immediately
 */

/**
 * PAYMENT METHOD 2: MoMo Wallet
 * 
 * Setup Instructions:
 * 1. Register at: https://developers.momo.vn/
 * 2. Create Application
 * 3. Get Sandbox Credentials:
 *    - Partner Code: MoMo_xxx
 *    - Access Key: xxx
 *    - Secret Key: xxx
 * 4. Copy credentials below
 * 5. Test at: /payment/test-payment.php
 * 
 * Current Status: Placeholder (can use test-payment.php for testing)
 */

define('MOMO_PARTNER_CODE', 'your_partner_code');
define('MOMO_ACCESS_KEY', 'your_access_key');
define('MOMO_SECRET_KEY', 'your_secret_key');
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');
define('MOMO_RETURN_URL', SITE_URL . '/payment/momo-return.php');
define('MOMO_IPN_URL', SITE_URL . '/payment/momo-ipn.php');

/**
 * PAYMENT METHOD 3: VNPay
 * 
 * Setup Instructions:
 * 1. Register at: https://sandbox.vnpayment.vn/
 * 2. Get Sandbox Credentials:
 *    - TMN Code: xxxx
 *    - Hash Secret: xxxxxxxxxxxx
 * 3. Copy credentials below
 * 4. Test at: /payment/test-payment.php
 * 
 * Current Status: Placeholder (can use test-payment.php for testing)
 */

define('VNPAY_TMN_CODE', 'your_tmn_code');
define('VNPAY_HASH_SECRET', 'your_hash_secret');
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNPAY_RETURN_URL', SITE_URL . '/payment/vnpay-return.php');

// ═════════════════════════════════════════════════════════════════════════════════
// 📎 FILE UPLOAD CONFIG
// ═════════════════════════════════════════════════════════════════════════════════

define('MAX_FILE_SIZE', 5242880);                    // 5MB
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/jpg',
    'image/webp'
]);
define('PRODUCT_IMAGE_WIDTH', 800);
define('PRODUCT_IMAGE_HEIGHT', 800);
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 300);

// ═════════════════════════════════════════════════════════════════════════════════
// 🔑 SECURITY & HASHING
// ═════════════════════════════════════════════════════════════════════════════════

define('HASH_ALGORITHM', PASSWORD_BCRYPT);
define('HASH_COST', 10);
define('TOKEN_LENGTH', 64);

// ═════════════════════════════════════════════════════════════════════════════════
// 📦 ORDERS & SHIPPING
// ═════════════════════════════════════════════════════════════════════════════════

define('ORDER_PREFIX', 'LS');           // Laptop Store
define('DEFAULT_SHIPPING_FEE', 30000);  // 30,000 VND

// ═════════════════════════════════════════════════════════════════════════════════
// 📋 PAGINATION
// ═════════════════════════════════════════════════════════════════════════════════

define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 20);

// ═════════════════════════════════════════════════════════════════════════════════
// 📌 STATUS CONSTANTS (No need to change)
// ═════════════════════════════════════════════════════════════════════════════════

// General Status
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

?>
