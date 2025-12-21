<?php
require_once __DIR__ . '/../includes/init.php';

if (!Auth::check()) {
    redirect('/login.php');
}

$db = Database::getInstance();

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    Session::setFlash('error', 'Đơn hàng không hợp lệ');
    redirect('/account/orders.php');
}

$order = $db->queryOne(
    "SELECT * FROM orders WHERE id = :id AND user_id = :uid",
    ['id' => $orderId, 'uid' => Auth::id()]
);

if (!$order) {
    Session::setFlash('error', 'Không tìm thấy đơn hàng');
    redirect('/account/orders.php');
}

// ========================================
// XỬ LÝ KẾT QUẢ TRẢ VỀ TỪ VNPAY
// ========================================
if (!empty($_GET['vnp_ResponseCode'])) {
    $responseCode = $_GET['vnp_ResponseCode'];
    $transactionId = $_GET['vnp_TransactionNo'] ?? 'VNP' . time();
    $amount = (float)($_GET['vnp_Amount'] ?? 0) / 100;
    $txnRef = $_GET['vnp_TxnRef'] ?? '';
    
    // Ghi log giao dịch
    try {
        $db->execute(
            "INSERT INTO payment_transactions (order_id, gateway, status, transaction_id, amount, message, ip_address)
             VALUES (:order_id, 'vnpay', :status, :txn_id, :amount, :message, :ip)",
            [
                'order_id' => $orderId,
                'status' => ($responseCode === '00' ? 'success' : 'failed'),
                'txn_id' => $transactionId,
                'amount' => $amount,
                'message' => 'VNPay Response Code: ' . $responseCode,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]
        );
    } catch (Exception $e) {
        error_log('VNPay log transaction error: ' . $e->getMessage());
    }
    
    if ($responseCode === '00') {
        // Thanh toán thành công
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
        Session::set('last_order_id', $orderId);
        redirect('/checkout.php?order_id=' . $orderId);
    } else {
        // Thanh toán thất bại
        Session::setFlash('error', 'Thanh toán thất bại. Mã lỗi: ' . escape($responseCode));
        redirect('/account/order-detail.php?id=' . $orderId);
    }
}

// ========================================
// TẠO URL THANH TOÁN VNPAY
// ========================================
try {
    require_once __DIR__ . '/../includes/payment/VNPayGateway.php';
    
    // Kiểm tra config
    if (!defined('VNPAY_TMN_CODE') || strpos(VNPAY_TMN_CODE, 'your_') === 0) {
        throw new Exception('VNPay chưa được cấu hình. Vui lòng cập nhật thông tin trong config hoặc admin panel.');
    }
    
    $vnpay = new VNPayGateway();
    $paymentUrl = $vnpay->createPaymentUrl($order);
    
    if (!$paymentUrl) {
        throw new Exception('Không thể tạo URL thanh toán');
    }
} catch (Throwable $e) {
    error_log('VNPay payment error: ' . $e->getMessage());
    Session::setFlash('error', 'Lỗi thanh toán: ' . $e->getMessage());
    redirect('/account/order-detail.php?id=' . $orderId);
}

$pageTitle = 'Thanh toán VNPay';
include __DIR__ . '/../includes/header.php';
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
