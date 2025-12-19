<?php
require_once __DIR__ . '/../includes/init.php';

if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để đánh giá sản phẩm');
    redirect('/login.php?redirect=/account/orders.php');
}

$db = Database::getInstance();

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
$product = $db->queryOne("SELECT id, name FROM products WHERE id = :id", ['id' => $productId]);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'CSRF token không hợp lệ';
    } else {
        $rating = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        
        if ($rating < 1 || $rating > 5) {
            $errors[] = 'Đánh giá phải từ 1 đến 5 sao';
        }
        
        if (empty($comment)) {
            $errors[] = 'Bình luận không được để trống';
        }
        
        if (empty($errors)) {
            try {
                if ($existingReview) {
                    // Update
                    $db->execute(
                        "UPDATE reviews SET rating = :rating, comment = :comment, updated_at = CURRENT_TIMESTAMP 
                         WHERE id = :id",
                        ['rating' => $rating, 'comment' => $comment, 'id' => $existingReview['id']]
                    );
                } else {
                    // Insert
                    $db->insert(
                        "INSERT INTO reviews (product_id, user_id, order_id, rating, comment, status, created_at)
                         VALUES (:pid, :uid, :oid, :rating, :comment, 'approved', CURRENT_TIMESTAMP)",
                        ['pid' => $productId, 'uid' => Auth::id(), 'oid' => $orderId, 'rating' => $rating, 'comment' => $comment]
                    );
                }
                Session::setFlash('success', 'Đánh giá sản phẩm thành công');
                redirect('/product-detail.php?id=' . $productId);
            } catch (Exception $e) {
                error_log('Review error: ' . $e->getMessage());
                $errors[] = 'Có lỗi xảy ra khi lưu đánh giá';
            }
        }
    }
}

$pageTitle = 'Đánh giá ' . escape($product['name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title mb-4">Đánh giá sản phẩm</h3>
                    <p class="text-muted"><strong><?= escape($product['name']) ?></strong></p>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= escape($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Xếp hạng <span class="text-danger">*</span></label>
                            <div>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="rating" id="rating<?= $i ?>" value="<?= $i ?>">
                                        <label class="form-check-label" for="rating<?= $i ?>">
                                            <?php for ($j = 0; $j < $i; $j++): ?>
                                                <i class="bi bi-star-fill text-warning"></i>
                                            <?php endfor; ?>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Bình luận <span class="text-danger">*</span></label>
                            <textarea name="comment" class="form-control" rows="5" placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check2"></i> Gửi đánh giá
                        </button>
                        <a href="<?= SITE_URL ?>/account/order-detail.php?id=<?= $orderId ?>" class="btn btn-outline-secondary w-100 mt-2">
                            Quay lại
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
