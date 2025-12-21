# Hướng Dẫn Tích Hợp EasyPay (Sepay)

## 📋 Mô Tả
EasyPay là cổng thanh toán trực tuyến của Sepay cho phép người dùng thanh toán qua nhiều hình thức:
- Ví điện tử (E-wallet)
- Chuyển khoản ngân hàng
- Thẻ tín dụng/ghi nợ
- Các phương thức thanh toán khác

## 🔗 Tài Liệu Chính Thức
- **Website**: https://sepay.vn/
- **Tài Liệu API**: https://sepay.vn/lap-trinh-cong-thanh-toan.html
- **Dashboard**: https://merchant.sepay.vn/

## ⚙️ Các Bước Cấu Hình

### 1. Đăng Ký Tài Khoản
- Truy cập https://sepay.vn/
- Click "Đăng ký" hoặc "Sign up"
- Điền thông tin:
  - Email
  - Mật khẩu
  - Tên công ty / Tên cá nhân
  - Số điện thoại
  - Địa chỉ
  - Loại hình kinh doanh
- Xác minh email
- Xác thực tài khoản (có thể cần upload giấy tờ)

### 2. Lấy Thông Tin API
Sau khi tài khoản được xác thực:
1. Đăng nhập vào https://merchant.sepay.vn/
2. Vào phần **Settings** hoặc **API Keys**
3. Tìm các thông tin:
   - **Partner Code**: Mã định danh của bạn
   - **API Key**: Khóa API riêng tư (GIỮ BẢO MẬT)
   - **Webhook URL**: Địa chỉ để nhận thông báo từ EasyPay

### 3. Cấu Hình Ứng Dụng
Mở file `includes/config/config.php` và cập nhật:

```php
// EasyPay/Sepay
define('EASYPAY_PARTNER_CODE', 'your_actual_partner_code_here');
define('EASYPAY_API_KEY', 'your_actual_api_key_here');
define('EASYPAY_ENDPOINT', 'https://easypay.vn/api/openapi/pay-url');
```

**Ví dụ:**
```php
define('EASYPAY_PARTNER_CODE', 'SEPAY123456');
define('EASYPAY_API_KEY', 'sk_test_abc123xyz789...');
```

### 4. Cấu Hình Webhook
Trong EasyPay Merchant Dashboard:
1. Vào **Settings** → **Webhooks**
2. Thêm webhook URL:
   ```
   https://your-site.com/payment/easy-pay-ipn.php
   ```
3. Chọn sự kiện: **Payment completed** hoặc **Transaction status changed**
4. Lưu và test webhook

### 5. Cấu Hình Return URL
Return URL được tự động thiết lập trong code:
```
https://your-site.com/payment/easy-pay-return.php?id={order_id}
```
Người dùng sẽ được điều hướng về URL này sau khi hoàn tất/hủy thanh toán trên EasyPay.

## 📁 File Thích Hợp Tích Hợp

### Gateway Class
- **File**: `includes/payment/EasyPayGateway.php`
- **Chức năng**:
  - Tạo URL thanh toán
  - Xác thực webhook từ EasyPay
  - Xác thực return data
  - Query trạng thái giao dịch
  - Ghi log giao dịch

### Return Handler
- **File**: `payment/easy-pay-return.php`
- **Chức năng**:
  - Tiếp nhận người dùng quay lại từ EasyPay
  - Cập nhật trạng thái đơn hàng
  - Ghi log thanh toán

### Webhook Handler
- **File**: `payment/easy-pay-ipn.php`
- **Chức năng**:
  - Nhận thông báo từ EasyPay (webhook)
  - Xác thực chữ ký
  - Cập nhật trạng thái nếu thanh toán thành công

### Checkout Integration
- **File**: `checkout.php`
- **Thay đổi**:
  - Thêm `EASYPAY` vào danh sách payment methods
  - Thêm UI card cho lựa chọn EasyPay
  - Thêm redirect case cho EASYPAY

## 🔐 Bảo Mật

### Mã Hóa Chữ Ký
EasyPay sử dụng **MD5 hash** để xác thực:
```
Signature = MD5(partner_code + request_id + amount + api_key)
```

**Ví dụ:**
```php
$signature = md5('SEPAY123456' . '123456789012' . '100000' . 'sk_test_abc123...');
```

### Quy Tắc Bảo Mật
1. **Không để lộ API Key**: Chỉ lưu trên server, không gửi client-side
2. **Xác thực webhook**: Luôn verify signature trước khi update order
3. **HTTPS Only**: Đảm bảo tất cả kết nối đều dùng HTTPS
4. **IP Whitelist**: Nếu EasyPay hỗ trợ, thêm IP server vào whitelist

