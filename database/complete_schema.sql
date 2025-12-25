-- =============================================
-- LAPTOP STORE - COMPLETE DATABASE SCHEMA (PostgreSQL)
-- Multi-vendor E-commerce Platform
-- Hợp nhất: schema chính + payment tables + settings + shop rating
-- =============================================
-- Version: 2.0
-- Date: 2024-01
-- =============================================

-- Drop existing tables (in correct order to handle foreign keys)
DROP TABLE IF EXISTS payment_transactions CASCADE;
DROP TABLE IF EXISTS payment_config CASCADE;
DROP TABLE IF EXISTS settings CASCADE;
DROP TABLE IF EXISTS reviews CASCADE;
DROP TABLE IF EXISTS order_items CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS cart_items CASCADE;
DROP TABLE IF EXISTS wishlist CASCADE;
DROP TABLE IF EXISTS product_images CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS brands CASCADE;
DROP TABLE IF EXISTS shops CASCADE;
DROP TABLE IF EXISTS addresses CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS roles CASCADE;
DROP TABLE IF EXISTS banners CASCADE;
DROP TABLE IF EXISTS coupons CASCADE;

-- =============================================
-- ROLES TABLE
-- =============================================
CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- BẢNG NGƯỜI DÙNG
-- Lưu thông tin tài khoản người dùng
('admin', 'Administrator with full access'),
('shop', 'Shop owner/vendor'),
('customer', 'Regular customer');

-- =============================================
-- USERS TABLE
-- =============================================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    role_id INTEGER NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    avatar VARCHAR(255),
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    password_reset_token VARCHAR(255),
    password_reset_expires TIMESTAMP,
-- BẢNG SẢN PHẨM
-- Lưu thông tin sản phẩm laptop
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

-- Indexes for users
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role_id);
CREATE INDEX idx_users_status ON users(status);

-- =============================================
-- ADDRESSES TABLE
-- =============================================
CREATE TABLE addresses (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    -- BẢNG NGƯỜI DÙNG
    -- Lưu thông tin tài khoản người dùng
    ward VARCHAR(100),
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_addresses_user ON addresses(user_id);
         email_verified BOOLEAN DEFAULT FALSE, -- Đã xác thực email
         email_verification_token VARCHAR(255), -- Mã xác thực email
         password_reset_token VARCHAR(255), -- Mã đặt lại mật khẩu
         password_reset_expires TIMESTAMP, -- Thời hạn mã đặt lại mật khẩu
         status VARCHAR(20) DEFAULT 'active', -- Trạng thái: hoạt động, bị khóa, chờ xác thực
         last_login TIMESTAMP, -- Lần đăng nhập cuối
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Ngày tạo
         updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Ngày cập nhật
         FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
    logo VARCHAR(255),
    banner VARCHAR(255),
    -- BẢNG SẢN PHẨM
    -- Lưu thông tin sản phẩm laptop
-- BẢNG DANH MỤC
-- Lưu các nhóm/danh mục sản phẩm
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_shops_status ON shops(status);
         cpu VARCHAR(255), -- Bộ vi xử lý
         ram VARCHAR(100), -- Bộ nhớ RAM
         storage VARCHAR(255), -- Ổ cứng
         screen_size VARCHAR(50), -- Kích thước màn hình
         graphics VARCHAR(255), -- Card đồ họa
         weight VARCHAR(50), -- Trọng lượng
         battery VARCHAR(100), -- Pin
         os VARCHAR(100), -- Hệ điều hành
-- Lưu các ảnh liên quan đến sản phẩm
         price DECIMAL(15,2) NOT NULL, -- Giá bán
         sale_price DECIMAL(15,2), -- Giá khuyến mãi
         stock_quantity INTEGER DEFAULT 0, -- Số lượng tồn kho
         low_stock_alert INTEGER DEFAULT 10, -- Ngưỡng cảnh báo hết hàng
);
         thumbnail VARCHAR(255), -- Ảnh đại diện
         featured BOOLEAN DEFAULT FALSE, -- Sản phẩm nổi bật
         status VARCHAR(20) DEFAULT 'active', -- Trạng thái: hoạt động, ẩn, hết hàng
