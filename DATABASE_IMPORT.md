# 📦 HƯỚNG DẪN IMPORT DATABASE

## Quick Start

```bash
# 1. Tạo database (nếu chưa có)
createdb laptop_store

# 2. Import schema hoàn chỉnh (bao gồm: tables + payment + settings)
psql -U postgres -d laptop_store -f database/complete_schema.sql

# 3. Import dữ liệu mẫu (15 sản phẩm laptop + hình ảnh)
psql -U postgres -d laptop_store -f database/sample_data.sql
```

## Tệp Database

### ✅ Tệp hiện tại (khuyên dùng)

- **`complete_schema.sql`** ⭐ - Schema hoàn chỉnh bao gồm:
  - Tất cả bảng chính (users, shops, products, orders, v.v.)
  - Bảng thanh toán (payment_config, payment_transactions)
  - Bảng cài đặt (settings)
  - Shop rating columns
  - Triggers & functions
  - Tài khoản admin mặc định

- **`sample_data.sql`** - Dữ liệu mẫu:
  - 3 shop owners + 4 customers
  - 3 shops (Tech World, Laptop Pro, Digital Shop)
  - 20 sản phẩm laptop (Dell, HP, Lenovo, ASUS, Acer, Apple, MSI, Razer)
  - Hình ảnh thực tế từ Unsplash
  - 14 đơn hàng mẫu với reviews

### 📜 Tệp cũ (không cần dùng nữa)

Các tệp sau đã được hợp nhất vào `complete_schema.sql`:
- ~~`schema.sql`~~ → đã hợp nhất
- ~~`payment_tables_postgres.sql`~~ → đã hợp nhất
- ~~`settings_table.sql`~~ → đã hợp nhất
- ~~`add_shop_rating.sql`~~ → đã hợp nhất
- ~~`fix_order_items_shop_id.sql`~~ → không cần (schema mới đã đầy đủ)
- ~~`reset_complete.sql`~~ → thay bằng complete_schema.sql

## Chi tiết Import

### Bước 1: Tạo Database

```bash
# PostgreSQL
createdb laptop_store

# Hoặc dùng psql
psql -U postgres
CREATE DATABASE laptop_store;
\q
```

### Bước 2: Import Schema Hoàn Chỉnh

```bash
psql -U postgres -d laptop_store -f database/complete_schema.sql
```

**Schema bao gồm:**
- 19 bảng chính
- Indexes để tối ưu performance
- Triggers tự động cập nhật `updated_at`
- Triggers tự động cập nhật rating sản phẩm
- Default roles: admin, shop, customer
- Default brands: Dell, HP, Lenovo, ASUS, Acer, Apple, MSI, Razer
- Default categories: Laptop Văn Phòng, Gaming, Đồ Họa, Ultrabook, Workstation
- Payment config cho VNPay, MoMo, EasyPay
- Settings mặc định
- **Admin account**: admin@laptopstore.com / 123456

### Bước 3: Import Dữ Liệu Mẫu

```bash
psql -U postgres -d laptop_store -f database/sample_data.sql
```

**Dữ liệu mẫu bao gồm:**
- 3 shop owners (shop1@laptopstore.com, shop2@, shop3@) - password: 123456
- 4 customers (customer1@example.com đến customer4@) - password: 123456
- 3 shops đã được duyệt (active)
- 20 sản phẩm laptop với specs đầy đủ
- 40+ hình ảnh sản phẩm chất lượng cao
- 14 đơn hàng mẫu (status: confirmed)
- 14 reviews cho các sản phẩm
- 3 banners quảng cáo

## Kiểm tra sau khi Import

```bash
psql -U postgres -d laptop_store
```

```sql
-- Kiểm tra số lượng bản ghi
SELECT 'users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'shops', COUNT(*) FROM shops
UNION ALL
SELECT 'products', COUNT(*) FROM products
UNION ALL
SELECT 'orders', COUNT(*) FROM orders
UNION ALL
SELECT 'reviews', COUNT(*) FROM reviews;

-- Kết quả mong đợi:
-- users: 8 (1 admin + 3 shops + 4 customers)
-- shops: 3
-- products: 20
-- orders: 14
-- reviews: 14

-- Kiểm tra admin
SELECT id, email, full_name, role_id FROM users WHERE email = 'admin@laptopstore.com';
```

## Reset Database

Nếu cần reset và import lại:

```bash
# Xóa database cũ
dropdb laptop_store

# Tạo mới
createdb laptop_store

# Import lại
psql -U postgres -d laptop_store -f database/complete_schema.sql
psql -U postgres -d laptop_store -f database/sample_data.sql
```

## Tài khoản Test

### Admin
- Email: `admin@laptopstore.com`
- Password: `123456`
- URL: http://localhost/laptop_store/admin/

### Shop Owners
- Email: `shop1@laptopstore.com`, `shop2@laptopstore.com`, `shop3@laptopstore.com`
- Password: `123456`
- URL: http://localhost/laptop_store/shop/

### Customers
- Email: `customer1@example.com` đến `customer4@example.com`
- Password: `123456`
- URL: http://localhost/laptop_store/

## Troubleshooting

### Lỗi: "relation already exists"
```bash
# Drop tất cả tables và import lại
psql -U postgres -d laptop_store -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;"
psql -U postgres -d laptop_store -f database/complete_schema.sql
```

### Lỗi: "permission denied"
```bash
# Grant quyền cho user
psql -U postgres -d laptop_store -c "GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO your_username;"
psql -U postgres -d laptop_store -c "GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO your_username;"
```

### Lỗi: "could not connect to server"
```bash
# Kiểm tra PostgreSQL service
sudo systemctl status postgresql
sudo systemctl start postgresql
```

## Lưu ý

1. **Mật khẩu mặc định**: Tất cả tài khoản test đều dùng password `123456` (đã hash với bcrypt)
2. **Shop rating**: Cột `rating` và `total_reviews` trong bảng `shops` đã được thêm
3. **Order items shop_id**: Đã được populate tự động khi tạo đơn hàng
4. **Payment tables**: Đã sẵn sàng cho VNPay, MoMo, EasyPay
5. **Settings**: Có thể tùy chỉnh trong admin panel sau khi đăng nhập

## Sau khi Import

1. Cập nhật `includes/config/config.php` với thông tin database
2. Cấu hình payment credentials trong admin panel hoặc trực tiếp trong bảng `payment_config`
3. Upload hình ảnh thực tế vào `assets/uploads/` nếu muốn thay ảnh Unsplash
4. Đổi mật khẩu admin ngay sau lần đăng nhập đầu tiên

---

**Cần hỗ trợ?** Mở issue trên GitHub hoặc liên hệ: support@laptopstore.com