## 🧪 Test & Debug

### Test Payment
1. Truy cập: `http://localhost/diagnostics/test-easypay.php`
2. Kiểm tra cấu hình
3. Chọn order để test
4. Click "Test Payment"

### Xem Log Giao Dịch
```sql
SELECT * FROM payment_transactions 
WHERE gateway = 'easypay' 
ORDER BY created_at DESC 
LIMIT 10;
```

### Debug Mode
Kiểm tra `error.log` hoặc `php_error.log` để xem lỗi từ EasyPay.

## 💡 Mẹo

### Khi Nào Được Cập Nhật Status
| Sự kiện | Khi nào | Cách cập nhật |
|--------|--------|-------------|
| Người dùng click thanh toán | Ngay lập tức | Return handler + webhook |
| Thanh toán thành công | Webhook từ EasyPay | Easy-pay-ipn.php |
| Thanh toán thất bại | Webhook hoặc query API | Easy-pay-ipn.php hoặc return |

### Xử Lý Webhook
- Webhook được gửi qua POST với JSON body
- Phải respond với HTTP 200 + JSON để EasyPay biết đã nhận
- Nếu không nhận được response, EasyPay sẽ retry

### Trạng Thái Đơn Hàng
- **pending**: Chưa thanh toán
- **confirmed**: Đã thanh toán thành công
- **failed**: Thanh toán thất bại

## 🚀 Triển Khai Production

### Trước Triển Khai
- [ ] Cấu hình API Key production (không test)
- [ ] Test thanh toán thực tế với số tiền nhỏ
- [ ] Kiểm tra webhook hoạt động
- [ ] Enable HTTPS trên server
- [ ] Backup database trước khi live

### Sau Triển Khai
- [ ] Monitor payment transactions
- [ ] Kiểm tra log lỗi hàng ngày
- [ ] Xử lý thủ công các giao dịch lỗi
- [ ] Liên hệ hỗ trợ EasyPay nếu có vấn đề

## 📞 Hỗ Trợ

### Liên Hệ EasyPay
- **Website**: https://sepay.vn/
- **Email Support**: support@sepay.vn
- **Hotline**: Xem trên website Sepay
- **Live Chat**: Có trên merchant dashboard

### Lỗi Thường Gặp

#### 1. "Configuration not found"
**Giải pháp**: Kiểm tra EASYPAY_PARTNER_CODE và EASYPAY_API_KEY trong config.php

#### 2. "Invalid signature"
**Giải pháp**: Đảm bảo API Key chính xác, không có space hoặc ký tự thừa

#### 3. "Webhook not received"
**Giải pháp**: 
- Kiểm tra firewall/server logs
- Đảm bảo webhook URL đúng
- Test webhook từ dashboard
- Kiểm tra IP whitelist (nếu có)

#### 4. "Payment URL is empty"
**Giải pháp**: 
- Kiểm tra response từ EasyPay API
- Xem log error.log để xem chi tiết lỗi
- Liên hệ support EasyPay

## 📊 Monitoring & Analytics

### Theo Dõi Giao Dịch
```sql
-- Tổng số giao dịch EasyPay
SELECT COUNT(*) as total_transactions 
FROM payment_transactions 
WHERE gateway = 'easypay';

-- Giao dịch thành công
SELECT COUNT(*) as success_count 
FROM payment_transactions 
WHERE gateway = 'easypay' AND status = 'success';

-- Doanh thu từ EasyPay
SELECT SUM(amount) as total_revenue 
FROM payment_transactions 
WHERE gateway = 'easypay' AND status = 'success';
```

### Kiểm Tra Webhook
```php
// Xem webhook logs
SELECT * FROM payment_transactions 
WHERE gateway = 'easypay' AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY created_at DESC;
```

## ✅ Checklist Cấu Hình Hoàn Chỉnh

- [ ] Đăng ký tài khoản Sepay
- [ ] Xác thực tài khoản
- [ ] Lấy Partner Code và API Key
- [ ] Cập nhật config.php
- [ ] Cấu hình webhook URL
- [ ] Test payment trên sandbox
- [ ] Kiểm tra webhook hoạt động
- [ ] Upload code lên server
- [ ] Test lại trên production
- [ ] Giám sát giao dịch hàng ngày

---

**Ghi chú**: EasyPay là phương thức thanh toán tuyệt vời để bổ sung cùng VNPay, MoMo và COD. Đảm bảo cấu hình đúng để tránh mất giao dịch của khách hàng.
