# ⚡ PERFORMANCE OPTIMIZATION GUIDE

## 1. Database Query Optimization

### 🔴 Problem: N+1 Queries

**Example**: Listing orders with items
```php
// ❌ BAD - N+1 queries (1 + N extra queries)
$orders = $db->query("SELECT * FROM orders WHERE user_id = :user_id", ['user_id' => $userId]);

foreach ($orders as $order) {
    // This runs QUERY for each order! 
    // If 10 orders: 1 + 10 = 11 queries!
    $items = $db->query("SELECT * FROM order_items WHERE order_id = :order_id", ['order_id' => $order['id']]);
    $order['items'] = $items;
}
```

**Impact**: 
- 10 orders = 11 database queries (SLOW!)
- 100 orders = 101 queries (VERY SLOW!)
- High database load, slow page load

**✅ FIX: Use JOIN in single query**
```php
// ✅ GOOD - Single query with JOIN
$sql = "
    SELECT 
        o.id, o.user_id, o.order_number, o.total_amount, o.status,
        oi.id as item_id, oi.product_id, oi.quantity, oi.unit_price
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = :user_id
    ORDER BY o.id, oi.id
";

$rawResults = $db->query($sql, ['user_id' => $userId]);

// Group by order ID
$orders = [];
foreach ($rawResults as $row) {
    $orderId = $row['id'];
    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'order_number' => $row['order_number'],
            'items' => []
        ];
    }
    if ($row['item_id']) {
        $orders[$orderId]['items'][] = [
            'id' => $row['item_id'],
            'product_id' => $row['product_id'],
            'quantity' => $row['quantity'],
            'unit_price' => $row['unit_price']
        ];
    }
}

$orders = array_values($orders);  // Reset keys
```

### 2. Missing Database Indexes

**Current Indexes** (found in database):
```sql
CREATE INDEX idx_coupons_code ON coupons(code);
CREATE INDEX idx_users_email ON users(email);
```

**Missing Indexes** (causing SLOW queries):
```sql
-- For product search
CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_products_category_id ON products(category_id);

-- For user lookups
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_created_at ON orders(created_at);

-- For order items
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_order_items_product_id ON order_items(product_id);

-- For reviews
CREATE INDEX idx_reviews_product_id ON reviews(product_id);
CREATE INDEX idx_reviews_user_id ON reviews(user_id);

-- For wishlist
CREATE INDEX idx_wishlist_user_id ON wishlist(user_id);
CREATE INDEX idx_wishlist_product_id ON wishlist(product_id);

-- For payment transactions
CREATE INDEX idx_payment_transactions_order_id ON payment_transactions(order_id);
CREATE INDEX idx_payment_transactions_txn_ref ON payment_transactions(txn_ref);
```

**Implementation**:
```sql
-- Run these commands on your database
-- PostgreSQL
CREATE INDEX CONCURRENTLY idx_products_name ON products(LOWER(name));  -- For case-insensitive search

-- MySQL
CREATE INDEX idx_products_name ON products(name(50));  -- Index first 50 chars
```

### 3. Query Optimization Examples

#### ❌ SLOW: Selecting all columns
```php
$sql = "SELECT * FROM products";  // Gets 30+ columns (wasteful)
```

#### ✅ FAST: Select only needed columns
```php
$sql = "SELECT id, name, price, image_url FROM products LIMIT 20";
```

---

## 2. Caching Strategy

### 🔴 Problem: No Caching
Currently:
- Every page load queries database for products
- Every search query hits database
- Every category view queries database
- **Result**: 1000 concurrent users = 1000+ database queries/second!

### ✅ Solution: Implement Caching

#### Option 1: APCu (In-memory cache, no external dependency)
```php
// Check cache first
$cacheKey = 'products_list_page_1';
$products = apcu_fetch($cacheKey);

if ($products === false) {
    // Cache miss - query database
    $products = $db->query("SELECT id, name, price FROM products LIMIT 20");
    
    // Cache for 1 hour
    apcu_store($cacheKey, $products, 3600);
}
```

