# Hướng Dẫn Import Database

## 1. Import Schema (Cấu trúc bảng)

```bash
psql -U postgres -d laptop_store -f database/schema.sql
```

Hoặc nếu chưa tạo database:

```bash
# Tạo database
psql -U postgres -c "CREATE DATABASE laptop_store;"

# Import schema
psql -U postgres -d laptop_store -f database/schema.sql
```

## 2. Import Sample Data (Dữ liệu mẫu)

Sau khi import schema thành công, import dữ liệu mẫu:

```bash
psql -U postgres -d laptop_store -f database/sample_data.sql
```

## 3. Kiểm tra Import

Kiểm tra xem dữ liệu đã được import thành công:

```bash
psql -U postgres -d laptop_store
```

Trong psql, chạy các lệnh sau:

```sql
-- Kiểm tra số lượng sản phẩm
SELECT COUNT(*) FROM products;

-- Kiểm tra số lượng hình ảnh
SELECT COUNT(*) FROM product_images;

-- Kiểm tra số lượng banners
SELECT COUNT(*) FROM banners;

-- Xem danh sách sản phẩm
SELECT id, name, brand, price FROM products LIMIT 5;
```

Kết quả mong đợi:
- **Products**: 15 sản phẩm laptop
- **Product Images**: ~25-30 hình ảnh sản phẩm
- **Banners**: 3 banners khuyến mãi

## 4. Cấu Hình Kết Nối Database

Cập nhật thông tin kết nối trong file `includes/config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'laptop_store');
define('DB_USER', 'postgres');
define('DB_PASS', 'your_password_here'); // Thay đổi mật khẩu của bạn
```

## 5. Dữ Liệu Mẫu Bao Gồm

### Sản phẩm (15 laptops):
1. Dell Latitude 5430 - 21.49M VND
2. Dell Inspiron 15 3520 - 13.99M VND
3. HP EliteBook 840 G9 - 25.49M VND
4. HP Victus 15 - 19.49M VND
5. Lenovo X1 Carbon Gen 10 - 33.99M VND
6. Lenovo Legion 5 Pro - 34.49M VND
7. ASUS ROG Strix G15 - 36.99M VND
8. ASUS ZenBook 14 OLED - 20.49M VND
9. Acer Predator Helios 300 - 26.99M VND
10. Acer Aspire 5 - 12.99M VND
11. MacBook Air M2 - 26.49M VND
12. MacBook Pro 14 M2 - 47.99M VND
13. MSI Katana GF66 - 24.49M VND
14. MSI GE76 Raider - 64.99M VND
15. Razer Blade 15 - 49.99M VND

### Hình ảnh:
- 15 hình ảnh sản phẩm chính
- 3 banner khuyến mãi

### Đường dẫn hình ảnh:
- Products: `assets/uploads/products/`
- Banners: `assets/uploads/banners/`

## 6. Lưu Ý Quan Trọng

⚠️ **Hình ảnh đã được tải xuống:**
Tất cả hình ảnh sản phẩm và banner đã được tải xuống vào thư mục `assets/uploads/`. 
File script tải hình: `download_images.sh`

⚠️ **Đường dẫn trong database:**
Đường dẫn hình ảnh trong database đã được cập nhật đúng format: `assets/uploads/products/...`

⚠️ **Cập nhật SITE_URL:**
Đảm bảo SITE_URL trong `includes/config/config.php` khớp với môi trường của bạn:
```php
define('SITE_URL', 'http://localhost/laptop_store');
```

## 7. Xử Lý Lỗi Thường Gặp

### Lỗi: "relation does not exist"
Nguyên nhân: Chưa import schema
Giải pháp: Import file schema.sql trước

### Lỗi: "duplicate key value violates unique constraint"
Nguyên nhân: Đã import dữ liệu mẫu trước đó
Giải pháp: Drop database và tạo lại:
```bash
psql -U postgres -c "DROP DATABASE IF EXISTS laptop_store;"
psql -U postgres -c "CREATE DATABASE laptop_store;"
psql -U postgres -d laptop_store -f database/schema.sql
psql -U postgres -d laptop_store -f database/sample_data.sql
```

### Lỗi: Hình ảnh không hiển thị
Nguyên nhân: Đường dẫn không đúng hoặc thiếu quyền
Giải pháp:
```bash
# Cấp quyền cho thư mục uploads
chmod -R 755 assets/uploads/
```

## 8. Import Toàn Bộ (One-liner)

```bash
psql -U postgres -c "DROP DATABASE IF EXISTS laptop_store;" && \
psql -U postgres -c "CREATE DATABASE laptop_store;" && \
psql -U postgres -d laptop_store -f database/schema.sql && \
psql -U postgres -d laptop_store -f database/sample_data.sql && \
echo "✅ Import hoàn tất!"
```

## 9. Tài Khoản Mặc Định

Sau khi import, bạn có thể tạo tài khoản admin/shop/customer thông qua trang đăng ký hoặc chạy script SQL:

```sql
-- Tạo tài khoản Admin
INSERT INTO users (email, password, full_name, phone, role_id, status) VALUES
('admin@laptopstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', '0901234567', 1, 'active');
-- Password: password

-- Tạo tài khoản Shop
INSERT INTO users (email, password, full_name, phone, role_id, status) VALUES
('shop@laptopstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Shop Owner', '0901234568', 2, 'active');

-- Tạo tài khoản Customer
INSERT INTO users (email, password, full_name, phone, role_id, status) VALUES
('customer@laptopstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer', '0901234569', 3, 'active');
```

**Mật khẩu mặc định cho tất cả tài khoản:** `password`

---

🎉 **Hoàn tất!** Database đã sẵn sàng với dữ liệu mẫu và hình ảnh.
