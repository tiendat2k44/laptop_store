<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Trang chủ';

$db = Database::getInstance();

// Get featured products
$featuredProducts = $db->query("
    SELECT p.*, b.name as brand_name, s.shop_name,
           (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY display_order LIMIT 1) as main_image
    FROM products p
    JOIN brands b ON p.brand_id = b.id
    JOIN shops s ON p.shop_id = s.id
    WHERE p.status = 'active' AND p.featured = true
    ORDER BY p.created_at DESC
    LIMIT 8
");

// Get latest products
$latestProducts = $db->query("
    SELECT p.*, b.name as brand_name, s.shop_name,
           (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY display_order LIMIT 1) as main_image
    FROM products p
    JOIN brands b ON p.brand_id = b.id
    JOIN shops s ON p.shop_id = s.id
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
    LIMIT 8
");

// Get banners
$banners = $db->query("SELECT * FROM banners WHERE status = 'active' ORDER BY display_order LIMIT 5");

// Get categories
$categories = $db->query("SELECT * FROM categories WHERE parent_id IS NULL AND status = 'active' ORDER BY display_order, name");

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-0">
    <!-- Hero Carousel -->
    <?php if (!empty($banners)): ?>
    <div id="heroCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach ($banners as $index => $banner): ?>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $index; ?>" <?php echo $index === 0 ? 'class="active"' : ''; ?>></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach ($banners as $index => $banner): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <?php if (!empty($banner['link'])): ?>
                        <a href="<?php echo escape($banner['link']); ?>">
                    <?php endif; ?>
                        <img src="<?php echo image_url($banner['image']); ?>" class="d-block w-100" alt="<?php echo escape($banner['title']); ?>" style="max-height: 500px; object-fit: cover;">
                    <?php if (!empty($banner['link'])): ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="container">
    <!-- Categories Section -->
    <?php if (!empty($categories)): ?>
    <section class="mb-5">
        <h2 class="mb-4">Danh mục sản phẩm</h2>
        <div class="row g-3">
            <?php foreach ($categories as $category): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                        <div class="card text-center h-100 category-card">
                            <?php if (!empty($category['image'])): ?>
                                <img src="<?php echo image_url($category['image']); ?>" class="card-img-top p-3" alt="<?php echo escape($category['name']); ?>">
                            <?php else: ?>
                                <div class="p-5 bg-light">
                                    <i class="bi bi-laptop fs-1 text-primary"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h6 class="card-title mb-0"><?php echo escape($category['name']); ?></h6>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Products -->
    <?php if (!empty($featuredProducts)): ?>
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="bi bi-star-fill text-warning"></i> Sản phẩm nổi bật</h2>
            <a href="<?php echo SITE_URL; ?>/products.php?featured=1" class="btn btn-outline-primary">Xem tất cả</a>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php else: ?>
    <section class="mb-5 text-center bg-light p-5 rounded">
        <h4 class="mb-2">Chưa có sản phẩm nổi bật</h4>
        <p class="text-muted mb-3">Hãy nhập dữ liệu mẫu (database/sample_data.sql) hoặc thêm sản phẩm mới.</p>
        <a class="btn btn-primary" href="<?php echo SITE_URL; ?>/products.php">Xem tất cả sản phẩm</a>
    </section>
    <?php endif; ?>

    <!-- Latest Products -->
    <?php if (!empty($latestProducts)): ?>
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Sản phẩm mới nhất</h2>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline-primary">Xem tất cả</a>
        </div>
        <div class="row g-4">
            <?php foreach ($latestProducts as $product): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php else: ?>
    <section class="mb-5 text-center bg-light p-5 rounded">
        <h4 class="mb-2">Chưa có sản phẩm để hiển thị</h4>
        <p class="text-muted mb-3">Vui lòng nhập dữ liệu mẫu (database/sample_data.sql) hoặc thêm sản phẩm.</p>
        <a class="btn btn-primary" href="<?php echo SITE_URL; ?>/products.php">Xem tất cả sản phẩm</a>
    </section>
    <?php endif; ?>

    <!-- Features Section -->
    <section class="mb-5">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="text-center p-4 bg-white rounded shadow-sm">
                    <i class="bi bi-truck fs-1 text-primary mb-3"></i>
                    <h5>Giao hàng toàn quốc</h5>
                    <p class="text-muted mb-0">Miễn phí vận chuyển đơn từ 500k</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-4 bg-white rounded shadow-sm">
                    <i class="bi bi-shield-check fs-1 text-success mb-3"></i>
                    <h5>Bảo hành chính hãng</h5>
                    <p class="text-muted mb-0">Bảo hành tại hãng trên toàn quốc</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-4 bg-white rounded shadow-sm">
                    <i class="bi bi-arrow-clockwise fs-1 text-warning mb-3"></i>
                    <h5>Đổi trả dễ dàng</h5>
                    <p class="text-muted mb-0">Đổi trả trong vòng 7 ngày</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-4 bg-white rounded shadow-sm">
                    <i class="bi bi-headset fs-1 text-info mb-3"></i>
                    <h5>Hỗ trợ 24/7</h5>
                    <p class="text-muted mb-0">Hotline: 1900-xxxx</p>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