('Dell', 'American technology company'),
         views INTEGER DEFAULT 0, -- Lượt xem
         sold_count INTEGER DEFAULT 0, -- Số lượng đã bán
         rating_average DECIMAL(3,2) DEFAULT 0, -- Điểm đánh giá trung bình
         review_count INTEGER DEFAULT 0, -- Số lượng đánh giá
('MSI', 'Gaming laptops'),
('Razer', 'Gaming laptops and peripherals');

-- =============================================
-- CATEGORIES TABLE
-- =============================================
CREATE TABLE categories (
    -- BẢNG DANH MỤC
    -- Lưu các nhóm/danh mục sản phẩm
    image VARCHAR(255),
    display_order INTEGER DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE INDEX idx_categories_slug ON categories(slug);
CREATE INDEX idx_categories_parent ON categories(parent_id);

-- Insert default categories
INSERT INTO categories (name, slug, description) VALUES 
('Laptop Văn Phòng', 'laptop-van-phong', 'Laptop cho công việc văn phòng, học tập'),
    -- BẢNG HÌNH ẢNH SẢN PHẨM
    -- Lưu các ảnh liên quan đến sản phẩm
-- =============================================
-- PRODUCTS TABLE
-- =============================================
-- BẢNG CHI TIẾT ĐƠN HÀNG
-- Lưu các sản phẩm thuộc từng đơn hàng
    category_id INTEGER NOT NULL,
    brand_id INTEGER NOT NULL,
    name VARCHAR(500) NOT NULL,
    slug VARCHAR(500) UNIQUE NOT NULL,
    -- BẢNG ĐƠN HÀNG
    -- Lưu thông tin các đơn hàng của khách
    storage VARCHAR(255),
    screen_size VARCHAR(50),
    graphics VARCHAR(255),
    weight VARCHAR(50),
    battery VARCHAR(100),
         recipient_name VARCHAR(255) NOT NULL, -- Tên người nhận
         recipient_phone VARCHAR(20) NOT NULL, -- Số điện thoại người nhận
         shipping_address TEXT NOT NULL, -- Địa chỉ nhận hàng
         city VARCHAR(100) NOT NULL, -- Tỉnh/Thành phố
         district VARCHAR(100), -- Quận/Huyện
         ward VARCHAR(100), -- Phường/Xã
    
         subtotal DECIMAL(15,2) NOT NULL, -- Tổng tiền hàng
         shipping_fee DECIMAL(15,2) DEFAULT 0, -- Phí vận chuyển
         discount_amount DECIMAL(15,2) DEFAULT 0, -- Số tiền giảm giá
         total_amount DECIMAL(15,2) NOT NULL, -- Tổng thanh toán
    
         payment_method VARCHAR(50) NOT NULL, -- Phương thức thanh toán: COD, MOMO, VNPAY, EASYPAY
         payment_status VARCHAR(20) DEFAULT 'pending', -- Trạng thái thanh toán: chờ, đã thanh toán, thất bại, hoàn tiền
         payment_transaction_id VARCHAR(255), -- Mã giao dịch thanh toán
         paid_at TIMESTAMP, -- Thời gian thanh toán
    review_count INTEGER DEFAULT 0,
         status VARCHAR(50) DEFAULT 'pending', -- Trạng thái đơn hàng: chờ, xác nhận, xử lý, giao, hoàn thành, hủy
         notes TEXT, -- Ghi chú đơn hàng
         cancel_reason TEXT, -- Lý do hủy đơn
    
-- BẢNG TỈNH/THÀNH, QUẬN/HUYỆN, PHƯỜNG/XÃ
-- Lưu dữ liệu hành chính Việt Nam
);

    -- BẢNG CHI TIẾT ĐƠN HÀNG
    -- Lưu các sản phẩm thuộc từng đơn hàng
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_featured ON products(featured);

-- =============================================
-- PRODUCT IMAGES TABLE
-- =============================================
CREATE TABLE product_images (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL,
         status VARCHAR(50) DEFAULT 'pending', -- Trạng thái sản phẩm trong đơn: chờ, xác nhận, xử lý, giao, hoàn thành, hủy
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Ngày tạo
         updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Ngày cập nhật
         FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
         FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
         FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE RESTRICT
CREATE INDEX idx_product_images_product ON product_images(product_id);

    -- BẢNG ĐỊA CHỈ
    -- Lưu địa chỉ nhận hàng của khách
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE(user_id, product_id)
);

