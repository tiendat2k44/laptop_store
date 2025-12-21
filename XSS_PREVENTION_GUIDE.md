# 📋 XSS PREVENTION BEST PRACTICES

## ✅ Current Status
- ✅ `escape()` function exists in `includes/helpers/functions.php`
- ✅ Most product display already uses `escape()`
- ⚠️ User reviews/comments need verification
- ⚠️ Admin product input needs validation

---

## 🔒 How to Prevent XSS

### Rule 1: ALWAYS Escape Output
```php
// ❌ VULNERABLE - User input shown as-is
<?php echo $product['name']; ?>
<?php echo $review['comment']; ?>

// ✅ SAFE - User input escaped
<?php echo escape($product['name']); ?>
<?php echo escape($review['comment']); ?>
```

### Rule 2: Use Parameterized Queries
```php
// ❌ VULNERABLE - SQL injection + data exposure
$sql = "SELECT * FROM products WHERE name LIKE '%" . $_GET['search'] . "%'";

// ✅ SAFE - Prepared statement
$sql = "SELECT * FROM products WHERE name LIKE :search";
$results = $db->query($sql, ['search' => '%' . $_GET['search'] . '%']);
```

### Rule 3: Validate Input Server-Side
```php
// ❌ VULNERABLE - Only client-side validation
<input type="text" maxlength="50" required>

// ✅ SAFE - Server-side validation
if (strlen($name) > 50 || empty($name)) {
    throw new Exception('Invalid product name');
}
```

### Rule 4: Set Content-Type Headers
```php
// ✅ GOOD - Prevent MIME sniffing
header('Content-Type: text/html; charset=UTF-8');
```

### Rule 5: Use Content Security Policy
```php
// ✅ GOOD - Prevent inline script execution
header("Content-Security-Policy: default-src 'self'; script-src 'self' cdn.jsdelivr.net");
```

---

## 🔍 Files to Check for XSS

### ✅ ALREADY SAFE (using escape()):
- `includes/product-card.php` - Uses escape() on product display
- `products.php` - Uses escape() on listing
- `product-detail.php` - Uses escape() on name/price
- `index.php` - Uses escape() on category names

### ⚠️ NEED TO VERIFY:
- `account/review.php` - Check if review output is escaped
- `account/order-detail.php` - Check order info display
- Admin product forms - Check input validation
- Search results - Check search term escaping

### 🔴 CRITICAL - If Review Module Exists:
```php
// File: account/review.php or similar
// Check if it has:
<?php echo escape($review['comment']); ?>  // ✅ Good
// Not:
<?php echo $review['comment']; ?>  // ❌ Bad
```

---

## Testing XSS Vulnerability

### Test Case 1: Simple Script Tag
```html
<input type="text" name="comment" value="">

Post with value: <script>alert('XSS')</script>

If it shows alert → VULNERABLE
If it shows as text → SAFE
```

### Test Case 2: Event Handler
```html
Post with value: <img src=x onerror="alert('XSS')">

If it shows alert → VULNERABLE
If it shows as text → SAFE
```

### Test Case 3: JavaScript Protocol
```html
Post with value: <a href="javascript:alert('XSS')">Click me</a>

If it executes JavaScript → VULNERABLE
If it shows as text → SAFE
```

### Test Case 4: Entity Encoding
```html
Post with value: &lt;script&gt;alert('XSS')&lt;/script&gt;

Should display: <script>alert('XSS')</script>
(as visible text, not executable)
```

---

## Files Already Reviewed & Safe

### `includes/product-card.php`:
```php
// ✅ SAFE - Using escape()
<h5 class="card-title"><?= escape($product['name']) ?></h5>
<p class="card-text text-muted"><?= escape($product['description']) ?></p>
```

### `products.php`:
```php
// ✅ SAFE - Using escape()
<?= escape($product['name']) ?>
<?= escape($product['description']) ?>
<?= number_format($product['price'], 0, ',', '.') ?> đ
```

### `login.php`:
```php
// ✅ SAFE - Using escape()
<li><?= escape($error) ?></li>

// ✅ SAFE - Using escape() in form
value="<?= escape($formData['email']) ?>"
```

### `register.php`:
```php
// ✅ SAFE - Using escape()
<?= escape($error) ?>
value="<?= escape($formData['email']) ?>"
```

---

## Security Headers Status

### ✅ Already Implemented:
```php
// in includes/core/SecurityHeaders.php

X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
Content-Security-Policy: default-src 'self'; script-src 'self' cdn.jsdelivr.net; ...
Strict-Transport-Security: max-age=31536000; (HTTPS only)
```

