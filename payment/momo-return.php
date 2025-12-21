<?php
require_once __DIR__ . '/../includes/init.php';

if (!Auth::check()) {
    redirect('/login.php');
}

$db = Database::getInstance();

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

// ========================================
// XỬ LÝ KẾT QUẢ TRẢ VỀ TỪ MOMO
// ========================================
if (!empty($_GET['resultCode'])) {
    $resultCode = (int)$_GET['resultCode'];
    $transId = $_GET['transId'] ?? 'MOMO' . time();
    $amount = (float)($_GET['amount'] ?? 0);
    $message = $_GET['message'] ?? '';
    
    // Ghi log giao dịch
    try {
        $db->execute(
            "INSERT INTO payment_transactions (order_id, gateway, status, transaction_id, amount, message, ip_address)
             VALUES (:order_id, 'momo', :status, :txn_id, :amount, :message, :ip)",
            [
                'order_id' => $orderId,
                'status' => ($resultCode === 0 ? 'success' : 'failed'),
                'txn_id' => $transId,
                'amount' => $amount,
                'message' => 'MoMo Result Code: ' . $resultCode . ' - ' . $message,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]
        );
    } catch (Exception $e) {
        error_log('MoMo log transaction error: ' . $e->getMessage());
    }
    
    if ($resultCode === 0) {
        // Thanh toán thành công
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
        Session::set('last_order_id', $orderId);
        redirect('/checkout.php?order_id=' . $orderId);
    } else {
        // Thanh toán thất bại
        Session::setFlash('error', 'Thanh toán thất bại: ' . escape($message));
        redirect('/checkout.php');
    }
}

// ========================================
// TẠO REQUEST THANH TOÁN MOMO
// ========================================
try {
    require_once __DIR__ . '/../includes/payment/MoMoGateway.php';
    
    // Kiểm tra config
    if (!defined('MOMO_PARTNER_CODE') || strpos(MOMO_PARTNER_CODE, 'your_') === 0) {
        throw new Exception('MoMo chưa được cấu hình. Vui lòng cập nhật thông tin trong config hoặc admin panel.');
    }
    
    $momo = new MoMoGateway();
    $paymentRequest = $momo->createPayment($order);
    
    if (!$paymentRequest['success']) {
        throw new Exception('Không thể tạo request thanh toán: ' . ($paymentRequest['error'] ?? 'Unknown error'));
    }
    
    $paymentData = $paymentRequest['data'];
    $endpoint = $paymentRequest['endpoint'];
} catch (Throwable $e) {
    error_log('MoMo payment error: ' . $e->getMessage());
    Session::setFlash('error', 'Lỗi thanh toán: ' . $e->getMessage());
    redirect('/checkout.php');
}

$pageTitle = 'Thanh toán MoMo';
include __DIR__ . '/../includes/header.php';
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
                    <form method="POST" action="<?= escape($endpoint) ?>" id="momoForm">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
