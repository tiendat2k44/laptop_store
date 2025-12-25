<?php
/**
 * Lớp Xác Thực (Auth)
 * Quản lý đăng nhập, đăng xuất và phân quyền người dùng
 */

class Auth {
    private static $db;
    
    /**
     * Khởi tạo Auth - Kết nối database và session
     */
    private static function init() {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        Session::start();
    }
    
    /**
     * Đăng nhập người dùng
     * @param string $email Email đăng nhập
     * @param string $password Mật khẩu
     * @param bool $remember Ghi nhớ đăng nhập
     * @return array ['success' => bool, 'message' => string, 'user' => array]
     */
    public static function login($email, $password, $remember = false) {
        self::init();
        
        // Kiểm tra dữ liệu đầu vào
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email và mật khẩu không được để trống'];
        }
        
        // Lấy người dùng từ cơ sở dữ liệu
        $sql = "SELECT u.*, r.name as role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.email = :email";
        
        $user = self::$db->queryOne($sql, ['email' => $email]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email hoặc mật khẩu không đúng'];
        }
        
        // Xác thực mật khẩu
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Email hoặc mật khẩu không đúng'];
        }
        
        // Kiểm tra email đã xác thực chưa
        if (!$user['email_verified']) {
            return ['success' => false, 'message' => 'Vui lòng xác thực email trước khi đăng nhập'];
        }
        
        // Kiểm tra trạng thái tài khoản
        if ($user['status'] === 'locked') {
            return ['success' => false, 'message' => 'Tài khoản đã bị khóa'];
        }
        
        if ($user['status'] === 'pending') {
            return ['success' => false, 'message' => 'Tài khoản đang chờ phê duyệt'];
        }
        
        // Cập nhật thời gian đăng nhập cuối
        $updateSql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
        self::$db->execute($updateSql, ['id' => $user['id']]);
        
        // Lưu thông tin vào session
        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('user_email', $user['email']);
        Session::set('user_name', $user['full_name']);
        Session::set('user_role', $user['role_id']);
        Session::set('user_role_name', $user['role_name']);
        Session::set('logged_in', true);
        
        // Xử lý ghi nhớ đăng nhập
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + REMEMBER_ME_DURATION, '/', '', false, true);
            // Lưu token vào database (implement if needed)
        }
        
        // Xóa password khỏi mảng user trả về
        unset($user['password_hash']);
        
        return ['success' => true, 'message' => 'Đăng nhập thành công', 'user' => $user];
    }
    
    /**
     * Đăng xuất người dùng
     */
    public static function logout() {
        self::init();
        
        // Xóa cookie ghi nhớ đăng nhập
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        Session::destroy();
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public static function check() {
        self::init();
        return Session::get('logged_in', false) === true && Session::has('user_id');
    }
    
    /**
     * Get current user ID
     * @return int|null
     */
    public static function id() {
        self::init();
        return Session::get('user_id');
    }
    
    /**
     * Get current user data
     * @return array|null
     */
    public static function user() {
        self::init();
        
        if (!self::check()) {
            return null;
        }
        
        $sql = "SELECT u.*, r.name as role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.id = :id";
        
        $user = self::$db->queryOne($sql, ['id' => self::id()]);
        
        if ($user) {
            unset($user['password_hash']);
        }
        
        return $user;
    }
    
    /**
     * Check if user has specific role
     * @param int|array $roleId Role ID or array of role IDs
     * @return bool
     */
    public static function hasRole($roleId) {
        self::init();
        
        if (!self::check()) {
            return false;
        }
        
        $userRole = Session::get('user_role');
        
        if (is_array($roleId)) {
            return in_array($userRole, $roleId);
        }
        
        return $userRole === $roleId;
    }
    
    /**
     * Check if user is admin
     * @return bool
     */
    public static function isAdmin() {
        return self::hasRole(ROLE_ADMIN);
    }
    
    /**
     * Check if user is shop owner
     * @return bool
     */
    public static function isShop() {
        return self::hasRole(ROLE_SHOP);
    }
    
    /**
     * Check if user is customer
     * @return bool
     */
    public static function isCustomer() {
        return self::hasRole(ROLE_CUSTOMER);
    }
    
    /**
     * Get shop ID for current shop owner
     * @return int|null
     */
    public static function getShopId() {
        self::init();
        
        if (!self::isShop()) {
            return null;
        }
        
        $sql = "SELECT id FROM shops WHERE user_id = :user_id AND status = 'active'";
        $shop = self::$db->queryOne($sql, ['user_id' => self::id()]);
        
        return $shop ? $shop['id'] : null;
    }
    
    /**
     * Require login (redirect if not logged in)
     * @param string $redirectUrl
     */
    public static function requireLogin($redirectUrl = '/login.php') {
        if (!self::check()) {
            Session::setFlash('error', 'Vui lòng đăng nhập để tiếp tục');
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Require specific role (redirect if not authorized)
     * @param int|array $roleId
     * @param string $redirectUrl
     */
    public static function requireRole($roleId, $redirectUrl = '/') {
        self::requireLogin();
        
        if (!self::hasRole($roleId)) {
            Session::setFlash('error', 'Bạn không có quyền truy cập trang này');
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Hash password
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, HASH_ALGORITHM, ['cost' => HASH_COST]);
    }
    
    /**
     * Generate random token
     * @param int $length
     * @return string
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}