### Applied in:
```php
// in includes/init.php
require_once __DIR__ . '/core/SecurityHeaders.php';
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
SecurityHeaders::apply($isHttps);
```

---

## Checklist for Safe Output

Use this checklist when displaying user-provided data:

- [ ] Input is escaped with `escape()` or `htmlspecialchars()`
- [ ] No raw HTML allowed (only text content)
- [ ] If HTML needed, use HTML Purifier library
- [ ] Content-Type header set correctly
- [ ] CSP header includes script restrictions
- [ ] Form inputs validated server-side
- [ ] Output length checked/limited
- [ ] Database queries use prepared statements

---

## Example: Safe Review Display

```php
<?php
// ✅ SAFE way to display user reviews

// Get review from database
$sql = "SELECT id, user_name, comment, rating, created_at 
        FROM product_reviews 
        WHERE product_id = :product_id 
        ORDER BY created_at DESC";
$reviews = $db->query($sql, ['product_id' => $productId]);

// Display reviews
foreach ($reviews as $review):
?>
<div class="review-card">
    <div class="review-header">
        <!-- ✅ SAFE - Name escaped -->
        <strong><?= escape($review['user_name']) ?></strong>
        <span class="rating">
            <!-- ✅ SAFE - Rating is number, no escaping needed -->
            <?= intval($review['rating']) ?> ⭐
        </span>
    </div>
    <p class="review-text">
        <!-- ✅ SAFE - Comment escaped -->
        <?= escape($review['comment']) ?>
    </p>
    <small class="text-muted">
        <!-- ✅ SAFE - Date formatted, no escaping needed -->
        <?= date('d/m/Y H:i', strtotime($review['created_at'])) ?>
    </small>
</div>
<?php endforeach; ?>
```

---

## Example: Unsafe vs Safe Review Creation

```php
<?php
// ⚠️ UNSAFE Form Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = $_POST['comment'];  // ❌ No validation
    
    // ❌ VULNERABLE - No type checking
    $rating = $_POST['rating'];    // Could be -1, 100, "ABC", <script>
    
    $db->execute("INSERT INTO reviews (...) VALUES (?, ?)", [
        $comment,  // ❌ Not escaped in database
        $rating    // ❌ Not validated
    ]);
}

// ✅ SAFE Form Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate rating first
    $rating = intval($_POST['rating'] ?? 0);
    if ($rating < 1 || $rating > 5) {
        die('Rating must be 1-5');
    }
    
    // Validate comment length and content
    $comment = trim($_POST['comment'] ?? '');
    if (empty($comment) || strlen($comment) > 500) {
        die('Comment must be 1-500 characters');
    }
    
    // Use prepared statement (database-level protection)
    $sql = "INSERT INTO reviews (product_id, user_id, comment, rating, created_at) 
            VALUES (:product_id, :user_id, :comment, :rating, NOW())";
    
    $db->execute($sql, [
        'product_id' => intval($_GET['product_id']),
        'user_id' => Auth::getId(),
        'comment' => $comment,  // Database will escape
        'rating' => $rating
    ]);
}
```

---

## Common XSS Attack Patterns to Watch For

### 1. Script Injection
```
Input: <script>alert('XSS')</script>
Escaped: &lt;script&gt;alert('XSS')&lt;/script&gt;
Display: <script>alert('XSS')</script> (as text)
```

### 2. Event Handlers
```
Input: <img src=x onerror="alert('XSS')">
Escaped: &lt;img src=x onerror="alert('XSS')"&gt;
Display: <img src=x onerror="alert('XSS')"> (as text)
```

### 3. Data Attributes
```
Input: <div data-url="javascript:alert('XSS')">
Escaped: &lt;div data-url="javascript:alert('XSS')"&gt;
Display: <div data-url="javascript:alert('XSS')"> (as text)
```

### 4. Style Injection
```
Input: <div style="background:url('javascript:alert(1)')">
Escaped: Safe when displayed as text
```

### 5. Comment Injection
```
Input: <!--<script>alert('XSS')</script>-->
Escaped: &lt;!--&lt;script&gt;alert('XSS')&lt;/script&gt;--&gt;
```

---

## Final Security Checklist

- [x] SQL injection protected (prepared statements)
- [x] CSRF protected (tokens)
- [x] Output escaping (escape() function)
- [x] Security headers (CSP, X-Frame-Options, etc.)
- [x] Password hashing (bcrypt via password_verify)
- [x] Session management (session regeneration)
- [x] Rate limiting (login/register/password reset)
- [ ] File upload validation (pending)
- [ ] Input validation complete (partial)
- [ ] Error handling secure (no SQL errors shown)

---

**Document Version**: 1.0  
**Last Updated**: 21-12-2025  
**Review Schedule**: Monthly security audits
