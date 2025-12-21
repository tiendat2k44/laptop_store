# 💳 Hệ thống thanh toán VNPay & MoMo - Hướng dẫn cài đặt

## 📋 Mục lục
1. [Cấu trúc hệ thống](#cấu-trúc)
2. [Cài đặt database](#database)
3. [Cấu hình VNPay](#vnpay)
4. [Cấu hình MoMo](#momo)
5. [Cách sử dụng](#cách-sử-dụng)
6. [API Reference](#api)
7. [Troubleshooting](#troubleshooting)

---

## 🏗️ Cấu trúc hệ thống {#cấu-trúc}

### Thư mục chính:
```
laptop_store/
├── includes/
│   ├── payment/
│   │   ├── VNPayGateway.php      # Class xử lý VNPay
│   │   └── MoMoGateway.php       # Class xử lý MoMo
│   └── services/
│       └── PaymentService.php    # Service tổng hợp
├── payment/
│   ├── vnpay-return.php          # Return URL từ VNPay
│   ├── vnpay-ipn.php             # IPN callback từ VNPay
│   ├── momo-return.php           # Return URL từ MoMo
│   ├── momo-ipn.php              # IPN callback từ MoMo
│   └── test-payment.php          # Page test thanh toán
├── admin/
│   └── modules/
│       └── payments/
│           └── index.php         # Admin config page
└── database/
    └── payment_tables.sql        # SQL tạo bảng
```

### Các bảng database:
- `payment_config` - Lưu trữ cấu hình VNPay/MoMo
- `payment_transactions` - Log tất cả giao dịch
- `payments` - Chi tiết thanh toán (tuỳ chọn)

---

## 💾 Cài đặt Database {#database}

### Bước 1: Tạo các bảng
```bash
# Sử dụng MySQL command line
mysql -u root -p your_database < database/payment_tables.sql

# Hoặc sử dụng phpmyadmin: Chạy file SQL database/payment_tables.sql
```

### Bước 2: Tạo folder logs (nếu chưa có)
```bash
mkdir -p logs
chmod 777 logs
```

### Bước 3: Kiểm tra bảng được tạo
```sql
SHOW TABLES LIKE 'payment%';
SELECT * FROM payment_config;
SELECT * FROM payment_transactions;
```

---

## 🏦 Cấu hình VNPay {#vnpay}

### Lấy thông tin từ VNPay:

1. **Truy cập VNPay Merchant**: https://sandbox.vnpayment.vn/
   - Đăng ký tài khoản merchant
   - Lấy thông tin TMN Code và Hash Secret

2. **Thông tin cần:**
   - **TMN Code**: Mã nhân dạo merchant (ví dụ: 1XXXXX)
   - **Hash Secret**: Khóa bí mật dùng để mã hóa
   - **URL Sandbox**: `https://sandbox.vnpayment.vn/paymentv2/vpcpay.html`
   - **URL Production**: `https://payment.vnpayment.vn/paymentv2/vpcpay.html`

### Nhập cấu hình:

1. **Vào Admin Dashboard**: `/admin/modules/payments/`
2. **Tab "Cấu hình" → "VNPay"**
3. **Nhập các trường:**
   - TMN Code: `1XXXXX` (thay bằng code của bạn)
   - Hash Secret: `XXXXX...` (thay bằng secret của bạn)
   - VNPay URL: Chọn sandbox hoặc production

### File cấu hình:
```php
// includes/config/config.php
define('VNPAY_TMN_CODE', '1XXXXX');           // Thay thế
define('VNPAY_HASH_SECRET', 'XXXXX...');      // Thay thế
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
```

### Test VNPay:
```
1. Tạo đơn hàng
2. Chọn "VNPay" làm phương thức thanh toán
3. Sẽ được redirect tới VNPay
4. Dùng thẻ test: 4111111111111111 (sandbox)
5. Kiểm tra Admin → Payments → "Lịch sử giao dịch"
```

---

## 💳 Cấu hình MoMo {#momo}

### Lấy thông tin từ MoMo:

1. **Truy cập MoMo Merchant**: https://business.momo.vn/
   - Đăng ký tài khoản merchant
   - Lấy Partner Code, Access Key, Secret Key

2. **Thông tin cần:**
   - **Partner Code**: Mã đối tác (ví dụ: MXXXXXXXX)
   - **Access Key**: Khóa truy cập
   - **Secret Key**: Khóa bí mật dùng để ký
   - **Endpoint Sandbox**: `https://test-payment.momo.vn/v2/gateway/api/create`
   - **Endpoint Production**: `https://payment.momo.vn/v2/gateway/api/create`

### Nhập cấu hình:

1. **Vào Admin Dashboard**: `/admin/modules/payments/`
2. **Tab "Cấu hình" → "MoMo"**
3. **Nhập các trường:**
   - Partner Code: `MXXXXXXXX`
   - Access Key: `XXXXX...`
   - Secret Key: `XXXXX...`
   - Endpoint: Chọn test hoặc production

### File cấu hình:
```php
// includes/config/config.php
define('MOMO_PARTNER_CODE', 'MXXXXXXXX');         // Thay thế
define('MOMO_ACCESS_KEY', 'XXXXX...');            // Thay thế
define('MOMO_SECRET_KEY', 'XXXXX...');            // Thay thế
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');
```

### Test MoMo:
```
1. Tạo đơn hàng
2. Chọn "MoMo" làm phương thức thanh toán
3. Sẽ được redirect tới MoMo
4. Thử thanh toán với tài khoản test MoMo
5. Kiểm tra Admin → Payments → "Lịch sử giao dịch"
```

---

## 🚀 Cách sử dụng {#cách-sử-dụng}

### 1. Flow thanh toán:

```
User tạo đơn hàng
    ↓
Chọn phương thức (COD, VNPay, MoMo)
    ↓
Nếu COD: Order confirmed ngay
Nếu VNPay/MoMo: Redirect tới gateway
    ↓
User thanh toán tại gateway
    ↓
Gateway gửi IPN callback
    ↓
Server update status + gửi email
    ↓
User redirect về trang kết quả
```

### 2. Sử dụng trong code:

```php
<?php
require_once __DIR__ . '/includes/init.php';

$db = Database::getInstance();
require_once __DIR__ . '/includes/services/PaymentService.php';

$paymentService = new PaymentService($db);

// Khởi tạo thanh toán VNPay
$result = $paymentService->initializePayment($orderId, 'VNPAY');
if ($result['success']) {
    redirect($result['url']);
}

// Khởi tạo thanh toán MoMo
$result = $paymentService->initializePayment($orderId, 'MOMO');
if ($result['success']) {
    // Gửi request MoMo với $result['data']
}

// Lấy lịch sử giao dịch
$transactions = $paymentService->getTransactionLog($orderId);

// Lấy thống kê
$stats = $paymentService->getStatistics('month');
```

---

## 📚 API Reference {#api}

### VNPayGateway

```php
// Tạo URL thanh toán
$gateway = new VNPayGateway();
$url = $gateway->createPaymentUrl($order);
// redirect($url);

// Xác thực return
$result = $gateway->verifyReturn($_GET);
// ['success' => bool, 'message' => string, 'code' => string]

// Query trạng thái
$status = $gateway->queryTransaction($orderId, $txnRef);
```

### MoMoGateway

```php
// Tạo request thanh toán
$gateway = new MoMoGateway();
$result = $gateway->createPayment($order);
// ['success' => true, 'data' => array, 'endpoint' => string]

// Xác thực return
$result = $gateway->verifyReturn($_GET);
// ['success' => bool, 'message' => string]

// Xác thực callback
$result = $gateway->verifyCallback($_POST);
// ['success' => bool, 'message' => string, 'code' => int]
```

### PaymentService

```php
$service = new PaymentService($db);

// Khởi tạo thanh toán
$result = $service->initializePayment($orderId, 'VNPAY');

// Xác nhận thanh toán
$result = $service->confirmPayment('vnpay', $_GET);

// Lấy lịch sử
$logs = $service->getTransactionLog($orderId);

// Lấy tất cả giao dịch
$transactions = $service->getAllTransactions(['gateway' => 'vnpay', 'status' => 'success']);

// Lấy thống kê
$stats = $service->getStatistics('month');

// Quản lý cấu hình
$value = $service->getConfig('VNPAY_TMN_CODE');
$service->updateConfig('VNPAY_TMN_CODE', 'new_value');
```

---

## 🔒 Bảo mật {#bảo-mật}

### 1. Lưu trữ khóa bí mật

❌ **Không được:**
```php
$secret = 'XXXXX'; // Mã cứng trong code
```

✅ **Nên:**
```php
// Lưu trong database payment_config
$secret = $service->getConfig('VNPAY_HASH_SECRET');

// Hoặc trong .env file
define('VNPAY_HASH_SECRET', getenv('VNPAY_HASH_SECRET'));
```

### 2. Xác thực chữ ký

- VNPay và MoMo đều dùng HMAC-SHA256
- Luôn xác thực signature trước khi cập nhật status
- Kiểm tra IP whitelist nếu cần

### 3. Ngăn chặn replay attack

- Sử dụng `txnRef` hoặc `orderId` duy nhất
- Kiểm tra đơn hàng chưa thanh toán
- Validate số tiền trước khi cập nhật

### 4. Validate dữ liệu

```php
// Luôn validate input
if (!filter_var($_GET['amount'], FILTER_VALIDATE_FLOAT)) {
    die('Invalid amount');
}

// Check IP nếu cần
if (!in_array($_SERVER['REMOTE_ADDR'], ['GATEWAY_IPS'])) {
    die('Invalid IP');
}
```

---

## 🐛 Troubleshooting {#troubleshooting}

### VNPay không redirect được

**Triệu chứng:** Không thể tạo URL thanh toán

**Nguyên nhân:**
- TMN Code không đúng
- Hash Secret không đúng
- Cấu hình chưa được lưu vào database

**Giải pháp:**
```bash
# 1. Kiểm tra database
SELECT * FROM payment_config WHERE config_key LIKE 'VNPAY%';

# 2. Kiểm tra values có đúng không
# 3. Nhập lại từ admin panel

# 4. Xem error log
tail -f logs/ipn-vnpay-*.log
```

### MoMo callback không nhận được

**Triệu chứng:** Đơn hàng không tự update status

**Nguyên nhân:**
- IPN URL không đúng
- Server không thể receive POST request
- IP MoMo không được whitelist

**Giải pháp:**
```bash
# 1. Kiểm tra file logs/ipn-momo-*.log
tail logs/ipn-momo-*.log

# 2. Test IPN URL thủ công
curl -X POST https://yourdomain.com/payment/momo-ipn.php

# 3. Kiểm tra firewall
sudo ufw allow 443
```

### Lỗi "Invalid signature"

**Triệu chứng:** Callback bị từ chối

**Nguyên nhân:**
- Hash Secret hoặc Secret Key sai
- Dữ liệu bị mã hóa sai
- Parameter không sắp xếp đúng thứ tự

**Giải pháp:**
```php
// Debug signature
$rawHash = "..."; // Reconstruct raw hash
$calculatedSig = hash_hmac('sha256', $rawHash, $secret);
if ($calculatedSig !== $receivedSignature) {
    error_log("Signature mismatch: expected $calculatedSig, got $receivedSignature");
}
```

### Timeout khi connect tới gateway

**Triệu chứng:** Timeout lỗi khi query trạng thái

**Nguyên nhân:**
- Server bị block gateway IP
- CURL extension không enable
- Timeout quá ngắn

**Giải pháp:**
```php
// Kiểm tra CURL
php -r "var_dump(extension_loaded('curl'));"

// Tăng timeout
curl_setopt($curl, CURLOPT_TIMEOUT, 60);

// Allow outbound HTTPS
sudo ufw allow out 443
```

---

## 📞 Liên hệ hỗ trợ

- **VNPay Support**: support@vnpayment.vn
- **MoMo Support**: support@momo.vn
- **Dev Team**: Xem CONTACT.md

---

## 📄 Tài liệu tham khảo

- [VNPay API Documentation](https://sandbox.vnpayment.vn/apis/docs/)
- [MoMo API Documentation](https://developers.momo.vn/)
- [PHP HMAC-SHA256](https://www.php.net/manual/en/function.hash-hmac.php)

---

**Phiên bản**: 1.0.0 | **Cập nhật lần cuối**: 2025-12-21
