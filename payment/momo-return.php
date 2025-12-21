<?php
require_once __DIR__ . '/../includes/init.php';

if (!Auth::check()) {
    redirect('/login.php');
}

$db = Database::getInstance();
require_once __DIR__ . '/../includes/payment/MoMoGateway.php';

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    Session::setFlash('error', 'Đơn hàng không hợp lệ');
    redirect('/checkout.php');
}

$order = $db->queryOne(
    "SELECT * FROM orders WHERE id = :id AND user_id = :uid",
    ['id' => $orderId, 'uid' => Auth::id()]
);

if (!$order) {
    Session::setFlash('error', 'Không tìm thấy đơn hàng');
    redirect('/checkout.php');
}

if ($order['payment_status'] === 'paid') {
    Session::setFlash('success', 'Thanh toán thành công!');
    redirect('/account/order-detail.php?id=' . $orderId);
}

// Kiểm tra response từ MoMo
if (!empty($_GET['resultCode'])) {
    $resultCode = (int)$_GET['resultCode'];
    $transId = $_GET['transId'] ?? '';
    
    if ($resultCode === 0) {
        $db->execute(
            "UPDATE orders
             SET payment_status = 'paid',
                 payment_transaction_id = :txn,
                 paid_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP,
                 status = CASE WHEN status = 'pending' THEN 'confirmed' ELSE status END
             WHERE id = :id",
            ['txn' => $transId, 'id' => $orderId]
        );
        Session::setFlash('success', 'Thanh toán thành công!');
    } else {
        Session::setFlash('error', 'Thanh toán thất bại. Mã lỗi: ' . escape($resultCode));
    }
    redirect('/account/order-detail.php?id=' . $orderId);
}

// Tạo request MoMo
$momo = new MoMoGateway();
$paymentRequest = $momo->createPaymentRequest($order);

$pageTitle = 'Thanh toán MoMo';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-4"><i class="bi bi-wallet2"></i> Thanh toán MoMo</h3>
                    <p class="text-muted mb-4">
                        Vui lòng nhấp vào nút bên dưới để chuyển hướng đến cổng thanh toán MoMo.
                    </p>
                    <div class="alert alert-info">
                        <strong>Mã đơn hàng:</strong> <?= escape($order['order_number']) ?><br>
                        <strong>Số tiền:</strong> <?= formatPrice($order['total_amount']) ?>
                    </div>
                    <?php if ($paymentRequest['success']): ?>
                    <form method="POST" action="<?= $paymentRequest['url'] ?>" id="momoForm">
                        <?php foreach ($paymentRequest['data'] as $key => $val): ?>
                        <input type="hidden" name="<?= $key ?>" value="<?= escape($val) ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-primary w-100 btn-lg">
                            <i class="bi bi-wallet2"></i> Thanh toán ngay
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-danger">Không thể tạo request thanh toán</div>
                    <?php endif; ?>
                    <a href="/account/order-detail.php?id=<?= (int)$orderId ?>" class="btn btn-outline-secondary w-100 mt-2">
                        Quay lại
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
