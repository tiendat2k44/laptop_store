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
        redirect('/account/order-detail.php?id=' . $orderId);
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
    redirect('/account/order-detail.php?id=' . $orderId);
}

$pageTitle = 'Thanh toán EasyPay';
include __DIR__ . '/../includes/header.php';

// Tạo QR Code VietQR
$bankId = EASYPAY_BANK_ID ?? 'MB';
$accountNumber = EASYPAY_ACCOUNT_NUMBER ?? '';
$accountName = EASYPAY_ACCOUNT_NAME ?? 'ADMIN';
$amount = (int)$order['total_amount'];
$content = $order['order_number']; // Nội dung chuyển khoản

// VietQR URL: https://img.vietqr.io/image/{BANK_ID}-{ACCOUNT_NUMBER}-compact2.png?amount={AMOUNT}&addInfo={CONTENT}
$qrUrl = "https://img.vietqr.io/image/{$bankId}-{$accountNumber}-compact2.png"
    . "?amount={$amount}"
    . "&addInfo=" . urlencode($content)
    . "&accountName=" . urlencode($accountName);
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-qr-code-scan text-primary" style="font-size: 3rem;"></i>
                        <h3 class="mt-3">Quét mã QR để thanh toán</h3>
                        <p class="text-muted">Mở ứng dụng ngân hàng và quét mã QR bên dưới</p>
                    </div>

                    <!-- QR Code -->
                    <div class="text-center mb-4">
                        <div class="bg-light p-3 rounded d-inline-block">
                            <img src="<?= escape($qrUrl) ?>" alt="QR Code" style="max-width: 300px; width: 100%;">
                        </div>
                    </div>

                    <!-- Thông tin đơn hàng -->
                    <div class="alert alert-info mb-4">
                        <div class="row">
                            <div class="col-6">
                                <strong>Mã đơn hàng:</strong>
                            </div>
                            <div class="col-6 text-end">
                                <span class="badge bg-primary"><?= escape($order['order_number']) ?></span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <strong>Số tiền:</strong>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-danger fw-bold"><?= formatPrice($order['total_amount']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Hướng dẫn chuyển khoản thủ công -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-info-circle"></i> Hoặc chuyển khoản thủ công
                            </h6>
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td class="fw-bold" style="width: 40%;">Ngân hàng:</td>
                                    <td><?= escape($bankId === 'MB' ? 'MB Bank (Quân đội)' : $bankId) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Số tài khoản:</td>
                                    <td>
                                        <span class="user-select-all"><?= escape($accountNumber) ?></span>
                                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('<?= escape($accountNumber) ?>')">
                                            <i class="bi bi-clipboard"></i> Copy
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Chủ tài khoản:</td>
                                    <td><?= escape($accountName) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Số tiền:</td>
                                    <td class="text-danger fw-bold"><?= formatPrice($amount) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Nội dung:</td>
                                    <td>
                                        <span class="user-select-all badge bg-warning text-dark"><?= escape($content) ?></span>
                                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('<?= escape($content) ?>')">
                                            <i class="bi bi-clipboard"></i> Copy
                                        </button>
                                    </td>
                                </tr>
                            </table>
                            <div class="alert alert-warning mt-3 mb-0 small">
                                <i class="bi bi-exclamation-triangle"></i> 
                                <strong>Lưu ý:</strong> Vui lòng chuyển <strong>ĐÚNG số tiền</strong> và <strong>ĐÚNG nội dung</strong> để đơn hàng được xử lý tự động.
                            </div>
                        </div>
                    </div>

                    <!-- Nút hành động -->
                    <div class="d-grid gap-2">
                        <a href="<?= SITE_URL ?>/account/order-detail.php?id=<?= (int)$orderId ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-eye"></i> Xem chi tiết đơn hàng
                        </a>
                        <a href="<?= SITE_URL ?>/products.php" class="btn btn-outline-secondary">
                            <i class="bi bi-shop"></i> Tiếp tục mua sắm
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Đã copy: ' + text);
    }).catch(err => {
        console.error('Copy failed:', err);
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


