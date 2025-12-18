# Website Thương Mại Điện Tử Bán Laptop

## Mô tả dự án

Website thương mại điện tử (TMĐT) bán Laptop theo mô hình đa cửa hàng (Multi-vendor), hỗ trợ 3 phân hệ chính:
- **Khách hàng (Customer)**: Duyệt sản phẩm, mua sắm, thanh toán online
- **Cửa hàng (Shop)**: Quản lý sản phẩm, đơn hàng, doanh thu
- **Quản trị viên (Admin)**: Quản lý toàn bộ hệ thống

## Công nghệ sử dụng

- **Backend**: PHP 8.x với PDO
- **Database**: PostgreSQL
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript (ES6+), jQuery
- **Web Server**: Apache (XAMPP/LAMP)
- **Thư viện**: PHPMailer (gửi email), Chart.js (biểu đồ)

## Tính năng chính

### Phân hệ Khách hàng
- ✅ Đăng ký/Đăng nhập/Quên mật khẩu
- ✅ Tìm kiếm & lọc sản phẩm nâng cao
- ✅ Giỏ hàng & Danh sách yêu thích
- ✅ Thanh toán COD, MoMo, VNPay (Sandbox)
- ✅ Quản lý đơn hàng & đánh giá sản phẩm
- ✅ Responsive design

### Phân hệ Cửa hàng (Shop)
- ✅ Dashboard với thống kê tổng quan
- ✅ Quản lý sản phẩm (CRUD, upload ảnh)
- ✅ Quản lý đơn hàng theo shop
- ✅ Báo cáo doanh thu

### Phân hệ Quản trị (Admin)
- ✅ Dashboard với biểu đồ thống kê
- ✅ Quản lý người dùng & phân quyền
- ✅ Duyệt đăng ký shop
- ✅ Quản lý danh mục, thương hiệu, banner
- ✅ Quản lý đơn hàng toàn hệ thống

## Bảo mật

Dự án tuân thủ các nguyên tắc bảo mật OWASP Top 10:
- ✅ Mã hóa mật khẩu (bcrypt)
- ✅ Chống SQL Injection (Prepared Statements)
- ✅ Chống XSS (htmlspecialchars)
- ✅ CSRF Protection (Token validation)
- ✅ Kiểm soát truy cập (Role-Based)
- ✅ Session management an toàn

## Yêu cầu hệ thống

- PHP >= 8.0
- PostgreSQL >= 12
- Apache với mod_rewrite
- Extension PHP: PDO, pdo_pgsql, gd, mbstring, openssl

## Cài đặt

Xem file [INSTALL.md](INSTALL.md) để biết hướng dẫn cài đặt chi tiết.

### Nhanh chóng

```bash
# 1. Import database
psql -U postgres -d laptop_store -f database/schema.sql

# 2. Cấu hình includes/config/config.php với thông tin database

# 3. Cấp quyền
chmod -R 755 assets/uploads

# 4. Truy cập http://localhost/laptop_store/
```

### Tài khoản Admin mặc định
- Email: admin@laptopstore.com
- Password: Admin@123

## Cấu trúc dự án

```
/laptop_store/
├── assets/             # CSS, JS, uploads
├── includes/           # Core classes, config
├── admin/              # Admin panel
├── shop/               # Shop panel
├── ajax/               # AJAX endpoints
├── database/           # SQL schema
└── index.php           # Homepage
```

## License

MIT License