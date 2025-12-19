<?php
/**
 * Generate Password Hash for Admin
 * Tạo password hash để update vào database
 */

$password = '123456';

// Tạo nhiều hash khác nhau để test
echo "=== TẠO PASSWORD HASH CHO ADMIN ===\n\n";
echo "Password: $password\n\n";

// Tạo 3 hash khác nhau (mỗi lần tạo sẽ có salt khác nhau)
for ($i = 1; $i <= 3; $i++) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    echo "Hash #$i:\n";
    echo "$hash\n";
    
    // Verify ngay
    if (password_verify($password, $hash)) {
        echo "✅ Verify thành công!\n";
    } else {
        echo "❌ Verify thất bại!\n";
    }
    
    echo "\nSQL Command #$i:\n";
    echo "UPDATE users SET password_hash = '$hash' WHERE email = 'admin@laptopstore.com';\n";
    echo "\n" . str_repeat("-", 80) . "\n\n";
}

echo "\n=== HOẶC XÓA VÀ TẠO LẠI ===\n\n";
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "DELETE FROM users WHERE email = 'admin@laptopstore.com';\n\n";
echo "INSERT INTO users (role_id, email, password_hash, full_name, phone, email_verified, status, created_at, updated_at)\n";
echo "VALUES (\n";
echo "    1,\n";
echo "    'admin@laptopstore.com',\n";
echo "    '$hash',\n";
echo "    'System Administrator',\n";
echo "    '0123456789',\n";
echo "    TRUE,\n";
echo "    'active',\n";
echo "    CURRENT_TIMESTAMP,\n";
echo "    CURRENT_TIMESTAMP\n";
echo ");\n";
