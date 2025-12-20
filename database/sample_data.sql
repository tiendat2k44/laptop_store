-- =============================================
-- DỮ LIỆU MẪU - LAPTOP STORE
-- Chạy file này sau khi đã chạy schema.sql
-- =============================================

-- XÓA DỮ LIỆU CŨ (nếu đã tồn tại)
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

-- Tạo 3 shop owners và các khách hàng với mật khẩu: 123456
INSERT INTO users (role_id, email, password_hash, full_name, phone, status, email_verified, created_at)
VALUES 
(2, 'shop1@laptopstore.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Nguyễn Văn A - Shop Owner', '0901234567', 'active', true, CURRENT_TIMESTAMP),
(2, 'shop2@laptopstore.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Trần Thị B - Shop Owner', '0909876543', 'active', true, CURRENT_TIMESTAMP),
(2, 'shop3@laptopstore.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Lê Văn C - Shop Owner', '0912345678', 'active', true, CURRENT_TIMESTAMP),
(3, 'customer1@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Phạm Ngọc Huy', '0987654321', 'active', true, CURRENT_TIMESTAMP),
(3, 'customer2@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Ngô Thị Lan', '0987654322', 'active', true, CURRENT_TIMESTAMP),
(3, 'customer3@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Vũ Đức Minh', '0987654323', 'active', true, CURRENT_TIMESTAMP),
(3, 'customer4@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Trương Anh Tuấn', '0987654324', 'active', true, CURRENT_TIMESTAMP);

-- Tạo shops và products sử dụng DO block để tự động lấy shop_id
DO $$
DECLARE
    shop1_user_id INT;
    shop2_user_id INT;
    shop3_user_id INT;
    shop1_id INT;
    shop2_id INT;
    shop3_id INT;
