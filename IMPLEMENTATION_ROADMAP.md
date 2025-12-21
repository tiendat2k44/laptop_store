# 🚀 IMPLEMENTATION ROADMAP - Complete Action Plan

## 📌 CURRENT STATUS
**System**: Largely functional but with security gaps  
**Score**: 6.4/10 (MEDIUM RISK)  
**Priority**: 🔴 CRITICAL fixes needed before production

---

## 📅 PHASE 1: CRITICAL (Days 1-2) - MUST DO IMMEDIATELY

### Day 1: Security Foundation

#### Task 1.1: Environment Configuration ✅ DONE
- [x] Created `.env.example` template
- [x] Created `includes/core/Env.php` class
- [x] Updated `.gitignore` to include `.env`
- [ ] **ACTION**: Create `.env` with your real credentials
  ```bash
  cp .env.example .env
  nano .env  # Edit with real credentials
  ```

#### Task 1.2: Rate Limiting ✅ DONE
- [x] Created `includes/core/RateLimiter.php`
- [x] Added rate limiting to `login.php` (5 attempts/5 min)
- [ ] **ACTION**: Add to `register.php`
  ```php
  $limiter = new RateLimiter('register_' . $_SERVER['REMOTE_ADDR']);
  if (!$limiter->isAllowed(3, 3600)) {  // 3 registrations/hour
      die('Too many registration attempts');
  }
  ```

#### Task 1.3: Security Headers ✅ DONE
- [x] Created `includes/core/SecurityHeaders.php`
- [x] Applied headers in `includes/init.php`
- [ ] **ACTION**: Test headers
  ```bash
  curl -i http://localhost/.../index.php | grep -E "X-Frame|CSP|HSTS"
  ```

#### Task 1.4: Update Config.php
- [ ] **ACTION**: Update `includes/config/config.php` to use `Env::get()`
  ```php
  // BEFORE
  define('DB_PASS', 'your_password_here');
  
  // AFTER
  define('DB_PASS', Env::get('DB_PASS', 'password'));
  ```

#### Task 1.5: XSS Prevention Verification
- [ ] Verify `account/review.php` escapes output
- [ ] Verify product details page escapes all user input
- [ ] Test with XSS payload: `<script>alert('test')</script>`

**Estimated Time**: 3-4 hours  
**Risk if Not Done**: 🔴 CRITICAL - Credentials in git, brute force attacks possible, XSS vulnerabilities

---

### Day 2: Payment & File Security

#### Task 2.1: Payment System Verification
- [ ] Test VNPay payment flow (use test credentials)
- [ ] Test MoMo payment flow (use test credentials)
- [ ] Verify IPN handlers have idempotency check
- [ ] Verify signature verification on all IPN requests

**Test Steps**:
```bash
1. Checkout with COD (Cash on Delivery)
2. Checkout with VNPay test
3. Checkout with MoMo test
4. Verify order created correctly
5. Verify payment status updates
```

#### Task 2.2: File Upload Validation
- [ ] Create file upload validation helper
- [ ] Add MIME type checking
- [ ] Add file size limits
- [ ] Test with malicious files

**Implementation**:
```php
// Add to includes/helpers/file-upload.php
function validateUpload($file, $maxSize = 5*1024*1024, $allowedMimes = []) {
    // Validate...
}
```

#### Task 2.3: Sensitive Data Audit
- [ ] Check database for plaintext credit card data (should be NONE)
- [ ] Check if password hashes ever logged
- [ ] Remove secrets from error messages
- [ ] Setup error logging (server-side only)

**Estimated Time**: 3-4 hours  
**Risk if Not Done**: 🔴 CRITICAL - Double-charge bugs, file upload vulnerabilities, data breaches

---

## 📅 PHASE 2: HIGH PRIORITY (Days 3-5) - Important, Do Soon

### Task 2.1: Database Optimization
- [ ] Create missing indexes (products, orders, reviews, etc.)
- [ ] Fix N+1 queries in:
  - [ ] `account/orders.php` - listing with items
  - [ ] `product-detail.php` - reviews with user info
  - [ ] Admin product list
  - [ ] Admin order list

**SQL Indexes to Create**:
```sql
CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_reviews_product_id ON reviews(product_id);
CREATE INDEX idx_wishlist_user_id ON wishlist(user_id);
```

**Estimated Time**: 4-5 hours  
**Impact**: 50-70% query speedup

### Task 2.2: Implement Caching
- [ ] Install APCu (if not already installed)
  ```bash
  apt-get install php-apcu
  ```
- [ ] Implement cache for:
  - [ ] Product list
  - [ ] Categories
  - [ ] Search results
  - [ ] Config settings

**Example Implementation**:
```php
function getCachedProducts() {
    $cache = apcu_fetch('products_list');
    if ($cache === false) {
        $cache = Database::getInstance()->query("SELECT id, name, price FROM products");
        apcu_store('products_list', $cache, 3600);
    }
    return $cache;
}
```

