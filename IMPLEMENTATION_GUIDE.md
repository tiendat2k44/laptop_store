# 🎯 SPRINT 3 IMPLEMENTATION GUIDE

**Status**: Code ✅ Created | Setup ⚠️  Needed | Testing 🔄 Required

---

## 📊 Current Situation

### What's Done ✅
- VNPay/MoMo payment gateway code
- IPN and return handlers
- XLSX export functionality
- Lazy-load image optimization
- SEO meta tags & sitemap
- Address book (Sprints 1-2)
- Coupon system (Sprint 2)
- Email notifications (Sprint 1)

### What's Broken ⚠️
1. **Database** not connected (password placeholder)
2. **Admin Dashboard** uses PostgreSQL-specific syntax (fixed in latest commit)
3. **Payment Flow** blocked by credential placeholders (use test-payment.php instead)
4. **Orders** may not be visible if database not configured

### Solution Strategy 🎯
1. **Quick Path** (5 min): Database config + test with COD + verify with diagnostic
2. **Full Path** (20 min): Above + MoMo/VNPay sandbox setup + end-to-end testing
3. **Production Path** (1 hour): Above + real payment credentials + deployment

---

## 🚀 PATH 1: QUICK START (5 MINUTES)

### Step 1.1: Setup Database
```bash
# PostgreSQL (recommended)
sudo apt install postgresql
psql -U postgres -c "CREATE DATABASE laptop_store;"

# Or MySQL
sudo apt install mysql-server
mysql -u root -p -e "CREATE DATABASE laptop_store;"
```

### Step 1.2: Import Schema
```bash
# PostgreSQL
psql -U postgres -d laptop_store < database/schema.sql
psql -U postgres -d laptop_store < database/sample_data.sql

# MySQL
mysql -u root -p laptop_store < database/schema.sql
mysql -u root -p laptop_store < database/sample_data.sql
```

### Step 1.3: Update Config
**File**: `/includes/config/config.php`

```php
// Database connection
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');  // 5432 for PostgreSQL, 3306 for MySQL
define('DB_NAME', 'laptop_store');
define('DB_USER', 'postgres');  // or 'root' for MySQL
define('DB_PASS', '');  // Your database password

// Website URL
define('SITE_URL', 'http://localhost/TienDat123/laptop_store-main');
```

**If using MySQL**, also update:
📁 `/includes/core/Database.php` (line 13)
```php
// Change from:
// $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;

// To:
$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
```

### Step 1.4: Verify Setup
Open browser:
```
http://localhost/TienDat123/laptop_store-main/diagnostics/full_diagnostic.php
```

**Expected output**:
```
✅ Database Connection: OK
✅ Table Existence: users, products, orders, order_items, ...
✅ Orders count: 10 (from sample data)
```

### Step 1.5: Test Login & Orders
1. **Login**: Use sample user
   - Email: `customer1@example.com`
   - Password: `password123`

2. **Browse Orders**: Visit `/account/orders.php`
   - Should see 10 sample orders

3. **View Admin** (if admin user):
   - Login as admin user first
   - Visit `/admin/`
   - Should see dashboard with stats

---

## 🛒 PATH 2: FULL SETUP (20 MINUTES)

### Everything from Path 1 + Payment Setup

### Step 2.1: Setup MoMo (Optional)

1. **Register**:
   - Go to: https://developers.momo.vn/
   - Sign up for developer account
   - Create Application

2. **Get Credentials**:
   - Copy: Partner Code, Access Key, Secret Key
   - Keep in safe place

3. **Update Config**:
```php
// /includes/config/config.php (lines 115-122)
define('MOMO_PARTNER_CODE', 'your_partner_code_from_momo');
define('MOMO_ACCESS_KEY', 'your_access_key_from_momo');
define('MOMO_SECRET_KEY', 'your_secret_key_from_momo');
```

### Step 2.2: Setup VNPay (Optional)

1. **Register**:
   - Go to: https://sandbox.vnpayment.vn/
   - Sign up and verify

2. **Get Credentials**:
   - Copy: TMN Code, Hash Secret
   - Keep in safe place

3. **Update Config**:
```php
// /includes/config/config.php (lines 125-129)
define('VNPAY_TMN_CODE', 'your_tmn_code_from_vnpay');
define('VNPAY_HASH_SECRET', 'your_hash_secret_from_vnpay');
```

### Step 2.3: Test Checkout Flow

**Scenario 1: COD (Cash on Delivery)**
1. Login to user account
2. Add products to cart
3. Checkout → Select COD
4. Order created with status "pending"
5. Order visible in `/account/orders.php`

**Scenario 2: Test Payment (No credentials needed)**
1. Login to user account
2. Create COD order (see Scenario 1)
3. Go to `/payment/test-payment.php`
4. Select the order
5. Click "Simulate MoMo Success" or "Simulate VNPay Success"
6. Order status changes to "paid"
7. Order shows "💰 Đã thanh toán" in `/account/orders.php`

