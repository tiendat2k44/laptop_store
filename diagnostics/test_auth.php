<?php
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "=== TEST ĐĂNG NHẬP & ĐĂNG XUẤT ===\n\n";

// Test 1: Kiểm tra constants
echo "1. Cấu hình URL:\n";
echo "   SITE_URL = " . SITE_URL . "\n";
echo "   Script name = " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "   Request URI = " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n\n";

// Test 2: Kiểm tra session
echo "2. Session:\n";
echo "   Session started = " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "\n";
echo "   Session ID = " . session_id() . "\n";
echo "   Logged in = " . (Auth::check() ? 'YES (User: ' . Auth::user()['email'] . ')' : 'NO') . "\n\n";

// Test 3: Test đăng nhập
if (!Auth::check()) {
    echo "3. Test đăng nhập với admin@laptopstore.com / 123456:\n";
    $result = Auth::login('admin@laptopstore.com', '123456', false);
    
    if ($result['success']) {
        echo "   ✅ Đăng nhập OK\n";
        echo "   User ID: " . Session::get('user_id') . "\n";
        echo "   User email: " . Session::get('user_email') . "\n";
        echo "   User role: " . Session::get('user_role') . " (" . Session::get('user_role_name') . ")\n\n";
        
        // Test đăng xuất
        echo "4. Test đăng xuất:\n";
        Auth::logout();
        echo "   ✅ Đã đăng xuất\n";
        echo "   Logged in sau logout = " . (Auth::check() ? 'YES' : 'NO') . "\n\n";
    } else {
        echo "   ❌ Đăng nhập FAIL: " . $result['message'] . "\n\n";
    }
} else {
    echo "3. Đã đăng nhập rồi: " . Auth::user()['email'] . "\n";
    echo "   Để test lại, hãy logout trước.\n\n";
}

// Test 4: Kiểm tra redirect paths
echo "5. Đường dẫn redirect:\n";
$paths = [
    'Trang chủ' => '/',
    'Login' => '/login.php',
    'Logout' => '/logout.php',
    'Admin' => '/admin/',
    'Shop' => '/shop/',
    'Products' => '/products.php',
    'Cart' => '/cart.php'
];

foreach ($paths as $name => $path) {
    $fullUrl = SITE_URL . $path;
    echo "   $name: $fullUrl\n";
}

echo "\n=== KẾT THÚC TEST ===\n";
