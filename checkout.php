<?php
require_once __DIR__ . '/includes/init.php';

if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để thanh toán');
    redirect('/login.php?redirect=/checkout.php');
}

$db = Database::getInstance();

// Lấy items trong giỏ
$items = $db->query(
    "SELECT ci.id as item_id, ci.quantity, 
            p.id as product_id, p.name, p.price, p.sale_price, p.stock_quantity, p.shop_id,
            (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY display_order LIMIT 1) AS main_image
     FROM cart_items ci
     JOIN products p ON ci.product_id = p.id
     WHERE ci.user_id = :user_id
     ORDER BY ci.created_at DESC",
    ['user_id' => Auth::id()]
);

if (empty($items)) {
    Session::setFlash('error', 'Giỏ hàng trống, vui lòng thêm sản phẩm trước khi thanh toán');
    redirect('/products.php');
}

// Tính tổng tiền
$subtotal = 0;
foreach ($items as $it) {
    $price = (!empty($it['sale_price']) && $it['sale_price'] < $it['price']) ? $it['sale_price'] : $it['price'];
    $subtotal += $price * $it['quantity'];
}
$shipping_fee = 0;
$discount_amount = 0;
$total_amount = $subtotal + $shipping_fee - $discount_amount;

// Xử lý submit đặt hàng
$errors = [];
$orderSuccess = false;
$orderNumber = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $recipient_name = trim($_POST['recipient_name'] ?? '');
        $recipient_phone = trim($_POST['recipient_phone'] ?? '');
        $shipping_address = trim($_POST['shipping_address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $ward = trim($_POST['ward'] ?? '');
        $payment_method = trim($_POST['payment_method'] ?? 'COD');
        $notes = trim($_POST['notes'] ?? '');
        
        if ($recipient_name === '') $errors[] = 'Họ tên người nhận không được để trống';
        if ($recipient_phone === '') $errors[] = 'Số điện thoại không được để trống';
        if ($shipping_address === '' || $city === '') $errors[] = 'Địa chỉ giao hàng không được để trống';
        if (!in_array($payment_method, ['COD','MOMO','VNPAY'], true)) $errors[] = 'Phương thức thanh toán không hợp lệ';
        
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                // Tạo mã đơn hàng
                $orderNumber = 'ORD' . date('YmdHis') . substr(strval(random_int(1000, 9999)), -4);
                
                // Tạo đơn hàng
                $sqlOrder = "INSERT INTO orders (
                    order_number, user_id,
                    recipient_name, recipient_phone, shipping_address, city, district, ward,
                    subtotal, shipping_fee, discount_amount, total_amount,
                    payment_method, payment_status, status, notes, created_at
                ) VALUES (
                    :order_number, :user_id,
                    :recipient_name, :recipient_phone, :shipping_address, :city, :district, :ward,
                    :subtotal, :shipping_fee, :discount_amount, :total_amount,
                    :payment_method, 'pending', 'pending', :notes, CURRENT_TIMESTAMP
                ) RETURNING id";
                
                $orderRow = $db->queryOne($sqlOrder, [
                    'order_number' => $orderNumber,
                    'user_id' => Auth::id(),
                    'recipient_name' => $recipient_name,
                    'recipient_phone' => $recipient_phone,
                    'shipping_address' => $shipping_address,
                    'city' => $city,
                    'district' => $district,
                    'ward' => $ward,
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shipping_fee,
                    'discount_amount' => $discount_amount,
                    'total_amount' => $total_amount,
                    'payment_method' => $payment_method,
                    'notes' => $notes
                ]);
                
                $orderId = $orderRow['id'] ?? null;
                if (!$orderId) {
                    throw new Exception('Không thể tạo đơn hàng');
                }
                
                // Thêm order items + cập nhật tồn kho, sold_count
                foreach ($items as $it) {
                    $unitPrice = (!empty($it['sale_price']) && $it['sale_price'] < $it['price']) ? $it['sale_price'] : $it['price'];
                    $itemSubtotal = $unitPrice * $it['quantity'];
                    
                    // Lấy thumbnail
                    $thumb = $it['main_image'] ?? null;
                    
                    $sqlItem = "INSERT INTO order_items (
                        order_id, product_id, shop_id, product_name, product_thumbnail,
                        price, quantity, subtotal, status, created_at
                    ) VALUES (
                        :order_id, :product_id, :shop_id, :product_name, :product_thumbnail,
                        :price, :quantity, :subtotal, 'pending', CURRENT_TIMESTAMP
                    )";
                    
                    $db->insert($sqlItem, [
                        'order_id' => $orderId,
                        'product_id' => $it['product_id'],
                        'shop_id' => $it['shop_id'],
                        'product_name' => $it['name'],
                        'product_thumbnail' => $thumb,
                        'price' => $unitPrice,
                        'quantity' => $it['quantity'],
                        'subtotal' => $itemSubtotal
                    ]);
                    
                    // Cập nhật tồn kho và sold_count
                    $db->execute(
                        "UPDATE products SET stock_quantity = stock_quantity - :qty, sold_count = sold_count + :qty WHERE id = :pid AND stock_quantity >= :qty",
                        ['qty' => $it['quantity'], 'pid' => $it['product_id']]
                    );
                }
                
                // Xóa giỏ hàng
                $db->execute("DELETE FROM cart_items WHERE user_id = :user_id", ['user_id' => Auth::id()]);
                
                $db->commit();
                $orderSuccess = true;
                
            } catch (Exception $e) {
                $db->rollback();
                error_log('Checkout error: ' . $e->getMessage());
                $errors[] = 'Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại.';
            }
        }
    }
}

