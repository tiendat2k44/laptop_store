# INSTALLATION GUIDE - LAPTOP STORE

## Hướng dẫn cài đặt chi tiết

### Bước 1: Chuẩn bị môi trường

#### 1.1. Cài đặt XAMPP (Windows) hoặc LAMP (Linux)

**Windows:**
- Download XAMPP từ https://www.apachefriends.org/
- Cài đặt và khởi động Apache

**Linux:**
```bash
sudo apt update
sudo apt install apache2 php php-pgsql php-gd php-mbstring php-curl
```

#### 1.2. Cài đặt PostgreSQL

**Windows:**
- Download từ https://www.postgresql.org/download/windows/
- Cài đặt và thiết lập password cho user postgres

**Linux:**
```bash
sudo apt install postgresql postgresql-contrib
sudo -u postgres psql
ALTER USER postgres PASSWORD 'your_password';
```

### Bước 2: Tạo database

```bash
# Truy cập PostgreSQL
psql -U postgres

# Tạo database
CREATE DATABASE laptop_store;

# Thoát
\q

# Import schema
psql -U postgres -d laptop_store -f database/schema.sql
```

### Bước 3: Cấu hình project

1. Copy project vào thư mục web root:
   - Windows XAMPP: `C:\xampp\htdocs\laptop_store`
   - Linux: `/var/www/html/laptop_store`

2. Chỉnh sửa `includes/config/config.php`:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'laptop_store');
define('DB_USER', 'postgres');
define('DB_PASS', 'your_password'); // Thay bằng password của bạn

// Site URL
define('SITE_URL', 'http://localhost/laptop_store');
```

3. Cấp quyền thư mục uploads:

```bash
# Linux
chmod -R 755 assets/uploads

# Windows: Chuột phải > Properties > Security > Edit
```

### Bước 4: Cài đặt PHP Extensions

Đảm bảo các extension sau được enable trong `php.ini`:

```ini
extension=pdo_pgsql
extension=pgsql
extension=gd
extension=mbstring
extension=openssl
extension=curl
```

Restart Apache sau khi thay đổi.

### Bước 5: Test kết nối

Tạo file `test_connection.php` trong thư mục gốc:

```php
<?php
require_once 'includes/init.php';

try {
    $db = Database::getInstance();
    echo "✅ Kết nối database thành công!";
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>
```

Truy cập: http://localhost/laptop_store/test_connection.php

### Bước 6: Truy cập ứng dụng

- **Trang chủ**: http://localhost/laptop_store/
- **Admin Panel**: http://localhost/laptop_store/admin/
  - Email: `admin@laptopstore.com`
  - Password: `Admin@123`

### Bước 7: Tạo tài khoản test

1. Đăng ký tài khoản khách hàng từ trang chủ
2. Đăng ký tài khoản shop (cần admin duyệt)
3. Đăng nhập admin để duyệt shop

### Lỗi thường gặp và cách khắc phục

#### Lỗi 1: "Database connection failed"
**Nguyên nhân:** Thông tin kết nối sai hoặc PostgreSQL chưa chạy

**Giải pháp:**
```bash
# Kiểm tra PostgreSQL
sudo service postgresql status

# Khởi động nếu chưa chạy
sudo service postgresql start

# Test kết nối
psql -U postgres -d laptop_store
```

#### Lỗi 2: "Fatal error: Class 'PDO' not found"
**Nguyên nhân:** Extension PDO chưa được cài

**Giải pháp:**
```bash
# Linux
sudo apt install php-pgsql
sudo service apache2 restart

# Windows: Enable trong php.ini
extension=pdo_pgsql
```

#### Lỗi 3: "Permission denied" khi upload ảnh
**Nguyên nhân:** Thư mục uploads không có quyền ghi

**Giải pháp:**
```bash
# Linux
chmod -R 755 assets/uploads
chown -R www-data:www-data assets/uploads

# Windows: Thêm quyền Full Control cho Everyone
```

#### Lỗi 4: "Session start failed"
**Nguyên nhân:** Thư mục session không có quyền ghi

**Giải pháp:**
```bash
# Linux
sudo chmod 1733 /var/lib/php/sessions

# hoặc cấu hình session path riêng trong php.ini
session.save_path = "/path/to/writable/directory"
```

### Cấu hình nâng cao

#### Bật HTTPS (Production)

1. Cài đặt SSL certificate
2. Sửa `.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

3. Cập nhật `config.php`:
```php
define('SITE_URL', 'https://yourdomain.com');
```

#### Cấu hình Email

1. Tạo App Password cho Gmail:
   - Vào Google Account > Security
   - Bật 2-Step Verification
   - Tạo App Password

2. Cập nhật `config.php`:
```php
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
```

#### Tối ưu hiệu năng

1. Bật OPcache trong `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

2. Cấu hình PostgreSQL:
```sql
-- Tăng cache
ALTER SYSTEM SET shared_buffers = '256MB';
ALTER SYSTEM SET effective_cache_size = '1GB';

-- Reload config
SELECT pg_reload_conf();
```

### Backup & Restore

#### Backup database
```bash
pg_dump -U postgres laptop_store > backup_$(date +%Y%m%d).sql
```

#### Restore database
```bash
psql -U postgres laptop_store < backup_20240101.sql
```

### Monitoring

#### Check logs
```bash
# Apache logs
tail -f /var/log/apache2/error.log

# PostgreSQL logs
tail -f /var/log/postgresql/postgresql-12-main.log

# PHP errors (nếu cấu hình)
tail -f /path/to/php-error.log
```

### Support

Nếu gặp vấn đề, vui lòng:
1. Kiểm tra logs để xác định lỗi
2. Tìm kiếm trong README.md và INSTALL.md
3. Liên hệ: support@laptopstore.com
