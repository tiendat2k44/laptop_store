# 🔧 SPRINT 3 COMPLETE FIX GUIDE

**Status**: Payment không hoạt động + Orders không visible + Admin bị lỗi

## 📋 Tóm Tắt Vấn Đề

Sprint 3 được "hoàn thành" với code cho VNPay/MoMo/XLSX/SEO nhưng **không thể sử dụng được** vì:

1. **Config Placeholder Issues**: MoMo/VNPay credentials là `your_partner_code` → payment form không submit
2. **Orders Not Visible**: Đơn hàng tạo được nhưng không hiển thị trong `account/orders.php` 
3. **Admin Broken**: `admin/index.php` có PostgreSQL-specific queries (date_trunc, INTERVAL)
4. **Database Connection**: Password chưa được cấu hình (`your_password_here`)

---

## 🚀 SOLUTIONS (Chọn theo nhu cầu)

### **Solution A: QUICK FIX (Sử dụng Test Payment Mode)**

Nếu bạn **không có** VNPay/MoMo sandbox credentials, hãy dùng cách này để test end-to-end:

#### Step 1: Update config (tạm thời)
```php
// /includes/config/config.php - Dòng 15-17
define('DB_PASS', '');  // Hoặc password của PostgreSQL của bạn

// Hoặc nếu dùng MySQL:
// define('DB_HOST', 'localhost:3306');
// định nghĩa lại DSN trong Database.php
```

#### Step 2: Test tạo đơn hàng
1. Register new account → `/register.php`
2. Add products to cart → `/products.php`
3. Checkout → `/checkout.php` (chọn COD)
4. Check orders → `/account/orders.php`
5. Test payment simulation → `/payment/test-payment.php`

#### Step 3: Kiểm tra Admin
```sql
-- Chạy lệnh trong PostgreSQL console:
UPDATE users SET is_admin = TRUE WHERE id = 1;
```
Rồi truy cập `/admin/` với user đó.

---

### **Solution B: FULL FIX (Cấu Hình Sandbox Credentials)**

Nếu bạn **muốn** dùng MoMo/VNPay thực:

#### MoMo Sandbox Setup:
1. Đăng ký tại: https://developers.momo.vn/
2. Lấy sandbox credentials:
   - Partner Code
   - Access Key
   - Secret Key
3. Update `/includes/config/config.php`:
```php
define('MOMO_PARTNER_CODE', 'your_real_partner_code');
define('MOMO_ACCESS_KEY', 'your_real_access_key');
define('MOMO_SECRET_KEY', 'your_real_secret_key');
```

#### VNPay Sandbox Setup:
1. Đăng ký tại: https://sandbox.vnpayment.vn/
2. Lấy credentials:
   - TMN Code
   - Hash Secret
3. Update `/includes/config/config.php`:
```php
define('VNPAY_TMN_CODE', 'your_real_tmn_code');
define('VNPAY_HASH_SECRET', 'your_real_hash_secret');
```

---

### **Solution C: DATABASE FIX (Quản Lý Orders & Admin)**

#### Fix 1: Tạo User Admin (Nếu chưa có)
```sql
-- PostgreSQL
INSERT INTO users (email, password, full_name, is_admin, created_at) 
VALUES ('admin@test.com', '<bcrypt_hash>', 'Admin', TRUE, NOW());

-- Hoặc update user hiện tại:
UPDATE users SET is_admin = TRUE WHERE id = 1;
```

#### Fix 2: Kiểm tra Orders Table Structure
```sql
-- Xem structure của orders table
\d orders

-- Nếu bị thiếu cột, thêm:
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_transaction_id VARCHAR(255);
ALTER TABLE orders ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(50) DEFAULT 'pending';
```

#### Fix 3: Verify Orders Được Tạo
```sql
-- Xem tất cả orders
SELECT id, order_number, user_id, status, payment_status, created_at FROM orders;

-- Xem orders của user cụ thể
SELECT * FROM orders WHERE user_id = 1;
```