$pageTitle = 'Thanh toán';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <h3 class="mb-4"><i class="bi bi-credit-card"></i> Thanh toán</h3>
    
    <?php if ($orderSuccess): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> Đặt hàng thành công! Mã đơn hàng: <strong><?= escape($orderNumber) ?></strong>
        </div>
        <a href="<?= SITE_URL ?>/account/orders.php" class="btn btn-success"><i class="bi bi-list-check"></i> Xem đơn hàng của tôi</a>
        <a href="<?= SITE_URL ?>/products.php" class="btn btn-outline-primary ms-2">Tiếp tục mua sắm</a>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?= escape($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-7">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin giao hàng</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Họ tên người nhận <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="recipient_name" value="<?= escape(Auth::user()['full_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="recipient_phone" value="<?= escape(Auth::user()['phone'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="shipping_address" placeholder="Số nhà, đường, phường/xã" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                                    <select class="form-select" id="citySelect" name="city" required onchange="loadDistricts()">
                                        <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                        <option value="Hà Nội">Hà Nội</option>
                                        <option value="Hải Phòng">Hải Phòng</option>
                                        <option value="TP Hồ Chí Minh">TP Hồ Chí Minh</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Quận/Huyện</label>
                                    <select class="form-select" id="districtSelect" name="district" onchange="loadWards()">
                                        <option value="">-- Chọn Quận/Huyện --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Phường/Xã</label>
                                    <select class="form-select" id="wardSelect" name="ward">
                                        <option value="">-- Chọn Phường/Xã --</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Phương thức thanh toán</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="pmCOD" value="COD" checked>
                                <label class="form-check-label" for="pmCOD">Thanh toán khi nhận hàng (COD)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="pmMOMO" value="MOMO" disabled>
                                <label class="form-check-label" for="pmMOMO">Ví MoMo (đang phát triển)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="pmVNPAY" value="VNPAY" disabled>
                                <label class="form-check-label" for="pmVNPAY">VNPAY (đang phát triển)</label>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Ghi chú (tuỳ chọn)</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Đặt hàng</button>
                </form>
            </div>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Tóm tắt đơn hàng</h5>
                        <?php foreach ($items as $it):
                            $img = image_url($it['main_image'] ?? '');
                            $unitPrice = (!empty($it['sale_price']) && $it['sale_price'] < $it['price']) ? $it['sale_price'] : $it['price'];
                        ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?= $img ?>" style="width:60px;height:60px;object-fit:cover;border-radius:8px;" class="me-2">
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?= escape($it['name']) ?></div>
                                <div class="text-muted small">Số lượng: <?= (int)$it['quantity'] ?></div>
                            </div>
                            <div class="text-danger fw-bold ms-2"><?= formatPrice($unitPrice * $it['quantity']) ?></div>
                        </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Tạm tính</span>
                            <span><?= formatPrice($subtotal) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Phí vận chuyển</span>
                            <span><?= formatPrice($shipping_fee) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Giảm giá</span>
                            <span>-<?= formatPrice($discount_amount) ?></span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold fs-5 mt-2">
                            <span>Tổng cộng</span>
                            <span class="text-danger"><?= formatPrice($total_amount) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