**Scenario 3: Real Payment (With credentials)**
1. Login to user account
2. Add products to cart
3. Checkout → Select MoMo or VNPay
4. Redirected to payment gateway
5. Complete payment in gateway
6. Return to checkout page with success
7. Order status "confirmed" and "paid"

---

## 📧 PATH 3: EMAIL NOTIFICATIONS (OPTIONAL, 10 MINUTES)

### Step 3.1: Setup Gmail App Password

1. Go to: https://myaccount.google.com/
2. Enable 2-Factor Authentication
3. Go to Security → App Passwords
4. Select "Mail" and "Windows Computer"
5. Google generates 16-character password
6. Copy the password

### Step 3.2: Update Config

```php
// /includes/config/config.php (lines 92-97)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-16-character-app-password');
define('MAIL_FROM_EMAIL', 'noreply@laptopstore.com');
define('MAIL_FROM_NAME', 'Laptop Store');
```

### Step 3.3: Test Email

When you:
- Register new account
- Create order
- Reset password
- etc.

You should receive emails at registered email address.

---

## 👥 PATH 4: MULTI-USER SETUP (OPTIONAL, 5 MINUTES)

### Create Admin User

```sql
-- Method 1: If database empty
INSERT INTO users (email, password, full_name, phone, is_admin, email_verified, created_at)
VALUES (
    'admin@test.local',
    '$2y$10$slYQmyNdGzin15JTwLP5/.v9/j5MfIjZ9QxqnH8.mu4BXbFP34nFm',
    'Admin User',
    '0901000000',
    TRUE,
    TRUE,
    NOW()
);

-- Method 2: Update existing user
UPDATE users SET is_admin = TRUE WHERE email = 'customer1@example.com';
```

**Password for hash above**: `password123`

### Create Shop User

```sql
-- Create user first
INSERT INTO users (email, password, full_name, phone, is_admin, email_verified, created_at)
VALUES (
    'shop@test.local',
    '$2y$10$slYQmyNdGzin15JTwLP5/.v9/j5MfIjZ9QxqnH8.mu4BXbFP34nFm',
    'Shop Owner',
    '0909000000',
    FALSE,
    TRUE,
    NOW()
) RETURNING id INTO shop_user_id;

-- Create shop
INSERT INTO shops (user_id, shop_name, description, phone, email, status, rating_count, created_at, updated_at)
VALUES (shop_user_id, 'My Laptop Shop', 'Premium laptops', '0909000000', 'shop@test.local', 'active', 0, NOW(), NOW());
```

---

## 🧪 TESTING CHECKLIST

### Pre-Testing
- [ ] Database connection working (see diagnostic page)
- [ ] All tables exist and have sample data
- [ ] Config file updated with correct database credentials

### Registration & Login
- [ ] Can register new account at `/register.php`
- [ ] Can login with registered account
- [ ] Can reset password at `/forgot-password.php`
- [ ] Email notifications work (if setup)

### Shopping Flow
- [ ] Can browse products at `/products.php`
- [ ] Can add products to cart
- [ ] Cart updates correctly
- [ ] Can apply coupons at checkout
- [ ] Can select shipping address

### Checkout
- [ ] Can checkout with COD payment method
- [ ] Order created successfully
- [ ] Order visible in `/account/orders.php`
- [ ] Can view order detail in `/account/order-detail.php`

### Payment
- [ ] Test payment at `/payment/test-payment.php` works
- [ ] Order status changes from "pending" to "paid"
- [ ] Payment status shows correctly in order list

### Admin
- [ ] Can access `/admin/` (requires admin user)
- [ ] Dashboard shows correct stats
- [ ] Can view orders list
- [ ] Can export orders to XLSX
- [ ] Can approve pending shops

### Optional Features
- [ ] Address book works at `/account/addresses.php`
- [ ] Can add/edit/delete addresses
- [ ] Default address selectable in checkout
- [ ] SEO works (check page titles, meta tags)
- [ ] Lazy-loading works (check Network tab for image loading)

---

## 🐛 TROUBLESHOOTING

### Database Issues

**"Kết nối cơ sở dữ liệu thất bại"**
```
1. Check DB_HOST, DB_PORT, DB_NAME in config.php
2. Verify database server is running:
   - PostgreSQL: sudo systemctl status postgresql
   - MySQL: sudo systemctl status mysql
3. Check credentials:
   - PostgreSQL: psql -U postgres
   - MySQL: mysql -u root -p
4. Verify database exists:
   - PostgreSQL: psql -l | grep laptop_store
   - MySQL: mysql -u root -p -e "SHOW DATABASES;"
```

### Login Issues

