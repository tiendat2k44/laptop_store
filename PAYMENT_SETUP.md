# 🔐 Hướng Dẫn Cấu Hình Thanh Toán MoMo & VNPay

## 📋 Tổng Quan

Hệ thống hỗ trợ 3 phương thức thanh toán:
- **COD** (Cash On Delivery) - Thanh toán khi nhận hàng ✅ Đã hoạt động
- **VNPay** - Cổng thanh toán ngân hàng ⚙️ Cần cấu hình
- **MoMo** - Ví điện tử MoMo ⚙️ Cần cấu hình

---

## 🏦 1. Cấu Hình VNPay

### Bước 1: Đăng ký tài khoản Sandbox VNPay
1. Truy cập: https://sandbox.vnpayment.vn/
2. Đăng ký tài khoản merchant test
3. Lấy thông tin:
   - **TMN Code** (Mã merchant)
   - **Hash Secret** (Khóa bảo mật)

### Bước 2: Cập nhật config
Mở file `includes/config/config.php` và sửa:

```php
// VNPay Configuration
define('VNPAY_TMN_CODE', 'YOUR_TMN_CODE_HERE');
define('VNPAY_HASH_SECRET', 'YOUR_HASH_SECRET_HERE');
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNPAY_RETURN_URL', SITE_URL . '/payment/vnpay-return.php');
```

### Bước 3: Kiểm tra Database
Đảm bảo bảng `payment_transactions` đã được tạo:

```sql
SELECT * FROM payment_transactions LIMIT 1;
```

Nếu chưa có, chạy:
```bash
mysql -u root -p laptop_store < database/payment_tables.sql
```

### Bước 4: Test Thanh Toán
1. Tạo đơn hàng test
2. Chọn phương thức **VNPAY**
3. Click "Đặt hàng"
4. Sử dụng thẻ test của VNPay Sandbox:
   - Số thẻ: `9704198526191432198`
   - Tên: `NGUYEN VAN A`
   - Ngày hết hạn: `07/15`
   - OTP: `123456`

---

## 💳 2. Cấu Hình MoMo

### Bước 1: Đăng ký tài khoản Test MoMo
1. Truy cập: https://developers.momo.vn/
2. Đăng ký tài khoản merchant test
3. Lấy thông tin:
   - **Partner Code**
   - **Access Key**
   - **Secret Key**

### Bước 2: Cập nhật config
Mở file `includes/config/config.php` và sửa:

```php
// MoMo Configuration
define('MOMO_PARTNER_CODE', 'YOUR_PARTNER_CODE');
define('MOMO_ACCESS_KEY', 'YOUR_ACCESS_KEY');
define('MOMO_SECRET_KEY', 'YOUR_SECRET_KEY');
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');
define('MOMO_RETURN_URL', SITE_URL . '/payment/momo-return.php');
define('MOMO_IPN_URL', SITE_URL . '/payment/momo-ipn.php');
```

### Bước 3: Test Thanh Toán
1. Tạo đơn hàng test
2. Chọn phương thức **MoMo**
3. Click "Đặt hàng"
4. Quét mã QR bằng app MoMo test
5. Xác nhận thanh toán

---

## 🗄️ 3. Cấu Trúc Database

### Bảng `payment_transactions`
Lưu lịch sử tất cả giao dịch thanh toán:

```sql
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `gateway` VARCHAR(20) NOT NULL,           -- 'vnpay', 'momo', 'cod'
  `status` VARCHAR(20) NOT NULL,            -- 'pending', 'success', 'failed'
  `transaction_id` VARCHAR(255) NOT NULL,   -- Mã GD từ gateway
  `amount` DECIMAL(12, 2) NOT NULL,
  `message` TEXT,                           -- Chi tiết kết quả
  `ip_address` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
);
```

### Xem lịch sử giao dịch
```sql
SELECT 
  t.*,
  o.order_number,
  u.full_name
FROM payment_transactions t
JOIN orders o ON t.order_id = o.id
JOIN users u ON o.user_id = u.id
ORDER BY t.created_at DESC
LIMIT 20;
```

