<?php
/**
 * Test Admin Login
 * Script để kiểm tra đăng nhập admin và password hash
 */

require_once __DIR__ . '/includes/init.php';

echo "=== TEST ĐĂNG NHẬP ADMIN ===\n\n";

// Thông tin đăng nhập
$email = 'admin@laptopstore.com';
$password = '123456';

echo "Email: $email\n";
echo "Password: $password\n\n";

// Lấy thông tin user từ database
$db = Database::getInstance();
$sql = "SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.email = :email";

$user = $db->queryOne($sql, ['email' => $email]);

if (!$user) {
    echo "❌ KHÔNG TÌM THẤY USER VỚI EMAIL: $email\n";
    echo "\nKiểm tra tất cả users trong database:\n";
    $allUsers = $db->query("SELECT id, email, full_name, role_id FROM users");
    foreach ($allUsers as $u) {
        echo "  - ID: {$u['id']}, Email: {$u['email']}, Name: {$u['full_name']}, Role: {$u['role_id']}\n";
    }
    exit;
}

echo "✅ Tìm thấy user:\n";
echo "  - ID: {$user['id']}\n";
echo "  - Email: {$user['email']}\n";
echo "  - Tên: {$user['full_name']}\n";
echo "  - Role: {$user['role_name']} (ID: {$user['role_id']})\n";
echo "  - Status: {$user['status']}\n";
echo "  - Email verified: " . ($user['email_verified'] ? 'Có' : 'Không') . "\n\n";

// Kiểm tra password hash hiện tại
echo "Password Hash trong DB:\n";
echo "{$user['password_hash']}\n\n";

// Test password_verify
echo "Test password_verify với '123456':\n";
if (password_verify($password, $user['password_hash'])) {
    echo "✅ Password ĐÚNG!\n\n";
} else {
    echo "❌ Password SAI!\n\n";
    
    // Tạo hash mới để so sánh
    $newHash = password_hash($password, PASSWORD_BCRYPT);
    echo "Hash mới được tạo:\n";
    echo "$newHash\n\n";
    
    echo "Test với hash mới:\n";
    if (password_verify($password, $newHash)) {
        echo "✅ Hash mới hoạt động!\n\n";
        
        echo "Lệnh SQL để update password:\n";
        echo "UPDATE users SET password_hash = '$newHash' WHERE email = 'admin@laptopstore.com';\n\n";
    }
}

// Test đăng nhập thực tế
echo "=== TEST ĐĂNG NHẬP THỰC TẾ ===\n";
$loginResult = Auth::login($email, $password, false);

if ($loginResult['success']) {
    echo "✅ ĐĂNG NHẬP THÀNH CÔNG!\n";
    echo "Message: {$loginResult['message']}\n";
    
    // Kiểm tra session
    echo "\nThông tin session:\n";
    echo "  - User ID: " . Session::get('user_id') . "\n";
    echo "  - User Email: " . Session::get('user_email') . "\n";
    echo "  - User Name: " . Session::get('user_name') . "\n";
    echo "  - User Role: " . Session::get('user_role') . "\n";
    echo "  - Role Name: " . Session::get('user_role_name') . "\n";
    
    // Đăng xuất
    Auth::logout();
    echo "\n✅ Đã đăng xuất\n";
} else {
    echo "❌ ĐĂNG NHẬP THẤT BẠI!\n";
    echo "Message: {$loginResult['message']}\n";
}

echo "\n=== KẾT THÚC TEST ===\n";
