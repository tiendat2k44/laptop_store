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
$db->execute("UPDATE products SET views = views + 1 WHERE id = :id", [':id' => $productId]);

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

<div class="container-fluid my-5 px-3 px-md-5">
    <div class="row g-4">
        <!-- Cột trái: Ảnh sản phẩm (60%) -->
        <div class="col-lg-6">
            <div class="sticky-top" style="top: 100px;">
                <!-- Ảnh chính - Lớn hơn, tỉ lệ hình ảnh tốt -->
                <div class="mb-3 rounded-lg overflow-hidden" style="background: #f8f9fa; aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border: 1px solid #e9ecef;">
                    <img id="mainImage" 
                         src="<?= image_url($images[0]['image_url'] ?? $product['thumbnail']) ?>" 
                         alt="<?= escape($product['name']) ?>"
                         style="max-width: 100%; max-height: 100%; object-fit: contain; padding: 20px;"
                         loading="lazy"
                         class="img-fluid">
                </div>

                <!-- Ảnh nhỏ - Grid 5 ảnh, cuộn ngang nếu cần -->
                <?php if (!empty($images) && count($images) > 1): ?>
                <div class="d-flex gap-2 overflow-x-auto pb-2">
                    <?php foreach ($images as $idx => $img): ?>
                    <img src="<?= image_url($img['image_url']) ?>" 
                         alt="" 
                         class="rounded border flex-shrink-0 cursor-pointer transition <?= $idx === 0 ? 'border-primary border-3' : 'border-2 border-light' ?>"
                         style="width: 90px; height: 90px; object-fit: cover; cursor: pointer; opacity: <?= $idx === 0 ? '1' : '0.7' ?>;"
                         onclick="document.getElementById('mainImage').src='<?= image_url($img['image_url']) ?>'; updateImageSelection(this);"
                         loading="lazy">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cột phải: Thông tin sản phẩm (40%) -->
        <div class="col-lg-6">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/products.php">Sản phẩm</a></li>
                    <li class="breadcrumb-item active"><?= escape($product['category_name'] ?? 'Khác') ?></li>
                </ol>
            </nav>

            <!-- Tiêu đề -->
            <h1 class="fs-2 fw-bold mb-2"><?= escape($product['name']) ?></h1>

            <!-- Rating & Reviews -->
            <div class="mb-3 d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-1">
                    <span class="text-warning fs-5">
                        <?php for ($i = 0; $i < round($product['rating_average'] ?? 0); $i++): ?>
                        <i class="bi bi-star-fill"></i>
                        <?php endfor; ?>
                        <?php for ($i = round($product['rating_average'] ?? 0); $i < 5; $i++): ?>
                        <i class="bi bi-star"></i>
                        <?php endfor; ?>
                    </span>
                    <span class="fw-bold text-dark"><?= number_format($product['rating_average'] ?? 0, 1) ?>/5</span>
                </div>
                <a href="#reviews" class="text-decoration-none text-muted">
                    <small>(<?= (int)($product['review_count'] ?? 0) ?> đánh giá)</small>
                </a>
                <span class="badge bg-success">Bán chạy</span>
            </div>

            <!-- Giá & Discount -->
            <div class="card border-0 bg-light p-4 mb-4">
                <div class="d-flex align-items-baseline gap-3">
                    <span class="fs-2 fw-bold text-danger">
                        <?= formatPrice($displayPrice) ?>
                    </span>
                    <?php if ($discount > 0): ?>
                    <span class="fs-5 text-muted" style="text-decoration: line-through;">
                        <?= formatPrice($product['price']) ?>
                    </span>
                    <span class="badge bg-danger fs-6">Giảm <?= (int)$discount ?>%</span>
                    <?php endif; ?>
                </div>
                <small class="text-muted mt-2 d-block">Đã bán: <strong><?= (int)$product['sold_count'] ?></strong> sản phẩm</small>
            </div>

            <!-- Thông số chính nổi bật -->
            <div class="row g-2 mb-4">
                <div class="col-6">
                    <div class="p-3 border rounded text-center">
                        <small class="text-muted d-block mb-1">Processor</small>
                        <strong class="fs-6"><?= escape($product['cpu'] ?? 'N/A') ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 border rounded text-center">
                        <small class="text-muted d-block mb-1">RAM</small>
                        <strong class="fs-6"><?= escape($product['ram'] ?? 'N/A') ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 border rounded text-center">
                        <small class="text-muted d-block mb-1">Storage</small>
                        <strong class="fs-6"><?= escape($product['storage'] ?? 'N/A') ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 border rounded text-center">
                        <small class="text-muted d-block mb-1">Màn hình</small>
                        <strong class="fs-6"><?= escape($product['screen_size'] ?? 'N/A') ?>"</strong>
                    </div>
                </div>
            </div>

            <!-- Tình trạng kho -->
            <div class="mb-4">
                <?php if ($product['stock_quantity'] > 0): ?>
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle fs-5 me-2"></i>
                    <div>
                        <strong>Còn hàng</strong>
                        <br>
                        <small><?= (int)$product['stock_quantity'] ?> sản phẩm sẵn sàng giao</small>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="bi bi-exclamation-circle fs-5 me-2"></i>
                    <div>
                        <strong>Hết hàng tạm thời</strong>
                        <br>
                        <small>Vui lòng theo dõi để cập nhật khi có hàng</small>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Nút hành động -->
            <div class="d-grid gap-2 mb-4">
                <button class="btn btn-success btn-lg fw-bold py-3 btn-add-to-cart" 
                        data-product-id="<?= (int)$productId ?>"
                        data-quantity="1"
                        <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-cart-plus fs-5 me-2"></i> Thêm vào giỏ hàng
                </button>
                <button class="btn btn-primary btn-lg fw-bold py-3 btn-buy-now" 
                        data-product-id="<?= (int)$productId ?>"
                        <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-lightning-charge fs-5 me-2"></i> Mua ngay
                </button>
            </div>

            <!-- Thông tin cửa hàng -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">Bán bởi</h6>
                            <strong class="fs-5"><?= escape($product['shop_name']) ?></strong>
                            <br>
                            <small class="text-warning d-block mt-1">
                                <i class="bi bi-star-fill"></i> 
                                <?= number_format($product['shop_rating'] ?? 0, 1) ?>/5 
                                <span class="text-muted">(Đánh giá cửa hàng)</span>
                            </small>
                        </div>
                        <a href="tel:<?= escape($product['shop_phone']) ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-telephone"></i> Liên hệ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mô tả chi tiết & Thông số kỹ thuật -->
    <div class="row g-4 mt-2">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="bi bi-file-text"></i> Mô tả sản phẩm</h5>
                </div>
                <div class="card-body">
                    <div class="text-muted lh-lg">
                        <?= !empty($product['description']) ? nl2br(escape($product['description'])) : '<em>Chưa có mô tả</em>' ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0"><i class="bi bi-sliders"></i> Thông số kỹ thuật</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tbody>
                            <tr>
                                <td class="text-muted"><strong>Processor:</strong></td>
                                <td><?= escape($product['cpu'] ?? 'N/A') ?></td>
                            </tr>
                            <tr class="table-light">
                                <td class="text-muted"><strong>RAM:</strong></td>
                                <td><?= escape($product['ram'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Storage:</strong></td>
                                <td><?= escape($product['storage'] ?? 'N/A') ?></td>
                            </tr>
                            <tr class="table-light">
                                <td class="text-muted"><strong>Màn hình:</strong></td>
                                <td><?= escape($product['screen_size'] ?? 'N/A') ?>"</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Danh mục:</strong></td>
                                <td><span class="badge bg-secondary"><?= escape($product['category_name'] ?? 'N/A') ?></span></td>
                            </tr>
                            <tr class="table-light">
                                <td class="text-muted"><strong>Lượt xem:</strong></td>
                                <td><?= (int)($product['views'] ?? 0) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Đã bán:</strong></td>
                                <td class="fw-bold text-success"><?= (int)($product['sold_count'] ?? 0) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-5">

    <!-- Đánh giá chi tiết -->
    <div class="row g-4" id="reviews">
        <div class="col-lg-8">
            <div class="mb-4">
                <h3 class="mb-4">
                    <i class="bi bi-chat-dots-fill"></i> Đánh giá của khách hàng
                </h3>

                <!-- Nút đánh giá cho người đã mua -->
                <?php if (Auth::check() && $canReview): ?>
                <div class="alert alert-info alert-dismissible fade show">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Bạn đã mua sản phẩm này!</strong>
                    <a href="<?= SITE_URL ?>/account/review.php?product_id=<?= $productId ?>&order_id=0" 
                       class="alert-link fw-bold">
                        Chia sẻ đánh giá của bạn →
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php elseif (!Auth::check()): ?>
                <div class="alert alert-secondary mb-4">
                    <i class="bi bi-lock me-2"></i>
                    <a href="<?= SITE_URL ?>/login.php" class="alert-link fw-bold">Đăng nhập</a> 
                    để xem đánh giá chi tiết và chia sẻ trải nghiệm của bạn
                </div>
                <?php endif; ?>

                <?php if (empty($reviews)): ?>
                <div class="alert alert-secondary">
                    <i class="bi bi-inbox fs-5"></i>
                    <p class="mb-0 mt-2">Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này!</p>
                </div>
                <?php else: ?>
                <!-- Summary stats -->
                <div class="d-flex gap-3 mb-4 p-3 bg-light rounded">
                    <div class="text-center">
                        <h4 class="mb-0 text-danger"><?= number_format($product['rating_average'], 1) ?></h4>
                        <small class="text-muted">trên 5</small>
                    </div>
                    <div>
                        <div class="small">
                            <div class="mb-1">
                                <span class="text-warning">★★★★★</span> 
                                <span class="text-muted">(?)</span>
                            </div>
                            <div class="mb-1">
                                <span class="text-warning">★★★★☆</span> 
                                <span class="text-muted">(?)</span>
                            </div>
                            <div class="mb-1">
                                <span class="text-warning">★★★☆☆</span> 
                                <span class="text-muted">(?)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reviews list -->
                <div class="space-y-3">
                    <?php foreach ($reviews as $rev): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between mb-2">
                                <div>
                                    <h6 class="mb-1">
                                        <strong><?= escape($rev['full_name']) ?></strong>
                                    </h6>
                                    <div class="text-warning small mb-2">
                                        <?php for ($i = 0; $i < $rev['rating']; $i++): ?>
                                        <i class="bi bi-star-fill"></i>
                                        <?php endfor; ?>
                                        <?php for ($i = $rev['rating']; $i < 5; $i++): ?>
                                        <i class="bi bi-star"></i>
                                        <?php endfor; ?>
                                        <span class="text-muted ms-2">(<?= (int)$rev['rating'] ?> sao)</span>
                                    </div>
                                </div>
                                <small class="text-muted"><?= formatDate($rev['created_at']) ?></small>
                            </div>
                            <p class="mb-0 text-muted">
                                <?= nl2br(escape($rev['comment'])) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($reviews) >= 10): ?>
                <div class="text-center mt-4">
                    <a href="#" class="btn btn-outline-secondary">Xem thêm đánh giá</a>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sản phẩm liên quan -->
    <?php if (!empty($relatedProducts)): ?>
    <hr class="my-5">

    <div>
        <h3 class="mb-4">
            <i class="bi bi-lightbulb"></i> Sản phẩm liên quan trong danh mục
        </h3>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($relatedProducts as $rel): 
                $relPrice = getDisplayPrice($rel['price'], $rel['sale_price']);
                $relDiscount = calculateDiscount($rel['price'], $rel['sale_price']);
            ?>
            <div class="col">
                <a href="<?= SITE_URL ?>/product-detail.php?id=<?= (int)$rel['id'] ?>" class="text-decoration-none text-dark">
                    <div class="card h-100 shadow-sm border-0 transition" style="cursor: pointer;">
                        <div class="position-relative overflow-hidden" style="height: 220px; background: #f8f9fa;">
                            <img src="<?= image_url($rel['main_image']) ?>" 
                                 alt="<?= escape($rel['name']) ?>"
                                 class="card-img-top h-100"
                                 style="object-fit: cover; transition: transform 0.3s;"
                                 loading="lazy"
                                 onmouseover="this.style.transform='scale(1.05)'"
                                 onmouseout="this.style.transform='scale(1)'">
                            <?php if ($relDiscount > 0): ?>
                            <span class="badge bg-danger position-absolute top-2 end-2 fs-6">
                                -<?= (int)$relDiscount ?>%
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title text-dark" style="font-size: 0.95rem;">
                                <?= escape(mb_substr($rel['name'], 0, 50)) ?>
                            </h6>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <strong class="text-danger fs-5">
                                    <?= formatPrice($relPrice) ?>
                                </strong>
                                <?php if ($relDiscount > 0): ?>
                                <small class="text-muted" style="text-decoration: line-through;">
                                    <?= formatPrice($rel['price']) ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <div class="text-warning small mb-2">
                                <i class="bi bi-star-fill"></i> 
                                <strong><?= number_format($rel['rating_average'] ?? 0, 1) ?></strong>
                                <span class="text-muted">(<?= (int)($rel['review_count'] ?? 0) ?>)</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .transition {
        transition: all 0.3s ease;
    }
    
    .transition:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
    }
    
    .space-y-3 > * + * {
        margin-top: 1rem;
    }
    
    .text-muted {
        opacity: 0.75;
    }
    
    img.img-fluid {
        transition: transform 0.3s;
    }
</style>

<script>
function updateImageSelection(element) {
    // Bỏ border khỏi tất cả ảnh
    document.querySelectorAll('.overflow-x-auto img').forEach(img => {
        img.classList.remove('border-primary', 'border-3');
        img.classList.add('border-light', 'border-2');
        img.style.opacity = '0.7';
    });
    
    // Thêm border vào ảnh được chọn
    element.classList.remove('border-light', 'border-2');
    element.classList.add('border-primary', 'border-3');
    element.style.opacity = '1';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