---

## 🔍 4. Kiểm Tra & Debug

### Test Config
Truy cập: `http://localhost/payment/test-payment.php`

File này sẽ kiểm tra:
- ✅ Config đã được set đầy đủ
- ✅ Database connection
- ✅ Bảng `payment_transactions` tồn tại
- ✅ Gateway classes load được

### Kiểm tra lỗi
Xem log lỗi trong PHP error log:
```bash
tail -f /var/log/apache2/error.log  # hoặc
tail -f /var/log/php/error.log
```

### Debug thanh toán thất bại
1. Kiểm tra table `payment_transactions`:
   ```sql
   SELECT * FROM payment_transactions WHERE status = 'failed' ORDER BY created_at DESC LIMIT 10;
   ```
2. Xem `message` column để biết lý do

---

## 🚀 5. Chuyển Sang Production

### Bước 1: Đăng ký merchant chính thức
- **VNPay**: https://vnpay.vn/dang-ky-merchant
- **MoMo**: https://business.momo.vn/

### Bước 2: Cập nhật config production
```php
// VNPay Production
define('VNPAY_URL', 'https://vnpayment.vn/paymentv2/vpcpay.html');

// MoMo Production
define('MOMO_ENDPOINT', 'https://payment.momo.vn/v2/gateway/api/create');
```

### Bước 3: SSL/HTTPS
Đảm bảo website có SSL certificate (bắt buộc cho payment gateway):
```bash
# Cài Let's Encrypt
sudo certbot --apache -d yourdomain.com
```

---

## 📊 6. Xem Thống Kê Thanh Toán

### Admin Panel
Truy cập: `http://localhost/admin/modules/payments/`

Xem:
- 💰 Tổng giao dịch
- ✅ Thành công / ❌ Thất bại
- 📈 Biểu đồ theo thời gian
- 🏦 Phân bổ VNPay/MoMo/COD

---

## ⚠️ Lưu Ý Quan Trọng

### Bảo mật
- ❌ **KHÔNG** commit secret keys lên Git
- ✅ Dùng `.env` file để lưu credentials
- ✅ Set quyền file config: `chmod 600 config.php`

### Xử lý lỗi
- COD: Luôn thành công (không cần cấu hình)
- VNPay/MoMo: Hiển thị thông báo rõ ràng nếu chưa config

### Webhook/IPN
- File `payment/vnpay-ipn.php` và `payment/momo-ipn.php` xử lý callback tự động
- Đảm bảo domain public để gateway gọi được

---

## 🆘 Troubleshooting

### Lỗi: "VNPay/MoMo chưa được cấu hình"
➡️ **Giải pháp**: Cập nhật credentials trong `config.php`

### Lỗi: "Table payment_transactions doesn't exist"
➡️ **Giải pháp**: 
```bash
mysql -u root -p laptop_store < database/payment_tables.sql
```

### Thanh toán thành công nhưng không update DB
➡️ **Giải pháp**: Kiểm tra permission table `orders` và `payment_transactions`

### MoMo QR không hiển thị
➡️ **Giải pháp**: Kiểm tra endpoint và signature trong MoMoGateway.php

---

## 📞 Hỗ Trợ

- VNPay Support: https://sandbox.vnpayment.vn/apis/
- MoMo Support: https://developers.momo.vn/
- Documentation: `/PAYMENT_SETUP.md` (file này)

---

**✅ Checklist Hoàn Thành:**
- [ ] Tạo bảng `payment_transactions`
- [ ] Cập nhật VNPay credentials
- [ ] Cập nhật MoMo credentials
- [ ] Test thanh toán COD
- [ ] Test thanh toán VNPay (sandbox)
- [ ] Test thanh toán MoMo (sandbox)
- [ ] Kiểm tra lịch sử giao dịch trong admin
- [ ] Setup SSL cho production
- [ ] Đăng ký merchant production (khi sẵn sàng)
