<?php
/**
 * Admin - Chi tiết & quản lý đơn hàng
 * Cho phép admin xem chi tiết và cập nhật trạng thái đơn hàng
 */

require_once __DIR__ . '/../../../includes/init.php';

// Kiểm tra quyền Admin
Auth::requireRole(ROLE_ADMIN, '/login.php');

// Khởi tạo database và service
$db = Database::getInstance();
require_once __DIR__ . '/../../../includes/services/AdminOrderService.php';
$service = new AdminOrderService($db);

// Lấy ID đơn hàng từ URL
$orderId = intval($_GET['id'] ?? 0);
if ($orderId <= 0) {
    Session::setFlash('error', 'Đơn hàng không hợp lệ');
    redirect('/admin/modules/orders/');
}

// Xử lý các hành động POST (cập nhật trạng thái, thanh toán, hủy đơn)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xác thực CSRF token để bảo mật
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'CSRF token không hợp lệ');
        redirect('/admin/modules/orders/view.php?id=' . $orderId);
    }
    
    $action = $_POST['action'] ?? '';
    try {
        // Cập nhật trạng thái đơn hàng (pending -> processing -> shipped -> delivered)
        if ($action === 'update_status') {
            $newStatus = trim($_POST['new_status'] ?? '');
            $ok = $service->updateStatus($orderId, $newStatus);
            if ($ok) {
                Session::setFlash('success', 'Cập nhật trạng thái thành công');
            } else {
                Session::setFlash('error', 'Không thể cập nhật trạng thái. Vui lòng thử lại.');
            }
        } 
        // Cập nhật trạng thái thanh toán (pending -> paid -> refunded)
        elseif ($action === 'update_payment') {
            $newPayment = trim($_POST['new_payment_status'] ?? '');
            $tx = trim($_POST['transaction_id'] ?? ''); // Mã giao dịch (tùy chọn)
            $service->updatePaymentStatus($orderId, $newPayment, $tx);
            Session::setFlash('success', 'Cập nhật trạng thái thanh toán thành công');
        } 
        // Hủy đơn hàng với lý do
        elseif ($action === 'cancel') {
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
    
    // Redirect lại trang chi tiết sau khi xử lý
    // Redirect lại trang chi tiết sau khi xử lý
    redirect('/admin/modules/orders/view.php?id=' . $orderId);
}

// Lấy thông tin chi tiết đơn hàng
$order = $service->getOrder($orderId);
if (!$order) {
    Session::setFlash('error', 'Không tìm thấy đơn hàng');
    redirect('/admin/modules/orders/');
}

// Lấy danh sách sản phẩm trong đơn hàng
$items = $service->getOrderItems($orderId);

// Thiết lập tiêu đề trang
$pageTitle = 'Đơn ' . $order['order_number'];
include __DIR__ . '/../../includes/header.php';

// Danh sách trạng thái hợp lệ cho dropdown
$validStatuses = getOrderStatusKeys();
$validPayments = getPaymentStatusKeys();
?>

<!-- Header trang với nút quay lại -->

<!-- Header trang với nút quay lại -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-receipt"></i> Đơn hàng <?= escape($order['order_number']) ?></h2>
    <a href="<?php echo SITE_URL; ?>/admin/modules/orders/" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
</div>

<div class="row">
    <!-- Cột trái: Thông tin đơn hàng & Danh sách sản phẩm -->
    <div class="col-lg-8 mb-4">
        <!-- Card thông tin đơn hàng -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light"><strong>Thông tin đơn</strong></div>
            <div class="card-body">
                <div class="row">
                    <!-- Cột thông tin khách hàng & trạng thái -->
                    <div class="col-md-6">
                        <div class="mb-2"><small class="text-muted">Khách hàng</small><div class="fw-bold"><?= escape($order['customer_name']) ?> (<?= escape($order['customer_email']) ?>)</div></div>
                        <div class="mb-2"><small class="text-muted">Trạng thái</small><div><?= getOrderStatusBadge($order['status']) ?></div></div>
                        <div class="mb-2"><small class="text-muted">Thanh toán</small><div><?= getPaymentStatusBadge($order['payment_status']) ?></div></div>
                        <div class="mb-2"><small class="text-muted">Ngày tạo</small><div><?= formatDate($order['created_at']) ?></div></div>
                    </div>
                    <!-- Cột thông tin giao hàng -->
                    <div class="col-md-6">
                        <div class="mb-2"><small class="text-muted">Người nhận</small><div class="fw-bold"><?= escape($order['recipient_name']) ?></div></div>
                        <div class="mb-2"><small class="text-muted">Điện thoại</small><div><?= escape($order['recipient_phone']) ?></div></div>
                        <div class="mb-2">
                            <small class="text-muted">Phương thức thanh toán</small>
                            <div>
                                <?php 
                                // Danh sách phương thức thanh toán với màu badge tương ứng
                                $paymentMethods = [
                                    'COD' => ['Thanh toán khi nhận (COD)', 'secondary'],
                                    'MOMO' => ['MoMo', 'success'],
                                    'VNPAY' => ['VNPay', 'primary'],
                                    'EASYPAY' => ['EasyPay (VietQR)', 'info']
                                ];
                                $pm = $order['payment_method'] ?? 'COD';
                                [$pmLabel, $pmClass] = $paymentMethods[$pm] ?? ['Không xác định', 'secondary'];
                                ?>
                                <span class="badge bg-<?= $pmClass ?>"><?= $pmLabel ?></span>
                            </div>
                        </div>
                        <div class="mb-2"><small class="text-muted">Địa chỉ</small><div><?= escape($order['shipping_address']) ?>, <?= escape($order['ward']) ?>, <?= escape($order['district']) ?>, <?= escape($order['city']) ?></div></div>
                    </div>
                </div>
                <?php if (!empty($order['notes'])): ?>
                    <hr>
                    <!-- Hiển thị ghi chú của khách hàng nếu có -->
                    <div><small class="text-muted">Ghi chú</small><div><?= escape($order['notes']) ?></div></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card danh sách sản phẩm -->
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
    
    <!-- Cột phải: Panel quản lý đơn hàng -->
    <div class="col-lg-4">
        <div class="card shadow-sm sticky-top" style="top:20px;">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-wallet2"></i> Quản lý đơn</h5>
            </div>
            <div class="card-body">
                <!-- Hiển thị tổng tiền với chi tiết phí -->
                <div class="bg-light rounded-3 p-3 mb-4">
                    <small class="text-muted d-block mb-1">Tổng giá trị</small>
                    <h3 class="text-danger mb-3"><?= formatPrice($order['total_amount']) ?></h3>
                    <div class="small mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Tạm tính</span>
                            <strong><?= formatPrice($order['subtotal']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Vận chuyển</span>
                            <strong><?= formatPrice($order['shipping_fee']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between text-success mb-1">
                            <span>Giảm giá</span>
                            <strong>-<?= formatPrice($order['discount_amount']) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Form cập nhật trạng thái đơn hàng (pending/processing/shipped/delivered/cancelled) -->
                <div class="mb-4">
                    <label class="form-label fw-bold mb-2">
                        <i class="bi bi-clock-history"></i> Trạng thái đơn hàng
                    </label>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                        <input type="hidden" name="action" value="update_status">
                        <select name="new_status" class="form-select form-select-sm">
                            <?php foreach (getOrderStatusMap() as $st => $info): ?>
                                <option value="<?= $st ?>" <?= $order['status']===$st?'selected':'' ?>>
                                    <?= $info['emoji'] ?> <?= $info['label'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary" title="Cập nhật">
                            <i class="bi bi-check-lg"></i>
                        </button>
                    </form>
                </div>

                <!-- Form cập nhật trạng thái thanh toán (pending/paid/refunded) -->
                <div class="mb-4">
                    <label class="form-label fw-bold mb-2">
                        <i class="bi bi-credit-card"></i> Trạng thái thanh toán
                    </label>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                        <input type="hidden" name="action" value="update_payment">
                        <div class="mb-2">
                            <select name="new_payment_status" class="form-select form-select-sm mb-2">
                                <?php foreach (getPaymentStatusMap() as $ps => $info): ?>
                                    <option value="<?= $ps ?>" <?= $order['payment_status']===$ps?'selected':'' ?>>
                                        <?= $info['emoji'] ?> <?= $info['label'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="transaction_id" class="form-control form-control-sm mb-2" 
                                   placeholder="Mã giao dịch (tùy chọn)" 
                                   value="<?= escape($order['payment_transaction_id'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-success w-100">
                            <i class="bi bi-save"></i> Lưu
                        </button>
                        <small class="text-muted d-block mt-2">
                            💡 Chọn "Paid" để ghi thời điểm thanh toán
                        </small>
                    </form>
                </div>

                <!-- Nút hủy đơn hàng (chỉ hiện khi đơn chưa bị hủy) -->
                <?php if ($order['status'] !== 'cancelled'): ?>
                <div class="mb-4">
                    <button type="button" class="btn btn-outline-danger w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                        <i class="bi bi-x-circle"></i> Hủy đơn hàng
                    </button>
                </div>

                <!-- Modal xác nhận hủy đơn hàng với lý do -->
                <div class="modal fade" id="cancelOrderModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Hủy đơn hàng</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i> Bạn sắp hủy đơn hàng này. Hành động này không thể hoàn tác.
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold">Lý do hủy (bắt buộc)</label>
                                        <textarea name="reason" class="form-control" rows="3" placeholder="Nhập lý do hủy đơn..." required></textarea>
                                        <small class="text-muted d-block mt-2">
                                            Khách hàng sẽ được thông báo về lý do này
                                        </small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-x"></i> Đóng
                                    </button>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bi bi-x-circle"></i> Xác nhận hủy
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
