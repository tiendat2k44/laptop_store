-- =============================================
-- DỮ LIỆU MẪU - LAPTOP STORE
-- =============================================

-- Thêm shops mẫu
INSERT INTO shops (user_id, shop_name, description, phone, email, status, approved_by, approved_at, created_at) VALUES
(1, 'Tech World Store', 'Cửa hàng chuyên laptop cao cấp', '0901234567', 'techworld@example.com', 'active', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
(1, 'Laptop Pro', 'Laptop gaming và đồ họa chuyên nghiệp', '0909876543', 'laptoppro@example.com', 'active', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
(1, 'Digital Shop', 'Laptop văn phòng giá tốt', '0912345678', 'digitalshop@example.com', 'active', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

-- Thêm sản phẩm mẫu với URL hình ảnh từ các nguồn miễn phí
-- Dell Laptops
INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, thumbnail, featured, status, created_at) VALUES
(1, 1, 1, 'Dell Latitude 5430 - Core i7-1265U, 16GB RAM, 512GB SSD', 'dell-latitude-5430', 
'Laptop Dell Latitude 5430 là dòng laptop doanh nghiệp cao cấp với hiệu năng mạnh mẽ, thiết kế bền bỉ và bảo mật cao. Phù hợp cho công việc văn phòng, lập trình và xử lý đa nhiệm.', 
'Intel Core i7-1265U (10 cores, 12 threads, up to 4.8GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '14 inch FHD (1920x1080)', 'Intel Iris Xe Graphics', '1.37kg', '4-cell 58Wh', 'Windows 11 Pro', 22990000, 21490000, 15, 'assets/uploads/products/dell-latitude-5430.jpg', true, 'active', CURRENT_TIMESTAMP),

(1, 1, 1, 'Dell Inspiron 15 3520 - Core i5-1235U, 8GB RAM, 256GB SSD', 'dell-inspiron-15-3520',
'Laptop Dell Inspiron 15 3520 với thiết kế thanh lịch, hiệu năng ổn định cho công việc văn phòng và học tập. Màn hình 15.6 inch cho trải nghiệm làm việc thoải mái.',
'Intel Core i5-1235U (10 cores, up to 4.4GHz)', '8GB DDR4 2666MHz', '256GB SSD NVMe', '15.6 inch FHD', 'Intel UHD Graphics', '1.85kg', '3-cell 41Wh', 'Windows 11 Home', 14990000, 13990000, 25, 'assets/uploads/products/dell-inspiron-15-3520.jpg', false, 'active', CURRENT_TIMESTAMP),

-- HP Laptops
(1, 1, 2, 'HP EliteBook 840 G9 - Core i7-1255U, 16GB RAM, 512GB SSD', 'hp-elitebook-840-g9',
'HP EliteBook 840 G9 là laptop cao cấp dành cho doanh nghiệp với bảo mật vượt trội, thiết kế mỏng nhẹ và hiệu năng mạnh mẽ. Màn hình 14 inch sắc nét.',
'Intel Core i7-1255U (10 cores, up to 4.7GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '14 inch FHD IPS', 'Intel Iris Xe Graphics', '1.32kg', '3-cell 51Wh', 'Windows 11 Pro', 26990000, 25490000, 12, 'assets/uploads/products/hp-elitebook-840-g9.jpg', true, 'active', CURRENT_TIMESTAMP),

(2, 2, 2, 'HP Victus 15 Gaming - Core i5-12450H, RTX 3050, 8GB RAM', 'hp-victus-15-gaming',
'HP Victus 15 là laptop gaming giá tốt với card đồ họa RTX 3050, hiệu năng mạnh mẽ cho game và đồ họa. Màn hình 15.6 inch 144Hz mượt mà.',
'Intel Core i5-12450H (8 cores, up to 4.4GHz)', '8GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD 144Hz', 'NVIDIA RTX 3050 4GB', '2.29kg', '4-cell 70Wh', 'Windows 11 Home', 20990000, 19490000, 18, 'assets/uploads/products/hp-victus-15.jpg', true, 'active', CURRENT_TIMESTAMP),

-- Lenovo Laptops
(2, 1, 3, 'Lenovo ThinkPad X1 Carbon Gen 10 - Core i7-1260P, 16GB RAM', 'lenovo-thinkpad-x1-carbon-gen10',
'Lenovo ThinkPad X1 Carbon Gen 10 là ultrabook cao cấp nhất với trọng lượng siêu nhẹ, bàn phím tuyệt vời và thời lượng pin lâu. Lý tưởng cho doanh nhân.',
'Intel Core i7-1260P (12 cores, up to 4.7GHz)', '16GB LPDDR5', '512GB SSD NVMe', '14 inch WUXGA IPS', 'Intel Iris Xe Graphics', '1.12kg', '4-cell 57Wh', 'Windows 11 Pro', 35990000, 33990000, 8, 'assets/uploads/products/lenovo-x1-carbon-gen10.jpg', true, 'active', CURRENT_TIMESTAMP),

(2, 2, 3, 'Lenovo Legion 5 Pro - Ryzen 7 6800H, RTX 3070Ti, 16GB RAM', 'lenovo-legion-5-pro',
'Lenovo Legion 5 Pro là laptop gaming cao cấp với màn hình 16 inch QHD+ 165Hz, RTX 3070Ti cho trải nghiệm gaming đỉnh cao và thiết kế đẹp mắt.',
'AMD Ryzen 7 6800H (8 cores, 16 threads, up to 4.7GHz)', '16GB DDR5 4800MHz', '512GB SSD NVMe', '16 inch WQXGA 165Hz', 'NVIDIA RTX 3070Ti 8GB', '2.5kg', '4-cell 80Wh', 'Windows 11 Home', 36990000, 34490000, 10, 'assets/uploads/products/lenovo-legion-5-pro.jpg', true, 'active', CURRENT_TIMESTAMP),

-- ASUS Laptops
(2, 2, 4, 'ASUS ROG Strix G15 - Ryzen 9 6900HX, RTX 3070, 16GB RAM', 'asus-rog-strix-g15',
'ASUS ROG Strix G15 là laptop gaming mạnh mẽ với Ryzen 9 6900HX, RTX 3070 và màn hình 300Hz cho trải nghiệm gaming cực đỉnh. Thiết kế RGB ấn tượng.',
'AMD Ryzen 9 6900HX (8 cores, 16 threads, up to 4.9GHz)', '16GB DDR5 4800MHz', '1TB SSD NVMe', '15.6 inch FHD 300Hz', 'NVIDIA RTX 3070 8GB', '2.3kg', '90Wh', 'Windows 11 Home', 38990000, 36990000, 7, 'assets/uploads/products/asus-rog-strix-g15.jpg', true, 'active', CURRENT_TIMESTAMP),

(3, 4, 4, 'ASUS ZenBook 14 OLED - Core i5-1240P, 8GB RAM, 512GB SSD', 'asus-zenbook-14-oled',
'ASUS ZenBook 14 OLED với màn hình OLED tuyệt đẹp, thiết kế mỏng nhẹ cao cấp và hiệu năng tốt. Hoàn hảo cho công việc sáng tạo và di động.',
'Intel Core i5-1240P (12 cores, up to 4.4GHz)', '8GB LPDDR5', '512GB SSD NVMe', '14 inch 2.8K OLED', 'Intel Iris Xe Graphics', '1.39kg', '75Wh', 'Windows 11 Home', 21990000, 20490000, 20, 'assets/uploads/products/asus-zenbook-14-oled.jpg', false, 'active', CURRENT_TIMESTAMP),

-- Acer Laptops
(3, 2, 5, 'Acer Predator Helios 300 - Core i7-12700H, RTX 3060, 16GB RAM', 'acer-predator-helios-300',
'Acer Predator Helios 300 là laptop gaming cân bằng giữa hiệu năng và giá cả với RTX 3060, màn hình 144Hz và hệ thống tản nhiệt tốt.',
'Intel Core i7-12700H (14 cores, up to 4.7GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD 144Hz', 'NVIDIA RTX 3060 6GB', '2.5kg', '4-cell 59Wh', 'Windows 11 Home', 28990000, 26990000, 15, 'assets/uploads/products/acer-predator-helios-300.jpg', true, 'active', CURRENT_TIMESTAMP),

(3, 1, 5, 'Acer Aspire 5 - Core i5-1235U, 8GB RAM, 512GB SSD', 'acer-aspire-5',
'Acer Aspire 5 là laptop phổ thông với cấu hình tốt, giá cả hợp lý cho học sinh, sinh viên và văn phòng. Màn hình 15.6 inch FHD sắc nét.',
'Intel Core i5-1235U (10 cores, up to 4.4GHz)', '8GB DDR4 2666MHz', '512GB SSD NVMe', '15.6 inch FHD', 'Intel UHD Graphics', '1.8kg', '3-cell 48Wh', 'Windows 11 Home', 13990000, 12990000, 30, 'assets/uploads/products/acer-aspire-5.jpg', false, 'active', CURRENT_TIMESTAMP),

-- Apple MacBook
(1, 4, 6, 'Apple MacBook Air M2 2022 - 8GB RAM, 256GB SSD', 'apple-macbook-air-m2-2022',
'MacBook Air M2 với thiết kế mới, chip M2 mạnh mẽ, màn hình Liquid Retina và thời lượng pin lên đến 18 giờ. Siêu mỏng nhẹ chỉ 1.24kg.',
'Apple M2 (8-core CPU, up to 3.49GHz)', '8GB Unified Memory', '256GB SSD', '13.6 inch Liquid Retina', 'Apple M2 GPU 8-core', '1.24kg', 'Up to 18 hours', 'macOS Ventura', 27990000, 26490000, 12, 'assets/uploads/products/macbook-air-m2.jpg', true, 'active', CURRENT_TIMESTAMP),

(1, 4, 6, 'Apple MacBook Pro 14 M2 Pro - 16GB RAM, 512GB SSD', 'apple-macbook-pro-14-m2-pro',
'MacBook Pro 14 inch với chip M2 Pro cho hiệu năng chuyên nghiệp, màn hình Liquid Retina XDR tuyệt đẹp và hệ thống âm thanh 6 loa đỉnh cao.',
'Apple M2 Pro (10-core CPU)', '16GB Unified Memory', '512GB SSD', '14.2 inch Liquid Retina XDR', 'Apple M2 Pro GPU 16-core', '1.6kg', 'Up to 17 hours', 'macOS Ventura', 49990000, 47990000, 6, 'assets/uploads/products/macbook-pro-14-m2.jpg', true, 'active', CURRENT_TIMESTAMP),

-- MSI Gaming Laptops
(2, 2, 7, 'MSI Katana GF66 - Core i7-12650H, RTX 3060, 16GB RAM', 'msi-katana-gf66',
'MSI Katana GF66 là laptop gaming với thiết kế lấy cảm hứng từ katana Nhật Bản, RTX 3060 và màn hình 144Hz cho trải nghiệm gaming mượt mà.',
'Intel Core i7-12650H (10 cores, up to 4.7GHz)', '16GB DDR4 3200MHz', '512GB SSD NVMe', '15.6 inch FHD 144Hz', 'NVIDIA RTX 3060 6GB', '2.25kg', '53.5Wh', 'Windows 11 Home', 25990000, 24490000, 13, 'assets/uploads/products/msi-katana-gf66.jpg', false, 'active', CURRENT_TIMESTAMP),

(2, 2, 7, 'MSI GE76 Raider - Core i9-12900HK, RTX 3080Ti, 32GB RAM', 'msi-ge76-raider',
'MSI GE76 Raider là laptop gaming cao cấp nhất với Core i9-12900HK, RTX 3080Ti và RGB Mystic Light Bar. Hiệu năng khủng cho mọi tác vụ.',
'Intel Core i9-12900HK (14 cores, up to 5.0GHz)', '32GB DDR5 4800MHz', '1TB SSD NVMe', '17.3 inch FHD 360Hz', 'NVIDIA RTX 3080Ti 16GB', '2.9kg', '99.9Wh', 'Windows 11 Pro', 68990000, 64990000, 3, 'assets/uploads/products/msi-ge76-raider.jpg', true, 'active', CURRENT_TIMESTAMP),

-- Razer Laptops
(2, 2, 8, 'Razer Blade 15 - Core i7-12800H, RTX 3070Ti, 16GB RAM', 'razer-blade-15',
'Razer Blade 15 với thiết kế CNC nguyên khối sang trọng, màn hình QHD 240Hz và RGB Chroma. Laptop gaming cao cấp đẳng cấp thế giới.',
'Intel Core i7-12800H (14 cores, up to 4.8GHz)', '16GB DDR5 4800MHz', '1TB SSD NVMe', '15.6 inch QHD 240Hz', 'NVIDIA RTX 3070Ti 8GB', '2.01kg', '80Wh', 'Windows 11 Home', 52990000, 49990000, 5, 'assets/uploads/products/razer-blade-15.jpg', true, 'active', CURRENT_TIMESTAMP);

-- =============================================
-- DỮ LIỆU MẪU - LAPTOP STORE (ĐÃ CẬP NHẬT)
-- =============================================

-- Lưu ý: File này chỉ chứa phần INSERT hình ảnh và banners
-- Phần INSERT products giữ nguyên từ sample_data.sql

-- XÓA DỮ LIỆU CŨ (nếu cần)
-- TRUNCATE TABLE product_images, banners CASCADE;

-- =============================================
-- THÊM HÌNH ẢNH SẢN PHẨM (khớp với tên file thực tế)
-- =============================================
INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES
-- Sản phẩm 1: Dell Latitude 5430
(1, 'assets/uploads/products/dell-latitude-5430.jpg', true, 1),

-- Sản phẩm 2: Dell Inspiron 15 3520
(2, 'assets/uploads/products/dell-inspiron-15-3520.jpg', true, 1),

-- Sản phẩm 3: HP EliteBook 840 G9
(3, 'assets/uploads/products/hp-elitebook-840-g9.jpg', true, 1),

-- Sản phẩm 4: HP Victus 15
(4, 'assets/uploads/products/hp-victus-15.jpg', true, 1),

-- Sản phẩm 5: Lenovo X1 Carbon Gen 10
(5, 'assets/uploads/products/lenovo-x1-carbon-gen10.jpg', true, 1),

-- Sản phẩm 6: Lenovo Legion 5 Pro
(6, 'assets/uploads/products/lenovo-legion-5-pro.jpg', true, 1),

-- Sản phẩm 7: ASUS ROG Strix G15
(7, 'assets/uploads/products/asus-rog-strix-g15.jpg', true, 1),

-- Sản phẩm 8: ASUS ZenBook 14 OLED
(8, 'assets/uploads/products/asus-zenbook-14-oled.jpg', true, 1),

-- Sản phẩm 9: Acer Predator Helios 300
(9, 'assets/uploads/products/acer-predator-helios-300.jpg', true, 1),

-- Sản phẩm 10: Acer Aspire 5
(10, 'assets/uploads/products/acer-aspire-5.jpg', true, 1),

-- Sản phẩm 11: MacBook Air M2
(11, 'assets/uploads/products/macbook-air-m2.jpg', true, 1),

-- Sản phẩm 12: MacBook Pro 14 M2
(12, 'assets/uploads/products/macbook-pro-14-m2.jpg', true, 1),

-- Sản phẩm 13: MSI Katana GF66
(13, 'assets/uploads/products/msi-katana-gf66.jpg', true, 1),

-- Sản phẩm 14: MSI GE76 Raider
(14, 'assets/uploads/products/msi-ge76-raider.jpg', true, 1),

-- Sản phẩm 15: Razer Blade 15
(15, 'assets/uploads/products/razer-blade-15.jpg', true, 1);

-- =============================================
-- THÊM BANNERS (khớp với tên file thực tế)
-- =============================================
INSERT INTO banners (title, image, link, display_order, status) VALUES
('Khuyến mãi cuối năm - Giảm đến 30%', 'assets/uploads/banners/banner-1.jpg', '/products.php?sale=1', 1, 'active'),
('Laptop Gaming RTX 40 Series mới nhất', 'assets/uploads/banners/banner-2.jpg', '/products.php?category=2', 2, 'active'),
('MacBook Air M2 - Mỏng nhẹ đỉnh cao', 'assets/uploads/banners/banner-3.jpg', '/products.php?brand=6', 3, 'active');

-- =============================================
-- HOÀN TẤT
-- =============================================
