#!/bin/bash

# Script tải hình ảnh mẫu cho laptop store
# Sử dụng placeholder images từ các nguồn miễn phí

echo "Đang tạo thư mục cho hình ảnh..."

# Tạo thư mục
mkdir -p /workspaces/laptop_store/assets/uploads/products
mkdir -p /workspaces/laptop_store/assets/uploads/banners
mkdir -p /workspaces/laptop_store/assets/uploads/shops

echo "Đang tải hình ảnh laptop mẫu..."

# Tải hình ảnh laptop từ placeholder.com hoặc picsum.photos
# Sử dụng laptop placeholder images

# Dell laptops
curl -o /workspaces/laptop_store/assets/uploads/products/dell-latitude-5430.jpg "https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=800&h=600&fit=crop"
curl -o /workspaces/laptop_store/assets/uploads/products/dell-inspiron-15-3520.jpg "https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=800&h=600&fit=crop"

# HP laptops
curl -o /workspaces/laptop_store/assets/uploads/products/hp-elitebook-840-g9.jpg "https://images.unsplash.com/photo-1589561253898-768105ca91a8?w=800&h=600&fit=crop"
curl -o /workspaces/laptop_store/assets/uploads/products/hp-victus-15.jpg "https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=800&h=600&fit=crop"

# Lenovo laptops
curl -o /workspaces/laptop_store/assets/uploads/products/lenovo-x1-carbon-gen10.jpg "https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=800&h=600&fit=crop&q=80"
curl -o /workspaces/laptop_store/assets/uploads/products/lenovo-legion-5-pro.jpg "https://images.unsplash.com/photo-1625842268584-8f3296236761?w=800&h=600&fit=crop"

# ASUS laptops
curl -o /workspaces/laptop_store/assets/uploads/products/asus-rog-strix-g15.jpg "https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=800&h=600&fit=crop&q=90"
curl -o /workspaces/laptop_store/assets/uploads/products/asus-zenbook-14-oled.jpg "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&h=600&fit=crop"

# Acer laptops
curl -o /workspaces/laptop_store/assets/uploads/products/acer-predator-helios-300.jpg "https://images.unsplash.com/photo-1625842268584-8f3296236761?w=800&h=600&fit=crop&q=95"
curl -o /workspaces/laptop_store/assets/uploads/products/acer-aspire-5.jpg "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&h=600&fit=crop"

# Apple MacBook
curl -o /workspaces/laptop_store/assets/uploads/products/macbook-air-m2.jpg "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&h=600&fit=crop&q=90"
curl -o /workspaces/laptop_store/assets/uploads/products/macbook-pro-14-m2.jpg "https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=800&h=600&fit=crop"

# MSI laptops
curl -o /workspaces/laptop_store/assets/uploads/products/msi-katana-gf66.jpg "https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=800&h=600&fit=crop&q=85"
curl -o /workspaces/laptop_store/assets/uploads/products/msi-ge76-raider.jpg "https://images.unsplash.com/photo-1625842268584-8f3296236761?w=800&h=600&fit=crop&q=85"

# Razer laptop
curl -o /workspaces/laptop_store/assets/uploads/products/razer-blade-15.jpg "https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=800&h=600&fit=crop&q=95"

# Banners
curl -o /workspaces/laptop_store/assets/uploads/banners/banner-1.jpg "https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=1400&h=500&fit=crop"
curl -o /workspaces/laptop_store/assets/uploads/banners/banner-2.jpg "https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=1400&h=500&fit=crop"
curl -o /workspaces/laptop_store/assets/uploads/banners/banner-3.jpg "https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=1400&h=500&fit=crop"

echo "✅ Hoàn tất tải hình ảnh!"
echo "Tổng số file đã tải: $(ls -1 /workspaces/laptop_store/assets/uploads/products/*.jpg | wc -l) sản phẩm"
echo "Banner: $(ls -1 /workspaces/laptop_store/assets/uploads/banners/*.jpg | wc -l) ảnh"
