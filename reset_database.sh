#!/bin/bash

# =============================================
# RESET DATABASE SCRIPT
# Xóa database cũ và tạo lại từ đầu
# =============================================

echo "🔄 Bắt đầu reset database..."

# Kiểm tra kết nối PostgreSQL
if ! command -v psql &> /dev/null; then
    echo "❌ PostgreSQL không được cài đặt"
    exit 1
fi

# Thiết lập biến
DB_NAME="laptop_store"
DB_USER="postgres"
DB_HOST="localhost"
DB_PORT="5432"

echo "📌 Database: $DB_NAME"
echo "📌 User: $DB_USER"

# Xóa database cũ
echo "🗑️  Xóa database cũ..."
psql -U $DB_USER -h $DB_HOST -p $DB_PORT -tc "DROP DATABASE IF EXISTS $DB_NAME;"

if [ $? -ne 0 ]; then
    echo "❌ Lỗi khi xóa database"
    exit 1
fi

# Tạo database mới
echo "✅ Xóa xong"
echo "📝 Tạo database mới..."
psql -U $DB_USER -h $DB_HOST -p $DB_PORT -tc "CREATE DATABASE $DB_NAME;"

if [ $? -ne 0 ]; then
    echo "❌ Lỗi khi tạo database"
    exit 1
fi

# Chạy schema.sql
echo "✅ Database tạo thành công"
echo "📝 Chạy schema.sql..."
psql -U $DB_USER -h $DB_HOST -p $DB_PORT -d $DB_NAME -f "$(dirname "$0")/database/schema.sql"

if [ $? -ne 0 ]; then
    echo "❌ Lỗi khi chạy schema.sql"
    exit 1
fi

# Chạy sample_data.sql
echo "✅ Schema tạo thành công"
echo "📝 Chạy sample_data.sql..."
psql -U $DB_USER -h $DB_HOST -p $DB_PORT -d $DB_NAME -f "$(dirname "$0")/database/sample_data.sql"

if [ $? -ne 0 ]; then
    echo "❌ Lỗi khi chạy sample_data.sql"
    exit 1
fi

echo ""
echo "✅ ============================================"
echo "✅ Reset database hoàn tất!"
echo "✅ Database: $DB_NAME"
echo "✅ ============================================"
