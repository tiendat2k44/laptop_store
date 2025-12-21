# Shop Rating Migration Guide

Nếu bạn gặp lỗi "Undefined array key 'rating'" trên trang shop dashboard, vui lòng chạy câu lệnh SQL sau để thêm cột rating vào bảng shops.

## Hướng dẫn chạy Migration

### Cách 1: Chạy file SQL trực tiếp (PostgreSQL)

```bash
psql -U postgres -d laptop_store -f database/add_shop_rating.sql
```

### Cách 2: Chạy trong phpmyadmin/pgAdmin

Copy câu lệnh SQL bên dưới và chạy trực tiếp trong query editor:

```sql
-- Add rating column to shops table
ALTER TABLE shops ADD COLUMN IF NOT EXISTS rating DECIMAL(3, 2) DEFAULT 0.0;
ALTER TABLE shops ADD COLUMN IF NOT EXISTS total_reviews INTEGER DEFAULT 0;

-- Create index for faster queries
CREATE INDEX IF NOT EXISTS idx_shops_rating ON shops(rating DESC);
```

### Cách 3: Chạy qua PHP CLI (nếu cấu hình database trong .env)

```bash
php -r "
require_once 'includes/init.php';
\$db = Database::getInstance();
\$db->execute('ALTER TABLE shops ADD COLUMN IF NOT EXISTS rating DECIMAL(3, 2) DEFAULT 0.0');
\$db->execute('ALTER TABLE shops ADD COLUMN IF NOT EXISTS total_reviews INTEGER DEFAULT 0');
\$db->execute('CREATE INDEX IF NOT EXISTS idx_shops_rating ON shops(rating DESC)');
echo 'Migration complete!';
"
```

## Kết quả sau khi migration

✅ Cột `rating` được thêm vào bảng shops (default 0.0)
✅ Cột `total_reviews` được thêm để tracking số lượng reviews
✅ Index được tạo để tối ưu query theo rating
✅ Trang shop dashboard sẽ hoạt động bình thường

## Lưu ý

- `IF NOT EXISTS` giúp bạn chạy lệnh này mà không lo lỗi nếu cột đã tồn tại
- Rating sẽ tự động update khi có review mới từ khách hàng
- Default giá trị là 0.0 cho các shop mới chưa có review