---

## 📊 VERIFICATION CHECKLIST

Sau khi áp dụng fix, kiểm tra:

### 1. Database Connection
- [ ] Truy cập `/diagnostics/full_diagnostic.php`
- [ ] Database Connection: ✅ OK
- [ ] Table Existence: Tất cả ✅

### 2. Authentication
- [ ] Đăng nhập được với account
- [ ] Thông tin hiển thị đúng

### 3. Orders Flow
- [ ] [ ] Tạo đơn hàng qua `/checkout.php`
- [ ] Đơn hàng hiển thị trong `/account/orders.php`
- [ ] Có thể xem chi tiết đơn hàng

### 4. Admin
- [ ] Truy cập `/admin/` được (nếu admin user)
- [ ] Dashboard hiển thị stats
- [ ] Xem được danh sách orders

### 5. Payment (tùy solution)
- [ ] COD: Order có trạng thái pending
- [ ] Test Payment (`/payment/test-payment.php`): Có thể simulate success
- [ ] Real Payment (nếu có credentials): Form submit được

---

## 🐛 TROUBLESHOOTING

### "Database Connection Failed"
```
→ Kiểm tra DB_PASS trong config.php
→ Kiểm tra PostgreSQL running: psql -U postgres
→ Nếu dùng MySQL, update Database.php DSN
```

### "Orders không hiển thị"
```
→ Check SQL: SELECT * FROM orders WHERE user_id = <id>;
→ Verify OrderService::getUserOrders() được gọi
→ Check browser console cho JS errors
```

### "Admin Dashboard lỗi"
```
→ Nếu database là MySQL, fix query date_trunc → DATE_FORMAT
→ Verify user có is_admin = TRUE
→ Check admin includes path
```

### "Payment form không submit"
```
→ Nếu config có placeholder → dùng Solution A (Test Payment)
→ Nếu muốn real payment → áp dụng Solution B + update credentials
→ Check config_validation() logic trong payment gateways
```

---

## 📁 KEY FILES

| File | Purpose | Status |
|------|---------|--------|
| `/includes/config/config.php` | Configuration + credentials | ⚠️  Placeholder values |
| `/includes/core/Database.php` | Database connection | ✅ Working |
| `/includes/services/OrderService.php` | Order creation/fetch | ✅ Code OK |
| `/account/orders.php` | User order list | ✅ Code OK |
| `/checkout.php` | Checkout flow | ✅ Code OK |
| `/admin/index.php` | Admin dashboard | ⚠️  PostgreSQL-specific |
| `/payment/test-payment.php` | Payment simulator | ✅ Working |
| `/payment/vnpay-return.php` | VNPay handler | ⚠️  Need credentials |
| `/payment/momo-return.php` | MoMo handler | ⚠️  Need credentials |

---

## 🎯 RECOMMENDED NEXT STEPS

### For Development/Testing:
1. ✅ Fix DB password (Solution C Fix 1)
2. ✅ Create admin user (Solution C Fix 1)
3. ✅ Test COD checkout (Solution A Step 2)
4. ✅ Use test-payment.php for payment testing (Solution A Step 2)
5. ✅ Verify admin dashboard (Solution C Fix 1)

### For Production:
1. ✅ Setup MoMo Sandbox (Solution B - MoMo)
2. ✅ Setup VNPay Sandbox (Solution B - VNPay)
3. ✅ Update config with real credentials
4. ✅ Test end-to-end payment flow
5. ✅ Deploy to production

---

## 📞 SUPPORT

Nếu vẫn có lỗi sau khi fix:
1. Chạy `/diagnostics/full_diagnostic.php` để check status
2. Xem error logs: Browser console (F12) + Server logs
3. Run verification checklist trên

---

**Last Updated**: Sprint 3 Rework
**Status**: INCOMPLETE - Cần áp dụng solution trên để hoàn thành
