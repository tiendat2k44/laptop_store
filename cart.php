<?php
require_once __DIR__ . '/includes/init.php';

// Kiểm tra đăng nhập
if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để xem giỏ hàng');
    redirect('/login.php?redirect=/cart.php');
}

// Khởi tạo service
$db = Database::getInstance();
require_once __DIR__ . '/includes/services/CartService.php';

$cart = new CartService($db, Auth::id());
$items = $cart->getItems();
$total = $cart->getTotal();

$pageTitle = 'Giỏ hàng';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <div class="mb-4">
        <h2><i class="bi bi-cart"></i> Giỏ hàng</h2>
        <hr>
    </div>

    <!-- Trường hợp: Giỏ hàng trống -->
    <?php if (empty($items)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Giỏ hàng của bạn đang trống.
        <a href="<?= SITE_URL ?>/products.php" class="alert-link fw-bold">Tiếp tục mua sắm →</a>
    </div>

    <!-- Trường hợp: Có sản phẩm trong giỏ -->
    <?php else: ?>
    <div class="row">
        <!-- Cột trái: Danh sách sản phẩm -->
        <div class="col-lg-8 mb-4">
            <div class="list-group">
                <?php foreach ($items as $item):
                    $price = getDisplayPrice($item['price'], $item['sale_price']);
                    $itemTotal = $price * $item['quantity'];
                ?>
                <div class="list-group-item d-flex align-items-center gap-3 p-3">
                    <!-- Ảnh sản phẩm -->
                    <img src="<?= image_url($item['product_thumbnail']) ?>" 
                         alt="<?= escape($item['product_name']) ?>" 
                         class="rounded flex-shrink-0" 
                         style="width: 80px; height: 80px; object-fit: cover;">

                    <!-- Thông tin sản phẩm -->
                    <div class="flex-grow-1">
                        <a href="<?= SITE_URL ?>/product-detail.php?id=<?= (int)$item['product_id'] ?>" 
                           class="text-decoration-none fw-bold text-dark">
                            <?= escape($item['product_name']) ?>
                        </a>
                        <div class="text-muted small mt-1">
                            Còn <?= (int)$item['stock_quantity'] ?> trong kho
                        </div>
                        <div class="text-danger fw-bold mt-2">
                            <?= formatPrice($price) ?>
                        </div>
                    </div>

                    <!-- Số lượng & xóa -->
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <input type="number" 
                               class="form-control form-control-sm cart-quantity-input" 
                               style="width: 80px;" 
                               value="<?= (int)$item['quantity'] ?>" 
                               min="1" 
                               max="<?= (int)$item['stock_quantity'] ?>" 
                               data-item-id="<?= (int)$item['item_id'] ?>">
                        
                        <button class="btn btn-sm btn-outline-danger btn-remove-cart-item" 
                                data-item-id="<?= (int)$item['item_id'] ?>" 
                                title="Xóa khỏi giỏ">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    <!-- Tổng cộng sản phẩm -->
                    <div class="text-end flex-shrink-0" style="min-width: 100px;">
                        <small class="text-muted">Tổng</small>
                        <p class="mb-0 fw-bold text-danger">
                            <?= formatPrice($itemTotal) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cột phải: Tóm tắt tiền -->
        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h5 class="card-title mb-4">Tóm tắt giỏ hàng</h5>

                    <!-- Chi tiết -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <strong><?= formatPrice($total) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-muted small">
                            <span>Vận chuyển:</span>
                            <span>Tính khi thanh toán</span>
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Tổng cộng -->
                    <div class="d-flex justify-content-between fs-5 fw-bold mb-4">
                        <span>Tổng cộng</span>
                        <span class="text-danger"><?= formatPrice($total) ?></span>
                    </div>

                    <!-- Nút thanh toán -->
                    <a href="<?= SITE_URL ?>/checkout.php" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-credit-card"></i> Tiến hành thanh toán
                    </a>

                    <!-- Nút tiếp tục mua -->
                    <a href="<?= SITE_URL ?>/products.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-shop"></i> Tiếp tục mua sắm
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
