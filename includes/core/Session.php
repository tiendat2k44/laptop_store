<?php
/**
 * Session Class
 * Secure session management
 */

class Session {
    private static $started = false;
    
    /**
     * Start session with security settings
     */
    public static function start() {
        if (!self::$started && session_status() === PHP_SESSION_NONE) {
            // Security settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
            ini_set('session.cookie_samesite', 'Lax');
            
            session_name('LAPTOP_STORE_SESSION');
            session_start();
            self::$started = true;
            
            // Regenerate session ID periodically for security
            if (!self::has('last_regeneration')) {
                self::regenerate();
            } elseif (time() - self::get('last_regeneration') > 300) {
                self::regenerate();
            }
        }
    }
    
    /**
     * Regenerate session ID
     */
    public static function regenerate() {
        session_regenerate_id(true);
        self::set('last_regeneration', time());
    }
    
    /**
     * Set session variable
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     * @param string $key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session variable exists
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session variable
     * @param string $key
     */
    public static function remove($key) {
        self::start();
        unset($_SESSION[$key]);
    }
    
    /**
     * Destroy all session data
     */
    public static function destroy() {
        self::start();
        $_SESSION = [];
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        self::$started = false;
    }
    
    /**
     * Set flash message
     * @param string $key
     * @param mixed $value
     */
    public static function setFlash($key, $value) {
        self::set('flash_' . $key, $value);
    }
    
    /**
     * Get and remove flash message
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getFlash($key, $default = null) {
        $value = self::get('flash_' . $key, $default);
        self::remove('flash_' . $key);
        return $value;
    }
    
    /**
     * Check if flash message exists
     * @param string $key
     * @return bool
     */
    public static function hasFlash($key) {
        return self::has('flash_' . $key);
    }
    
    /**
     * Generate CSRF token
     * @return string
     */
    public static function generateToken() {
        $token = bin2hex(random_bytes(32));
        self::set('csrf_token', $token);
        return $token;
    }
    
    /**
     * Verify CSRF token
     * @param string $token
     * @return bool
     */
    public static function verifyToken($token) {
        return self::has('csrf_token') && hash_equals(self::get('csrf_token'), $token);
    }
    
    /**
     * Get CSRF token (generate if not exists)
     * @return string
     */
    public static function getToken() {
        if (!self::has('csrf_token')) {
            return self::generateToken();
        }
        return self::get('csrf_token');
    }
}