CREATE INDEX idx_cart_user ON cart_items(user_id);

-- =============================================
-- WISHLIST TABLE
-- =============================================
    -- BẢNG CỬA HÀNG
    -- Lưu thông tin cửa hàng
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE(user_id, product_id)
);

CREATE INDEX idx_wishlist_user ON wishlist(user_id);

-- =============================================
-- ORDERS TABLE
-- =============================================
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    
    -- Shipping Information
    recipient_name VARCHAR(255) NOT NULL,
    recipient_phone VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    district VARCHAR(100),
    ward VARCHAR(100),
    
    -- Order Details
    subtotal DECIMAL(15,2) NOT NULL,
    shipping_fee DECIMAL(15,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    
    -- Payment
    payment_method VARCHAR(50) NOT NULL, -- COD, MOMO, VNPAY, EASYPAY
    payment_status VARCHAR(20) DEFAULT 'pending', -- pending, paid, failed, refunded
    payment_transaction_id VARCHAR(255),
    paid_at TIMESTAMP,
    
    -- Order Status
    status VARCHAR(50) DEFAULT 'pending', -- pending, confirmed, processing, shipping, delivered, cancelled
    notes TEXT,
    cancel_reason TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Indexes for orders
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_payment_status ON orders(payment_status);
CREATE INDEX idx_orders_order_number ON orders(order_number);
CREATE INDEX idx_orders_created_at ON orders(created_at);

-- =============================================
-- ORDER ITEMS TABLE
-- =============================================
CREATE TABLE order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    shop_id INTEGER NOT NULL,
    product_name VARCHAR(500) NOT NULL,
    product_thumbnail VARCHAR(255),
    price DECIMAL(15,2) NOT NULL,
    quantity INTEGER NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending', -- pending, confirmed, processing, shipping, delivered, cancelled
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE RESTRICT
);

CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_order_items_shop ON order_items(shop_id);
CREATE INDEX idx_order_items_product ON order_items(product_id);

-- =============================================
-- REVIEWS TABLE
-- =============================================
CREATE TABLE reviews (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    order_id INTEGER NOT NULL,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    images TEXT, -- JSON array of image URLs
    status VARCHAR(20) DEFAULT 'approved', -- pending, approved, rejected
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE(product_id, user_id, order_id)
);

CREATE INDEX idx_reviews_product ON reviews(product_id);
CREATE INDEX idx_reviews_user ON reviews(user_id);
CREATE INDEX idx_reviews_status ON reviews(status);

