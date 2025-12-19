<?php
require_once __DIR__ . '/includes/init.php';

if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để xem danh sách yêu thích');
    redirect('/login.php?redirect=/wishlist.php');
}

$db = Database::getInstance();

// Lấy danh sách sản phẩm yêu thích
$items = $db->query(
    "SELECT p.id, p.name, p.price, p.sale_price, p.rating_average, p.review_count, p.stock_quantity,
            (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY display_order LIMIT 1) AS main_image,
            s.name as shop_name
     FROM wishlist w
     JOIN products p ON w.product_id = p.id
     JOIN shops s ON p.shop_id = s.id
     WHERE w.user_id = :user_id
     ORDER BY w.created_at DESC",
    ['user_id' => Auth::id()]
);

$pageTitle = 'Danh sách yêu thích';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <h3 class="mb-4"><i class="bi bi-heart"></i> Danh sách yêu thích</h3>
    
    <?php if (empty($items)): ?>
        <div class="alert alert-info">
            Danh sách yêu thích của bạn đang trống. <a href="<?= SITE_URL ?>/products.php" class="alert-link">Khám phá sản phẩm</a>.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($items as $it):
                $img = image_url($it['main_image'] ?? '');
                $price = (!empty($it['sale_price']) && $it['sale_price'] < $it['price']) ? $it['sale_price'] : $it['price'];
                $displayPrice = $it['sale_price'] && $it['sale_price'] < $it['price'] ? $it['sale_price'] : $it['price'];
            ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card product-card shadow-sm">
                    <a href="<?= SITE_URL ?>/product-detail.php?id=<?= $it['id'] ?>">
                        <img src="<?= $img ?>" class="card-img-top" alt="<?= escape($it['name']) ?>">
                    </a>
                    <div class="card-body">
                        <a href="<?= SITE_URL ?>/product-detail.php?id=<?= $it['id'] ?>" class="text-decoration-none text-dark">
                            <h6 class="card-title"><?= escape($it['name']) ?></h6>
                        </a>
                        <div class="text-muted small mb-2"><?= escape($it['shop_name']) ?></div>
                        <div class="mb-2">
                            <?php
                            $rating = round($it['rating_average'] ?? 0);
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star"></i>';
                            }
                            ?>
                            <span class="text-muted small">(<?= $it['review_count'] ?>)</span>
                        </div>
                        <p class="text-danger fw-bold mb-3"><?= formatPrice($displayPrice) ?></p>
                        <button class="btn btn-primary btn-sm w-100 btn-add-to-cart" data-product-id="<?= $it['id'] ?>">
                            <i class="bi bi-cart-plus"></i> Thêm vào giỏ
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
