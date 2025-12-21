# 🔐 CRITICAL SECURITY FIXES - Implementation Guide

## Issues Phát Hiện & Fixes Cần Làm (Ngay)

### 🔴 CRITICAL ISSUE #1: Hardcoded Credentials
**Problem**: File `includes/config/config.php` chứa:
```php
define('DB_PASS', 'your_password_here');  // ❌ Visible in code!
define('VNPAY_TMN_CODE', 'placeholder');  // ❌ Secret keys exposed!
define('MOMO_PARTNER_CODE', 'your_partner_code');  // ❌ Exposed!
```

**Impact**: 
- 🔴 Production secrets visible in git history
- 🔴 Anyone with code access có thể thấy credentials
- 🔴 Cannot commit config to version control safely

**Solution**: Use .env file (environment variables)

---

## Step 1: Create .env File

### 1a. Copy template to .env
```bash
cp .env.example .env
```

### 1b. Edit .env with your actual credentials
```bash
# .env
DB_PASS=your_actual_database_password
VNPAY_TMN_CODE=your_real_tmn_code
VNPAY_HASH_SECRET=your_real_hash_secret
MOMO_PARTNER_CODE=your_real_partner_code
MOMO_SECRET_KEY=your_real_secret_key
MAIL_PASSWORD=your_app_password
```

### 1c. Make sure .env is in .gitignore
```bash
# Already updated in .gitignore
.env
.env.local
.env.*.local
```

### 1d. Verify .env is NOT committed
```bash
git status
# Should NOT show .env file
```

---

## Step 2: Update config.php to Use Env Variables

### 2a. Current config.php (UNSAFE)
```php
// ❌ OLD - Hardcoded
define('DB_PASS', 'your_password_here');
define('VNPAY_TMN_CODE', 'placeholder');
```

### 2b. New config.php (SAFE)
```php
// ✅ NEW - From environment
define('DB_PASS', Env::get('DB_PASS', 'password'));
define('VNPAY_TMN_CODE', Env::get('VNPAY_TMN_CODE', ''));
define('VNPAY_HASH_SECRET', Env::get('VNPAY_HASH_SECRET', ''));
```

---

## Step 3: Database Configuration Update

### Current Code (in Database.php):
```php
$dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,  // ✅ GOOD - SQL injection protected
];
$this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
```

This is already safe ✅ - Uses prepared statements

---

## Step 4: Add Rate Limiting to Login

### Location: `login.php` Line 18
```php
// ✅ ADDED - Rate limiting to prevent brute force
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$limiter = new RateLimiter('login_' . $ipAddress);

if (!$limiter->isAllowed(5, 300)) {  // 5 attempts in 5 minutes
    $errors[] = 'Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau 5 phút.';
}
```

---

## Step 5: Apply Security Headers

### Location: `includes/init.php`
```php
// ✅ ADDED - Security headers
require_once __DIR__ . '/core/SecurityHeaders.php';

// Apply security headers
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
SecurityHeaders::apply($isHttps);
```

**Headers Applied**:
- ✅ X-Frame-Options: SAMEORIGIN (chống clickjacking)
- ✅ X-Content-Type-Options: nosniff (chống MIME sniffing)
- ✅ X-XSS-Protection: 1; mode=block (XSS filter)
- ✅ Referrer-Policy: strict-origin-when-cross-origin
- ✅ Content-Security-Policy (chống XSS injection)
- ✅ HSTS (force HTTPS)

---

## 🔴 CRITICAL ISSUE #2: Missing Rate Limiting

**Problem**: Không có protection chống:
- Brute force login attacks
- Password reset spam
- Payment request spam

**Files Already Fixed**:
- ✅ `login.php` - Now has rate limiting
- ✅ New `includes/core/RateLimiter.php` - Reusable class

**Where to Add Rate Limiting** (Cần làm tiếp):
```php
// register.php - Prevent spam registration
$emailLimiter = new RateLimiter('register_email_' . $email);
if (!$emailLimiter->isAllowed(3, 3600)) {  // 3 regs per email per hour
    die('Quá nhiều lần đăng ký. Vui lòng thử lại sau 1 giờ.');
}

// forgot-password.php - Prevent spam
$resetLimiter = new RateLimiter('reset_' . $_SERVER['REMOTE_ADDR']);
if (!$resetLimiter->isAllowed(3, 1800)) {  // 3 requests per 30 minutes
    die('Quá nhiều yêu cầu reset. Vui lòng thử lại sau 30 phút.');
}

// payment/vnpay-ipn.php - Prevent duplicate processing
// Already has idempotency check ✅
```

---

## 🔴 CRITICAL ISSUE #3: Missing Security Headers

**Problem**: Không có HTTP security headers

**Files Already Added**:
- ✅ `includes/core/SecurityHeaders.php` (new file)
- ✅ Applied in `includes/init.php`

**Verification**: Check response headers
```bash
curl -i http://localhost/TienDat123/laptop_store-main/index.php | grep -E "X-Frame|X-Content|CSP|HSTS"
```

Expected output:
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'; ...
```

---

## 🟡 HIGH ISSUE #4: XSS in Reviews/Comments

**Problem**: User comments không escape output

**Status**: PENDING - Need to implement

**Fix Example**:
```php
// ❌ VULNERABLE
<?php echo $review['comment']; ?>

// ✅ SAFE
<?php echo escape($review['comment']); ?>