**Estimated Time**: 3-4 hours  
**Impact**: 80-90% reduction on repeated requests

### Task 2.3: Input Validation Enhancement
- [ ] Add comprehensive input validation
- [ ] Whitelist validation (not blacklist)
- [ ] Email validation for all email fields
- [ ] Phone number validation
- [ ] Amount validation for payments

**Estimated Time**: 3-4 hours  
**Impact**: Prevent invalid data in database

**Estimated Time for Phase 2**: 10-13 hours  
**Risk if Not Done**: 🟡 HIGH - Slow page loads, brute force possible, bad data

---

## 📅 PHASE 3: MEDIUM PRIORITY (Days 6-7) - Nice to Have

### Task 3.1: Frontend Performance
- [ ] Implement lazy loading for images
- [ ] Minify CSS and JavaScript
- [ ] Add cache headers for static assets
- [ ] Optimize images (compress)

**Estimated Time**: 3-4 hours  
**Impact**: 60-70% faster initial page load

### Task 3.2: Monitoring & Logging
- [ ] Setup error logging to file
- [ ] Setup slow query logging (> 100ms)
- [ ] Setup security event logging
- [ ] Create log rotation script

**Estimated Time**: 2-3 hours  
**Impact**: Early detection of issues

### Task 3.3: Testing & Documentation
- [ ] Create test cases for security fixes
- [ ] Document deployment process
- [ ] Create troubleshooting guide
- [ ] Create admin operational guide

**Estimated Time**: 3-4 hours  
**Impact**: Easier maintenance and debugging

**Estimated Time for Phase 3**: 8-11 hours  
**Priority**: 🟢 LOW - Nice to have, not critical

---

## 📋 DETAILED IMPLEMENTATION CHECKLIST

### SECURITY CHECKLIST

#### SQL Injection Protection
- [x] Using prepared statements everywhere
- [x] Database::query() uses parameters
- [x] Database::queryOne() uses parameters
- [x] Database::execute() uses parameters
- [ ] Audit all custom SQL in admin modules
  - [ ] `admin/modules/products/` - Check queries
  - [ ] `admin/modules/categories/` - Check queries
  - [ ] `admin/modules/orders/` - Check queries

#### CSRF Protection
- [x] CSRF token in Session class
- [x] Login form has CSRF token
- [x] Register form has CSRF token
- [ ] Verify all POST forms have tokens
  - [ ] Checkout form
  - [ ] Review form
  - [ ] Admin forms

#### Authentication Security
- [x] Using password_verify() with bcrypt
- [x] Session regeneration on login
- [x] Remember me with httponly cookie
- [ ] Verify email requirement enforced
- [ ] Test password reset flow:
  - [ ] Token TTL (1 hour max)
  - [ ] Token deletion after use

#### File Security
- [ ] File upload validation (MIME type, size)
- [ ] Filename sanitization
- [ ] Uploaded files outside webroot (ideal)
- [ ] File permissions (644 for files, 755 for dirs)

#### Data Security
- [ ] No plaintext passwords (using bcrypt)
- [ ] No credit card data stored
- [ ] Sensitive errors logged, not displayed
- [ ] No sensitive data in URLs
- [ ] No sensitive data in cookies (except httponly session)

#### API Security
- [ ] AJAX endpoints validate CSRF tokens
- [ ] AJAX endpoints require authentication
- [ ] Rate limiting on AJAX endpoints
- [ ] Input validation on AJAX data

---

### PERFORMANCE CHECKLIST

#### Database Performance
- [ ] All SELECT queries have WHERE clauses
- [ ] JOIN queries used instead of N+1
- [ ] Indexes created for frequently searched columns
- [ ] Query optimization reviewed for:
  - [ ] Product listing (< 50ms)
  - [ ] Order listing (< 50ms)
  - [ ] Search (< 100ms)

#### Caching Implementation
- [ ] Cache static content (1 hour min)
- [ ] Cache database queries (1-24 hours)
- [ ] Cache invalidation on data changes
- [ ] Monitor cache hit ratio

#### Frontend Performance
- [ ] Images lazy loaded
- [ ] CSS/JS minified and compressed
- [ ] Cache headers set
- [ ] No render-blocking resources
- [ ] Page load time < 3 seconds

---

### FUNCTIONALITY CHECKLIST

#### Authentication Flow
- [ ] Login works
- [ ] Register works
- [ ] Email verification works
- [ ] Password reset works
- [ ] Remember me works
- [ ] Logout works

#### E-Commerce Flow
- [ ] Browse products works
- [ ] Search products works
- [ ] View product details works
- [ ] Add to cart works
- [ ] Update cart works
- [ ] Remove from cart works
- [ ] Checkout works
- [ ] Order confirmation works
- [ ] Order history works