#### Option 2: Redis (If available, better for distributed systems)
```php
$redis = new Redis();
$redis->connect('localhost', 6379);

$cacheKey = 'products_list_page_1';
$cached = $redis->get($cacheKey);

if ($cached) {
    $products = json_decode($cached, true);
} else {
    $products = $db->query("SELECT id, name, price FROM products LIMIT 20");
    $redis->setex($cacheKey, 3600, json_encode($products));  // Cache for 1 hour
}
```

#### Cache Invalidation (Clear cache when data changes)
```php
// When product is added/updated/deleted
function clearProductCache() {
    // APCu
    apcu_delete('products_list_*');  // Clear all product caches
    
    // Or Redis
    // $redis->del(array of cache keys);
}

// In product creation/update:
$db->execute("INSERT INTO products (...) VALUES (...);");
clearProductCache();  // Invalidate cache
```

### Recommended Caching Strategy

| Data | Cache Duration | Method | Invalidation |
|------|---|---|---|
| Product list | 1 hour | APCu/Redis | On product change |
| Product details | 30 minutes | APCu/Redis | On product update |
| Categories | 24 hours | APCu/Redis | On category change |
| Config settings | 24 hours | APCu/Redis | Manual |
| Search results | 5 minutes | APCu/Redis | On product change |
| User data | No cache | - | Real-time |
| Order data | No cache | - | Real-time |

---

## 3. Frontend Performance

### 🔴 Problem 1: No Lazy Loading for Images

**Current**: All images load on page load
```html
<!-- ❌ BAD - All 20 product images load immediately -->
<img src="products/laptop1.jpg" alt="Laptop">
<img src="products/laptop2.jpg" alt="Laptop">
<!-- ... 18 more ... -->
```

**Impact**: 
- 20 images × 200KB = 4MB download
- Slow initial page load
- High bandwidth usage

**✅ FIX: Lazy loading**
```html
<!-- Method 1: Browser native (recommended) -->
<img 
    src="placeholder.jpg" 
    data-src="products/laptop1.jpg" 
    loading="lazy" 
    alt="Laptop"
>

<!-- Method 2: JavaScript library (for older browsers) -->
<script src="https://cdn.jsdelivr.net/npm/lazysizes@5.3.2"></script>
<img 
    src="placeholder.jpg" 
    data-src="products/laptop1.jpg" 
    class="lazyload" 
    alt="Laptop"
>
```

**Effect**:
- Initial load: 1 image = 200KB
- On scroll: Only visible images load
- Total bandwidth: Same, but distributed
- **Time to interactive**: 90% faster ⚡

### 🔴 Problem 2: No CSS/JS Minification

**Current**: Loading full Bootstrap
```html
<!-- ❌ BAD - Full library sizes -->
<link href="bootstrap.css" rel="stylesheet">  <!-- 190KB -->
<script src="jquery.js"></script>  <!-- 87KB -->
<script src="main.js"></script>  <!-- maybe 10KB -->
```

**Impact**: 287KB + gzip = ~80KB

**✅ FIX: Use CDN with minified versions**
```html
<!-- ✅ GOOD - CDN minified -->
<link 
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
    rel="stylesheet"
>  <!-- 30KB minified -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>  <!-- 45KB minified -->

<!-- Minify your own CSS/JS -->
<link href="assets/css/style.min.css" rel="stylesheet">  <!-- 5KB minified -->
<script src="assets/js/main.min.js"></script>  <!-- 2KB minified -->
```

**Effect**: 80KB → 35KB = 56% reduction! 🚀

### 🔴 Problem 3: Missing Cache Headers

**Current**: Browser downloads all assets every time
```
Response Headers: (missing Cache-Control)
```

