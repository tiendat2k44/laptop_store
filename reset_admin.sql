-- =============================================
-- SCRIPT TEST VÀ RESET ADMIN PASSWORD
-- Email: admin@laptopstore.com
-- Password: 123456
-- =============================================

-- Kiểm tra user admin hiện tại
SELECT 
    id, 
    email, 
    full_name, 
    role_id,
    email_verified,
    status,
    password_hash,
    created_at
FROM users 
WHERE email = 'admin@laptopstore.com';

-- Xóa user admin cũ (nếu có)
DELETE FROM users WHERE email = 'admin@laptopstore.com';

-- Tạo lại user admin với mật khẩu 123456
-- Hash được tạo bằng: password_hash('123456', PASSWORD_BCRYPT)
INSERT INTO users (
    role_id, 
    email, 
    password_hash, 
    full_name, 
    phone,
    email_verified, 
    status,
    created_at,
    updated_at
) 
VALUES (
    1,  -- role_id = 1 là ADMIN
    'admin@laptopstore.com', 
    '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',  -- password: 123456
    'System Administrator',
    '0123456789',
    TRUE,  -- email đã được xác thực
    'active',  -- trạng thái active
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
);

-- Kiểm tra lại sau khi tạo
SELECT 
    id, 
    email, 
    full_name, 
    role_id,
    email_verified,
    status,
    LENGTH(password_hash) as hash_length,
    created_at
FROM users 
WHERE email = 'admin@laptopstore.com';

-- Kiểm tra tất cả roles để đảm bảo role_id = 1 tồn tại
SELECT * FROM roles ORDER BY id;
