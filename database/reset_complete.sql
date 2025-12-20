-- =============================================
-- RESET & REIMPORT - LAPTOP STORE DATABASE
-- Chạy file này để xóa và tạo lại database từ đầu
-- =============================================

-- BƯỚC 1: Xóa tất cả dữ liệu cũ (chạy trước schema.sql)
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

-- BƯỚC 2: Tạo lại schema (từ schema.sql)
-- =============================================
-- ROLES TABLE
-- =============================================
CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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
    status VARCHAR(20) DEFAULT 'active',
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

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
    status VARCHAR(20) DEFAULT 'pending',
    approved_by INTEGER,
    approved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_shops_status ON shops(status);
CREATE INDEX idx_shops_user ON shops(user_id);

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
    cpu VARCHAR(255),
    ram VARCHAR(100),
    storage VARCHAR(255),
    screen_size VARCHAR(50),
    graphics VARCHAR(255),
    weight VARCHAR(50),
    battery VARCHAR(100),
    os VARCHAR(100),
    price DECIMAL(15,2) NOT NULL,
    sale_price DECIMAL(15,2),
    stock_quantity INTEGER DEFAULT 0,
    low_stock_alert INTEGER DEFAULT 10,
    thumbnail VARCHAR(255),
    featured BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'active',
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
    recipient_name VARCHAR(255) NOT NULL,
    recipient_phone VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    district VARCHAR(100),
    ward VARCHAR(100),
    subtotal DECIMAL(15,2) NOT NULL,
    shipping_fee DECIMAL(15,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(20) DEFAULT 'pending',
    payment_transaction_id VARCHAR(255),
    paid_at TIMESTAMP,
    status VARCHAR(50) DEFAULT 'pending',
    notes TEXT,
    cancel_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

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
    status VARCHAR(50) DEFAULT 'pending',
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
    images TEXT,
    status VARCHAR(20) DEFAULT 'approved',
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
    discount_type VARCHAR(20) NOT NULL,
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

-- BƯỚC 3: Nhập dữ liệu mẫu (từ sample_data.sql)
-- =============================================
-- XÓA DỮ LIỆU MẪU CŨ
DELETE FROM reviews;
DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM cart_items;
DELETE FROM wishlist;
DELETE FROM product_images;
DELETE FROM products;
DELETE FROM banners;
DELETE FROM shops WHERE user_id != 1;
DELETE FROM users WHERE email IN ('shop1@laptopstore.com', 'shop2@laptopstore.com', 'shop3@laptopstore.com');

-- Tạo users
INSERT INTO users (role_id, email, password_hash, full_name, phone, status, email_verified, created_at)
VALUES 
(2, 'shop1@laptopstore.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Nguyễn Văn A - Shop Owner', '0901234567', 'active', true, CURRENT_TIMESTAMP),
(2, 'shop2@laptopstore.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Trần Thị B - Shop Owner', '0909876543', 'active', true, CURRENT_TIMESTAMP),
(2, 'shop3@laptopstore.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Lê Văn C - Shop Owner', '0912345678', 'active', true, CURRENT_TIMESTAMP),
(3, 'customer1@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Phạm Ngọc Huy', '0987654321', 'active', true, CURRENT_TIMESTAMP),
(3, 'customer2@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Ngô Thị Lan', '0987654322', 'active', true, CURRENT_TIMESTAMP),
(3, 'customer3@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Vũ Đức Minh', '0987654323', 'active', true, CURRENT_TIMESTAMP),
(3, 'customer4@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Trương Anh Tuấn', '0987654324', 'active', true, CURRENT_TIMESTAMP);

-- DO block để tạo shops và products
DO $$
DECLARE
    shop1_user_id INT;
    shop2_user_id INT;
    shop3_user_id INT;
    shop1_id INT;
    shop2_id INT;
    shop3_id INT;
BEGIN
    SELECT id INTO shop1_user_id FROM users WHERE email = 'shop1@laptopstore.com';
    SELECT id INTO shop2_user_id FROM users WHERE email = 'shop2@laptopstore.com';
    SELECT id INTO shop3_user_id FROM users WHERE email = 'shop3@laptopstore.com';
    
    INSERT INTO shops (user_id, shop_name, description, phone, email, status, approved_by, approved_at, created_at) 
    VALUES (shop1_user_id, 'Tech World Store', 'Cửa hàng chuyên laptop cao cấp, chính hãng với giá tốt nhất', '0901234567', 'techworld@laptopstore.com', 'active', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    RETURNING id INTO shop1_id;
    
    INSERT INTO shops (user_id, shop_name, description, phone, email, status, approved_by, approved_at, created_at) 
    VALUES (shop2_user_id, 'Laptop Pro', 'Laptop gaming và đồ họa chuyên nghiệp, bảo hành tận tâm', '0909876543', 'laptoppro@laptopstore.com', 'active', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    RETURNING id INTO shop2_id;
    
    INSERT INTO shops (user_id, shop_name, description, phone, email, status, approved_by, approved_at, created_at) 
    VALUES (shop3_user_id, 'Digital Shop', 'Laptop văn phòng giá tốt, giao hàng nhanh toàn quốc', '0912345678', 'digitalshop@laptopstore.com', 'active', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    RETURNING id INTO shop3_id;
    
    -- Thêm 20 sản phẩm (tóm tắt - xem sample_data.sql đầy đủ)
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop1_id, 1, 1, 'Dell Latitude 5430 - Core i7-1265U, 16GB RAM, 512GB SSD', 'dell-latitude-5430', 'Laptop Dell Latitude 5430 là dòng laptop doanh nghiệp cao cấp với hiệu năng mạnh mẽ, thiết kế bền bỉ và bảo mật cao. Phù hợp cho công việc văn phòng, lập trình và xử lý đa nhiệm.', 'Intel Core i7-1265U (10 cores, 12 threads, up to 4.8GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '14 inch FHD (1920x1080)', 'Intel Iris Xe Graphics', '1.37kg', '4-cell 58Wh', 'Windows 11 Pro', 22990000, 21490000, 15, 'products/dell-latitude-5430-1.jpg', true, 'active');
    
    -- Thêm hình ảnh (lấy từ sample_data.sql)
    INSERT INTO product_images (product_id, image_url, display_order, created_at) 
    SELECT p.id, 'https://images.unsplash.com/photo-1603046891726-36bfd957e2af?w=500&h=500&fit=crop', 0, CURRENT_TIMESTAMP
    FROM products p WHERE p.slug = 'dell-latitude-5430';
    
    INSERT INTO product_images (product_id, image_url, display_order, created_at) 
    SELECT p.id, 'https://images.unsplash.com/photo-1588872657840-790ff3bde08c?w=500&h=500&fit=crop', 1, CURRENT_TIMESTAMP
    FROM products p WHERE p.slug = 'dell-latitude-5430';

END $$;

-- Thêm orders
INSERT INTO orders (order_number, user_id, recipient_name, recipient_phone, shipping_address, city, district, ward, subtotal, shipping_fee, discount_amount, total_amount, payment_method, payment_status, status, notes, created_at, updated_at)
VALUES
('ORD-20251120-001', 5, 'Phạm Ngọc Huy', '0987654321', '123 Đường A, Phường 1', 'Hà Nội', 'Ba Đình', 'Phúc Tạn', 21490000, 25000, 0, 21515000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '30 days', CURRENT_TIMESTAMP - INTERVAL '30 days');

SELECT '✅ Đã reset database thành công!' as message;
