<?php
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();
require_once __DIR__ . '/../../includes/services/AdminOrderService.php';
$service = new AdminOrderService($db);

$orderId = intval($_GET['id'] ?? 0);
if ($orderId <= 0) {
    Session::setFlash('error', 'Đơn hàng không hợp lệ');
    redirect('/admin/modules/orders/');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'CSRF token không hợp lệ');
        redirect('/admin/modules/orders/view.php?id=' . $orderId);
    }
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'update_status') {
            $newStatus = trim($_POST['new_status'] ?? '');
            $service->updateStatus($orderId, $newStatus);
            Session::setFlash('success', 'Cập nhật trạng thái thành công');
        } elseif ($action === 'update_payment') {
            $newPayment = trim($_POST['new_payment_status'] ?? '');
            $tx = trim($_POST['transaction_id'] ?? '');
            $service->updatePaymentStatus($orderId, $newPayment, $tx);
            Session::setFlash('success', 'Cập nhật trạng thái thanh toán thành công');
        } elseif ($action === 'cancel') {
            $reason = trim($_POST['reason'] ?? '');
            if ($service->cancelOrder($orderId, $reason)) {
                Session::setFlash('success', 'Đã hủy đơn hàng');
            } else {
                Session::setFlash('error', 'Không thể hủy đơn hàng');
            }
        }
    } catch (Exception $e) {
        Session::setFlash('error', $e->getMessage());
    }
    redirect('/admin/modules/orders/view.php?id=' . $orderId);
}

$order = $service->getOrder($orderId);
if (!$order) {
    Session::setFlash('error', 'Không tìm thấy đơn hàng');
    redirect('/admin/modules/orders/');
}
$items = $service->getOrderItems($orderId);

$pageTitle = 'Đơn ' . $order['order_number'];
include __DIR__ . '/../../includes/header.php';

$validStatuses = ['pending','confirmed','processing','shipping','delivered','cancelled'];
$validPayments = ['pending','paid','failed','refunded'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-receipt"></i> Đơn hàng <?= escape($order['order_number']) ?></h2>
    <a href="/admin/modules/orders/" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light"><strong>Thông tin đơn</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2"><small class="text-muted">Khách hàng</small><div class="fw-bold"><?= escape($order['customer_name']) ?> (<?= escape($order['customer_email']) ?>)</div></div>
                        <div class="mb-2"><small class="text-muted">Trạng thái</small><div><?= getOrderStatusBadge($order['status']) ?></div></div>
                        <div class="mb-2"><small class="text-muted">Thanh toán</small><div><?= getPaymentStatusBadge($order['payment_status']) ?></div></div>
                        <div class="mb-2"><small class="text-muted">Ngày tạo</small><div><?= formatDate($order['created_at']) ?></div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2"><small class="text-muted">Người nhận</small><div class="fw-bold"><?= escape($order['recipient_name']) ?></div></div>
                        <div class="mb-2"><small class="text-muted">Điện thoại</small><div><?= escape($order['recipient_phone']) ?></div></div>
                        <div class="mb-2"><small class="text-muted">Địa chỉ</small><div><?= escape($order['shipping_address']) ?>, <?= escape($order['ward']) ?>, <?= escape($order['district']) ?>, <?= escape($order['city']) ?></div></div>
                    </div>
                </div>
                <?php if (!empty($order['notes'])): ?>
                    <hr>
                    <div><small class="text-muted">Ghi chú</small><div><?= escape($order['notes']) ?></div></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light"><strong>Sản phẩm (<?= count($items) ?>)</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-end">Giá</th>
                                <th class="text-center">SL</th>
                                <th class="text-end">Tạm tính</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?= escape($it['product_name']) ?></td>
                                <td class="text-end"><?= formatPrice($it['price']) ?></td>
                                <td class="text-center"><?= (int)$it['quantity'] ?></td>
                                <td class="text-end text-danger fw-bold"><?= formatPrice($it['subtotal']) ?></td>
                                <td><small><?= escape($it['status']) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm sticky-top" style="top:20px;">
            <div class="card-body">
                <h5 class="mb-3">Tổng tiền</h5>
                <div class="d-flex justify-content-between"><span>Tạm tính</span><strong><?= formatPrice($order['subtotal']) ?></strong></div>
                <div class="d-flex justify-content-between"><span>Vận chuyển</span><strong><?= formatPrice($order['shipping_fee']) ?></strong></div>
                <div class="d-flex justify-content-between text-success"><span>Giảm giá</span><strong>-<?= formatPrice($order['discount_amount']) ?></strong></div>
                <hr>
                <div class="d-flex justify-content-between fs-5 fw-bold"><span>Tổng cộng</span><span class="text-danger"><?= formatPrice($order['total_amount']) ?></span></div>

                <hr>
                <form method="POST" class="mb-3 d-flex gap-2 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                    <input type="hidden" name="action" value="update_status">
                    <div class="flex-grow-1">
                        <label class="form-label">Cập nhật trạng thái</label>
                        <select name="new_status" class="form-select">
                            <?php foreach ($validStatuses as $st): ?>
                                <option value="<?= $st ?>" <?= $order['status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-save"></i></button>
                </form>

                <form method="POST" class="mb-3 d-flex gap-2 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                    <input type="hidden" name="action" value="update_payment">
                    <div class="flex-grow-1">
                        <label class="form-label">Trạng thái thanh toán</label>
                        <div class="input-group">
                            <select name="new_payment_status" class="form-select">
                                <?php foreach ($validPayments as $ps): ?>
                                    <option value="<?= $ps ?>" <?= $order['payment_status']===$ps?'selected':'' ?>><?= ucfirst($ps) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="transaction_id" class="form-control" placeholder="Mã giao dịch (tuỳ chọn)" value="<?= escape($order['payment_transaction_id'] ?? '') ?>">
                            <button class="btn btn-outline-primary" title="Lưu"><i class="bi bi-credit-card"></i></button>
                        </div>
                        <small class="text-muted">Chọn "Paid" sẽ tự ghi thời điểm trả tiền vào "paid_at".</small>
                    </div>
                </form>

                <?php if ($order['status'] !== 'cancelled'): ?>
                <form method="POST" onsubmit="return confirm('Xác nhận hủy đơn?');">
                    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                    <input type="hidden" name="action" value="cancel">
                    <div class="mb-2">
                        <label class="form-label">Lý do hủy (tuỳ chọn)</label>
                        <input type="text" name="reason" class="form-control" placeholder="Ghi lý do...">
                    </div>
                    <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle"></i> Hủy đơn</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