**"Có lỗi xảy ra" during login**
```
1. Check user exists: SELECT * FROM users WHERE email = 'test@example.com';
2. Check password hash is valid (should start with $2y$)
3. Clear browser cookies/cache
4. Try incognito/private mode
```

### Orders Issues

**"Orders không hiển thị"**
```
1. Check data in database: SELECT * FROM orders;
2. Verify getUserOrders() in OrderService.php
3. Check Auth::id() returns correct user ID
4. Create test order via checkout.php
5. Check browser console (F12) for JS errors
6. Check server error logs
```

**"Cannot create order"**
```
1. Check cart has items: SELECT * FROM cart_items WHERE user_id = <id>;
2. Check stock_quantity in products (must be > 0)
3. Check OrderService::createOrder() error logs
4. Verify order_items table exists and has correct columns
5. Try with simpler data first (1 item)
```

### Payment Issues

**"Payment form không submit"**
```
1. If config has placeholder (your_partner_code):
   - Use test-payment.php instead
   - Or update config with real credentials
2. Check payment gateway URLs in config
3. Check returnURL and IPN URL are correct
4. Check browser console (F12) for form errors
5. Test with simpler payment method first (COD)
```

**"Test payment page blank or error"**
```
1. Check /payment/test-payment.php exists
2. Check database has orders
3. Verify OrderService can fetch orders
4. Check PHP error logs
5. Try with new order first (COD)
```

### Admin Issues

**"Admin access denied"**
```
1. Check user is_admin = TRUE:
   SELECT * FROM users WHERE id = <your_id>;
2. Update if needed: UPDATE users SET is_admin = TRUE WHERE id = 1;
3. Logout and login again
4. Try incognito/private mode
5. Check browser console for errors
```

**"Admin dashboard error"**
```
1. Run diagnostic: /diagnostics/full_diagnostic.php
2. Check admin queries in admin/index.php
3. Verify database has orders table
4. Check PHP error logs
5. If MySQL, verify non-PostgreSQL queries used
```

---

## 📁 Key Files Reference

| File | Purpose | Required Config |
|------|---------|-----------------|
| `/includes/config/config.php` | Main configuration | ✅ YES - Database, Site URL |
| `/includes/core/Database.php` | Database driver | ✅ YES - If MySQL, change DSN |
| `/database/schema.sql` | Database structure | ✅ YES - Import first |
| `/database/sample_data.sql` | Test data | ✅ YES - For testing |
| `/account/orders.php` | User orders list | ✅ Works if DB configured |
| `/checkout.php` | Checkout flow | ✅ Works if DB configured |
| `/admin/index.php` | Admin dashboard | ✅ Works if DB configured |
| `/payment/test-payment.php` | Payment simulator | ✅ For testing without credentials |
| `/diagnostics/full_diagnostic.php` | System check | ✅ For troubleshooting |
| `CONFIG_TEMPLATE.php` | Config reference | ℹ️ Copy and modify as config.php |
| `QUICK_START.md` | 5-minute setup | ℹ️ Quick reference |
| `SPRINT3_FIX_GUIDE.md` | Detailed fixes | ℹ️ Troubleshooting guide |

---

## 📚 READING ORDER

1. **First time setup**: Read `QUICK_START.md`
2. **Stuck somewhere**: Run `/diagnostics/full_diagnostic.php`
3. **Detailed help**: Read `SPRINT3_FIX_GUIDE.md`
4. **Config reference**: Check `CONFIG_TEMPLATE.php`
5. **Full instructions**: You're reading this!

---

## ✨ Expected Results

After completing any path above, you should have:

✅ **Minimum (Path 1)**:
- Database connected and populated
- Users can login and see orders
- Orders can be created via COD
- Admin can access dashboard

✅ **Good (Path 2)**:
- Everything above +
- MoMo/VNPay payment working
- Test payment simulator functional
- Admin can manage orders

✅ **Complete (Path 3-4)**:
- Everything above +
- Email notifications working
- Multiple user types (admin, shop, customer)
- Full e-commerce functionality ready

---

## 🎓 Learning Path

After getting system working:
1. **Customize**: Modify CSS/HTML in `/assets/css/`
2. **Add features**: Use existing patterns in services/
3. **Connect real payment**: Update credentials in config
4. **Deploy**: Move to production server
5. **Monitor**: Check error logs regularly

---

## 📞 Support Resources

- **Diagnostic Tool**: `/diagnostics/full_diagnostic.php`
- **Database Docs**: `IMPORT_DATABASE.md`
- **Installation Docs**: `INSTALL.md`
- **Verification**: `VERIFICATION_CHECKLIST.md`
- **Browser Console**: F12 key for frontend errors
- **PHP Error Logs**: Check server error_log file

---

**Last Updated**: Sprint 3 Rework
**Status**: READY FOR IMPLEMENTATION ✅

Start with `QUICK_START.md` for fastest setup!