#### Payment Flow
- [ ] COD (Cash on Delivery) works
- [ ] VNPay payment works
- [ ] MoMo payment works
- [ ] Payment confirmation email sent
- [ ] Order status updates correctly
- [ ] Handles duplicate payments (idempotent)

#### Admin Flow
- [ ] Admin dashboard loads
- [ ] Can view products
- [ ] Can create product
- [ ] Can update product
- [ ] Can delete product
- [ ] Can view orders
- [ ] Can update order status
- [ ] Can view categories
- [ ] Can manage categories
- [ ] Can view users
- [ ] Can manage users

---

## 🔧 IMPLEMENTATION ORDER

**Recommended sequence**:

1. ✅ **DONE**: Environment variables & credentials
2. ✅ **DONE**: Rate limiting on login
3. ✅ **DONE**: Security headers
4. ⏳ **TODO**: Complete rate limiting (register, password reset)
5. ⏳ **TODO**: XSS prevention audit
6. ⏳ **TODO**: Payment idempotency verification
7. ⏳ **TODO**: File upload validation
8. ⏳ **TODO**: Database indexes
9. ⏳ **TODO**: Fix N+1 queries
10. ⏳ **TODO**: Implement caching
11. ⏳ **TODO**: Frontend optimization
12. ⏳ **TODO**: Comprehensive testing

---

## 📊 TIME ESTIMATES

| Phase | Tasks | Time | Status |
|-------|-------|------|--------|
| Phase 1 - CRITICAL | 5 tasks | 6-8 hours | 40% ✅ |
| Phase 2 - HIGH | 3 tasks | 10-13 hours | 0% ⏳ |
| Phase 3 - MEDIUM | 3 tasks | 8-11 hours | 0% ⏳ |
| **TOTAL** | **11 tasks** | **24-32 hours** | **15% ✅** |

**Recommended**: 4-5 days at 6-8 hours/day

---

## ⚠️ CRITICAL ISSUES SUMMARY

| Issue | Impact | Fix Time | Status |
|-------|--------|----------|--------|
| Hardcoded credentials | 🔴 HIGH | 30 min | ✅ DONE |
| No rate limiting | 🔴 HIGH | 1 hour | ✅ DONE |
| Missing security headers | 🔴 HIGH | 30 min | ✅ DONE |
| Incomplete rate limiting | 🟡 MEDIUM | 2 hours | ⏳ TODO |
| No file validation | 🟡 MEDIUM | 2 hours | ⏳ TODO |
| Slow queries (N+1) | 🟡 MEDIUM | 3-4 hours | ⏳ TODO |
| No caching | 🟡 MEDIUM | 3-4 hours | ⏳ TODO |
| No lazy loading | 🟢 LOW | 1-2 hours | ⏳ TODO |

---

## ✅ NEXT IMMEDIATE STEPS

### TODAY (CRITICAL):
1. [ ] Create .env file with your credentials
2. [ ] Test login with rate limiting (5 attempts should block 6th)
3. [ ] Verify security headers are sent
4. [ ] Commit all security changes to git

### THIS WEEK (HIGH):
1. [ ] Complete payment system testing
2. [ ] Implement file upload validation
3. [ ] Create database indexes
4. [ ] Audit N+1 queries

### NEXT WEEK (MEDIUM):
1. [ ] Implement caching layer
2. [ ] Frontend optimization
3. [ ] Comprehensive security testing
4. [ ] Load testing (100+ concurrent users)

---

## 📚 REFERENCE DOCUMENTS

Created documentation:
- ✅ `SECURITY_AUDIT_REPORT.md` - Full audit findings
- ✅ `CRITICAL_SECURITY_FIXES.md` - Step-by-step fixes
- ✅ `XSS_PREVENTION_GUIDE.md` - XSS prevention guide
- ✅ `PERFORMANCE_OPTIMIZATION_GUIDE.md` - Performance improvements
- ✅ `IMPLEMENTATION_ROADMAP.md` - This document

---

## 🎯 SUCCESS CRITERIA

**System will be considered "Production Ready" when**:
- ✅ All CRITICAL security issues fixed
- ✅ Rate limiting on all sensitive endpoints
- ✅ Database indexes created
- ✅ Caching implemented
- ✅ Payment system tested end-to-end
- ✅ Load test passes (100+ concurrent users, < 3s response time)
- ✅ Security audit score: 8/10+
- ✅ Performance score: 7/10+
- ✅ All functionality tests pass
- ✅ Zero critical vulnerabilities

**Current Status**: 6.4/10 → Target: 8.5/10 ✅

---

**Document Version**: 1.0  
**Created**: 21-12-2025  
**Last Updated**: 21-12-2025  
**Next Review**: After Phase 1 completion (2-3 days)
