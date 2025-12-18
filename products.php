<?php
/**
 * Trang Danh Sách Sản Phẩm
 * Hiển thị tất cả sản phẩm laptop với lọc và tìm kiếm
 */

require_once 'includes/config/config.php';
require_once 'includes/core/Database.php';
require_once 'includes/core/Session.php';
require_once 'includes/core/Auth.php';
require_once 'includes/helpers/functions.php';

Session::start();
$db = Database::getInstance();

// Lấy tham số tìm kiếm và lọc
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : (isset($_GET['search']) ? trim($_GET['search']) : '');
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Xây dựng câu truy vấn SQL
$where = ["p.status = 'active'", "s.status = 'approved'"];
$params = [];

if (!empty($keyword)) {
    $where[] = "(p.name ILIKE :keyword OR p.description ILIKE :keyword)";
    $params[':keyword'] = "%$keyword%";
}

if ($category_id > 0) {
    $where[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if (!empty($brand)) {
    $where[] = "p.brand = :brand";
    $params[':brand'] = $brand;
}

if ($min_price > 0) {
    $where[] = "p.price >= :min_price";
    $params[':min_price'] = $min_price;
}

if ($max_price > 0) {
    $where[] = "p.price <= :max_price";
    $params[':max_price'] = $max_price;
}

// Sắp xếp
$order_by = match($sort) {
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'popular' => 'p.sold_count DESC',
    'rating' => 'p.rating DESC',
    default => 'p.created_at DESC'
};

// Tính offset cho phân trang
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Đếm tổng số sản phẩm
$count_sql = "SELECT COUNT(*) as total 
              FROM products p 
              JOIN shops s ON p.shop_id = s.id 
              WHERE " . implode(' AND ', $where);
$total_result = $db->queryOne($count_sql, $params);
$total_products = $total_result['total'] ?? 0;
$total_pages = ceil($total_products / ITEMS_PER_PAGE);

// Lấy danh sách sản phẩm
$products_sql = "SELECT p.*, s.name as shop_name, s.slug as shop_slug,
                        c.name as category_name,
                        (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = true LIMIT 1) as main_image
                 FROM products p
                 JOIN shops s ON p.shop_id = s.id
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY $order_by
                 LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset";

$products = $db->query($products_sql, $params);

// Lấy danh sách danh mục
$categories = $db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

// Lấy danh sách thương hiệu
$brands = $db->query("SELECT DISTINCT brand FROM products WHERE status = 'active' AND brand IS NOT NULL ORDER BY brand");

$page_title = !empty($keyword) ? "Tìm kiếm: $keyword" : "Tất Cả Laptop";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        .filter-sidebar {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        .filter-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 8px;
        }
        .product-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .product-image {
            position: relative;
            height: 220px;
            overflow: hidden;
            background: #f8f9fa;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 1;
        }
        .product-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1;
        }
        .product-card:hover .product-actions {
            opacity: 1;
        }
        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            background: #007bff;
            color: white;
            transform: scale(1.1);
        }
        .product-info {
            padding: 15px;
        }
        .product-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.8rem;
        }
        .product-specs {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 10px;
        }
        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 10px;
        }
        .product-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
            font-size: 0.85rem;
        }
        .stars {
            color: #ffc107;
        }
        .shop-info {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 0.85rem;
            color: #666;
        }
        .sort-options {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .filter-chip {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        .filter-chip i {
            cursor: pointer;
            margin-left: 5px;
        }
        .no-products {
            text-align: center;
            padding: 60px 20px;
        }
        .no-products i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container my-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Trang chủ</a></li>
                <li class="breadcrumb-item active"><?= $page_title ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Sidebar Lọc -->
            <div class="col-lg-3">
                <form method="GET" action="" id="filterForm">
                    <!-- Tìm kiếm -->
                    <div class="filter-sidebar">
                        <h5 class="filter-title"><i class="fas fa-search"></i> Tìm kiếm</h5>
                        <input type="text" name="keyword" class="form-control" placeholder="Nhập từ khóa..." value="<?= htmlspecialchars($keyword) ?>">
                    </div>

                    <!-- Danh mục -->
                    <div class="filter-sidebar">
                        <h5 class="filter-title"><i class="fas fa-th-large"></i> Danh mục</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="category" value="0" id="cat_all" <?= $category_id == 0 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="cat_all">Tất cả</label>
                        </div>
                        <?php foreach ($categories as $cat): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="category" value="<?= $cat['id'] ?>" id="cat_<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="cat_<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Thương hiệu -->
                    <?php if (!empty($brands)): ?>
                    <div class="filter-sidebar">
                        <h5 class="filter-title"><i class="fas fa-copyright"></i> Thương hiệu</h5>
                        <select name="brand" class="form-select">
                            <option value="">Tất cả thương hiệu</option>
                            <?php foreach ($brands as $b): ?>
                            <option value="<?= htmlspecialchars($b['brand']) ?>" <?= $brand == $b['brand'] ? 'selected' : '' ?>><?= htmlspecialchars($b['brand']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Khoảng giá -->
                    <div class="filter-sidebar">
                        <h5 class="filter-title"><i class="fas fa-dollar-sign"></i> Khoảng giá</h5>
                        <div class="mb-2">
                            <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Giá tối thiểu" value="<?= $min_price > 0 ? $min_price : '' ?>">
                        </div>
                        <div>
                            <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Giá tối đa" value="<?= $max_price > 0 ? $max_price : '' ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Áp dụng lọc</button>
                </form>
            </div>

            <!-- Danh sách sản phẩm -->
            <div class="col-lg-9">
                <!-- Các filter đã chọn -->
                <?php if (!empty($keyword) || $category_id > 0 || !empty($brand) || $min_price > 0 || $max_price > 0): ?>
                <div class="mb-3">
                    <strong>Bộ lọc:</strong>
                    <?php if (!empty($keyword)): ?>
                    <span class="filter-chip">Từ khóa: <?= htmlspecialchars($keyword) ?> <i class="fas fa-times" onclick="removeFilter('keyword')"></i></span>
                    <?php endif; ?>
                    <?php if ($category_id > 0): ?>
                    <span class="filter-chip">Danh mục <i class="fas fa-times" onclick="removeFilter('category')"></i></span>
                    <?php endif; ?>
                    <?php if (!empty($brand)): ?>
                    <span class="filter-chip">Thương hiệu: <?= htmlspecialchars($brand) ?> <i class="fas fa-times" onclick="removeFilter('brand')"></i></span>
                    <?php endif; ?>
                    <?php if ($min_price > 0 || $max_price > 0): ?>
                    <span class="filter-chip">Giá: <?= formatPrice($min_price) ?> - <?= formatPrice($max_price) ?> <i class="fas fa-times" onclick="removeFilter('price')"></i></span>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/products.php" class="btn btn-sm btn-outline-secondary">Xóa tất cả</a>
                </div>
                <?php endif; ?>

                <!-- Sắp xếp và kết quả -->
                <div class="sort-options">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <strong>Tìm thấy <?= number_format($total_products) ?> sản phẩm</strong>
                        </div>
                        <div class="col-md-6">
                            <select name="sort" class="form-select form-select-sm" onchange="changeSort(this.value)">
                                <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                                <option value="popular" <?= $sort == 'popular' ? 'selected' : '' ?>>Bán chạy</option>
                                <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Giá thấp - cao</option>
                                <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Giá cao - thấp</option>
                                <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>Đánh giá cao</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Grid sản phẩm -->
                <?php if (!empty($products)): ?>
                <div class="row g-3">
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card product-card">
                            <div class="product-image">
                                <?php if ($product['discount_percentage'] > 0): ?>
                                <span class="product-badge">-<?= $product['discount_percentage'] ?>%</span>
                                <?php endif; ?>
                                
                                <div class="product-actions">
                                    <button class="action-btn btn-wishlist" data-id="<?= $product['id'] ?>" title="Yêu thích">
                                        <i class="far fa-heart"></i>
                                    </button>
                                    <button class="action-btn" onclick="quickView(<?= $product['id'] ?>)" title="Xem nhanh">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>

                                <a href="<?= SITE_URL ?>/product-detail.php?id=<?= $product['id'] ?>">
                                    <?php $img = image_url($product['main_image'] ?? ''); ?>
                                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                </a>
                            </div>

                            <div class="product-info">
                                <a href="<?= SITE_URL ?>/product-detail.php?id=<?= $product['id'] ?>" class="text-decoration-none">
                                    <h6 class="product-name"><?= htmlspecialchars($product['name']) ?></h6>
                                </a>

                                <div class="product-specs">
                                    <?php if (!empty($product['processor'])): ?>
                                    <span><i class="fas fa-microchip"></i> <?= htmlspecialchars($product['processor']) ?></span><br>
                                    <?php endif; ?>
                                    <?php if (!empty($product['ram'])): ?>
                                    <span><i class="fas fa-memory"></i> RAM <?= htmlspecialchars($product['ram']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="product-rating">
                                    <span class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?= $i <= $product['rating'] ? '' : '-o' ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                    <span>(<?= $product['review_count'] ?>)</span>
                                </div>

                                <div class="product-price">
                                    <?= formatPrice($product['price']) ?>
                                    <?php if ($product['original_price'] > $product['price']): ?>
                                    <small class="text-muted text-decoration-line-through"><?= formatPrice($product['original_price']) ?></small>
                                    <?php endif; ?>
                                </div>

                                <button class="btn btn-primary btn-sm w-100 btn-add-to-cart" data-id="<?= $product['id'] ?>">
                                    <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                                </button>

                                <div class="shop-info">
                                    <i class="fas fa-store"></i>
                                    <a href="<?= SITE_URL ?>/shop.php?slug=<?= $product['shop_slug'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($product['shop_name']) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Phân trang -->
                <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Trước</a></li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Sau</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

                <?php else: ?>
                <div class="no-products">
                    <i class="fas fa-inbox"></i>
                    <h4>Không tìm thấy sản phẩm nào</h4>
                    <p class="text-muted">Vui lòng thử lại với bộ lọc khác</p>
                    <a href="<?= SITE_URL ?>/products.php" class="btn btn-primary">Xem tất cả sản phẩm</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
    <script>
        // Thay đổi sắp xếp
        function changeSort(sort) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sort);
            window.location.href = url.toString();
        }

        // Xóa bộ lọc
        function removeFilter(type) {
            const url = new URL(window.location.href);
            if (type === 'keyword') url.searchParams.delete('keyword');
            if (type === 'category') url.searchParams.delete('category');
            if (type === 'brand') url.searchParams.delete('brand');
            if (type === 'price') {
                url.searchParams.delete('min_price');
                url.searchParams.delete('max_price');
            }
            window.location.href = url.toString();
        }

        // Xem nhanh sản phẩm
        function quickView(productId) {
            // TODO: Implement quick view modal
            window.location.href = '<?= SITE_URL ?>/product-detail.php?id=' + productId;
        }

        // Tự động submit form khi thay đổi radio
        $('input[type=radio]').on('change', function() {
            $('#filterForm').submit();
        });
    </script>
</body>
</html>
