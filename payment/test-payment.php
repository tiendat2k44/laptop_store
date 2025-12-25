<?php
require_once __DIR__ . '/../includes/init.php';

// Chỉ cho phép dev/test (xóa dòng này trên production)
// if (empty($_ENV['APP_DEBUG'])) { redirect('/checkout.php'); }

if (!Auth::check()) {
    redirect('/login.php');
}

$db = Database::getInstance();

// Lấy danh sách đơn chưa thanh toán
$unpaidOrders = $db->query(
    "SELECT id, order_number, total_amount, payment_status, payment_method 
     FROM orders 
     WHERE user_id = :uid AND payment_status = 'pending'
     ORDER BY created_at DESC
     LIMIT 20",
    ['uid' => Auth::id()]
);

$pageTitle = 'Test Thanh Toán';
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning">
                    <h4 class="mb-0">🧪 Test Thanh Toán (Chỉ dùng cho Development)</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Chọn một đơn hàng chưa thanh toán và gateway để test luồng thanh toán giả lập.
                    </p>

                    <?php if (empty($unpaidOrders)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Không có đơn hàng chưa thanh toán. 
                        <a href="<?= SITE_URL ?>/checkout.php" class="alert-link">Tạo đơn hàng mới →</a>
                    </div>
                    <?php else: ?>
                    <form method="POST" action="">
                        <div class="row g-3">
                            <!-- Chọn đơn hàng -->
                            <div class="col-md-6">
                                <label class="form-label"><strong>Chọn đơn hàng</strong></label>
                                <select class="form-select" name="order_id" id="orderSelect" required>
                                    <option value="">-- Chọn đơn --</option>
                                    <?php foreach ($unpaidOrders as $order): ?>
                                    <option value="<?= (int)$order['id'] ?>" data-method="<?= escape($order['payment_method']) ?>">
                                        <?= escape($order['order_number']) ?> - <?= formatPrice($order['total_amount']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Chọn gateway -->
                            <div class="col-md-6">
                                <label class="form-label"><strong>Chọn cổng thanh toán</strong></label>
                                <select class="form-select" name="gateway" required>
                                    <option value="">-- Chọn --</option>
                                    <option value="momo">MoMo (Ví điện tử)</option>
                                    <option value="vnpay">VNPay (Thẻ/Ví)</option>
                                </select>
                            </div>

                            <!-- Kết quả test -->
                            <div class="col-12">
                                <label class="form-label"><strong>Kết quả</strong></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="result" id="resultSuccess" value="success" checked>
                                    <label class="btn btn-outline-success" for="resultSuccess">
                                        ✅ Thanh toán thành công
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="result" id="resultFailed" value="failed">
                                    <label class="btn btn-outline-danger" for="resultFailed">
                                        ❌ Thanh toán thất bại
                                    </label>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100 btn-lg">
                                    <i class="bi bi-play-circle"></i> Chạy test
                                </button>
                            </div>
                        </div>
                    </form>

                    <?php endif; ?>

                    <!-- Ghi chú -->
                    <hr class="my-4">
                    <div class="alert alert-light">
                        <small><strong>Ghi chú:</strong></small>
                        <ul class="mb-0" style="font-size: 0.9rem;">
                            <li>✔️ Nút này giả lập response từ gateway thanh toán.</li>
                            <li>✔️ Đơn hàng sẽ được cập nhật <code>payment_status='paid'</code> và <code>status='confirmed'</code>.</li>
                            <li>✔️ Email xác nhận sẽ được gửi (nếu mail được cấu hình).</li>
                            <li>⚠️ Chỉ dùng để test development, không dùng production.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
// Xử lý test payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $gateway = trim($_POST['gateway'] ?? '');
    $result = trim($_POST['result'] ?? 'success');

    if ($orderId <= 0 || !in_array($gateway, ['momo', 'vnpay'], true)) {
        die('Invalid request');
    }

    // Xác minh đơn hàng thuộc người dùng hiện tại
    $order = $db->queryOne(
        "SELECT id FROM orders WHERE id = :id AND user_id = :uid",
        ['id' => $orderId, 'uid' => Auth::id()]
    );

    if (!$order) {
        die('Order not found');
    }

    // Simulate payment callback
    if ($gateway === 'momo') {
        if ($result === 'success') {
            // Simulate successful MoMo callback
            $_GET['resultCode'] = 0;
            $_GET['transId'] = 'TEST' . time();
            $_GET['orderId'] = $orderId;
        } else {
            // Simulate failed MoMo callback
            $_GET['resultCode'] = 1;
            $_GET['transId'] = '';
            $_GET['orderId'] = $orderId;
        }

        // Process as if returning from MoMo
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
            Session::setFlash('success', '✅ Test: Thanh toán MoMo thành công!');
        } else {
            Session::setFlash('error', '❌ Test: Thanh toán MoMo thất bại (resultCode=1)');
        }
    } elseif ($gateway === 'vnpay') {
        if ($result === 'success') {
            // Simulate successful VNPay callback
            $_GET['vnp_ResponseCode'] = '00';
            $_GET['vnp_TransactionNo'] = 'TEST' . time();
        } else {
            // Simulate failed VNPay callback
            $_GET['vnp_ResponseCode'] = '01';
            $_GET['vnp_TransactionNo'] = '';
        }

        // Process as if returning from VNPay
        $responseCode = $_GET['vnp_ResponseCode'];
        $transactionId = $_GET['vnp_TransactionNo'] ?? '';

        if ($responseCode === '00') {
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
            Session::setFlash('success', '✅ Test: Thanh toán VNPay thành công!');
        } else {
            Session::setFlash('error', '❌ Test: Thanh toán VNPay thất bại (responseCode=01)');
        }
    }

    redirect('/account/order-detail.php?id=' . $orderId);
}
