<?php
/**
 * Initialize application
 * Load all required files and start session
 */

// Load environment variables
require_once __DIR__ . '/core/Env.php';

// Load configuration
require_once __DIR__ . '/config/config.php';

// Load core classes
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/RateLimiter.php';
require_once __DIR__ . '/core/SecurityHeaders.php';

// Tải các hàm trợ giúp
require_once __DIR__ . '/helpers/functions.php';
// Mailer + Email templates (nhẹ, không cần thư viện ngoài)
require_once __DIR__ . '/helpers/mailer.php';
require_once __DIR__ . '/helpers/email_templates.php';
// SEO helper
require_once __DIR__ . '/helpers/seo.php';

// Apply security headers
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
SecurityHeaders::apply($isHttps);

// Start session
Session::start();

// Set error handler for production
if (!ini_get('display_errors')) {
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        error_log("Error [$errno]: $errstr in $errfile on line $errline");
        return true;
    });
}

