# Website Thương Mại Điện Tử Bán Laptop 🛒💻

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.x-blue.svg)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-13+-blue.svg)](https://www.postgresql.org/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)](https://getbootstrap.com/)

## 📋 Mô tả dự án

Website thương mại điện tử (TMĐT) bán Laptop theo mô hình đa cửa hàng (Multi-vendor), hỗ trợ 3 phân hệ chính:
- **Khách hàng (Customer)**: Duyệt sản phẩm, mua sắm, thanh toán online
- **Cửa hàng (Shop)**: Quản lý sản phẩm, đơn hàng, doanh thu
- **Quản trị viên (Admin)**: Quản lý toàn bộ hệ thống

## 🚀 Demo

- **Homepage**: [products.php](products.php) - Danh sách sản phẩm với lọc & tìm kiếm
- **Product Detail**: [product-detail.php](product-detail.php) - Chi tiết sản phẩm với gallery
- **Database**: 15 sản phẩm laptop mẫu + hình ảnh thực tế

## 💻 Công nghệ sử dụng

- **Backend**: PHP 8.x với PDO
- **Database**: PostgreSQL 13+
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript (ES6+), jQuery
- **Web Server**: Apache (XAMPP/LAMP) hoặc Nginx
- **Thư viện**: PHPMailer (gửi email), Chart.js (biểu đồ)

## ✨ Tính năng chính

### 🛍️ Phân hệ Khách hàng
- ✅ Đăng ký/Đăng nhập/Quên mật khẩu
- ✅ Tìm kiếm & lọc sản phẩm nâng cao (theo danh mục, thương hiệu, giá)
- ✅ Giỏ hàng & Danh sách yêu thích
- ✅ Thanh toán COD, MoMo, VNPay (Sandbox)
- ✅ Quản lý đơn hàng & đánh giá sản phẩm
- ✅ Responsive design (Mobile-first)
- ✅ Product Gallery với Lightbox
- ✅ Quick View & Add to Cart AJAX

### 🏪 Phân hệ Cửa hàng (Shop)
- ✅ Dashboard với thống kê tổng quan
- ✅ Quản lý sản phẩm (CRUD, upload nhiều ảnh)
- ✅ Quản lý đơn hàng theo shop
- ✅ Báo cáo doanh thu theo thời gian
- ✅ Quản lý kho hàng

### ⚙️ Phân hệ Quản trị (Admin)
- ✅ Dashboard với biểu đồ thống kê (Chart.js)
- ✅ Quản lý người dùng & phân quyền (Role-Based Access Control)
- ✅ Duyệt đăng ký shop
- ✅ Quản lý danh mục, thương hiệu, banner
- ✅ Quản lý đơn hàng toàn hệ thống
- ✅ Báo cáo tổng hợp

## 🔒 Bảo mật

Dự án tuân thủ các nguyên tắc bảo mật OWASP Top 10:
- ✅ Mã hóa mật khẩu (bcrypt với cost 10)
- ✅ Chống SQL Injection (Prepared Statements với PDO)
- ✅ Chống XSS (htmlspecialchars cho tất cả output)
- ✅ CSRF Protection (Token validation)
- ✅ Kiểm soát truy cập (Role-Based Authorization)
- ✅ Session management an toàn (httponly, secure cookies)
- ✅ Input validation & sanitization

## 📦 Yêu cầu hệ thống

- PHP >= 8.0
- PostgreSQL >= 12
- Apache với mod_rewrite hoặc Nginx
- Extension PHP: PDO, pdo_pgsql, gd, mbstring, openssl, curl

## 🔧 Cài đặt

### Bước 1: Clone Repository

```bash
git clone https://github.com/tiendat2k44/laptop_store.git
cd laptop_store
```

### Bước 2: Import Database

⭐ **Sử dụng schema hoàn chỉnh mới** (khuyên dùng):

```bash
# Tạo database
createdb laptop_store

# Import schema hoàn chỉnh (bao gồm: core tables + payment + settings)
psql -U postgres -d laptop_store -f database/complete_schema.sql

# Import dữ liệu mẫu (20 sản phẩm laptop + hình ảnh + đơn hàng)
psql -U postgres -d laptop_store -f database/sample_data.sql
```

**Xem hướng dẫn chi tiết:** [DATABASE_IMPORT.md](DATABASE_IMPORT.md)

**Lưu ý:** File `complete_schema.sql` đã hợp nhất tất cả migrations cũ (payment tables, settings, shop rating). Không cần chạy các file SQL riêng lẻ nữa.

### Bước 3: Cấu hình

Cập nhật file `includes/config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'laptop_store');
define('DB_USER', 'postgres');
define('DB_PASS', 'your_password');
define('SITE_URL', 'http://localhost/laptop_store');
```

### Bước 4: Cấp quyền

```bash
chmod -R 755 assets/uploads
```

### Bước 5: Truy cập

- **Homepage**: `http://localhost/laptop_store/`
- **Admin Panel**: `http://localhost/laptop_store/admin/`
- **Shop Panel**: `http://localhost/laptop_store/shop/`

### 🎨 Hình ảnh mẫu

Dự án đã bao gồm:
- ✅ **15 hình ảnh sản phẩm laptop** thực tế
- ✅ **3 banner khuyến mãi**
- ✅ Tất cả hình ảnh đã được tải xuống vào `assets/uploads/`

## 👤 Tài khoản mặc định

Sau khi import database, tạo tài khoản mới hoặc sử dụng tài khoản mẫu:

```sql
-- Tạo tài khoản Admin
INSERT INTO users (email, password, full_name, phone, role_id, status) VALUES
('admin@laptopstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', '0901234567', 1, 'active');
```

**Mật khẩu**: `password`

## 📂 Cấu trúc thư mục

```
laptop_store/
├── admin/                  # Phân hệ Admin
│   ├── index.php          # Dashboard
│   ├── includes/          # Header, sidebar, core files
│   └── modules/           # CRUD modules
├── shop/                   # Phân hệ Shop
│   ├── dashboard.php
│   └── includes/
├── includes/               # Core files
│   ├── config/            # Configuration
│   ├── core/              # Database, Auth, Session
│   └── helpers/           # Helper functions
├── assets/
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── uploads/           # Product images, banners
├── database/
│   ├── schema.sql         # Database schema
│   └── sample_data.sql    # Sample products data
├── ajax/                   # AJAX endpoints
├── products.php            # Danh sách sản phẩm
├── product-detail.php      # Chi tiết sản phẩm
├── cart.php               # Giỏ hàng
├── checkout.php           # Thanh toán
└── index.php              # Homepage
```

## 🎨 Screenshots

### Homepage
- Hiển thị danh sách sản phẩm với hình ảnh thực tế
- Carousel banners khuyến mãi
- Responsive design

### Product Listing (products.php)
- Lọc theo danh mục, thương hiệu, khoảng giá
- Sắp xếp: mới nhất, giá, đánh giá
- Phân trang

### Product Detail (product-detail.php)
- Gallery hình ảnh
- Thông số kỹ thuật chi tiết
- Đánh giá và nhận xét
- Sản phẩm liên quan

## 📝 Database Schema

Xem file [database/schema.sql](database/schema.sql) để biết cấu trúc database đầy đủ.

**Các bảng chính:**
- `users` - Người dùng (Admin, Shop, Customer)
- `shops` - Cửa hàng
- `products` - Sản phẩm laptop
- `product_images` - Hình ảnh sản phẩm
- `categories` - Danh mục
- `orders` - Đơn hàng
- `order_items` - Chi tiết đơn hàng
- `cart_items` - Giỏ hàng
- `reviews` - Đánh giá
- `banners` - Banner quảng cáo

## 🚀 Tính năng sắp ra mắt

- [ ] Chat real-time giữa khách hàng và shop
- [ ] Thông báo push notification
- [ ] Tích hợp API ship (GHN, GHTK)
- [ ] Xuất báo cáo Excel/PDF
- [ ] Multi-language support
- [ ] Progressive Web App (PWA)

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