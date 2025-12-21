# 🚀 QUICK START - SPRINT 3 WORKING SETUP

**Mục tiêu**: Có một hệ thống e-commerce hoàn toàn hoạt động trong **5 phút**

---

## 📝 BƯỚC 1: Kiểm tra Database (1 phút)

### Nếu dùng PostgreSQL:
```bash
# Kiểm tra kết nối
psql -U postgres -d laptop_store -c "SELECT COUNT(*) FROM users;"
```

### Nếu dùng MySQL:
```bash
mysql -u root -p laptop_store -e "SELECT COUNT(*) FROM users;"
```

---

## ⚙️ BƯỚC 2: Cập Nhật Config (1 phút)

**File**: `/includes/config/config.php`

```php
// Dòng 15-17: Database credentials
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');  // PostgreSQL or 3306 for MySQL
define('DB_NAME', 'laptop_store');
define('DB_USER', 'postgres');
define('DB_PASS', '');  // Nhập password của bạn (hoặc trống nếu không có)
```

**QUAN TRỌNG**: Nếu dùng MySQL, cũng cần sửa Database.php:

📁 `/includes/core/Database.php` (dòng 13):
```php
// PostgreSQL:
$dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;

// MySQL:
$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
```

---

## 👤 BƯỚC 3: Tạo Admin User (1 phút)

### Cách 1: SQL Command
```sql
-- Nếu database còn trống, tạo admin đầu tiên
INSERT INTO users (email, password, full_name, phone, is_admin, email_verified, created_at)
VALUES ('admin@test.local', '$2y$10$slYQmyNdGzin15JTwLP5/.v9/j5MfIjZ9QxqnH8.mu4BXbFP34nFm', 'Admin', '0901000000', TRUE, TRUE, NOW());

-- Hoặc update user hiện tại thành admin
UPDATE users SET is_admin = TRUE WHERE email = 'customer1@example.com';
```

**Password Hash trên**: `password123`

### Cách 2: Web UI
1. Truy cập `/register.php`
2. Tạo account bình thường
3. Chạy lệnh SQL update ở trên

---

## ✅ BƯỚC 4: Kiểm tra Hệ Thống (1 phút)

**Truy cập Diagnostic**:
```
http://localhost/TienDat123/laptop_store-main/diagnostics/full_diagnostic.php
```

Các dấu ✅ cần xuất hiện:
- ✅ Database Connection: OK
- ✅ Table Existence: Tất cả
- ✅ Authenticated as: (user name)
- ✅ Current user is admin

---

## 🛒 BƯỚC 5: Test Checkout Flow (1 phút)

### Flow:
1. **Login**: Đăng nhập với admin account
2. **Browse**: Xem sản phẩm → `/products.php`
3. **Add Cart**: Thêm sản phẩm vào giỏ
4. **Checkout**: Thanh toán → `/checkout.php`
   - Chọn payment method: **COD** (không cần credentials)
   - Nhập địa chỉ giao hàng
   - Click "Đặt hàng"
5. **View Orders**: Xem đơn → `/account/orders.php`
   - Đơn hàng phải xuất hiện trong danh sách

### Troubleshoot:
- ❌ Giỏ trống: Thêm sản phẩm trước checkout
- ❌ "Vui lòng đăng nhập": Chưa login, hãy login trước
- ❌ Order không xuất hiện: Xem error logs, chạy diagnostic

---

## 💳 BƯỚC 6: Test Payment (Optional)

**Nếu chỉ dùng COD** → Bỏ qua phần này

**Nếu muốn test MoMo/VNPay**:

### Option A: Test Payment Page (Không cần credentials)
```
http://localhost/TienDat123/laptop_store-main/payment/test-payment.php
```
- Chọn đơn hàng chưa thanh toán
- Chọn "Simulate MoMo Success" hoặc "Simulate VNPay Success"
- Order sẽ được đánh dấu là "Paid"

### Option B: Real Credentials (Nếu có)
1. Đăng ký MoMo Sandbox: https://developers.momo.vn/
2. Update `/includes/config/config.php`:
```php
define('MOMO_PARTNER_CODE', 'your_code_from_momo');
define('MOMO_ACCESS_KEY', 'your_access_key');
define('MOMO_SECRET_KEY', 'your_secret_key');
```
3. Tương tự với VNPay: https://sandbox.vnpayment.vn/

---

## 🔐 BƯỚC 7: Truy cập Admin (Optional)

```
http://localhost/TienDat123/laptop_store-main/admin/
```

Yêu cầu:
- User must have `is_admin = TRUE`
- Xem được:
  - Dashboard (stats, revenue)
  - Recent orders
  - Pending shops
  - Revenue charts

---

## 📊 VERIFICATION CHECKLIST

Sau khi hoàn thành, check lại:

```
☐ Database kết nối được
☐ Admin account được tạo
☐ Diagnostic page all green ✅
☐ Có thể add sản phẩm vào cart
☐ Có thể checkout với COD
☐ Order xuất hiện trong /account/orders.php
☐ Admin dashboard hiển thị được
☐ Có thể xem orders trong admin
```

---

## ⚠️ Gặp lỗi?

### "Kết nối cơ sở dữ liệu thất bại"
```
→ Kiểm tra DB_HOST, DB_USER, DB_PASS trong config
→ Chắc DB server đang chạy (psql/mysql)
→ Kiểm tra database tồn tại: psql -l (PostgreSQL) hoặc SHOW DATABASES; (MySQL)
```

### "Orders không hiển thị"
```
→ Chạy: SELECT * FROM orders;
→ Nếu trống, tạo test order qua checkout
→ Check browser F12 → Network/Console cho errors
```

### "Admin access denied"
```
→ Run: UPDATE users SET is_admin = TRUE WHERE id = 1;
→ Logout & login lại
→ Truy cập /admin/ lại
```

### "Payment form không submit"
```
→ Sử dụng COD (không cần credentials)
→ Hoặc sử dụng test-payment.php
→ Nếu muốn real payment, thêm credentials vào config
```

---

## 📁 Key Files

| File | Purpose |
|------|---------|
| `/includes/config/config.php` | **CẤP HẠNG 1**: Database + Payment config |
| `/includes/core/Database.php` | Database driver selection |
| `/account/orders.php` | User orders list |
| `/checkout.php` | Checkout flow |
| `/admin/index.php` | Admin dashboard |
| `/payment/test-payment.php` | Payment simulator |
| `/diagnostics/full_diagnostic.php` | System check |

---

## 🎯 Next Steps (Optional)

Sau khi all working:
1. **Tùy chỉnh email**: Update MAIL_* trong config
2. **Add real payment**: Setup MoMo/VNPay credentials
3. **Upload hình ảnh**: Tải lên sản phẩm từ admin
4. **Tự động hóa**: Setup cron jobs nếu cần

---

**Estimated Time**: 5 minutes ⏱️

**Support**: Chạy diagnostic page nếu gặp vấn đề