// ✅ SAFE - Alternative
<?php echo htmlspecialchars($review['comment'], ENT_QUOTES, 'UTF-8'); ?>
```

**Files to Update**:
- [ ] `account/review.php` - Escape comment output
- [ ] `product-detail.php` - Escape review display
- [ ] Admin product form - Escape product descriptions

---

## 🟡 HIGH ISSUE #5: File Upload Validation

**Problem**: `assets/uploads/` không validate file types

**Status**: PENDING - Need to implement

**Fix**:
```php
function uploadProductImage($file) {
    // 1. Check file type
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        throw new Exception('Invalid file type');
    }
    
    // 2. Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($mime, $allowedMimes)) {
        throw new Exception('Invalid MIME type');
    }
    
    // 3. Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File too large');
    }
    
    // 4. Generate safe filename
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    
    // 5. Move file
    move_uploaded_file($file['tmp_name'], '/assets/uploads/products/' . $filename);
    
    return '/assets/uploads/products/' . $filename;
}
```

---

## 🟡 HIGH ISSUE #6: Payment System Idempotency

**Problem**: Duplicate IPN calls could double-charge orders

**Status**: Already implemented ✅ in `vnpay-ipn.php` and `momo-ipn.php`

**Verify**:
```php
// Check for duplicate processing
$sql = "SELECT * FROM payment_transactions WHERE order_id = :order_id AND txn_ref = :txn_ref";
$existing = $db->queryOne($sql, ['order_id' => $orderId, 'txn_ref' => $txnRef]);

if ($existing) {
    // Already processed
    error_log("Duplicate payment IPN for order $orderId");
    http_response_code(200);  // Return 200 to not retry
    exit;
}
```

---

## Testing Checklist

### 1. Test Rate Limiting
```bash
# Try login 6 times in quick succession
# 6th attempt should be blocked
```

### 2. Test Security Headers
```bash
curl -i http://localhost/.../ | grep -E "X-Frame|CSP|HSTS"
```

### 3. Test SQL Injection Protection
```bash
# Try SQL injection in search
?search=test' OR '1'='1
# Should be escaped properly
```

### 4. Test XSS Protection
```bash
# Try posting comment with script tag
comment=<script>alert('XSS')</script>
# Should be escaped/sanitized
```

### 5. Test CSRF Protection
```bash
# Try form submission without CSRF token
# Should be rejected
```

### 6. Test Environment Variables
```php
// Add test script: test-env.php
<?php
require 'includes/init.php';
echo "DB_PASS: " . Env::get('DB_PASS');
echo "VNPAY_TMN_CODE: " . Env::get('VNPAY_TMN_CODE');
?>
```

---

## Deployment Checklist

### Before Going to Production:

- [ ] **1. Create .env file with real credentials**
  ```bash
  cp .env.example .env
  # Edit .env with production credentials
  ```

- [ ] **2. Update config.php to use Env**
  ```php
  define('DB_PASS', Env::get('DB_PASS'));
  ```

- [ ] **3. Disable debug mode**
  ```php
  error_reporting(0);
  ini_set('display_errors', 0);
  ```

- [ ] **4. Setup HTTPS**
  ```php
  $isHttps = !empty($_SERVER['HTTPS']);
  ```

- [ ] **5. Setup PHP max_execution_time**
  ```php
  set_time_limit(30);  // 30 seconds max
  ```

- [ ] **6. Test payment integration**
  - [ ] VNPay test payment
  - [ ] MoMo test payment
  - [ ] IPN callback handling

- [ ] **7. Test rate limiting**
  - [ ] Login brute force (5 attempts)
  - [ ] Password reset spam (3 requests/30min)

- [ ] **8. Monitor logs**
  ```bash
  tail -f /var/log/laptop_store/error.log
  ```

---

## IMPORTANT: Git Safety

### After implementing these fixes:
```bash
# 1. Check .env is ignored
git status
# Should NOT show .env

# 2. Remove any old config from git history
git rm --cached includes/config/config.php
# Edit to use Env:: then re-add

# 3. Create clean .env.example for team
cp .env .env.example
# Edit to remove real values
```

---

## Summary of Changes

| File | Change | Status |
|------|--------|--------|
| `.env.example` | Created template | ✅ DONE |
| `.gitignore` | Added .env | ✅ DONE |
| `includes/core/Env.php` | New environment loader | ✅ DONE |
| `includes/core/RateLimiter.php` | New rate limiter | ✅ DONE |
| `includes/core/SecurityHeaders.php` | New security headers | ✅ DONE |
| `includes/init.php` | Added Env, RateLimiter, SecurityHeaders | ✅ DONE |
| `login.php` | Added rate limiting | ✅ DONE |
| `includes/config/config.php` | PENDING: Update to use Env:: | ⏳ TODO |
| `register.php` | PENDING: Add rate limiting | ⏳ TODO |
| `forgot-password.php` | PENDING: Add rate limiting | ⏳ TODO |
| Review system | PENDING: Add XSS escaping | ⏳ TODO |
| File upload handlers | PENDING: Add validation | ⏳ TODO |

---

## Next Steps

1. **Create .env file** with your credentials
2. **Test locally** with all security features enabled
3. **Update remaining files** (register, forgot-password, reviews)
4. **Deploy to production** with HTTPS enabled
5. **Monitor logs** for any security events

---

**Created**: 21-12-2025  
**Priority**: 🔴 CRITICAL - Implement immediately  
**Estimated Time**: 2-3 hours for full implementation
