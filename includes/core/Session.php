<?php
/**
 * Lớp Quản Lý Session
 * Quản lý phiên làm việc với các thiết lập bảo mật
 */

class Session {
    private static $started = false;
    
    /**
     * Khởi động session với các thiết lập bảo mật
     */
    public static function start() {
        if (!self::$started && session_status() === PHP_SESSION_NONE) {
            // Thiết lập bảo mật cho session
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Đặt thành 1 nếu dùng HTTPS
            ini_set('session.cookie_samesite', 'Lax');
            
            session_name('LAPTOP_STORE_SESSION');
            session_start();
            self::$started = true;
            
            // Tái tạo session ID định kỳ để bảo mật
            if (!self::has('last_regeneration')) {
                self::regenerate();
            } elseif (time() - self::get('last_regeneration') > 300) {
                self::regenerate();
            }
        }
    }
    
    /**
     * Tái tạo session ID mới (bảo mật)
     */
    public static function regenerate() {
        session_regenerate_id(true);
        self::set('last_regeneration', time());
    }
    
    /**
     * Lưu biến vào session
     * @param string $key Tên biến
     * @param mixed $value Giá trị
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Lấy giá trị biến từ session
     * @param string $key Tên biến
     * @param mixed $default Giá trị mặc định nếu không tồn tại
     * @return mixed
     */
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Kiểm tra biến có tồn tại trong session không
     * @param string $key Tên biến
     * @return bool
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Xóa biến khỏi session
     * @param string $key Tên biến
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
