# 🚀 Quick Start - Chia Sẻ Website Ra Ngoài

## 📦 Đã cài đặt sẵn:
- ✅ Ngrok
- ✅ Scripts tự động

---

## ⚡ Sử Dụng Nhanh (3 Bước)

### **1️⃣ Đăng ký Ngrok (1 lần duy nhất)**

```bash
# Truy cập và đăng ký miễn phí
https://dashboard.ngrok.com/signup

# Lấy authtoken tại
https://dashboard.ngrok.com/get-started/your-authtoken

# Cấu hình (thay YOUR_TOKEN)
ngrok config add-authtoken YOUR_TOKEN
```

### **2️⃣ Khởi động Ngrok**

```bash
./start-ngrok.sh
```

Bạn sẽ thấy URL như: `https://abc123.ngrok-free.app`

### **3️⃣ Cập nhật SITE_URL**

```bash
./update-site-url.sh
```

Chọn option 1 và nhập URL Ngrok từ bước 2.

---

## 🎯 Hoàn Tất!

Giờ bạn có thể chia sẻ URL Ngrok cho bất kỳ ai để demo website! 🎉

---

## 📚 Tài Liệu Chi Tiết

Xem file [NGROK_GUIDE.md](NGROK_GUIDE.md) để biết thêm chi tiết về:
- Cấu hình nâng cao
- Xử lý lỗi
- Tips & tricks
- Bảo mật

---

## 🛑 Khi Demo Xong

1. Nhấn `Ctrl+C` để dừng Ngrok
2. Chạy `./update-site-url.sh` và chọn option 2 để đặt lại localhost

---

## 💡 Lệnh Hữu Ích

```bash
# Xem logs PHP server
tail -f /tmp/php-server.log

# Xem Ngrok dashboard
# Mở browser: http://127.0.0.1:4040

# Dừng tất cả
pkill -f ngrok
pkill -f "php -S"
```

---

**Happy Sharing! 🌐✨**
