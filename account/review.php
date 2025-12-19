<?php
require_once __DIR__ . '/../includes/init.php';

// Kiểm tra đăng nhập
if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để đánh giá sản phẩm');
    redirect('/login.php?redirect=/account/orders.php');
}

$db = Database::getInstance();

// Lấy thông tin từ URL
$productId = intval($_GET['product_id'] ?? 0);
$orderId = intval($_GET['order_id'] ?? 0);

if ($productId <= 0 || $orderId <= 0) {
    Session::setFlash('error', 'Thông tin không hợp lệ');
    redirect('/account/orders.php');
}

// Kiểm tra sản phẩm có trong đơn hàng của user không
$orderItem = $db->queryOne(
    "SELECT oi.*, o.user_id FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     WHERE oi.order_id = :order_id AND oi.product_id = :product_id AND o.user_id = :user_id",
    ['order_id' => $orderId, 'product_id' => $productId, 'user_id' => Auth::id()]
);

if (!$orderItem) {
    Session::setFlash('error', 'Không tìm thấy sản phẩm trong đơn hàng');
    redirect('/account/orders.php');
}

// Kiểm tra đã đánh giá chưa
$existingReview = $db->queryOne(
    "SELECT id FROM reviews WHERE product_id = :pid AND user_id = :uid AND order_id = :oid",
    ['pid' => $productId, 'uid' => Auth::id(), 'oid' => $orderId]
);

// Lấy thông tin sản phẩm
$product = $db->queryOne(
    "SELECT id, name, (SELECT image_url FROM product_images WHERE product_id = :id ORDER BY display_order LIMIT 1) as image 
     FROM products WHERE id = :id",
    ['id' => $productId]
);

// Xử lý form
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token không hợp lệ';
    } else {
        $rating = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        // Validation
        if ($rating < 1 || $rating > 5) {
            $errors[] = 'Đánh giá phải từ 1 đến 5 sao';
        }
        if (strlen($comment) < 10) {
            $errors[] = 'Bình luận phải có ít nhất 10 ký tự';
        }
        if (strlen($comment) > 1000) {
            $errors[] = 'Bình luận không được vượt quá 1000 ký tự';
        }

        if (empty($errors)) {
            try {
                if ($existingReview) {
                    // Cập nhật đánh giá
                    $db->execute(
                        "UPDATE reviews SET rating = :rating, comment = :comment, updated_at = CURRENT_TIMESTAMP 
                         WHERE id = :id",
                        ['rating' => $rating, 'comment' => $comment, 'id' => $existingReview['id']]
                    );
                    $msg = 'Cập nhật đánh giá thành công';
                } else {
                    // Thêm đánh giá mới
                    $db->insert(
                        "INSERT INTO reviews (product_id, user_id, order_id, rating, comment, status, created_at)
                         VALUES (:pid, :uid, :oid, :rating, :comment, 'approved', CURRENT_TIMESTAMP)",
                        ['pid' => $productId, 'uid' => Auth::id(), 'oid' => $orderId, 'rating' => $rating, 'comment' => $comment]
                    );
                    $msg = 'Thêm đánh giá thành công';
                }

                Session::setFlash('success', $msg);
                redirect('/product-detail.php?id=' . $productId);
            } catch (Exception $e) {
                error_log('Review error: ' . $e->getMessage());
                $errors[] = 'Có lỗi xảy ra khi lưu đánh giá';
            }
        }
    }
}

$pageTitle = 'Đánh giá sản phẩm';
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Tiêu đề -->
                    <div class="mb-4">
                        <h3 class="mb-2">Đánh giá sản phẩm</h3>
                        <p class="text-muted mb-0">
                            <strong><?= escape($product['name']) ?></strong>
                        </p>
                    </div>

                    <!-- Thông báo lỗi -->
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <strong>Có lỗi xảy ra:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $err): ?>
                            <li><?= escape($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Form đánh giá -->
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">

                        <!-- Sao đánh giá -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Đánh giá của bạn</label>
                            <div class="d-flex gap-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" 
                                       class="btn-check" 
                                       name="rating" 
                                       id="star<?= $i ?>" 
                                       value="<?= $i ?>">
                                <label for="star<?= $i ?>" 
                                       class="btn btn-lg p-0" 
                                       style="font-size: 2rem; color: #ffc107;">
                                    <i class="bi bi-star-fill"></i>
                                </label>
                                <?php endfor; ?>
                            </div>
                            <small class="text-muted d-block mt-2">Chọn số sao từ 1 đến 5</small>
                        </div>

                        <!-- Bình luận -->
                        <div class="mb-4">
                            <label for="comment" class="form-label fw-bold">Bình luận</label>
                            <textarea class="form-control" 
                                      id="comment" 
                                      name="comment" 
                                      rows="5" 
                                      placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này (tối thiểu 10 ký tự)"
                                      minlength="10" 
                                      maxlength="1000"></textarea>
                            <small class="text-muted">Tối thiểu 10 ký tự, tối đa 1000 ký tự</small>
                        </div>

                        <!-- Nút hành động -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="bi bi-check-circle"></i> 
                                <?= $existingReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' ?>
                            </button>
                            <a href="<?= SITE_URL ?>/product-detail.php?id=<?= $productId ?>" 
                               class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Quay lại
                            </a>
                        </div>

                        <?php if ($existingReview): ?>
                        <small class="text-muted d-block mt-3 text-center">
                            Bạn đã đánh giá sản phẩm này. Bạn có thể cập nhật đánh giá của mình bên dưới.
                        </small>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