**✅ FIX: Add cache headers**
```php
// For static assets (PHP files serving images)
header('Cache-Control: public, max-age=2592000');  // 30 days
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');

// For HTML pages
header('Cache-Control: public, max-age=3600');  // 1 hour

// For sensitive data
header('Cache-Control: no-store, no-cache, must-revalidate');
```

**Effect**: 
- First visit: download all assets
- Repeat visits: use cached assets
- **Speed improvement**: 2-10x faster! ⚡

---

## 4. Database Connection Optimization

### Current: Singleton Pattern ✅
```php
// Good - Only one connection per request
$db = Database::getInstance();
```

### Needs: Connection Pooling (for high traffic)
```php
// For large deployments (100+ concurrent users)
// Use persistent connections:
$dsn = "pgsql:host=localhost;dbname=laptop_store;persistent=1";

// Or use connection pool service:
// - PgBouncer (PostgreSQL)
// - ProxySQL (MySQL)
// - Redis pool (for caching layer)
```

---

## 5. Performance Testing

### Test 1: Page Load Time
```bash
# Install Apache Bench
apt-get install apache2-utils

# Test home page (10 requests)
ab -n 10 -c 1 http://localhost/TienDat123/laptop_store-main/

# Test with concurrent users (5 concurrent, 100 total)
ab -n 100 -c 5 http://localhost/TienDat123/laptop_store-main/products.php
```

Expected results before optimization:
```
Requests per second: 10-20 req/s
Time per request: 50-100ms
```

After optimization (with caching):
```
Requests per second: 100-200 req/s  ⚡ (10x faster!)
Time per request: 5-10ms
```

### Test 2: Database Query Performance
```php
// Add timing to query method
class Database {
    public function query($sql, $params = []) {
        $start = microtime(true);
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $duration = microtime(true) - $start;
        
        if ($duration > 0.1) {  // Longer than 100ms
            error_log("SLOW QUERY ($duration ms): $sql");
        }
        
        return $stmt->fetchAll();
    }
}
```

### Test 3: Memory Usage
```php
// Check memory leaks
$before = memory_get_usage();
// ... do work ...
$after = memory_get_usage();

echo "Memory used: " . ($after - $before) . " bytes";
```

Expected: < 10MB per request

---

## 6. Production Checklist

- [ ] Enable output buffering: `ob_start()`
- [ ] Enable gzip compression: `ini_set('zlib.output_compression', 1)`
- [ ] Set reasonable timeout: `set_time_limit(30)`
- [ ] Limit memory: `ini_set('memory_limit', '128M')`
- [ ] Implement caching (Redis or APCu)
- [ ] Setup image optimization (optimize-images.sh)
- [ ] Minify CSS/JS
- [ ] Enable lazy loading
- [ ] Add cache headers
- [ ] Monitor slow queries (> 100ms)
- [ ] Setup database indexes
- [ ] Use CDN for static assets
- [ ] Setup HTTPS with HTTP/2

---

## Estimated Performance Gains

| Optimization | Impact | Effort |
|---|---|---|
| Database indexes | 50-70% query speedup | 1 hour |
| Query JOIN fixes | 90% for listing pages | 2-3 hours |
| Caching | 80-90% reduction on frequent pages | 3-4 hours |
| Lazy loading images | 60% faster initial load | 1-2 hours |
| CSS/JS minification | 40-50% smaller files | 1 hour |
| Cache headers | 70-90% faster on repeat visits | 30 min |
| **TOTAL IMPROVEMENT** | **10x faster overall** | **8-12 hours** |

---

## Recommended Priority

1. **Week 1**: Database indexes + N+1 query fixes (highest impact)
2. **Week 2**: Implement caching (APCu minimum, Redis if possible)
3. **Week 3**: Frontend optimization (lazy loading, minification)
4. **Week 4**: Monitor & fine-tune

---

**Document Version**: 1.0  
**Created**: 21-12-2025  
**Review Schedule**: Monthly performance audits
