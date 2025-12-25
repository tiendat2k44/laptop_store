# 🌐 Hướng Dẫn Chia Sẻ Website Ra Bên Ngoài Bằng Ngrok

## 📋 Tổng Quan

Ngrok cho phép bạn chia sẻ website localhost ra internet một cách dễ dàng, hữu ích cho:
- ✅ Demo website cho khách hàng
- ✅ Test webhook từ các dịch vụ bên ngoài (payment gateway, API...)
- ✅ Chia sẻ với team để review
- ✅ Test trên thiết bị di động

---

## 🚀 Cách Sử Dụng Nhanh

### **Bước 1: Đăng ký tài khoản Ngrok (Miễn phí)**

1. Truy cập: https://dashboard.ngrok.com/signup
2. Đăng ký tài khoản miễn phí (dùng Google/GitHub)
3. Lấy **authtoken** tại: https://dashboard.ngrok.com/get-started/your-authtoken

### **Bước 2: Cấu hình authtoken**

Chạy lệnh sau (thay YOUR_TOKEN bằng token của bạn):

```bash
ngrok config add-authtoken YOUR_TOKEN
```

### **Bước 3: Khởi động website với Ngrok**

```bash
./start-ngrok.sh
```

**Hoặc** chạy thủ công:

```bash
# Khởi động PHP server
php -S localhost:8000 -t /workspaces/laptop_store &

# Khởi động Ngrok
ngrok http 8000
```

---

## 📱 Giao Diện Ngrok

Khi Ngrok chạy, bạn sẽ thấy:

```
ngrok

Session Status                online
Account                       Your Name (Plan: Free)
Version                       3.x.x
Region                        Asia Pacific (ap)
Latency                       -
Web Interface                 http://127.0.0.1:4040
Forwarding                    https://abc123.ngrok-free.app -> http://localhost:8000

Connections                   ttl     opn     rt1     rt5     p50     p90
                              0       0       0.00    0.00    0.00    0.00
```

### 🔗 **URL Chia Sẻ**

Link `https://abc123.ngrok-free.app` là URL công khai để chia sẻ!

### 📊 **Web Interface**

Truy cập `http://127.0.0.1:4040` để xem:
- Danh sách requests
- Request/Response details
- Replay requests

---

## ⚙️ Cấu Hình Website Cho Ngrok

### **Cập nhật SITE_URL trong config**

Mở file `includes/config/config.php` và thay đổi:

```php
// Development - Local
// define('SITE_URL', 'http://localhost:8000');

// Ngrok - Thay bằng URL Ngrok của bạn
define('SITE_URL', 'https://abc123.ngrok-free.app');
```

⚠️ **Lưu ý:** Nhớ đổi lại về localhost sau khi demo xong!

---

## 🎯 Các Lệnh Hữu Ích

### **Xem trạng thái Ngrok**
```bash
ngrok status
```

### **Xem danh sách tunnel đang chạy**
```bash
curl http://localhost:4040/api/tunnels
```

### **Dừng tất cả process**
```bash
pkill -f ngrok
pkill -f "php -S"
```

### **Khởi động với port khác**
```bash
php -S localhost:9000 -t /workspaces/laptop_store &
ngrok http 9000
```

---

## 🆓 Giới Hạn Gói Free

- ✅ 1 tunnel cùng lúc
- ✅ HTTPS miễn phí
- ✅ 40 connections/phút
- ⚠️ URL ngẫu nhiên (thay đổi mỗi lần restart)
- ⚠️ Session timeout sau 2 giờ (phải restart)

### **Nâng cấp lên trả phí (nếu cần)**

- 💰 $8/tháng: Custom subdomain, không giới hạn connections
- Truy cập: https://dashboard.ngrok.com/billing/subscription

---

## 🔒 Bảo Mật

### **Thêm Basic Auth (tùy chọn)**

```bash
ngrok http 8000 --basic-auth "username:password"
```

### **Chỉ cho phép IP cụ thể**

```bash
ngrok http 8000 --cidr-allow 1.2.3.4/32
```

---

## 🐛 Xử Lý Lỗi Thường Gặp

### **Lỗi: "authtoken not configured"**

**Nguyên nhân:** Chưa cấu hình authtoken

**Giải pháp:**
```bash
ngrok config add-authtoken YOUR_TOKEN
```

### **Lỗi: "Port already in use"**

**Nguyên nhân:** Port 8000 đang được sử dụng

**Giải pháp:**
```bash
# Tìm và kill process
lsof -ti:8000 | xargs kill -9

# Hoặc dùng port khác
php -S localhost:8001 -t /workspaces/laptop_store &
ngrok http 8001
```

### **Lỗi: CSS/JS không load**

**Nguyên nhân:** SITE_URL trong config chưa được cập nhật

**Giải pháp:** Đổi SITE_URL trong `includes/config/config.php` thành URL Ngrok

---

## 🌟 Tips & Tricks

### **1. Dùng subdomain tùy chỉnh (Pro plan)**
```bash
ngrok http 8000 --subdomain=mylaptopstore
# => https://mylaptopstore.ngrok-free.app
```

### **2. Lưu cấu hình vào file**

Tạo file `ngrok.yml`:
```yaml
tunnels:
  laptop-store:
    proto: http
    addr: 8000
    inspect: true
```

Chạy:
```bash
ngrok start laptop-store
```

### **3. Xem logs realtime**
```bash
tail -f /tmp/php-server.log
```

---

## 📞 Hỗ Trợ

- 📖 Docs: https://ngrok.com/docs
- 💬 Community: https://github.com/inconshreveable/ngrok
- 🆘 Support: https://dashboard.ngrok.com/support

---

## ✅ Checklist Trước Khi Demo

- [ ] Đã cấu hình Ngrok authtoken
- [ ] Đã cập nhật SITE_URL trong config.php
- [ ] Database có dữ liệu mẫu
- [ ] Tất cả chức năng hoạt động trên localhost
- [ ] Đã test trên nhiều trình duyệt
- [ ] Đã chuẩn bị tài khoản demo (admin, shop, customer)

---

**Chúc bạn demo thành công! 🎉**
