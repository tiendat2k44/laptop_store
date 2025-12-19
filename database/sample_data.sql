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

-- Tạo 3 shop owners với mật khẩu: 123456
INSERT INTO users (role_id, email, password_hash, full_name, phone, status, email_verified, created_at)
VALUES 
(2, 'shop1@laptopstore.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Nguyễn Văn A - Shop Owner', '0901234567', 'active', true, CURRENT_TIMESTAMP),
(2, 'shop2@laptopstore.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Trần Thị B - Shop Owner', '0909876543', 'active', true, CURRENT_TIMESTAMP),
(2, 'shop3@laptopstore.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Lê Văn C - Shop Owner', '0912345678', 'active', true, CURRENT_TIMESTAMP);

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
    'Intel Core i7-1265U (10 cores, 12 threads, up to 4.8GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '14 inch FHD (1920x1080)', 'Intel Iris Xe Graphics', '1.37kg', '4-cell 58Wh', 'Windows 11 Pro', 22990000, 21490000, 15, 'products/dell-latitude-5430.jpg', true, 'active'),
    
    (shop1_id, 1, 1, 'Dell Inspiron 15 3520 - Core i5-1235U, 8GB RAM, 256GB SSD', 'dell-inspiron-15-3520',
    'Laptop Dell Inspiron 15 3520 với thiết kế thanh lịch, hiệu năng ổn định cho công việc văn phòng và học tập. Màn hình 15.6 inch cho trải nghiệm làm việc thoải mái.',
    'Intel Core i5-1235U (10 cores, up to 4.4GHz)', '8GB DDR4 2666MHz', '256GB SSD NVMe', '15.6 inch FHD', 'Intel UHD Graphics', '1.85kg', '3-cell 41Wh', 'Windows 11 Home', 14990000, 13990000, 25, 'products/dell-inspiron-15.jpg', false, 'active');
    
    -- Thêm sản phẩm HP
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop1_id, 1, 2, 'HP EliteBook 840 G9 - Core i7-1255U, 16GB RAM, 512GB SSD', 'hp-elitebook-840-g9',
    'HP EliteBook 840 G9 là laptop cao cấp dành cho doanh nghiệp với bảo mật vượt trội, thiết kế mỏng nhẹ và hiệu năng mạnh mẽ. Màn hình 14 inch sắc nét.',
    'Intel Core i7-1255U (10 cores, up to 4.7GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '14 inch FHD IPS', 'Intel Iris Xe Graphics', '1.32kg', '3-cell 51Wh', 'Windows 11 Pro', 26990000, 25490000, 12, 'products/hp-elitebook-840.jpg', true, 'active'),
    
    (shop2_id, 2, 2, 'HP Victus 15 Gaming - Core i5-12450H, RTX 3050, 8GB RAM', 'hp-victus-15-gaming',
    'HP Victus 15 là laptop gaming giá tốt với card đồ họa RTX 3050, hiệu năng mạnh mẽ cho game và đồ họa. Màn hình 15.6 inch 144Hz mượt mà.',
    'Intel Core i5-12450H (8 cores, up to 4.4GHz)', '8GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD 144Hz', 'NVIDIA RTX 3050 4GB', '2.29kg', '4-cell 70Wh', 'Windows 11 Home', 20990000, 19490000, 18, 'products/hp-victus-15.jpg', true, 'active');
    
    -- Thêm sản phẩm Lenovo
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop2_id, 1, 3, 'Lenovo ThinkPad X1 Carbon Gen 10 - Core i7-1260P, 16GB RAM', 'lenovo-thinkpad-x1-carbon-gen10',
    'Lenovo ThinkPad X1 Carbon Gen 10 là ultrabook cao cấp nhất với trọng lượng siêu nhẹ, bàn phím tuyệt vời và thời lượng pin lâu. Lý tưởng cho doanh nhân.',
    'Intel Core i7-1260P (12 cores, up to 4.7GHz)', '16GB LPDDR5', '512GB SSD NVMe', '14 inch WUXGA IPS', 'Intel Iris Xe Graphics', '1.12kg', '4-cell 57Wh', 'Windows 11 Pro', 35990000, 33990000, 8, 'products/lenovo-x1-carbon.jpg', true, 'active'),
    
    (shop2_id, 2, 3, 'Lenovo Legion 5 Pro - Ryzen 7 6800H, RTX 3070Ti, 16GB RAM', 'lenovo-legion-5-pro',
    'Lenovo Legion 5 Pro là laptop gaming cao cấp với màn hình 16 inch QHD+ 165Hz, RTX 3070Ti cho trải nghiệm gaming đỉnh cao và thiết kế đẹp mắt.',
    'AMD Ryzen 7 6800H (8 cores, 16 threads, up to 4.7GHz)', '16GB DDR5 4800MHz', '512GB SSD NVMe', '16 inch WQXGA 165Hz', 'NVIDIA RTX 3070Ti 8GB', '2.5kg', '4-cell 80Wh', 'Windows 11 Home', 36990000, 34490000, 10, 'products/lenovo-legion-5-pro.jpg', true, 'active');
    
    -- Thêm sản phẩm ASUS
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop2_id, 2, 4, 'ASUS ROG Strix G15 - Ryzen 9 6900HX, RTX 3070, 16GB RAM', 'asus-rog-strix-g15',
    'ASUS ROG Strix G15 là laptop gaming mạnh mẽ với Ryzen 9 6900HX, RTX 3070 và màn hình 300Hz cho trải nghiệm gaming cực đỉnh. Thiết kế RGB ấn tượng.',
    'AMD Ryzen 9 6900HX (8 cores, 16 threads, up to 4.9GHz)', '16GB DDR5 4800MHz', '1TB SSD NVMe', '15.6 inch FHD 300Hz', 'NVIDIA RTX 3070 8GB', '2.3kg', '90Wh', 'Windows 11 Home', 38990000, 36990000, 7, 'products/asus-rog-strix-g15.jpg', true, 'active'),
    
    (shop3_id, 4, 4, 'ASUS ZenBook 14 OLED - Core i5-1240P, 8GB RAM, 512GB SSD', 'asus-zenbook-14-oled',
    'ASUS ZenBook 14 OLED với màn hình OLED tuyệt đẹp, thiết kế mỏng nhẹ cao cấp và hiệu năng tốt. Hoàn hảo cho công việc sáng tạo và di động.',
    'Intel Core i5-1240P (12 cores, up to 4.4GHz)', '8GB LPDDR5', '512GB SSD NVMe', '14 inch 2.8K OLED', 'Intel Iris Xe Graphics', '1.39kg', '75Wh', 'Windows 11 Home', 21990000, 20490000, 20, 'products/asus-zenbook-14-oled.jpg', false, 'active');
    
    -- Thêm sản phẩm Acer
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop3_id, 2, 5, 'Acer Predator Helios 300 - Core i7-12700H, RTX 3060, 16GB RAM', 'acer-predator-helios-300',
    'Acer Predator Helios 300 là laptop gaming cân bằng giữa hiệu năng và giá cả với RTX 3060, màn hình 144Hz và hệ thống tản nhiệt tốt.',
    'Intel Core i7-12700H (14 cores, up to 4.7GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD 144Hz', 'NVIDIA RTX 3060 6GB', '2.5kg', '4-cell 59Wh', 'Windows 11 Home', 28990000, 26990000, 15, 'products/acer-predator-helios-300.jpg', true, 'active'),
    
    (shop3_id, 1, 5, 'Acer Aspire 5 - Core i5-1235U, 8GB RAM, 512GB SSD', 'acer-aspire-5',
    'Acer Aspire 5 là laptop phổ thông với cấu hình tốt, giá cả hợp lý cho học sinh, sinh viên và văn phòng. Màn hình 15.6 inch FHD sắc nét.',
    'Intel Core i5-1235U (10 cores, up to 4.4GHz)', '8GB DDR4 2666MHz', '512GB SSD NVMe', '15.6 inch FHD', 'Intel UHD Graphics', '1.8kg', '3-cell 48Wh', 'Windows 11 Home', 13990000, 12990000, 30, 'products/acer-aspire-5.jpg', false, 'active');
    
    -- Thêm sản phẩm Apple MacBook
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop1_id, 4, 6, 'Apple MacBook Air M2 2022 - 8GB RAM, 256GB SSD', 'apple-macbook-air-m2-2022',
    'MacBook Air M2 với thiết kế mới, chip M2 mạnh mẽ, màn hình Liquid Retina và thời lượng pin lên đến 18 giờ. Siêu mỏng nhẹ chỉ 1.24kg.',
    'Apple M2 (8-core CPU, up to 3.49GHz)', '8GB Unified Memory', '256GB SSD', '13.6 inch Liquid Retina', 'Apple M2 GPU 8-core', '1.24kg', 'Up to 18 hours', 'macOS Ventura', 27990000, 26490000, 12, 'products/macbook-air-m2.jpg', true, 'active'),
    
    (shop1_id, 4, 6, 'Apple MacBook Pro 14 M2 Pro - 16GB RAM, 512GB SSD', 'apple-macbook-pro-14-m2-pro',
    'MacBook Pro 14 inch với chip M2 Pro cho hiệu năng chuyên nghiệp, màn hình Liquid Retina XDR tuyệt đẹp và hệ thống âm thanh 6 loa đỉnh cao.',
    'Apple M2 Pro (10-core CPU)', '16GB Unified Memory', '512GB SSD', '14.2 inch Liquid Retina XDR', 'Apple M2 Pro GPU 16-core', '1.6kg', 'Up to 17 hours', 'macOS Ventura', 49990000, 47990000, 6, 'products/macbook-pro-14-m2.jpg', true, 'active');
    
    -- Thêm sản phẩm MSI
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop2_id, 2, 7, 'MSI Katana GF66 - Core i7-12650H, RTX 3060, 16GB RAM', 'msi-katana-gf66',
    'MSI Katana GF66 là laptop gaming với thiết kế lấy cảm hứng từ katana Nhật Bản, RTX 3060 và màn hình 144Hz cho trải nghiệm gaming mượt mà.',
    'Intel Core i7-12650H (10 cores, up to 4.7GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD 144Hz', 'NVIDIA RTX 3060 6GB', '2.25kg', '53.5Wh', 'Windows 11 Home', 25990000, 24490000, 13, 'products/msi-katana-gf66.jpg', false, 'active'),
    
    (shop2_id, 2, 7, 'MSI GE76 Raider - Core i9-12900HK, RTX 3080Ti, 32GB RAM', 'msi-ge76-raider',
    'MSI GE76 Raider là laptop gaming cao cấp nhất với Core i9-12900HK, RTX 3080Ti và RGB Mystic Light Bar. Hiệu năng khủng cho mọi tác vụ.',
    'Intel Core i9-12900HK (14 cores, up to 5.0GHz)', '32GB DDR5 4800MHz', '1TB SSD NVMe', '17.3 inch FHD 360Hz', 'NVIDIA RTX 3080Ti 16GB', '2.9kg', '99.9Wh', 'Windows 11 Pro', 65990000, 62990000, 4, 'products/msi-ge76-raider.jpg', true, 'active');
    
    -- Thêm sản phẩm Razer
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop3_id, 2, 8, 'Razer Blade 15 - Core i7-12800H, RTX 3070Ti, 16GB RAM', 'razer-blade-15',
    'Razer Blade 15 là laptop gaming mỏng nhẹ cao cấp với thiết kế kim loại nguyên khối, RGB Chroma và hiệu năng mạnh mẽ. Perfect cho gamers và creators.',
    'Intel Core i7-12800H (14 cores, up to 4.8GHz)', '16GB DDR5 4800MHz', '1TB SSD NVMe', '15.6 inch QHD 240Hz', 'NVIDIA RTX 3070Ti 8GB', '2.01kg', '80Wh', 'Windows 11 Home', 52990000, 49990000, 6, 'products/razer-blade-15.jpg', true, 'active');
    
    -- Thêm thêm một số sản phẩm để đủ 20 sản phẩm
    INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status) VALUES
    (shop1_id, 1, 1, 'Dell Vostro 3510 - Core i3-1115G4, 4GB RAM, 256GB SSD', 'dell-vostro-3510',
    'Dell Vostro 3510 là laptop văn phòng giá rẻ với cấu hình đủ dùng cho công việc cơ bản, lướt web và văn phòng. Giá cả phải chăng.',
    'Intel Core i3-1115G4 (2 cores, up to 4.1GHz)', '4GB DDR4 2666MHz', '256GB SSD', '15.6 inch HD', 'Intel UHD Graphics', '1.69kg', '3-cell 42Wh', 'Windows 11 Home', 9990000, 8990000, 40, 'products/dell-vostro-3510.jpg', false, 'active'),
    
    (shop2_id, 3, 3, 'Lenovo ThinkBook 15 G4 - Core i5-1235U, 8GB RAM, 512GB SSD', 'lenovo-thinkbook-15-g4',
    'Lenovo ThinkBook 15 G4 là laptop cho doanh nghiệp vừa và nhỏ với thiết kế hiện đại, bảo mật tốt và hiệu năng ổn định.',
    'Intel Core i5-1235U (10 cores, up to 4.4GHz)', '8GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD', 'Intel Iris Xe Graphics', '1.7kg', '3-cell 45Wh', 'Windows 11 Pro', 16990000, 15490000, 22, 'products/lenovo-thinkbook-15.jpg', false, 'active'),
    
    (shop3_id, 3, 4, 'ASUS Vivobook 15 OLED - Core i5-12500H, 8GB RAM, 512GB SSD', 'asus-vivobook-15-oled',
    'ASUS Vivobook 15 OLED với màn hình OLED tuyệt đẹp, hiệu năng tốt và giá cả hợp lý. Phù hợp cho sinh viên và văn phòng.',
    'Intel Core i5-12500H (12 cores, up to 4.5GHz)', '8GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD OLED', 'Intel Iris Xe Graphics', '1.7kg', '42Wh', 'Windows 11 Home', 17990000, 16490000, 28, 'products/asus-vivobook-15-oled.jpg', true, 'active'),
    
    (shop1_id, 5, 2, 'HP ZBook Firefly 15 G9 - Core i7-1265U, 16GB RAM, 512GB SSD', 'hp-zbook-firefly-15-g9',
    'HP ZBook Firefly 15 G9 là workstation mỏng nhẹ cho công việc chuyên nghiệp, thiết kế đồ họa và kỹ thuật. Cấu hình mạnh mẽ với card đồ họa chuyên dụng.',
    'Intel Core i7-1265U (10 cores, up to 4.8GHz)', '16GB DDR5 4800MHz', '512GB SSD NVMe', '15.6 inch FHD IPS', 'NVIDIA T550 4GB', '1.74kg', '56Wh', 'Windows 11 Pro', 34990000, 32990000, 5, 'products/hp-zbook-firefly-15.jpg', false, 'active');
    
END $$;

-- Thêm banners quảng cáo
INSERT INTO banners (title, image, link, display_order, status) VALUES
('Banner Khuyến Mãi Laptop Gaming', 'banners/gaming-sale.jpg', '/products.php?category=2', 1, 'active'),
('Banner MacBook M2', 'banners/macbook-m2.jpg', '/product-detail.php?slug=apple-macbook-air-m2-2022', 2, 'active'),
('Banner Laptop Văn Phòng Giá Tốt', 'banners/office-laptop.jpg', '/products.php?category=1', 3, 'active');

-- Kết thúc
SELECT 'Đã nhập dữ liệu mẫu thành công!' as message;
SELECT COUNT(*) as total_products FROM products WHERE status = 'active';
SELECT COUNT(*) as total_shops FROM shops WHERE status = 'active';
SELECT COUNT(*) as total_users FROM users;
