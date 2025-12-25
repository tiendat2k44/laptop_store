#!/bin/bash

# Script khởi động website với Ngrok
# Chia sẻ localhost ra internet để demo/test
# Tự động lấy thư mục script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"


echo "🚀 Đang khởi động PHP Built-in Server và Ngrok..."
echo ""

# Màu sắc cho output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Xác định đường dẫn ngrok (ưu tiên ngrok trên PATH, fallback C:\ngrok\ngrok.exe)
NGROK_BIN="ngrok"
if ! command -v ngrok >/dev/null 2>&1; then
    if [ -x "/c/ngrok/ngrok.exe" ]; then
        NGROK_BIN="/c/ngrok/ngrok.exe"
    elif [ -x "C:/ngrok/ngrok.exe" ]; then
        NGROK_BIN="C:/ngrok/ngrok.exe"
    else
        echo -e "${RED}❌ Không tìm thấy lệnh ngrok${NC}"
        echo "Hướng dẫn cài nhanh:"
        echo "1) Giải nén ngrok vào C:/ngrok (đã có sẵn C:/ngrok/ngrok.exe của bạn)"
        echo "2) Thêm C:/ngrok vào PATH hoặc chạy bằng Git Bash đường dẫn /c/ngrok/ngrok.exe"
        echo "3) Chạy: /c/ngrok/ngrok.exe config add-authtoken <TOKEN>"
        echo "4) Chạy lại script: ./start-ngrok.sh"
        exit 1
    fi
fi

# Port mặc định
PORT=8000

# Kiểm tra port có đang được sử dụng không
if lsof -Pi :$PORT -sTCP:LISTEN -t >/dev/null 2>&1 ; then
    echo -e "${YELLOW}⚠️  Port $PORT đang được sử dụng. Đang dừng process...${NC}"
    kill $(lsof -t -i:$PORT) 2>/dev/null
    sleep 2
fi

# Khởi động PHP Built-in Server ở background
echo -e "${BLUE}📦 Khởi động PHP Server trên port $PORT...${NC}"
php -S localhost:$PORT -t "$SCRIPT_DIR" > /tmp/php-server.log 2>&1 &
PHP_PID=$!

# Đợi server khởi động
sleep 2

# Kiểm tra PHP server đã chạy chưa
if ! ps -p $PHP_PID > /dev/null; then 
    echo -e "${RED}❌ Lỗi: Không thể khởi động PHP server${NC}"
    cat /tmp/php-server.log
    exit 1
fi

echo -e "${GREEN}✅ PHP Server đã khởi động (PID: $PHP_PID)${NC}"
echo ""

# Kiểm tra Ngrok đã được cấu hình authtoken chưa
if ! "$NGROK_BIN" config check >/dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Chưa cấu hình Ngrok authtoken${NC}"
    echo ""
    echo "Để sử dụng Ngrok, bạn cần:"
    echo "1. Đăng ký tài khoản miễn phí tại: https://dashboard.ngrok.com/signup"
    echo "2. Lấy authtoken tại: https://dashboard.ngrok.com/get-started/your-authtoken"
    echo "3. Chạy lệnh: ngrok config add-authtoken YOUR_TOKEN"
    echo ""
    echo -e "${BLUE}Hoặc bạn có thể dùng localhost:$PORT để test local${NC}"
    echo ""
fi

# Khởi động Ngrok
echo -e "${BLUE}🌐 Đang khởi động Ngrok tunnel...${NC}"
echo ""

# Chạy ngrok và hiển thị thông tin
"$NGROK_BIN" http $PORT

# Khi ngrok dừng, dọn dẹp
echo ""
echo -e "${YELLOW}🛑 Đang dừng PHP Server...${NC}"
kill $PHP_PID 2>/dev/null
echo -e "${GREEN}✅ Hoàn tất!${NC}"
