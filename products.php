<?php
require_once 'includes/init.php';

// Lấy tham số tìm kiếm và lọc
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : (isset($_GET['search']) ? trim($_GET['search']) : '');
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Khởi tạo database
$db = Database::getInstance();

// Xây dựng câu truy vấn
$where = ["p.status = 'active'", "s.status = 'active'"];
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
    $where[] = "p.brand_id = :brand";
    $params[':brand'] = $brand;
}

// Sắp xếp
$order_by = match($sort) {
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'popular' => 'p.sold_count DESC',
    'rating' => 'p.rating_average DESC',
    default => 'p.created_at DESC'
};

// Phân trang
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
$products_sql = "SELECT p.id, p.name, p.price, p.sale_price, p.stock_quantity, 
                        p.cpu, p.ram, p.sold_count, p.rating_average, p.review_count,
                        s.shop_name,
                        (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY display_order LIMIT 1) as main_image
                 FROM products p
                 JOIN shops s ON p.shop_id = s.id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY $order_by
                 LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset";

$products = $db->query($products_sql, $params);

// Lấy danh sách danh mục
$categories = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

// Lấy danh sách thương hiệu
$brands = $db->query("SELECT DISTINCT brand_id FROM products WHERE status = 'active' ORDER BY brand_id");

$pageTitle = !empty($keyword) ? "Tìm kiếm: $keyword" : "Tất cả laptop";
include __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="mb-4">
        <h2><i class="bi bi-laptop"></i> <?= escape($pageTitle) ?></h2>
        <hr>
    </div>

    <div class="row">
        <!-- Bộ lọc bên trái -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Lọc sản phẩm</h5>

                    <!-- Tìm kiếm -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Tìm kiếm</label>
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm" 
                                   name="keyword" value="<?= escape($keyword) ?>" 
                                   placeholder="Tên sản phẩm...">
                            <button class="btn btn-sm btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Danh mục -->
                    <?php if (!empty($categories)): ?>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Danh mục</label>
                        <div class="list-group list-group-flush">
                            <a href="?<?= http_build_query(array_merge($_GET, ['category' => 0])) ?>" 
                               class="list-group-item list-group-item-action <?= $category_id == 0 ? 'active' : '' ?>">
                                Tất cả
                            </a>
                            <?php foreach ($categories as $cat): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['category' => $cat['id']])) ?>" 
                               class="list-group-item list-group-item-action <?= $category_id == $cat['id'] ? 'active' : '' ?>">
                                <?= escape($cat['name']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Sắp xếp -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sắp xếp</label>
                        <select class="form-select form-select-sm" onchange="window.location='?' + new URLSearchParams(Object.assign(Object.fromEntries(new URLSearchParams(window.location.search)), {sort: this.value})).toString()">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá: Thấp đến cao</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá: Cao đến thấp</option>
                            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Bán chạy</option>
                            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Đánh giá cao</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="col-lg-9">
            <!-- Trường hợp: Không có sản phẩm -->
            <?php if (empty($products)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Không tìm thấy sản phẩm phù hợp.
                <a href="<?= SITE_URL ?>/products.php" class="alert-link">Xem tất cả sản phẩm</a>
            </div>

            <!-- Trường hợp: Có sản phẩm -->
            <?php else: ?>
            <!-- Thông tin phân trang -->
            <div class="mb-3">
                <small class="text-muted">
                    Tìm thấy <strong><?= $total_products ?></strong> sản phẩm
                    (Trang <strong><?= $page ?></strong> / <strong><?= $total_pages ?></strong>)
                </small>
            </div>

            <!-- Lưới sản phẩm -->
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($products as $prod): 
                    $price = getDisplayPrice($prod['price'], $prod['sale_price']);
                    $discount = calculateDiscount($prod['price'], $prod['sale_price']);
                ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0 overflow-hidden">
                        <!-- Ảnh sản phẩm -->
                        <div class="position-relative" style="height: 200px; overflow: hidden; background: #f8f9fa;">
                            <img src="<?= image_url($prod['main_image']) ?>" 
                                 alt="<?= escape($prod['name']) ?>" 
                                 class="card-img-top h-100" 
                                 style="object-fit: cover;">
                            
                            <!-- Badge giảm giá -->
                            <?php if ($discount > 0): ?>
                            <span class="badge bg-danger position-absolute top-2 end-2">
                                -<?= (int)$discount ?>%
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <!-- Tên sản phẩm -->
                            <h5 class="card-title" style="font-size: 0.95rem; min-height: 2.4em;">
                                <a href="<?= SITE_URL ?>/product-detail.php?id=<?= (int)$prod['id'] ?>" 
                                   class="text-decoration-none text-dark">
                                    <?= escape($prod['name']) ?>
                                </a>
                            </h5>

                            <!-- Spec -->
                            <small class="text-muted mb-2">
                                <i class="bi bi-cpu"></i> <?= escape($prod['cpu']) ?> | 
                                <i class="bi bi-memory"></i> <?= (int)$prod['ram'] ?>GB
                            </small>

                            <!-- Rating -->
                            <div class="mb-2">
                                <span class="text-warning">
                                    <i class="bi bi-star-fill"></i> <?= number_format($prod['rating_average'], 1) ?>
                                </span>
                                <small class="text-muted">(<?= (int)$prod['review_count'] ?> đánh giá)</small>
                            </div>

                            <!-- Giá -->
                            <div class="mb-2">
                                <span class="fs-5 fw-bold text-danger">
                                    <?= formatPrice($price) ?>
                                </span>
                                <?php if (!empty($prod['sale_price']) && $prod['sale_price'] < $prod['price']): ?>
                                <span class="text-muted" style="text-decoration: line-through;">
                                    <?= formatPrice($prod['price']) ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <!-- Shop -->
                            <small class="text-muted mb-3">
                                <i class="bi bi-shop"></i> <?= escape($prod['shop_name']) ?>
                            </small>

                            <!-- Nút -->
                            <div class="mt-auto d-flex gap-2">
                                <a href="<?= SITE_URL ?>/product-detail.php?id=<?= (int)$prod['id'] ?>" 
                                   class="btn btn-outline-primary btn-sm flex-grow-1">
                                    <i class="bi bi-eye"></i> Xem chi tiết
                                </a>
                                <button class="btn btn-success btn-sm" 
                                        onclick="addToCart(<?= (int)$prod['id'] ?>)">
                                    <i class="bi bi-cart"></i> Thêm giỏ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Phân trang -->
            <?php if ($total_pages > 1): ?>
            <nav class="d-flex justify-content-center mt-5">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">Đầu</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Trước</a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Sau</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">Cuối</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
