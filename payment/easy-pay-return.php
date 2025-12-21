<?php
/**
 * Easy Pay Payment Handler
 * Tạo URL thanh toán EasyPay và xử lý kết quả trả về
 */

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
// XỬ LÝ KẾT QUẢ TRẢ VỀ TỪ EASYPAY
// ========================================
if (!empty($_GET['status'])) {
    $status = $_GET['status'] ?? 'failed';
    $transactionId = $_GET['transaction_id'] ?? 'EZP' . time();
    
    // Ghi log giao dịch
    try {
        $db->execute(
            "INSERT INTO payment_transactions (order_id, gateway, status, transaction_id, amount, message, ip_address)
             VALUES (:order_id, 'easypay', :status, :txn_id, :amount, :message, :ip)",
            [
                'order_id' => $orderId,
                'status' => ($status === 'success' ? 'success' : 'failed'),
                'txn_id' => $transactionId,
                'amount' => $order['total_amount'],
                'message' => 'EasyPay Status: ' . $status,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]
        );
    } catch (Exception $e) {
        error_log('EasyPay log transaction error: ' . $e->getMessage());
    }
    
    if ($status === 'success') {
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
        Session::setFlash('error', 'Thanh toán EasyPay thất bại. Vui lòng thử lại.');
        redirect('/checkout.php');
    }
}

// ========================================
// TẠO URL THANH TOÁN EASYPAY
// ========================================
try {
    require_once __DIR__ . '/../includes/payment/EasyPayGateway.php';
    
    // Kiểm tra config
    if (!defined('EASYPAY_PARTNER_CODE') || strpos(EASYPAY_PARTNER_CODE, 'your_') === 0) {
        throw new Exception('EasyPay chưa được cấu hình. Vui lòng cập nhật thông tin trong config hoặc admin panel.');
    }
    
    $easypay = new EasyPayGateway();
    $paymentResult = $easypay->createPaymentUrl($order);
    
    if (!$paymentResult['success']) {
        throw new Exception($paymentResult['error'] ?? 'Không thể tạo URL thanh toán');
    }
    
    $paymentUrl = $paymentResult['url'];
    
} catch (Throwable $e) {
    error_log('EasyPay payment error: ' . $e->getMessage());
    Session::setFlash('error', 'Lỗi thanh toán: ' . $e->getMessage());
    redirect('/checkout.php');
}

$pageTitle = 'Thanh toán EasyPay';
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-4"><i class="bi bi-credit-card"></i> Thanh toán EasyPay</h3>
                    <p class="text-muted mb-4">
                        Vui lòng nhấp vào nút bên dưới để chuyển hướng đến cổng thanh toán EasyPay.
                    </p>
                    <div class="alert alert-info">
                        <strong>Mã đơn hàng:</strong> <?= escape($order['order_number']) ?><br>
                        <strong>Số tiền:</strong> <?= formatPrice($order['total_amount']) ?>
                    </div>
                    <a href="<?= escape($paymentUrl) ?>" class="btn btn-primary w-100 btn-lg">
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