-- =============================================
-- BANNERS TABLE
-- =============================================
CREATE TABLE banners (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL,
    link VARCHAR(500),
    display_order INTEGER DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- COUPONS TABLE
-- =============================================
CREATE TABLE coupons (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type VARCHAR(20) NOT NULL, -- percentage, fixed
    discount_value DECIMAL(15,2) NOT NULL,
    min_order_value DECIMAL(15,2) DEFAULT 0,
    max_discount DECIMAL(15,2),
    usage_limit INTEGER,
    used_count INTEGER DEFAULT 0,
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_coupons_code ON coupons(code);
CREATE INDEX idx_coupons_status ON coupons(status);

-- =============================================
-- PAYMENT CONFIG TABLE
-- =============================================
CREATE TABLE payment_config (
    id SERIAL PRIMARY KEY,
    config_name VARCHAR(100) NOT NULL,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payment_config_key ON payment_config(config_key);

COMMENT ON TABLE payment_config IS 'Lưu trữ cấu hình thanh toán VNPay, MoMo, EasyPay';
COMMENT ON COLUMN payment_config.config_name IS 'Tên cấu hình';
COMMENT ON COLUMN payment_config.config_key IS 'Khóa cấu hình (ví dụ: VNPAY_TMN_CODE)';
COMMENT ON COLUMN payment_config.config_value IS 'Giá trị cấu hình';

-- Insert default payment config
INSERT INTO payment_config (config_name, config_key, config_value) VALUES
('VNPay TMN Code', 'VNPAY_TMN_CODE', 'your_tmn_code_here'),
('VNPay Hash Secret', 'VNPAY_HASH_SECRET', 'your_hash_secret_here'),
('VNPay URL', 'VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
('MoMo Partner Code', 'MOMO_PARTNER_CODE', 'your_partner_code_here'),
('MoMo Access Key', 'MOMO_ACCESS_KEY', 'your_access_key_here'),
('MoMo Secret Key', 'MOMO_SECRET_KEY', 'your_secret_key_here'),
('MoMo Endpoint', 'MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
('EasyPay API Key', 'EASYPAY_API_KEY', 'your_api_key_here'),
('EasyPay Secret', 'EASYPAY_SECRET', 'your_secret_here')
ON CONFLICT (config_key) DO NOTHING;

-- =============================================
-- PAYMENT TRANSACTIONS TABLE
-- =============================================
CREATE TABLE payment_transactions (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    gateway VARCHAR(20) NOT NULL, -- vnpay, momo, easypay, cod
    status VARCHAR(20) NOT NULL, -- pending, success, failed
    transaction_id VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    message TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE INDEX idx_payment_txn_order ON payment_transactions(order_id);
CREATE INDEX idx_payment_txn_gateway ON payment_transactions(gateway);
CREATE INDEX idx_payment_txn_status ON payment_transactions(status);
CREATE INDEX idx_payment_txn_transaction_id ON payment_transactions(transaction_id);
CREATE INDEX idx_payment_txn_created_at ON payment_transactions(created_at);

COMMENT ON TABLE payment_transactions IS 'Ghi lại tất cả các giao dịch thanh toán';
COMMENT ON COLUMN payment_transactions.gateway IS 'Cổng thanh toán (vnpay, momo, easypay, cod)';
COMMENT ON COLUMN payment_transactions.status IS 'Trạng thái (pending, success, failed)';
COMMENT ON COLUMN payment_transactions.transaction_id IS 'ID giao dịch từ gateway';
COMMENT ON COLUMN payment_transactions.amount IS 'Số tiền (VND)';

-- =============================================
-- SETTINGS TABLE
-- =============================================
CREATE TABLE settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_settings_key ON settings(setting_key);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Laptop Store'),
('site_email', 'support@laptopstore.com'),
('items_per_page', '12'),
('enable_registration', '1'),
('enable_shop_registration', '1'),
('maintenance_mode', '0')
ON CONFLICT (setting_key) DO NOTHING;

-- =============================================
-- CREATE DEFAULT ADMIN USER
-- Email: admin@laptopstore.com
-- Password: 123456
-- =============================================
INSERT INTO users (role_id, email, password_hash, full_name, email_verified, status) 
VALUES (
    1, 
    'admin@laptopstore.com', 
    '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 
    'System Administrator',
    TRUE,
    'active'
);

-- =============================================
-- FUNCTIONS AND TRIGGERS
-- =============================================

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Apply triggers to all tables with updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_addresses_updated_at BEFORE UPDATE ON addresses FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_shops_updated_at BEFORE UPDATE ON shops FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_brands_updated_at BEFORE UPDATE ON brands FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_categories_updated_at BEFORE UPDATE ON categories FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_products_updated_at BEFORE UPDATE ON products FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_cart_items_updated_at BEFORE UPDATE ON cart_items FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_orders_updated_at BEFORE UPDATE ON orders FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_order_items_updated_at BEFORE UPDATE ON order_items FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_reviews_updated_at BEFORE UPDATE ON reviews FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_banners_updated_at BEFORE UPDATE ON banners FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_coupons_updated_at BEFORE UPDATE ON coupons FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_payment_config_updated_at BEFORE UPDATE ON payment_config FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_settings_updated_at BEFORE UPDATE ON settings FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to update product rating when review is added/updated
CREATE OR REPLACE FUNCTION update_product_rating()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE products 
    SET 
        rating_average = (
            SELECT COALESCE(AVG(rating), 0) 
            FROM reviews 
            WHERE product_id = NEW.product_id AND status = 'approved'
        ),
        review_count = (
            SELECT COUNT(*) 
            FROM reviews 
            WHERE product_id = NEW.product_id AND status = 'approved'
        )
    WHERE id = NEW.product_id;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_product_rating_trigger 
AFTER INSERT OR UPDATE ON reviews 
FOR EACH ROW EXECUTE FUNCTION update_product_rating();

-- =============================================
-- SUCCESS MESSAGE
-- =============================================
SELECT '✅ Database schema created successfully! Ready to import sample data.' as message;
SELECT 'Default admin account: admin@laptopstore.com / 123456' as note;
