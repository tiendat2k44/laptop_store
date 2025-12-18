<?php
/**
 * Trang Chi Tiết Sản Phẩm
 * Hiển thị thông tin chi tiết laptop, hình ảnh, đánh giá
 */

require_once 'includes/config/config.php';
require_once 'includes/core/Database.php';
require_once 'includes/core/Session.php';
require_once 'includes/core/Auth.php';
require_once 'includes/helpers/functions.php';

Session::start();
$db = Database::getInstance();
$auth = Auth::getInstance();

// Lấy ID sản phẩm
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id == 0) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

// Lấy thông tin sản phẩm
$product_sql = "SELECT p.*, s.name as shop_name, s.slug as shop_slug, s.phone as shop_phone,
                       c.name as category_name
                FROM products p
                JOIN shops s ON p.shop_id = s.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id AND p.status = 'active' AND s.status = 'approved'";
$product = $db->queryOne($product_sql, [':id' => $product_id]);

if (!$product) {
    header('Location: ' . SITE_URL . '/products.php');
    exit;
}

// Lấy hình ảnh sản phẩm
$images = $db->query("SELECT * FROM product_images WHERE product_id = :id ORDER BY is_primary DESC, display_order", [':id' => $product_id]);

// Lấy đánh giá
$reviews = $db->query("SELECT r.*, u.full_name, u.avatar 
                       FROM reviews r 
                       JOIN users u ON r.user_id = u.id 
                       WHERE r.product_id = :id AND r.status = 'approved' 
                       ORDER BY r.created_at DESC 
                       LIMIT 10", [':id' => $product_id]);

// Lấy sản phẩm liên quan (cùng danh mục hoặc cùng shop)
$related_sql = "SELECT p.*, 
                       (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = true LIMIT 1) as main_image
                FROM products p
                WHERE p.id != :id 
                  AND p.status = 'active'
                  AND (p.category_id = :category_id OR p.shop_id = :shop_id)
                ORDER BY RANDOM()
                LIMIT 8";
$related_products = $db->query($related_sql, [
    ':id' => $product_id,
    ':category_id' => $product['category_id'],
    ':shop_id' => $product['shop_id']
]);

// Cập nhật lượt xem
$db->execute("UPDATE products SET view_count = view_count + 1 WHERE id = :id", [':id' => $product_id]);

$page_title = $product['name'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= SITE_NAME ?></title>
    <meta name="description" content="<?= htmlspecialchars(substr($product['description'], 0, 160)) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        .product-gallery {
            position: sticky;
            top: 20px;
        }
        .main-image {
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 15px;
            background: #f8f9fa;
            height: 450px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .main-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .thumbnail-gallery {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .thumbnail {
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            height: 100px;
            background: #f8f9fa;
        }
        .thumbnail:hover, .thumbnail.active {
            border-color: #007bff;
            transform: scale(1.05);
        }
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-info-box {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .product-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        .product-rating-box {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .rating-stars {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .rating-stars i {
            color: #ffc107;
            font-size: 1.1rem;
        }
        .rating-number {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }
        .rating-count {
            color: #666;
        }
        .product-price-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .current-price {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .original-price {
            font-size: 1.1rem;
            text-decoration: line-through;
            opacity: 0.8;
            margin-right: 10px;
        }
        .discount-badge {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
        }
        .specs-table {
            margin-top: 20px;
        }
        .specs-table tr {
            border-bottom: 1px solid #eee;
        }
        .specs-table td {
            padding: 12px 0;
        }
        .specs-table td:first-child {
            font-weight: 600;
            color: #666;
            width: 35%;
        }
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }
        .quantity-selector button {
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .quantity-selector button:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .quantity-selector input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            height: 40px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-add-cart {
            flex: 1;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
        }
        .btn-wishlist {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        .shop-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        .shop-card h6 {
            margin-bottom: 15px;
            font-weight: 600;
        }
        .shop-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        .tabs-section {
            margin-top: 40px;
        }
        .nav-tabs {
            border: none;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 10px 10px 0 0;
        }
        .nav-tabs .nav-link.active {
            background: white;
            color: #007bff;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }
        .tab-content {
            background: white;
            border-radius: 0 15px 15px 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .review-item {
            border-bottom: 1px solid #eee;
            padding: 20px 0;
        }
        .review-item:last-child {
            border-bottom: none;
        }
        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        .reviewer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .reviewer-name {
            font-weight: 600;
            margin-bottom: 3px;
        }
        .review-date {
            font-size: 0.85rem;
            color: #999;
        }
        .review-rating {
            color: #ffc107;
            margin-bottom: 8px;
        }
        .related-product-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
        }
        .related-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .related-product-image {
            height: 180px;
            overflow: hidden;
            background: #f8f9fa;
        }
        .related-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/products.php">Sản phẩm</a></li>
                <?php if ($product['category_name']): ?>
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/products.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Gallery -->
            <div class="col-lg-5">
                <div class="product-gallery">
                    <div class="main-image" id="mainImage">
                        <?php 
                        $mainImage = !empty($images) ? $images[0]['image_url'] : 'assets/images/no-image.jpg';
                        ?>
                        <img src="<?= SITE_URL ?>/<?= $mainImage ?>" alt="<?= htmlspecialchars($product['name']) ?>" id="mainImg">
                    </div>
                    
                    <?php if (count($images) > 1): ?>
                    <div class="thumbnail-gallery">
                        <?php foreach ($images as $index => $img): ?>
                        <div class="thumbnail <?= $index == 0 ? 'active' : '' ?>" onclick="changeImage('<?= SITE_URL ?>/<?= $img['image_url'] ?>', this)">
                            <img src="<?= SITE_URL ?>/<?= $img['image_url'] ?>" alt="">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Thông tin sản phẩm -->
            <div class="col-lg-7">
                <div class="product-info-box">
                    <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    
                    <div class="product-rating-box">
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?= $i <= $product['rating'] ? '' : '-o' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-number"><?= number_format($product['rating'], 1) ?></span>
                        <span class="rating-count">(<?= $product['review_count'] ?> đánh giá)</span>
                        <span class="text-muted">|</span>
                        <span class="text-muted"><?= number_format($product['sold_count']) ?> đã bán</span>
                        <span class="text-muted">|</span>
                        <span class="text-muted"><?= number_format($product['view_count']) ?> lượt xem</span>
                    </div>

                    <div class="product-price-box">
                        <div class="current-price"><?= formatPrice($product['price']) ?></div>
                        <?php if ($product['original_price'] > $product['price']): ?>
                        <div>
                            <span class="original-price"><?= formatPrice($product['original_price']) ?></span>
                            <span class="discount-badge">GIẢM <?= $product['discount_percentage'] ?>%</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Thông số kỹ thuật nhanh -->
                    <table class="table specs-table">
                        <tbody>
                            <?php if ($product['brand']): ?>
                            <tr>
                                <td><i class="fas fa-copyright"></i> Thương hiệu</td>
                                <td><?= htmlspecialchars($product['brand']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($product['processor']): ?>
                            <tr>
                                <td><i class="fas fa-microchip"></i> Bộ xử lý</td>
                                <td><?= htmlspecialchars($product['processor']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($product['ram']): ?>
                            <tr>
                                <td><i class="fas fa-memory"></i> RAM</td>
                                <td><?= htmlspecialchars($product['ram']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($product['storage']): ?>
                            <tr>
                                <td><i class="fas fa-hdd"></i> Ổ cứng</td>
                                <td><?= htmlspecialchars($product['storage']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($product['screen_size']): ?>
                            <tr>
                                <td><i class="fas fa-desktop"></i> Màn hình</td>
                                <td><?= htmlspecialchars($product['screen_size']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($product['graphics_card']): ?>
                            <tr>
                                <td><i class="fas fa-television"></i> Card đồ họa</td>
                                <td><?= htmlspecialchars($product['graphics_card']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td><i class="fas fa-warehouse"></i> Tình trạng</td>
                                <td>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                    <span class="text-success"><i class="fas fa-check-circle"></i> Còn hàng (<?= $product['stock_quantity'] ?>)</span>
                                    <?php else: ?>
                                    <span class="text-danger"><i class="fas fa-times-circle"></i> Hết hàng</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Chọn số lượng -->
                    <div class="quantity-selector">
                        <label style="font-weight: 600;">Số lượng:</label>
                        <button onclick="decreaseQty()"><i class="fas fa-minus"></i></button>
                        <input type="number" id="quantity" value="1" min="1" max="<?= $product['stock_quantity'] ?>">
                        <button onclick="increaseQty()"><i class="fas fa-plus"></i></button>
                        <span class="text-muted ms-2">(Còn <?= $product['stock_quantity'] ?>)</span>
                    </div>

                    <!-- Nút hành động -->
                    <div class="action-buttons">
                        <button class="btn btn-primary btn-add-cart" onclick="addToCart(<?= $product['id'] ?>)">
                            <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                        </button>
                        <button class="btn btn-outline-danger btn-wishlist btn-wishlist-toggle" data-id="<?= $product['id'] ?>">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>

                    <button class="btn btn-success w-100 mt-2" onclick="buyNow(<?= $product['id'] ?>)">
                        <i class="fas fa-bolt"></i> Mua ngay
                    </button>
                </div>

                <!-- Thông tin shop -->
                <div class="shop-card">
                    <h6><i class="fas fa-store"></i> Thông tin cửa hàng</h6>
                    <div class="shop-info-item">
                        <i class="fas fa-shop"></i>
                        <a href="<?= SITE_URL ?>/shop.php?slug=<?= $product['shop_slug'] ?>" class="text-decoration-none fw-bold">
                            <?= htmlspecialchars($product['shop_name']) ?>
                        </a>
                    </div>
                    <?php if ($product['shop_phone']): ?>
                    <div class="shop-info-item">
                        <i class="fas fa-phone"></i>
                        <span><?= htmlspecialchars($product['shop_phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <a href="<?= SITE_URL ?>/shop.php?slug=<?= $product['shop_slug'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-store"></i> Xem cửa hàng
                        </a>
                        <button class="btn btn-outline-secondary btn-sm ms-2">
                            <i class="fas fa-comments"></i> Chat ngay
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs mô tả, thông số, đánh giá -->
        <div class="tabs-section">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#description">Mô tả sản phẩm</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#specifications">Thông số kỹ thuật</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#reviews">Đánh giá (<?= $product['review_count'] ?>)</a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Mô tả -->
                <div id="description" class="tab-pane fade show active">
                    <h5>Mô tả sản phẩm</h5>
                    <div><?= nl2br(htmlspecialchars($product['description'])) ?></div>
                </div>

                <!-- Thông số kỹ thuật -->
                <div id="specifications" class="tab-pane fade">
                    <h5>Thông số kỹ thuật chi tiết</h5>
                    <table class="table table-bordered">
                        <tbody>
                            <?php if ($product['brand']): ?><tr><td width="30%">Thương hiệu</td><td><?= htmlspecialchars($product['brand']) ?></td></tr><?php endif; ?>
                            <?php if ($product['processor']): ?><tr><td>Bộ xử lý</td><td><?= htmlspecialchars($product['processor']) ?></td></tr><?php endif; ?>
                            <?php if ($product['ram']): ?><tr><td>RAM</td><td><?= htmlspecialchars($product['ram']) ?></td></tr><?php endif; ?>
                            <?php if ($product['storage']): ?><tr><td>Ổ cứng</td><td><?= htmlspecialchars($product['storage']) ?></td></tr><?php endif; ?>
                            <?php if ($product['screen_size']): ?><tr><td>Kích thước màn hình</td><td><?= htmlspecialchars($product['screen_size']) ?></td></tr><?php endif; ?>
                            <?php if ($product['graphics_card']): ?><tr><td>Card đồ họa</td><td><?= htmlspecialchars($product['graphics_card']) ?></td></tr><?php endif; ?>
                            <?php if ($product['operating_system']): ?><tr><td>Hệ điều hành</td><td><?= htmlspecialchars($product['operating_system']) ?></td></tr><?php endif; ?>
                            <?php if ($product['weight']): ?><tr><td>Trọng lượng</td><td><?= htmlspecialchars($product['weight']) ?></td></tr><?php endif; ?>
                            <?php if ($product['warranty_period']): ?><tr><td>Bảo hành</td><td><?= htmlspecialchars($product['warranty_period']) ?></td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Đánh giá -->
                <div id="reviews" class="tab-pane fade">
                    <h5>Đánh giá từ khách hàng</h5>
                    
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="reviewer-info">
                                <img src="<?= !empty($review['avatar']) ? SITE_URL . '/' . $review['avatar'] : SITE_URL . '/assets/images/default-avatar.png' ?>" 
                                     alt="<?= htmlspecialchars($review['full_name']) ?>" 
                                     class="reviewer-avatar">
                                <div>
                                    <div class="reviewer-name"><?= htmlspecialchars($review['full_name']) ?></div>
                                    <div class="review-date"><?= date('d/m/Y H:i', strtotime($review['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-o' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="review-comment"><?= nl2br(htmlspecialchars($review['comment'])) ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Chưa có đánh giá nào cho sản phẩm này.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sản phẩm liên quan -->
        <?php if (!empty($related_products)): ?>
        <div class="mt-5">
            <h4 class="mb-4">Sản phẩm liên quan</h4>
            <div class="row g-3">
                <?php foreach ($related_products as $rp): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card related-product-card">
                        <div class="related-product-image">
                            <a href="<?= SITE_URL ?>/product-detail.php?id=<?= $rp['id'] ?>">
                                <img src="<?= !empty($rp['main_image']) ? SITE_URL . '/' . $rp['main_image'] : SITE_URL . '/assets/images/no-image.jpg' ?>" alt="<?= htmlspecialchars($rp['name']) ?>">
                            </a>
                        </div>
                        <div class="card-body">
                            <a href="<?= SITE_URL ?>/product-detail.php?id=<?= $rp['id'] ?>" class="text-decoration-none">
                                <h6 class="card-title text-truncate"><?= htmlspecialchars($rp['name']) ?></h6>
                            </a>
                            <div class="text-danger fw-bold"><?= formatPrice($rp['price']) ?></div>
                            <div class="small text-muted">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i <= $rp['rating'] ? '' : '-o' ?>" style="color: #ffc107; font-size: 0.8rem;"></i>
                                <?php endfor; ?>
                                (<?= $rp['review_count'] ?>)
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
    <script>
        // Thay đổi hình ảnh chính
        function changeImage(imgSrc, thumbnail) {
            document.getElementById('mainImg').src = imgSrc;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            thumbnail.classList.add('active');
        }

        // Tăng số lượng
        function increaseQty() {
            const input = document.getElementById('quantity');
            const max = parseInt(input.max);
            if (parseInt(input.value) < max) {
                input.value = parseInt(input.value) + 1;
            }
        }

        // Giảm số lượng
        function decreaseQty() {
            const input = document.getElementById('quantity');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }

        // Thêm vào giỏ hàng
        function addToCart(productId) {
            const quantity = document.getElementById('quantity').value;
            
            $.ajax({
                url: '<?= SITE_URL ?>/ajax/cart-add.php',
                method: 'POST',
                data: {
                    product_id: productId,
                    quantity: quantity
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Đã thêm sản phẩm vào giỏ hàng!');
                        // Cập nhật số lượng giỏ hàng trong header nếu có
                        if (response.cart_count) {
                            $('.cart-count').text(response.cart_count);
                        }
                    } else {
                        alert(response.message || 'Có lỗi xảy ra!');
                    }
                },
                error: function() {
                    alert('Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!');
                }
            });
        }

        // Mua ngay
        function buyNow(productId) {
            addToCart(productId);
            setTimeout(function() {
                window.location.href = '<?= SITE_URL ?>/cart.php';
            }, 500);
        }
    </script>
</body>
</html>
