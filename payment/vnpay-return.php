<?php
require_once __DIR__ . '/../../includes/init.php';

if (!Auth::check()) {
    redirect('/login.php');
}

$db = Database::getInstance();
require_once __DIR__ . '/../../includes/payment/VNPayGateway.php';

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

// Nếu payment_status đã là paid, không cần redirect lại
if ($order['payment_status'] === 'paid') {
    Session::setFlash('success', 'Thanh toán thành công!');
    redirect('/account/order-detail.php?id=' . $orderId);
}

// Kiểm tra response từ VNPay
if (!empty($_GET['vnp_ResponseCode'])) {
    $responseCode = $_GET['vnp_ResponseCode'];
    $transactionId = $_GET['vnp_TransactionNo'] ?? '';
    
    if ($responseCode === '00') {
        // Thanh toán thành công - cập nhật trạng thái
        $db->execute(
            "UPDATE orders 
             SET payment_status = 'paid',
                 payment_transaction_id = :txn,
                 paid_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP,
                 status = CASE WHEN status = 'pending' THEN 'confirmed' ELSE status END
             WHERE id = :id",
            ['txn' => $transactionId, 'id' => $orderId]
        );
        Session::setFlash('success', 'Thanh toán thành công!');
    } else {
        Session::setFlash('error', 'Thanh toán thất bại. Mã lỗi: ' . escape($responseCode));
    }
    redirect('/account/order-detail.php?id=' . $orderId);
}

// Nếu chưa có response, tạo link thanh toán
$vnpay = new VNPayGateway();
$paymentUrl = $vnpay->createPaymentUrl($order);

$pageTitle = 'Thanh toán VNPay';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-4"><i class="bi bi-credit-card"></i> Thanh toán VNPay</h3>
                    <p class="text-muted mb-4">
                        Vui lòng nhấp vào nút bên dưới để chuyển hướng đến cổng thanh toán VNPay.
                    </p>
                    <div class="alert alert-info">
                        <strong>Mã đơn hàng:</strong> <?= escape($order['order_number']) ?><br>
                        <strong>Số tiền:</strong> <?= formatPrice($order['total_amount']) ?>
                    </div>
                    <a href="<?= $paymentUrl ?>" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-credit-card"></i> Thanh toán ngay
                    </a>
                    <a href="/account/order-detail.php?id=<?= (int)$orderId ?>" class="btn btn-outline-secondary w-100 mt-2">
                        Quay lại
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
