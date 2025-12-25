# 🧹 DATABASE FILES CLEANUP GUIDE

## Mục đích
Giữ lại các file cần thiết, xóa/archive các file đã cũ để tránh nhầm lẫn.

---

## ✅ File giữ lại (KEEP)

### 1. `complete_schema.sql` ⭐
- **Mục đích:** Schema hoàn chỉnh PostgreSQL
- **Trạng thái:** ✅ ACTIVE - File chính để import
- **Nội dung:** Tất cả tables + triggers + default data

### 2. `sample_data.sql` ⭐
- **Mục đích:** Dữ liệu mẫu cho testing/demo
- **Trạng thái:** ✅ ACTIVE
- **Nội dung:** 20 products, 3 shops, 8 users, 14 orders, 14 reviews

### 3. `payment_tables.sql` ℹ️
- **Mục đích:** MySQL version reference
- **Trạng thái:** ✅ KEEP for reference
- **Lý do:** Có thể cần nếu muốn port sang MySQL

---

## 🗑️ File nên xóa hoặc archive

### 4. `schema.sql` ❌
- **Trạng thái:** ⚠️ DEPRECATED - Đã được hợp nhất vào `complete_schema.sql`
- **Hành động:** Xóa hoặc move vào `database/old/`

### 5. `payment_tables_postgres.sql` ❌
- **Trạng thái:** ⚠️ DEPRECATED - Đã được hợp nhất vào `complete_schema.sql`
- **Hành động:** Xóa hoặc move vào `database/old/`

### 6. `settings_table.sql` ❌
- **Trạng thái:** ⚠️ DEPRECATED - Đã được hợp nhất vào `complete_schema.sql`
- **Hành động:** Xóa hoặc move vào `database/old/`

### 7. `add_shop_rating.sql` ❌
- **Trạng thái:** ⚠️ DEPRECATED - Shop rating đã có trong `complete_schema.sql`
- **Hành động:** Xóa hoặc move vào `database/old/`

### 8. `fix_order_items_shop_id.sql` ❌
- **Trạng thái:** ⚠️ DEPRECATED - Không cần nữa (schema mới đã đầy đủ)
- **Hành động:** Xóa hoặc move vào `database/old/`

### 9. `reset_complete.sql` ❌
- **Trạng thái:** ⚠️ DEPRECATED - Thay thế bởi `complete_schema.sql` + `sample_data.sql`
- **Hành động:** Xóa hoặc move vào `database/old/`

### 10. `setup_payment_tables.php` ⚠️
- **Trạng thái:** ℹ️ LEGACY - PHP script để tạo payment tables
- **Hành động:** Kiểm tra xem có code nào đang dùng không
  - Nếu không: Xóa
  - Nếu có: Cập nhật để dùng `complete_schema.sql`

---

## 📋 Cleanup Actions

### Option 1: Archive (Recommended)
Giữ lại các file cũ để reference sau này:

```bash
cd /workspaces/laptop_store/database

# Tạo thư mục archive
mkdir -p old_deprecated_2024

# Move các file cũ
mv schema.sql old_deprecated_2024/
mv payment_tables_postgres.sql old_deprecated_2024/
mv settings_table.sql old_deprecated_2024/
mv add_shop_rating.sql old_deprecated_2024/
mv fix_order_items_shop_id.sql old_deprecated_2024/
mv reset_complete.sql old_deprecated_2024/

# Kiểm tra setup_payment_tables.php
grep -r "setup_payment_tables.php" ../ --include="*.php"
# Nếu không có kết quả, move luôn:
mv setup_payment_tables.php old_deprecated_2024/

# Tạo README trong thư mục cũ
echo "# Deprecated Database Files
These files have been merged into complete_schema.sql.
Kept for historical reference only.
Date archived: $(date)" > old_deprecated_2024/README.md
```

### Option 2: Delete (Aggressive)
Xóa hoàn toàn nếu chắc chắn không cần:

```bash
cd /workspaces/laptop_store/database

# Xóa các file đã deprecated
rm -f schema.sql
rm -f payment_tables_postgres.sql
rm -f settings_table.sql
rm -f add_shop_rating.sql
rm -f fix_order_items_shop_id.sql
rm -f reset_complete.sql
# Kiểm tra trước khi xóa:
# rm -f setup_payment_tables.php
```

---

## 🎯 Cấu trúc sau cleanup

### Cấu trúc lý tưởng:
```
database/
├── complete_schema.sql       ⭐ Schema chính
├── sample_data.sql           ⭐ Dữ liệu mẫu
├── payment_tables.sql        ℹ️  MySQL reference
└── old_deprecated_2024/      📦 Archive (optional)
    ├── README.md
    ├── schema.sql
    ├── payment_tables_postgres.sql
    ├── settings_table.sql
    ├── add_shop_rating.sql
    ├── fix_order_items_shop_id.sql
    ├── reset_complete.sql
    └── setup_payment_tables.php
```

---

## ✅ Verification Checklist

Sau khi cleanup, kiểm tra:

- [ ] `complete_schema.sql` tồn tại và có đầy đủ nội dung
- [ ] `sample_data.sql` tồn tại
- [ ] Không còn file SQL riêng lẻ trong `database/` (trừ 2 file trên + payment_tables.sql)
- [ ] README.md đã cập nhật về import workflow mới
- [ ] DATABASE_IMPORT.md đã tạo với hướng dẫn chi tiết
- [ ] Không có code nào reference đến file đã xóa
- [ ] Có thể import thành công với 2 lệnh:
  ```bash
  psql -f database/complete_schema.sql
  psql -f database/sample_data.sql
  ```

---

## 🔍 Kiểm tra Dependencies

Trước khi xóa, tìm xem có code nào đang reference:

```bash
cd /workspaces/laptop_store

# Tìm references đến các file cũ
grep -r "schema.sql" --include="*.{php,md,sh,txt}" .
grep -r "payment_tables_postgres.sql" --include="*.{php,md,sh,txt}" .
grep -r "settings_table.sql" --include="*.{php,md,sh,txt}" .
grep -r "setup_payment_tables.php" --include="*.php" .

# Nếu có kết quả, cập nhật code trước khi xóa
```

---

## 📝 Notes

1. **Git History:** Nếu dùng Git, các file đã xóa vẫn có trong lịch sử commit
2. **Backup:** Có thể tạo backup toàn bộ thư mục database trước khi xóa:
   ```bash
   tar -czf database_backup_$(date +%Y%m%d).tar.gz database/
   ```
3. **Team Coordination:** Thông báo cho team biết về thay đổi này

---

**Khuyến nghị:** Sử dụng **Option 1 (Archive)** để an toàn.
