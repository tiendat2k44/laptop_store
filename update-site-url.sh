#!/bin/bash

# Script cập nhật SITE_URL nhanh cho Ngrok
# Tự động lấy thư mục script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"


echo "🔧 Công cụ cập nhật SITE_URL cho Ngrok"
echo ""

CONFIG_FILE="$SCRIPT_DIR/includes/config/config.php"

# Kiểm tra file tồn tại
if [ ! -f "$CONFIG_FILE" ]; then
    echo "❌ Không tìm thấy file config.php"
    exit 1
fi

# Lấy URL hiện tại
CURRENT_URL=$(grep "define('SITE_URL'" "$CONFIG_FILE" | sed -n "s/.*'\(.*\)'.*/\1/p")
echo "📍 URL hiện tại: $CURRENT_URL"
echo ""

# Hỏi người dùng
echo "Chọn hành động:"
echo "1. Nhập URL Ngrok thủ công"
echo "2. Đặt lại về localhost:8000"
echo "3. Thoát"
echo ""
read -p "Nhập lựa chọn (1-3): " choice

case $choice in
    1)
        read -p "Nhập URL Ngrok (VD: https://abc123.ngrok-free.app): " ngrok_url
        # Xóa dấu / ở cuối nếu có
        ngrok_url=$(echo "$ngrok_url" | sed 's:/*$::')
        
        # Backup file cũ
        cp "$CONFIG_FILE" "${CONFIG_FILE}.backup"
        
        # Thay thế URL
        sed -i "s|define('SITE_URL', '.*')|define('SITE_URL', '$ngrok_url')|g" "$CONFIG_FILE"
        
        echo ""
        echo "✅ Đã cập nhật SITE_URL thành: $ngrok_url"
        echo "📋 File backup: ${CONFIG_FILE}.backup"
        ;;
    2)
        # Backup file cũ
        cp "$CONFIG_FILE" "${CONFIG_FILE}.backup"
        
        # Thay thế về localhost
        sed -i "s|define('SITE_URL', '.*')|define('SITE_URL', 'http://localhost:8000')|g" "$CONFIG_FILE"
        
        echo ""
        echo "✅ Đã đặt lại SITE_URL về: http://localhost:8000"
        echo "📋 File backup: ${CONFIG_FILE}.backup"
        ;;
    3)
        echo "👋 Thoát"
        exit 0
        ;;
    *)
        echo "❌ Lựa chọn không hợp lệ"
        exit 1
        ;;
esac

echo ""
echo "🔄 Để áp dụng thay đổi, vui lòng:"
echo "   - Refresh trình duyệt"
echo "   - Hoặc restart PHP server"
