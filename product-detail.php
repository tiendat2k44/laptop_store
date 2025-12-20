<?php
require_once 'includes/init.php';

// Lấy ID sản phẩm
$productId = intval($_GET['id'] ?? 0);
if ($productId <= 0) {
    redirect('/products.php');
}

$db = Database::getInstance();

// Lấy thông tin sản phẩm
$product = $db->queryOne(
    "SELECT p.*, s.shop_name, s.phone as shop_phone, s.rating as shop_rating,
            c.name as category_name
     FROM products p
     JOIN shops s ON p.shop_id = s.id
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.id = :id AND p.status = 'active' AND s.status = 'active'",
    [':id' => $productId]
);

if (!$product) {
    redirect('/products.php');
}

// Lấy hình ảnh sản phẩm
$images = $db->query(
    "SELECT image_url FROM product_images WHERE product_id = :id ORDER BY display_order",
    [':id' => $productId]
);

// Lấy đánh giá (giới hạn 10)
$reviews = $db->query(
    "SELECT r.rating, r.comment, r.created_at, u.full_name, u.avatar
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     WHERE r.product_id = :id AND r.status = 'approved'
     ORDER BY r.created_at DESC
     LIMIT 10",
    [':id' => $productId]
);

// Lấy sản phẩm liên quan (cùng danh mục)
$relatedProducts = $db->query(
    "SELECT p.id, p.name, p.price, p.sale_price, p.rating_average,
            (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY display_order LIMIT 1) as main_image
     FROM products p
     WHERE p.id != :id AND p.status = 'active' 
           AND p.category_id = :category_id
     ORDER BY RANDOM()
     LIMIT 6",
    [':id' => $productId, ':category_id' => $product['category_id']]
);

// Cập nhật lượt xem
$db->execute("UPDATE products SET view_count = view_count + 1 WHERE id = :id", [':id' => $productId]);

// Kiểm tra user đã mua sản phẩm này không (để hiện nút đánh giá)
$canReview = false;
if (Auth::check()) {
    $orderCheck = $db->queryOne(
        "SELECT COUNT(*) as count FROM order_items oi
         JOIN orders o ON oi.order_id = o.id
         WHERE oi.product_id = :pid AND o.user_id = :uid AND o.status = 'delivered'",
        [':pid' => $productId, ':uid' => Auth::id()]
    );
    $canReview = ($orderCheck['count'] ?? 0) > 0;
}

// SEO meta data
$pageTitle = $product['name'];
$pageDescription = mb_substr(strip_tags($product['description'] ?? ''), 0, 160) . '... - Giá: ' . formatPrice($displayPrice = getDisplayPrice($product['price'], $product['sale_price']));
$pageImage = image_url(!empty($images) ? $images[0]['image_url'] : ($product['thumbnail'] ?? ''));
$pageUrl = SITE_URL . '/product-detail.php?id=' . $productId;

include __DIR__ . '/includes/header.php';

$discount = calculateDiscount($product['price'], $product['sale_price']);
?>

