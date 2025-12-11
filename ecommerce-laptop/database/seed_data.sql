-- Insert Admin
INSERT INTO users (email, password, full_name, role, is_active) VALUES
('admin@laptopstore.com', '$2a$10$XKr3kVKJxKJxKJxKJxKJxOeZKDGvBTCm5sFmqN9HGxg9vxvxvxvx', 'Admin', 'admin', true);

-- Insert Shop
INSERT INTO users (email, password, full_name, role, shop_name, shop_description, is_active) VALUES
('techshop@laptopstore.com', '$2a$10$XKr3kVKJxKJxKJxKJxKJxOeZKDGvBTCm5sFmqN9HGxg9vxvxvxvx', 'Tech Shop Owner', 'shop', 'Tech Shop', 'Chuyên cung cấp laptop chính hãng', true);

-- Insert Customer
INSERT INTO users (email, password, full_name, role, is_active) VALUES
('customer@example.com', '$2a$10$XKr3kVKJxKJxKJxKJxKJxOeZKDGvBTCm5sFmqN9HGxg9vxvxvxvx', 'Nguyễn Văn A', 'customer', true);

-- Insert Categories
INSERT INTO categories (category_name, description, slug) VALUES
('Laptop Gaming', 'Laptop dành cho game thủ', 'laptop-gaming'),
('Laptop Văn Phòng', 'Laptop cho công việc văn phòng', 'laptop-van-phong'),
('Laptop Đồ Họa', 'Laptop cho thiết kế đồ họa', 'laptop-do-hoa');

-- Insert Brands
INSERT INTO brands (brand_name, slug) VALUES
('Dell', 'dell'),
('HP', 'hp'),
('Lenovo', 'lenovo'),
('Asus', 'asus'),
('Acer', 'acer'),
('MSI', 'msi');