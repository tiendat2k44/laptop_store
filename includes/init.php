<?php
/**
 * Initialize application
 * Load all required files and start session
 */

// Load configuration
require_once __DIR__ . '/config/config.php';

// Load core classes
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Auth.php';

// Load helper functions
require_once __DIR__ . '/helpers/functions.php';

// Start session
Session::start();

// Set error handler for production
if (!ini_get('display_errors')) {
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        error_log("Error [$errno]: $errstr in $errfile on line $errline");
        return true;
    });
}
