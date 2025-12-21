# 💳 Hướng Dẫn Nhanh: Kích Hoạt Thanh Toán

## ⚡ Setup Nhanh (5 phút)

### Bước 1: Tạo Bảng Database
```
Truy cập: http://localhost/TienDat123/laptop_store-main/database/setup_payment_tables.php
```
Script tự động tạo:
- ✅ Bảng `payment_transactions`
- ✅ Bảng `payment_config`
- ✅ Indexes tối ưu

### Bước 2: Test Hệ Thống
```
Truy cập: http://localhost/TienDat123/laptop_store-main/payment/test-payment.php
```
Kiểm tra:
- ✅ Database connection
- ✅ Payment tables
- ✅ Gateway classes
- ⚠️ VNPay config (chưa setup)
- ⚠️ MoMo config (chưa setup)

### Bước 3: Thanh Toán COD
**Đã hoạt động 100%** - không cần cấu hình gì thêm!

1. Thêm sản phẩm vào giỏ
2. Checkout → Chọn "Thanh toán khi nhận hàng"
3. Đặt hàng → ✅ Thành công

---

## 🏦 Setup VNPay (Sandbox Test)

### Bước 1: Lấy Credentials Test
VNPay cung cấp sandbox miễn phí để test:

```php
// File: includes/config/config.php
define('VNPAY_TMN_CODE', 'DEMOSHOP');  // Test merchant code
define('VNPAY_HASH_SECRET', 'GZJAMCFZPGNZUOFPPUAKPDTGPLDHSQJB');  // Test secret
```

### Bước 2: Test Thanh Toán
1. Tạo đơn hàng → Chọn **VNPAY**
2. Sẽ redirect đến sandbox VNPay
3. Dùng thẻ test:
   - **Số thẻ**: 9704198526191432198
   - **Tên**: NGUYEN VAN A
   - **Ngày hết hạn**: 07/15
   - **OTP**: 123456

4. Xác nhận → Quay lại website → ✅ Thanh toán thành công

---

## 💳 Setup MoMo (Sandbox Test)

### Bước 1: Đăng Ký Test Account
1. Truy cập: https://developers.momo.vn/
2. Đăng ký tài khoản developer
3. Tạo app test → Lấy credentials

### Bước 2: Cập Nhật Config
```php
// File: includes/config/config.php
define('MOMO_PARTNER_CODE', 'YOUR_TEST_PARTNER_CODE');
define('MOMO_ACCESS_KEY', 'YOUR_TEST_ACCESS_KEY');
define('MOMO_SECRET_KEY', 'YOUR_TEST_SECRET_KEY');
```

### Bước 3: Test Thanh Toán
1. Tạo đơn hàng → Chọn **MoMo**
2. Quét QR bằng app MoMo test
3. Xác nhận → ✅ Thanh toán thành công

---

## 📊 Xem Lịch Sử Giao Dịch

### Admin Panel
```
http://localhost/TienDat123/laptop_store-main/admin/modules/payments/
```

Thấy được:
- 💰 Tổng giao dịch
- ✅ Thành công / ❌ Thất bại
- 📈 Biểu đồ thống kê
- 🔍 Chi tiết từng transaction

### Database Query
```sql
SELECT 
  t.id,
  o.order_number,
  t.gateway,
  t.status,
  t.amount,
  t.message,
  t.created_at
FROM payment_transactions t
JOIN orders o ON t.order_id = o.id
ORDER BY t.created_at DESC
LIMIT 20;
```

---

## ✅ Checklist Hoạt Động

- [x] **COD** - Hoạt động 100%
- [ ] **VNPay** - Cần update credentials trong config.php
- [ ] **MoMo** - Cần update credentials trong config.php
- [x] **Database** - Bảng payment_transactions
- [x] **Lưu lịch sử** - Tự động ghi log mọi giao dịch
- [x] **Admin panel** - Xem thống kê đầy đủ

---

## 🆘 Xử Lý Lỗi Thường Gặp

### "VNPay/MoMo chưa được cấu hình"
➡️ Cập nhật credentials trong `includes/config/config.php`

### "Table payment_transactions doesn't exist"
➡️ Truy cập `/database/setup_payment_tables.php` để tạo bảng

### Thanh toán COD thành công nhưng VNPay/MoMo không redirect
➡️ Kiểm tra SITE_URL trong config phải đúng với URL hiện tại

### Giao dịch không lưu vào DB
➡️ Kiểm tra quyền user PostgreSQL với bảng `orders` và `payment_transactions`

---

## 🚀 Production Checklist

Khi chuyển lên production:

- [ ] Đăng ký merchant VNPay chính thức
- [ ] Đăng ký merchant MoMo chính thức
- [ ] Cập nhật production credentials
- [ ] Đổi endpoint từ sandbox → production
- [ ] Cài SSL certificate (HTTPS bắt buộc)
- [ ] Set `display_errors = 0` trong config
- [ ] Backup database thường xuyên
- [ ] Monitor payment logs hàng ngày

---

## 📚 Tài Liệu Chi Tiết

- **Setup đầy đủ**: [PAYMENT_SETUP.md](PAYMENT_SETUP.md)
- **Test payment**: [/payment/test-payment.php](http://localhost/TienDat123/laptop_store-main/payment/test-payment.php)
- **Admin panel**: [/admin/modules/payments/](http://localhost/TienDat123/laptop_store-main/admin/modules/payments/)

---

**💡 Tip**: Nếu không cần VNPay/MoMo ngay, COD đã hoạt động hoàn hảo. Setup các gateway online sau khi có nhiều đơn hàng hơn!