BEGIN
    -- Lấy user_id của các shop owners
    SELECT id INTO shop1_user_id FROM users WHERE email = 'shop1@laptopstore.com';
    SELECT id INTO shop2_user_id FROM users WHERE email = 'shop2@laptopstore.com';
    SELECT id INTO shop3_user_id FROM users WHERE email = 'shop3@laptopstore.com';
    
    -- Thêm shops
    INSERT INTO shops (user_id, shop_name, description, phone, email, status, approved_by, approved_at, created_at) 
    VALUES (shop1_user_id, 'Tech World Store', 'Cửa hàng chuyên laptop cao cấp, chính hãng với giá tốt nhất', '0901234567', 'techworld@laptopstore.com', 'active', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    RETURNING id INTO shop1_id;
    
    INSERT INTO shops (user_id, shop_name, description, phone, email, status, approved_by, approved_at, created_at) 
    VALUES (shop2_user_id, 'Laptop Pro', 'Laptop gaming và đồ họa chuyên nghiệp, bảo hành tận tâm', '0909876543', 'laptoppro@laptopstore.com', 'active', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    RETURNING id INTO shop2_id;
    
    INSERT INTO shops (user_id, shop_name, description, phone, email, status, approved_by, approved_at, created_at) 
    VALUES (shop3_user_id, 'Digital Shop', 'Laptop văn phòng giá tốt, giao hàng nhanh toàn quốc', '0912345678', 'digitalshop@laptopstore.com', 'active', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    RETURNING id INTO shop3_id;
    
    -- Thêm sản phẩm Dell
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop1_id, 1, 1, 'Dell Latitude 5430 - Core i7-1265U, 16GB RAM, 512GB SSD', 'dell-latitude-5430', 
    'Laptop Dell Latitude 5430 là dòng laptop doanh nghiệp cao cấp với hiệu năng mạnh mẽ, thiết kế bền bỉ và bảo mật cao. Phù hợp cho công việc văn phòng, lập trình và xử lý đa nhiệm.', 
    'Intel Core i7-1265U (10 cores, 12 threads, up to 4.8GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '14 inch FHD (1920x1080)', 'Intel Iris Xe Graphics', '1.37kg', '4-cell 58Wh', 'Windows 11 Pro', 22990000, 21490000, 15, 'products/dell-latitude-5430-1.jpg', true, 'active'),
    
    (shop1_id, 1, 1, 'Dell Inspiron 15 3520 - Core i5-1235U, 8GB RAM, 256GB SSD', 'dell-inspiron-15-3520',
    'Laptop Dell Inspiron 15 3520 với thiết kế thanh lịch, hiệu năng ổn định cho công việc văn phòng và học tập. Màn hình 15.6 inch cho trải nghiệm làm việc thoải mái.',
    'Intel Core i5-1235U (10 cores, up to 4.4GHz)', '8GB DDR4 2666MHz', '256GB SSD NVMe', '15.6 inch FHD', 'Intel UHD Graphics', '1.85kg', '3-cell 41Wh', 'Windows 11 Home', 14990000, 13990000, 25, 'products/dell-inspiron-15-3520-1.jpg', false, 'active');
    
    -- Thêm sản phẩm HP
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop1_id, 1, 2, 'HP EliteBook 840 G9 - Core i7-1255U, 16GB RAM, 512GB SSD', 'hp-elitebook-840-g9',
    'HP EliteBook 840 G9 là laptop cao cấp dành cho doanh nghiệp với bảo mật vượt trội, thiết kế mỏng nhẹ và hiệu năng mạnh mẽ. Màn hình 14 inch sắc nét.',
    'Intel Core i7-1255U (10 cores, up to 4.7GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '14 inch FHD IPS', 'Intel Iris Xe Graphics', '1.32kg', '3-cell 51Wh', 'Windows 11 Pro', 26990000, 25490000, 12, 'products/hp-elitebook-840-g9-1.jpg', true, 'active'),
    
    (shop2_id, 2, 2, 'HP Victus 15 Gaming - Core i5-12450H, RTX 3050, 8GB RAM', 'hp-victus-15-gaming',
    'HP Victus 15 là laptop gaming giá tốt với card đồ họa RTX 3050, hiệu năng mạnh mẽ cho game và đồ họa. Màn hình 15.6 inch 144Hz mượt mà.',
    'Intel Core i5-12450H (8 cores, up to 4.4GHz)', '8GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD 144Hz', 'NVIDIA RTX 3050 4GB', '2.29kg', '4-cell 70Wh', 'Windows 11 Home', 20990000, 19490000, 18, 'products/hp-victus-15-gaming-1.jpg', true, 'active');
    
    -- Thêm sản phẩm Lenovo
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop2_id, 1, 3, 'Lenovo ThinkPad X1 Carbon Gen 10 - Core i7-1260P, 16GB RAM', 'lenovo-thinkpad-x1-carbon-gen10',
    'Lenovo ThinkPad X1 Carbon Gen 10 là ultrabook cao cấp nhất với trọng lượng siêu nhẹ, bàn phím tuyệt vời và thời lượng pin lâu. Lý tưởng cho doanh nhân.',
    'Intel Core i7-1260P (12 cores, up to 4.7GHz)', '16GB LPDDR5', '512GB SSD NVMe', '14 inch WUXGA IPS', 'Intel Iris Xe Graphics', '1.12kg', '4-cell 57Wh', 'Windows 11 Pro', 35990000, 33990000, 8, 'products/lenovo-thinkpad-x1-carbon-gen10-1.jpg', true, 'active'),
    
    (shop2_id, 2, 3, 'Lenovo Legion 5 Pro - Ryzen 7 6800H, RTX 3070Ti, 16GB RAM', 'lenovo-legion-5-pro',
    'Lenovo Legion 5 Pro là laptop gaming cao cấp với màn hình 16 inch QHD+ 165Hz, RTX 3070Ti cho trải nghiệm gaming đỉnh cao và thiết kế đẹp mắt.',
    'AMD Ryzen 7 6800H (8 cores, 16 threads, up to 4.7GHz)', '16GB DDR5 4800MHz', '512GB SSD NVMe', '16 inch WQXGA 165Hz', 'NVIDIA RTX 3070Ti 8GB', '2.5kg', '4-cell 80Wh', 'Windows 11 Home', 36990000, 34490000, 10, 'products/lenovo-legion-5-pro-1.jpg', true, 'active');
    
    -- Thêm sản phẩm ASUS
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop2_id, 2, 4, 'ASUS ROG Strix G15 - Ryzen 9 6900HX, RTX 3070, 16GB RAM', 'asus-rog-strix-g15',
    'ASUS ROG Strix G15 là laptop gaming mạnh mẽ với Ryzen 9 6900HX, RTX 3070 và màn hình 300Hz cho trải nghiệm gaming cực đỉnh. Thiết kế RGB ấn tượng.',
    'AMD Ryzen 9 6900HX (8 cores, 16 threads, up to 4.9GHz)', '16GB DDR5 4800MHz', '1TB SSD NVMe', '15.6 inch FHD 300Hz', 'NVIDIA RTX 3070 8GB', '2.3kg', '90Wh', 'Windows 11 Home', 38990000, 36990000, 7, 'products/asus-rog-strix-g15-1.jpg', true, 'active'),
    
    (shop3_id, 4, 4, 'ASUS ZenBook 14 OLED - Core i5-1240P, 8GB RAM, 512GB SSD', 'asus-zenbook-14-oled',
    'ASUS ZenBook 14 OLED với màn hình OLED tuyệt đẹp, thiết kế mỏng nhẹ cao cấp và hiệu năng tốt. Hoàn hảo cho công việc sáng tạo và di động.',
    'Intel Core i5-1240P (12 cores, up to 4.4GHz)', '8GB LPDDR5', '512GB SSD NVMe', '14 inch 2.8K OLED', 'Intel Iris Xe Graphics', '1.39kg', '75Wh', 'Windows 11 Home', 21990000, 20490000, 20, 'products/asus-vivobook-15-1.jpg', false, 'active');
    
    -- Thêm sản phẩm Acer
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop3_id, 2, 5, 'Acer Predator Helios 300 - Core i7-12700H, RTX 3060, 16GB RAM', 'acer-predator-helios-300',
    'Acer Predator Helios 300 là laptop gaming cân bằng giữa hiệu năng và giá cả với RTX 3060, màn hình 144Hz và hệ thống tản nhiệt tốt.',
    'Intel Core i7-12700H (14 cores, up to 4.7GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD 144Hz', 'NVIDIA RTX 3060 6GB', '2.5kg', '4-cell 59Wh', 'Windows 11 Home', 28990000, 26990000, 15, 'products/acer-predator-helios-300-1.jpg', true, 'active'),
    
    (shop3_id, 1, 5, 'Acer Aspire 5 - Core i5-1235U, 8GB RAM, 512GB SSD', 'acer-aspire-5',
    'Acer Aspire 5 là laptop phổ thông với cấu hình tốt, giá cả hợp lý cho học sinh, sinh viên và văn phòng. Màn hình 15.6 inch FHD sắc nét.',
    'Intel Core i5-1235U (10 cores, up to 4.4GHz)', '8GB DDR4 2666MHz', '512GB SSD NVMe', '15.6 inch FHD', 'Intel UHD Graphics', '1.8kg', '3-cell 48Wh', 'Windows 11 Home', 13990000, 12990000, 30, 'products/acer-aspire-5-1.jpg', false, 'active');
    
    -- Thêm sản phẩm Apple MacBook
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop1_id, 4, 6, 'Apple MacBook Air M2 2022 - 8GB RAM, 256GB SSD', 'apple-macbook-air-m2-2022',
    'MacBook Air M2 với thiết kế mới, chip M2 mạnh mẽ, màn hình Liquid Retina và thời lượng pin lên đến 18 giờ. Siêu mỏng nhẹ chỉ 1.24kg.',
    'Apple M2 (8-core CPU, up to 3.49GHz)', '8GB Unified Memory', '256GB SSD', '13.6 inch Liquid Retina', 'Apple M2 GPU 8-core', '1.24kg', 'Up to 18 hours', 'macOS Ventura', 27990000, 26490000, 12, 'products/macbook-air-m2-1.jpg', true, 'active'),
    
    (shop1_id, 4, 6, 'Apple MacBook Pro 14 M2 Pro - 16GB RAM, 512GB SSD', 'apple-macbook-pro-14-m2-pro',
    'MacBook Pro 14 inch với chip M2 Pro cho hiệu năng chuyên nghiệp, màn hình Liquid Retina XDR tuyệt đẹp và hệ thống âm thanh 6 loa đỉnh cao.',
    'Apple M2 Pro (10-core CPU)', '16GB Unified Memory', '512GB SSD', '14.2 inch Liquid Retina XDR', 'Apple M2 Pro GPU 16-core', '1.6kg', 'Up to 17 hours', 'macOS Ventura', 49990000, 47990000, 6, 'products/macbook-pro-14-m2-1.jpg', true, 'active');
    
    -- Thêm sản phẩm MSI
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop2_id, 2, 7, 'MSI Katana GF66 - Core i7-12650H, RTX 3060, 16GB RAM', 'msi-katana-gf66',
    'MSI Katana GF66 là laptop gaming với thiết kế lấy cảm hứng từ katana Nhật Bản, RTX 3060 và màn hình 144Hz cho trải nghiệm gaming mượt mà.',
    'Intel Core i7-12650H (10 cores, up to 4.7GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD 144Hz', 'NVIDIA RTX 3060 6GB', '2.25kg', '53.5Wh', 'Windows 11 Home', 25990000, 24490000, 13, 'products/msi-katana-gf66-1.jpg', false, 'active'),
    
    (shop2_id, 2, 7, 'MSI GE76 Raider - Core i9-12900HK, RTX 3080Ti, 32GB RAM', 'msi-ge76-raider',
    'MSI GE76 Raider là laptop gaming cao cấp nhất với Core i9-12900HK, RTX 3080Ti và RGB Mystic Light Bar. Hiệu năng khủng cho mọi tác vụ.',
    'Intel Core i9-12900HK (14 cores, up to 5.0GHz)', '32GB DDR5 4800MHz', '1TB SSD NVMe', '17.3 inch FHD 360Hz', 'NVIDIA RTX 3080Ti 16GB', '2.9kg', '99.9Wh', 'Windows 11 Pro', 65990000, 62990000, 4, 'products/msi-ge76-raider-1.jpg', true, 'active');
    
    -- Thêm sản phẩm Razer
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop3_id, 2, 8, 'Razer Blade 15 - Core i7-12800H, RTX 3070Ti, 16GB RAM', 'razer-blade-15',
    'Razer Blade 15 là laptop gaming mỏng nhẹ cao cấp với thiết kế kim loại nguyên khối, RGB Chroma và hiệu năng mạnh mẽ. Perfect cho gamers và creators.',
    'Intel Core i7-12800H (14 cores, up to 4.8GHz)', '16GB DDR5 4800MHz', '1TB SSD NVMe', '15.6 inch QHD 240Hz', 'NVIDIA RTX 3070Ti 8GB', '2.01kg', '80Wh', 'Windows 11 Home', 52990000, 49990000, 6, 'products/razer-blade-15-1.jpg', true, 'active');
    
    -- Thêm thêm một số sản phẩm để đủ 20 sản phẩm
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop1_id, 1, 1, 'Dell Vostro 3510 - Core i3-1115G4, 4GB RAM, 256GB SSD', 'dell-vostro-3510',
    'Dell Vostro 3510 là laptop văn phòng giá rẻ với cấu hình đủ dùng cho công việc cơ bản, lướt web và văn phòng. Giá cả phải chăng.',
    'Intel Core i3-1115G4 (2 cores, up to 4.1GHz)', '4GB DDR4 2666MHz', '256GB SSD', '15.6 inch HD', 'Intel UHD Graphics', '1.69kg', '3-cell 42Wh', 'Windows 11 Home', 9990000, 8990000, 40, 'products/dell-inspiron-15-3520-2.jpg', false, 'active'),
    
    (shop2_id, 3, 3, 'Lenovo ThinkBook 15 G4 - Core i5-1235U, 8GB RAM, 512GB SSD', 'lenovo-thinkbook-15-g4',
    'Lenovo ThinkBook 15 G4 là laptop cho doanh nghiệp vừa và nhỏ với thiết kế hiện đại, bảo mật tốt và hiệu năng ổn định.',
    'Intel Core i5-1235U (10 cores, up to 4.4GHz)', '8GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD', 'Intel Iris Xe Graphics', '1.7kg', '3-cell 45Wh', 'Windows 11 Pro', 16990000, 15490000, 22, 'products/lenovo-legion-5-pro-2.jpg', false, 'active'),
    
    (shop3_id, 3, 4, 'ASUS Vivobook 15 OLED - Core i5-12500H, 8GB RAM, 512GB SSD', 'asus-vivobook-15-oled',
    'ASUS Vivobook 15 OLED với màn hình OLED tuyệt đẹp, hiệu năng tốt và giá cả hợp lý. Phù hợp cho sinh viên và văn phòng.',
    'Intel Core i5-12500H (12 cores, up to 4.5GHz)', '8GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD OLED', 'Intel Iris Xe Graphics', '1.7kg', '42Wh', 'Windows 11 Home', 17990000, 16490000, 28, 'products/asus-vivobook-15-2.jpg', true, 'active'),
    
    (shop1_id, 5, 2, 'HP ZBook Firefly 15 G9 - Core i7-1265U, 16GB RAM, 512GB SSD', 'hp-zbook-firefly-15-g9',
    'HP ZBook Firefly 15 G9 là workstation mỏng nhẹ cho công việc chuyên nghiệp, thiết kế đồ họa và kỹ thuật. Cấu hình mạnh mẽ với card đồ họa chuyên dụng.',
    'Intel Core i7-1265U (10 cores, up to 4.8GHz)', '16GB DDR5 4800MHz', '512GB SSD NVMe', '15.6 inch FHD IPS', 'NVIDIA T550 4GB', '1.74kg', '56Wh', 'Windows 11 Pro', 34990000, 32990000, 5, 'products/hp-victus-15-gaming-2.jpg', false, 'active');
    
    -- Thêm hình ảnh cho các sản phẩm (dùng slug để lấy product_id) - Các URL Unsplash chất lượng cao
    INSERT INTO product_images (product_id, image_url, display_order, created_at) 
    SELECT p.id, u.img_url, u.order_num, CURRENT_TIMESTAMP
    FROM products p
    CROSS JOIN (
        VALUES
        ('dell-latitude-5430', 'https://images.unsplash.com/photo-1588871657840-790ff3bde08c?w=500&h=500&fit=crop&q=80', 0),
        ('dell-latitude-5430', 'https://images.unsplash.com/photo-1603046891726-36bfd957e2af?w=500&h=500&fit=crop&q=80', 1),
        ('dell-inspiron-15-3520', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=500&h=500&fit=crop&q=80', 0),
        ('dell-inspiron-15-3520', 'https://images.unsplash.com/photo-1593642532400-2682a8356f14?w=500&h=500&fit=crop&q=80', 1),
        ('hp-elitebook-840-g9', 'https://images.unsplash.com/photo-1527814050087-3793815479db?w=500&h=500&fit=crop&q=80', 0),
        ('hp-elitebook-840-g9', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500&h=500&fit=crop&q=80', 1),
        ('hp-victus-15-gaming', 'https://images.unsplash.com/photo-1602524206684-88c5dde2e2d5?w=500&h=500&fit=crop&q=80', 0),
        ('hp-victus-15-gaming', 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=500&h=500&fit=crop&q=80', 1),
        ('lenovo-thinkpad-x1-carbon-gen10', 'https://images.unsplash.com/photo-1522869635100-ce306f473385?w=500&h=500&fit=crop&q=80', 0),
        ('lenovo-thinkpad-x1-carbon-gen10', 'https://images.unsplash.com/photo-1484480974693-6ca0a78fb36b?w=500&h=500&fit=crop&q=80', 1),
        ('lenovo-legion-5-pro', 'https://images.unsplash.com/photo-1609034227505-5876f6aa4e90?w=500&h=500&fit=crop&q=80', 0),
        ('lenovo-legion-5-pro', 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=500&h=500&fit=crop&q=80', 1),
        ('asus-rog-strix-g15', 'https://images.unsplash.com/photo-1595225476933-0efccddcfc47?w=500&h=500&fit=crop&q=80', 0),
        ('asus-rog-strix-g15', 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=500&h=500&fit=crop&q=80', 1),
        ('asus-zenbook-14-oled', 'https://images.unsplash.com/photo-1560070094-e1f1cf0b6ddf?w=500&h=500&fit=crop&q=80', 0),
        ('asus-zenbook-14-oled', 'https://images.unsplash.com/photo-1610624497093-f9d79d5af766?w=500&h=500&fit=crop&q=80', 1),
        ('acer-predator-helios-300', 'https://images.unsplash.com/photo-1556056169-b0be9603ba0c?w=500&h=500&fit=crop&q=80', 0),
        ('acer-predator-helios-300', 'https://images.unsplash.com/photo-1589939705066-5470487067ea?w=500&h=500&fit=crop&q=80', 1),
        ('acer-aspire-5', 'https://images.unsplash.com/photo-1588871657840-790ff3bde08c?w=500&h=500&fit=crop&q=80', 0),
        ('acer-aspire-5', 'https://images.unsplash.com/photo-1525697203642-aae59a97c869?w=500&h=500&fit=crop&q=80', 1),
        ('apple-macbook-air-m2-2022', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=500&h=500&fit=crop&q=80', 0),
        ('apple-macbook-air-m2-2022', 'https://images.unsplash.com/photo-1527814050087-3793815479db?w=500&h=500&fit=crop&q=80', 1),
        ('apple-macbook-pro-14-m2-pro', 'https://images.unsplash.com/photo-1588872657840-790ff3bde08c?w=500&h=500&fit=crop&q=80', 0),
        ('apple-macbook-pro-14-m2-pro', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500&h=500&fit=crop&q=80', 1),
        ('msi-katana-gf66', 'https://images.unsplash.com/photo-1602524206684-88c5dde2e2d5?w=500&h=500&fit=crop&q=80', 0),
        ('msi-katana-gf66', 'https://images.unsplash.com/photo-1593642532400-2682a8356f14?w=500&h=500&fit=crop&q=80', 1),
        ('msi-ge76-raider', 'https://images.unsplash.com/photo-1595225476933-0efccddcfc47?w=500&h=500&fit=crop&q=80', 0),
        ('msi-ge76-raider', 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=500&h=500&fit=crop&q=80', 1),
        ('razer-blade-15', 'https://images.unsplash.com/photo-1556056169-b0be9603ba0c?w=500&h=500&fit=crop&q=80', 0),
        ('razer-blade-15', 'https://images.unsplash.com/photo-1589939705066-5470487067ea?w=500&h=500&fit=crop&q=80', 1),
        ('dell-vostro-3510', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=500&h=500&fit=crop&q=80', 0),
        ('dell-vostro-3510', 'https://images.unsplash.com/photo-1588871657840-790ff3bde08c?w=500&h=500&fit=crop&q=80', 1),
        ('lenovo-thinkbook-15-g4', 'https://images.unsplash.com/photo-1522869635100-ce306f473385?w=500&h=500&fit=crop&q=80', 0),
        ('lenovo-thinkbook-15-g4', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500&h=500&fit=crop&q=80', 1),
        ('asus-vivobook-15-oled', 'https://images.unsplash.com/photo-1560070094-e1f1cf0b6ddf?w=500&h=500&fit=crop&q=80', 0),
        ('asus-vivobook-15-oled', 'https://images.unsplash.com/photo-1610624497093-f9d79d5af766?w=500&h=500&fit=crop&q=80', 1),
        ('hp-zbook-firefly-15-g9', 'https://images.unsplash.com/photo-1527814050087-3793815479db?w=500&h=500&fit=crop&q=80', 0),
        ('hp-zbook-firefly-15-g9', 'https://images.unsplash.com/photo-1484480974693-6ca0a78fb36b?w=500&h=500&fit=crop&q=80', 1)
    ) AS u(slug, img_url, order_num)
    WHERE p.slug = u.slug;
    
    -- Thêm sample orders để reviews có order_id hợp lệ
    INSERT INTO orders (order_number, user_id, recipient_name, recipient_phone, shipping_address, city, district, ward, subtotal, shipping_fee, discount_amount, total_amount, payment_method, payment_status, status, notes, created_at, updated_at)
    VALUES
    ('ORD-20251120-001', 5, 'Phạm Ngọc Huy', '0987654321', '123 Đường A, Phường 1', 'Hà Nội', 'Ba Đình', 'Phúc Tạn', 21490000, 25000, 0, 21515000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '30 days', CURRENT_TIMESTAMP - INTERVAL '30 days'),
    ('ORD-20251120-002', 6, 'Ngô Thị Lan', '0987654322', '456 Đường B, Phường 2', 'Hà Nội', 'Hoàn Kiếm', 'Tràng Tiền', 19490000, 25000, 0, 19515000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '28 days', CURRENT_TIMESTAMP - INTERVAL '28 days'),
    ('ORD-20251120-003', 7, 'Vũ Đức Minh', '0987654323', '789 Đường C, Phường 3', 'TP.HCM', 'Quận 1', 'Bến Nghé', 34490000, 30000, 0, 34520000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '26 days', CURRENT_TIMESTAMP - INTERVAL '26 days'),
    ('ORD-20251120-004', 5, 'Phạm Ngọc Huy', '0987654321', '123 Đường A, Phường 1', 'Hà Nội', 'Ba Đình', 'Phúc Tạn', 36990000, 30000, 0, 37020000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '25 days', CURRENT_TIMESTAMP - INTERVAL '25 days'),
    ('ORD-20251120-005', 6, 'Ngô Thị Lan', '0987654322', '456 Đường B, Phường 2', 'Hà Nội', 'Hoàn Kiếm', 'Tràng Tiền', 12990000, 20000, 0, 13010000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '24 days', CURRENT_TIMESTAMP - INTERVAL '24 days'),
    ('ORD-20251120-006', 7, 'Vũ Đức Minh', '0987654323', '789 Đường C, Phường 3', 'TP.HCM', 'Quận 1', 'Bến Nghé', 26490000, 25000, 0, 26515000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '23 days', CURRENT_TIMESTAMP - INTERVAL '23 days'),
    ('ORD-20251120-007', 5, 'Phạm Ngọc Huy', '0987654321', '123 Đường A, Phường 1', 'Hà Nội', 'Ba Đình', 'Phúc Tạn', 36990000, 30000, 0, 37020000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '22 days', CURRENT_TIMESTAMP - INTERVAL '22 days'),
    ('ORD-20251120-008', 6, 'Ngô Thị Lan', '0987654322', '456 Đường B, Phường 2', 'Hà Nội', 'Hoàn Kiếm', 'Tràng Tiền', 62990000, 35000, 0, 63025000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '20 days', CURRENT_TIMESTAMP - INTERVAL '20 days'),
    ('ORD-20251120-009', 7, 'Vũ Đức Minh', '0987654323', '789 Đường C, Phường 3', 'TP.HCM', 'Quận 1', 'Bến Nghé', 12990000, 20000, 0, 13010000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '18 days', CURRENT_TIMESTAMP - INTERVAL '18 days'),
    ('ORD-20251120-010', 5, 'Phạm Ngọc Huy', '0987654321', '123 Đường A, Phường 1', 'Hà Nội', 'Ba Đình', 'Phúc Tạn', 12990000, 20000, 0, 13010000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '16 days', CURRENT_TIMESTAMP - INTERVAL '16 days'),
    ('ORD-20251120-011', 5, 'Phạm Ngọc Huy', '0987654321', '123 Đường A, Phường 1', 'Hà Nội', 'Ba Đình', 'Phúc Tạn', 26490000, 25000, 0, 26515000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '15 days', CURRENT_TIMESTAMP - INTERVAL '15 days'),
    ('ORD-20251120-012', 6, 'Ngô Thị Lan', '0987654322', '456 Đường B, Phường 2', 'Hà Nội', 'Hoàn Kiếm', 'Tràng Tiền', 26490000, 25000, 0, 26515000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '14 days', CURRENT_TIMESTAMP - INTERVAL '14 days'),
    ('ORD-20251120-013', 6, 'Ngô Thị Lan', '0987654322', '456 Đường B, Phường 2', 'Hà Nội', 'Hoàn Kiếm', 'Tràng Tiền', 62990000, 35000, 0, 63025000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '13 days', CURRENT_TIMESTAMP - INTERVAL '13 days'),
    ('ORD-20251120-014', 5, 'Phạm Ngọc Huy', '0987654321', '123 Đường A, Phường 1', 'Hà Nội', 'Ba Đình', 'Phúc Tạn', 49990000, 35000, 0, 50025000, 'COD', 'pending', 'confirmed', 'Đơn hàng xác nhận', CURRENT_TIMESTAMP - INTERVAL '12 days', CURRENT_TIMESTAMP - INTERVAL '12 days');

END $$;

-- Thêm reviews để hiển thị rating và nhận xét
-- Lưu ý: order_id phải tương ứng với user_id (order 1=user5, 2=user6, 3=user7, 4=user5, v.v.)
INSERT INTO reviews (product_id, user_id, order_id, rating, comment, status, created_at) 
SELECT p.id, u.user_id, u.order_id, u.rating, u.comment, u.status, u.created_at
FROM products p
CROSS JOIN (
    VALUES
    ('dell-latitude-5430', 5, 1, 5, 'Laptop rất bền, pin lâu, hiệu năng tốt. Đáng mua!', 'approved', CURRENT_TIMESTAMP - INTERVAL '30 days'),
    ('dell-latitude-5430', 6, 2, 4, 'Máy chạy mượt nhưng hơi nóng khi chơi game', 'approved', CURRENT_TIMESTAMP - INTERVAL '25 days'),
    ('dell-latitude-5430', 7, 3, 5, 'Dùng công ty, rất ưng ý. Giao hàng nhanh', 'approved', CURRENT_TIMESTAMP - INTERVAL '20 days'),
    ('hp-victus-15-gaming', 5, 4, 5, 'Chơi game 4K mượt, pin lâu hơn kỳ vọng', 'approved', CURRENT_TIMESTAMP - INTERVAL '28 days'),
    ('hp-victus-15-gaming', 6, 5, 4, 'RTX 3050 đủ dùng cho game, màn hình 144Hz rất mượt', 'approved', CURRENT_TIMESTAMP - INTERVAL '18 days'),
    ('lenovo-legion-5-pro', 7, 6, 5, 'Hiệu năng khủng, chơi game max setting', 'approved', CURRENT_TIMESTAMP - INTERVAL '26 days'),
    ('lenovo-legion-5-pro', 5, 7, 4, 'Máy mạnh nhưng khá nặng, không dễ mang đi', 'approved', CURRENT_TIMESTAMP - INTERVAL '12 days'),
    ('asus-rog-strix-g15', 6, 8, 5, 'Hiệu năng tương ứng với giá, RGB rất đẹp', 'approved', CURRENT_TIMESTAMP - INTERVAL '24 days'),
    ('acer-aspire-5', 7, 9, 4, 'Giá rẻ, hiệu năng ổn định cho học tập', 'approved', CURRENT_TIMESTAMP - INTERVAL '20 days'),
    ('acer-aspire-5', 5, 10, 4, 'SSD nhanh, RAM đủ, nhưng pin hơi yếu', 'approved', CURRENT_TIMESTAMP - INTERVAL '10 days'),
    ('apple-macbook-air-m2-2022', 5, 11, 5, 'Chip M2 mạnh mẽ, pin 18 giờ không hối tiếc', 'approved', CURRENT_TIMESTAMP - INTERVAL '22 days'),
    ('apple-macbook-air-m2-2022', 6, 12, 5, 'Màn hình đẹp, cực nhẹ, hoàn hảo cho sinh viên', 'approved', CURRENT_TIMESTAMP - INTERVAL '15 days'),
    ('msi-ge76-raider', 6, 13, 5, 'Chơi game 4K RTX3080Ti cực sướng, giá hợp lý', 'approved', CURRENT_TIMESTAMP - INTERVAL '23 days'),
    ('razer-blade-15', 5, 14, 5, 'Thiết kế đẹp, hiệu năng khủng, giá tương ứng', 'approved', CURRENT_TIMESTAMP - INTERVAL '19 days')
) AS u(slug, user_id, order_id, rating, comment, status, created_at)
WHERE p.slug = u.slug;

-- Cập nhật rating_average và review_count cho sản phẩm
UPDATE products p
SET 
  rating_average = ROUND(COALESCE((
    SELECT AVG(CAST(rating AS NUMERIC(3,2)))
    FROM reviews r
    WHERE r.product_id = p.id AND r.status = 'approved'
  ), 0), 2),
  review_count = COALESCE((
    SELECT COUNT(*)
    FROM reviews r
    WHERE r.product_id = p.id AND r.status = 'approved'
  ), 0),
  sold_count = CASE 
    WHEN p.slug IN ('dell-latitude-5430', 'hp-victus-15-gaming', 'lenovo-legion-5-pro', 'asus-rog-strix-g15', 'apple-macbook-air-m2-2022', 'msi-ge76-raider', 'razer-blade-15') THEN (p.id % 10) + 8
    ELSE 0
  END,
  views = CASE 
    WHEN p.slug IN ('dell-latitude-5430', 'hp-victus-15-gaming', 'lenovo-legion-5-pro', 'asus-rog-strix-g15', 'apple-macbook-air-m2-2022', 'msi-ge76-raider', 'razer-blade-15') THEN (p.id * 15) + 50
    ELSE p.id * 5
  END;

-- Thêm banners quảng cáo
INSERT INTO banners (title, image, link, display_order, status) VALUES
('Banner Khuyến Mãi Laptop Gaming', 'banners/banner-2.jpg', '/products.php?category=2', 1, 'active'),
('Banner MacBook M2', 'banners/banner-1.jpg', '/product-detail.php?slug=apple-macbook-air-m2-2022', 2, 'active'),
('Banner Laptop Văn Phòng Giá Tốt', 'banners/banner-3.jpg', '/products.php?category=1', 3, 'active');

-- Kết thúc
SELECT 'Đã nhập dữ liệu mẫu thành công!' as message;
SELECT COUNT(*) as total_products FROM products WHERE status = 'active';
SELECT COUNT(*) as total_shops FROM shops WHERE status = 'active';
SELECT COUNT(*) as total_users FROM users;
