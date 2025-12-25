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

-- Insert default roles
INSERT INTO roles (name, description) VALUES 
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
    status VARCHAR(20) DEFAULT 'active', -- active, locked, pending
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
    recipient_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address_line VARCHAR(500) NOT NULL,
    city VARCHAR(100) NOT NULL,
    district VARCHAR(100),
    ward VARCHAR(100),
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_addresses_user ON addresses(user_id);

-- =============================================
-- SHOPS TABLE
-- =============================================
CREATE TABLE shops (
    id SERIAL PRIMARY KEY,
    user_id INTEGER UNIQUE NOT NULL,
    shop_name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    logo VARCHAR(255),
    banner VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INTEGER DEFAULT 0,
    status VARCHAR(20) DEFAULT 'pending', -- pending, active, suspended
    approved_by INTEGER,
    approved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_shops_status ON shops(status);
CREATE INDEX idx_shops_user ON shops(user_id);
CREATE INDEX idx_shops_rating ON shops(rating DESC);

-- =============================================
-- BRANDS TABLE
-- =============================================
CREATE TABLE brands (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    logo VARCHAR(255),
    description TEXT,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert popular laptop brands
INSERT INTO brands (name, description) VALUES 
('Dell', 'American technology company'),
('HP', 'Hewlett-Packard'),
('Lenovo', 'Chinese multinational technology company'),
('ASUS', 'Taiwan-based multinational computer hardware and electronics company'),
('Acer', 'Taiwan-based hardware + electronics corporation'),
('Apple', 'MacBook series'),
('MSI', 'Gaming laptops'),
('Razer', 'Gaming laptops and peripherals');

-- =============================================
-- CATEGORIES TABLE
-- =============================================
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    parent_id INTEGER,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
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
('Laptop Gaming', 'laptop-gaming', 'Laptop chơi game hiệu năng cao'),
('Laptop Đồ Họa', 'laptop-do-hoa', 'Laptop cho thiết kế, render'),
('Ultrabook', 'ultrabook', 'Laptop mỏng nhẹ cao cấp'),
('Workstation', 'workstation', 'Laptop workstation chuyên nghiệp');

-- =============================================
-- PRODUCTS TABLE
-- =============================================
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    shop_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    brand_id INTEGER NOT NULL,
    name VARCHAR(500) NOT NULL,
    slug VARCHAR(500) UNIQUE NOT NULL,
    description TEXT,
    
    -- Specifications
    cpu VARCHAR(255),
    ram VARCHAR(100),
    storage VARCHAR(255),
    screen_size VARCHAR(50),
    graphics VARCHAR(255),
    weight VARCHAR(50),
    battery VARCHAR(100),
    os VARCHAR(100),
    
    -- Pricing & Inventory
    price DECIMAL(15,2) NOT NULL,
    sale_price DECIMAL(15,2),
    stock_quantity INTEGER DEFAULT 0,
    low_stock_alert INTEGER DEFAULT 10,
    
    -- SEO & Display
    thumbnail VARCHAR(255),
    featured BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'active', -- active, inactive, out_of_stock
    
    -- Statistics
    views INTEGER DEFAULT 0,
    sold_count INTEGER DEFAULT 0,
    rating_average DECIMAL(3,2) DEFAULT 0,
    review_count INTEGER DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE RESTRICT
);

-- Indexes for products
CREATE INDEX idx_products_shop ON products(shop_id);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_brand ON products(brand_id);
CREATE INDEX idx_products_slug ON products(slug);
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_featured ON products(featured);

-- =============================================
-- PRODUCT IMAGES TABLE
-- =============================================
CREATE TABLE product_images (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE INDEX idx_product_images_product ON product_images(product_id);

-- =============================================
-- CART ITEMS TABLE
-- =============================================
CREATE TABLE cart_items (
    id SERIAL PRIMARY KEY,
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
CREATE TABLE wishlist (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