<div class="container my-5">
    <div class="row">
        <!-- Cột trái: Ảnh sản phẩm -->
        <div class="col-lg-5 mb-4">
            <div class="sticky-top" style="top: 20px;">
                <!-- Ảnh chính -->
                <div class="mb-3 rounded overflow-hidden" style="background: #f8f9fa; height: 450px; display: flex; align-items: center; justify-content: center;">
                    <img id="mainImage" 
                         src="<?= image_url($images[0]['image_url'] ?? $product['thumbnail']) ?>" 
                         alt="<?= escape($product['name']) ?>"
                         style="max-width: 100%; max-height: 100%; object-fit: contain;"
                         loading="lazy">
                </div>

                <!-- Ảnh nhỏ -->
                <?php if (!empty($images) && count($images) > 1): ?>
                <div class="d-grid gap-2" style="grid-template-columns: repeat(4, 1fr);">
                    <?php foreach ($images as $idx => $img): ?>
                    <img src="<?= image_url($img['image_url']) ?>" 
                         alt="" 
                         class="rounded cursor-pointer border <?= $idx === 0 ? 'border-primary border-2' : '' ?>"
                         style="height: 80px; object-fit: cover; cursor: pointer;"
                         onclick="document.getElementById('mainImage').src='<?= image_url($img['image_url']) ?>';"
                         loading="lazy">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cột phải: Thông tin sản phẩm -->
        <div class="col-lg-7">
            <!-- Tiêu đề -->
            <h1 class="fs-3 fw-bold mb-2"><?= escape($product['name']) ?></h1>

            <!-- Rating -->
            <div class="mb-3">
                <span class="text-warning">
                    <i class="bi bi-star-fill"></i> 
                    <?= number_format($product['rating_average'], 1) ?>/5
                </span>
                <span class="text-muted">
                    (<?= (int)$product['review_count'] ?> đánh giá)
                </span>
            </div>

            <!-- Giá -->
            <div class="card shadow-sm p-4 mb-4" style="background: #f8f9fa;">
                <div class="d-flex align-items-end gap-2 mb-2">
                    <span class="fs-3 fw-bold text-danger">
                        <?= formatPrice($displayPrice) ?>
                    </span>
                    <?php if ($discount > 0): ?>
                    <span class="badge bg-danger fs-6">-<?= (int)$discount ?>%</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                <span class="text-muted" style="text-decoration: line-through;">
                    <?= formatPrice($product['price']) ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Thông số kỹ thuật -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Thông số kỹ thuật</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Processor:</strong> <?= escape($product['cpu']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>RAM:</strong> <?= (int)$product['ram'] ?> GB
                        </div>
                        <div class="col-md-6">
                            <strong>Storage:</strong> <?= (int)$product['storage_gb'] ?> GB
                        </div>
                        <div class="col-md-6">
                            <strong>Display:</strong> <?= escape($product['screen_size']) ?>"
                        </div>
                        <div class="col-12">
                            <strong>Mô tả:</strong>
                            <p><?= nl2br(escape($product['description'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thông tin kho -->
            <div class="alert <?= $product['stock_quantity'] > 0 ? 'alert-success' : 'alert-danger' ?>">
                <?php if ($product['stock_quantity'] > 0): ?>
                    <i class="bi bi-check-circle"></i> 
                    Còn <strong><?= (int)$product['stock_quantity'] ?></strong> sản phẩm trong kho
                <?php else: ?>
                    <i class="bi bi-x-circle"></i> 
                    Hiện đang hết hàng
                <?php endif; ?>
            </div>

            <!-- Nút hành động -->
            <div class="d-flex gap-2">
                <button class="btn btn-success btn-lg flex-grow-1" 
                        onclick="addToCart(<?= (int)$productId ?>)"
                        <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-cart"></i> Thêm vào giỏ
                </button>
                <a href="<?= SITE_URL ?>/checkout.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-credit-card"></i> Mua ngay
                </a>
            </div>

            <!-- Thông tin shop -->
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Thông tin cửa hàng</h5>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <strong><?= escape($product['shop_name']) ?></strong>
                            <br>
                            <small class="text-muted">
                                <i class="bi bi-star"></i> 
                                <?= number_format($product['shop_rating'], 1) ?>/5
                            </small>
                        </div>
                        <a href="tel:<?= escape($product['shop_phone']) ?>" class="btn btn-outline-primary">
                            <i class="bi bi-telephone"></i> Liên hệ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <!-- Đánh giá sản phẩm -->
    <div class="row">
        <div class="col-lg-8">
            <h3 class="mb-4">
                <i class="bi bi-chat-dots"></i> Đánh giá từ khách hàng
                (<?= count($reviews) ?>)
            </h3>

            <?php if (Auth::check() && $canReview): ?>
            <div class="alert alert-info mb-4">
                <a href="<?= SITE_URL ?>/account/review.php?product_id=<?= $productId ?>&order_id=0" 
                   class="alert-link fw-bold">
                    Bạn đã mua sản phẩm này - Chia sẻ đánh giá của bạn →
                </a>
            </div>
            <?php endif; ?>

            <?php if (empty($reviews)): ?>
            <div class="alert alert-secondary">
                <i class="bi bi-info-circle me-2"></i>
                Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này!
            </div>
            <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <strong><?= escape($rev['full_name']) ?></strong>
                        <span class="ms-auto text-warning">
                            <?php for ($i = 0; $i < $rev['rating']; $i++): ?>
                            <i class="bi bi-star-fill"></i>
                            <?php endfor; ?>
                            <?php for ($i = $rev['rating']; $i < 5; $i++): ?>
                            <i class="bi bi-star"></i>
                            <?php endfor; ?>
                        </span>
                    </div>
                    <p class="mb-1"><?= escape($rev['comment']) ?></p>
                    <small class="text-muted">
                        <?= formatDate($rev['created_at']) ?>
                    </small>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sản phẩm liên quan -->
    <?php if (!empty($relatedProducts)): ?>
    <hr class="my-5">

    <div>
        <h3 class="mb-4">
            <i class="bi bi-eye"></i> Sản phẩm liên quan
        </h3>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($relatedProducts as $rel): 
                $relPrice = getDisplayPrice($rel['price'], $rel['sale_price']);
                $relDiscount = calculateDiscount($rel['price'], $rel['sale_price']);
            ?>
            <div class="col">
                <div class="card h-100 shadow-sm border-0">
                    <div class="position-relative" style="height: 180px; overflow: hidden; background: #f8f9fa;">
                        <img src="<?= image_url($rel['main_image']) ?>" 
                             alt="<?= escape($rel['name']) ?>"
                             class="card-img-top h-100"
                             style="object-fit: cover;">
                        <?php if ($relDiscount > 0): ?>
                        <span class="badge bg-danger position-absolute top-2 end-2">
                            -<?= (int)$relDiscount ?>%
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" style="font-size: 0.9rem;">
                            <a href="<?= SITE_URL ?>/product-detail.php?id=<?= (int)$rel['id'] ?>" 
                               class="text-decoration-none text-dark">
                                <?= escape($rel['name']) ?>
                            </a>
                        </h5>
                        <p class="card-text">
                            <strong class="text-danger">
                                <?= formatPrice($relPrice) ?>
                            </strong>
                            <br>
                            <span class="text-warning small">
                                <i class="bi bi-star-fill"></i> 
                                <?= number_format($rel['rating_average'], 1) ?>
                            </span>
                        </p>
                        <a href="<?= SITE_URL ?>/product-detail.php?id=<?= (int)$rel['id'] ?>" 
                           class="btn btn-sm btn-outline-primary w-100">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
