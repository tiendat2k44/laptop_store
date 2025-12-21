<?php
require_once __DIR__ . '/includes/init.php';

// Kiểm tra đăng nhập
if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để thanh toán');
    redirect(SITE_URL . '/login.php?redirect=' . SITE_URL . '/checkout.php');
}

// Khởi tạo services
$db = Database::getInstance();
require_once __DIR__ . '/includes/services/CartService.php';
require_once __DIR__ . '/includes/services/OrderService.php';
require_once __DIR__ . '/includes/services/CouponService.php';

$cart = new CartService($db, Auth::id());
$orderService = new OrderService($db, Auth::id());
$couponService = new CouponService($db);

// Coupon session tracking
$couponCode = Session::get('checkout_coupon_code');
$couponDiscount = (float)Session::get('checkout_coupon_discount', 0);

// Cờ trạng thái và thông tin đơn hàng thành công (PRG)
$orderSuccess = false;
$orderNumber = null;
$successOrderId = null;

// Nếu có order_id trong URL hoặc Session, hiển thị trang thành công và bỏ qua kiểm tra giỏ hàng trống
$successOrderId = intval($_GET['order_id'] ?? 0);
if ($successOrderId <= 0) {
    $successOrderId = intval(Session::get('last_order_id') ?? 0);
}
if ($successOrderId > 0) {
    $order = $orderService->getOrderDetail($successOrderId);
    if ($order) {
        $orderSuccess = true;
        $orderNumber = $order['order_number'];
        // Dọn session để tránh hiển thị sai khi refresh/quay lại
        Session::set('last_order_id', null);
    }
}

// Chỉ tải giỏ hàng và tính tiền nếu chưa ở màn hình thành công
if (!$orderSuccess) {
    // Debug: log incoming request
    error_log('=== CHECKOUT PAGE LOAD ===');
    error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
    error_log('POST selected_items: ' . json_encode($_POST['selected_items'] ?? 'NOT SET'));
    
    // Lấy giỏ hàng
    $allItems = $cart->getItems();
    
    // Nếu có selected_items từ form, chỉ lấy những items được chọn
    $hasSelectedItems = isset($_POST['selected_items']) && is_array($_POST['selected_items']) && !empty($_POST['selected_items']);
    
    error_log('Has selected items: ' . ($hasSelectedItems ? 'YES' : 'NO'));
    
    if (!$hasSelectedItems) {
        // Nếu không có selected_items POST, này là lỗi - user phải chọn items trong cart
        error_log('REJECTED: No selected_items in POST. Redirecting to cart.php');
        error_log('POST keys: ' . json_encode(array_keys($_POST)));
        error_log('POST data: ' . json_encode($_POST));
        Session::setFlash('error', 'Vui lòng chọn ít nhất một sản phẩm để thanh toán');
        redirect(SITE_URL . '/cart.php');
    }
    
    $selectedItemIds = array_map('intval', $_POST['selected_items']);
    error_log('Selected item IDs: ' . json_encode($selectedItemIds));
    
    // Filter items theo selection
    $items = array_filter($allItems, function($item) use ($selectedItemIds) {
        return in_array($item['item_id'], $selectedItemIds, true);
    });
    
    if (empty($items)) {
        Session::setFlash('error', 'Giỏ hàng trống, vui lòng thêm sản phẩm trước khi thanh toán');
        redirect(SITE_URL . '/products.php');
    }

    // Tính toán số tiền
    $subtotal = 0;
    foreach ($items as $item) {
        $price = getDisplayPrice($item['price'], $item['sale_price']);
        $subtotal += $price * $item['quantity'];
    }

    $amounts = [
        'subtotal' => $subtotal,
        'shipping_fee' => 0,
        'discount_amount' => $couponDiscount,
        'total_amount' => max(0, $subtotal - $couponDiscount)
    ];
}

// Xử lý form đặt hàng
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('=== CHECKOUT POST START ===');
    error_log('User ID: ' . Auth::id());
    error_log('Payment Method: ' . ($_POST['payment_method'] ?? 'not set'));
    
    // Kiểm tra CSRF token
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        error_log('CSRF token verification FAILED');
        $errors[] = 'Lỗi bảo mật: CSRF token không hợp lệ';
    } else {
        // Lấy & chuẩn hóa dữ liệu từ form
        $shipping = [
            'name' => trim($_POST['recipient_name'] ?? ''),
            'phone' => trim($_POST['recipient_phone'] ?? ''),
            'address' => trim($_POST['shipping_address'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'district' => trim($_POST['district'] ?? ''),
            'ward' => trim($_POST['ward'] ?? ''),
            'payment_method' => trim($_POST['payment_method'] ?? 'COD'),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        // Validation
        if (empty($shipping['name'])) {
            $errors[] = 'Họ tên người nhận không được để trống';
        }
        if (empty($shipping['phone'])) {
            $errors[] = 'Số điện thoại không được để trống';
        } elseif (!isValidPhone($shipping['phone'])) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }
        if (empty($shipping['address']) || empty($shipping['city'])) {
            $errors[] = 'Địa chỉ giao hàng không đủ thông tin';
        }
        if (!in_array($shipping['payment_method'], ['COD', 'MOMO', 'VNPAY'], true)) {
            $errors[] = 'Phương thức thanh toán không hợp lệ';
        }
        
        error_log('Validation errors: ' . count($errors));
        if (!empty($errors)) {
            error_log('Validation failed: ' . json_encode($errors));
        }

        // Nếu hợp lệ, kiểm tra lại tồn kho trước khi tạo đơn
        if (empty($errors)) {
            $stockErrors = [];
            foreach ($items as $it) {
                $available = (int)($it['stock_quantity'] ?? 0);
                $need = (int)($it['quantity'] ?? 0);
                if ($need <= 0) {
                    $stockErrors[] = 'Số lượng sản phẩm không hợp lệ.';
                    continue;
                }
                if ($available < $need) {
                    $stockErrors[] = 'Sản phẩm "' . ($it['name'] ?? 'không xác định') . '" chỉ còn ' . $available . ' trong kho.';
                }
            }
            if (!empty($stockErrors)) {
                $errors = array_merge($errors, $stockErrors);
            }

        }

        // Nếu vẫn hợp lệ sau kiểm tra tồn kho, tạo đơn hàng
        if (empty($errors)) {
            error_log('Starting order creation...');
            error_log('Items count: ' . count($items));
            error_log('Total amount: ' . $amounts['total_amount']);
            
            // Xử lý coupon nếu có
            $appliedCoupon = trim($_POST['applied_coupon_code'] ?? '');
            $appliedDiscount = (float)($_POST['applied_discount'] ?? 0);
            if ($appliedCoupon !== '' && $appliedDiscount > 0) {
                $coupon = $db->queryOne(
                    "SELECT id FROM coupons WHERE code = :code",
                    ['code' => strtoupper($appliedCoupon)]
                );
                if ($coupon) {
                    $couponService->incrementUsage($coupon['id']);
                }
                $amounts['discount_amount'] = $appliedDiscount;
                $amounts['total_amount'] = max(0, $amounts['subtotal'] - $appliedDiscount);
            }

            $result = $orderService->createOrder($shipping, $items, $amounts);
            
            error_log('Order creation result: ' . json_encode($result));

            if (is_array($result) && !empty($result['id'])) {
                error_log('Order created successfully! ID: ' . $result['id'] . ', Number: ' . $result['order_number']);
                
                // Xóa các items đã checkout khỏi giỏ hàng
                if (!empty($selectedItemIds)) {
                    $cart->clearSelectedItems($selectedItemIds);
                    error_log('Cleared ' . count($selectedItemIds) . ' items from cart');
                }
                
                // Clear coupon session
                Session::set('checkout_coupon_code', null);
                Session::set('checkout_coupon_discount', 0);

                // Redirect theo phương thức thanh toán
                if ($shipping['payment_method'] === 'VNPAY') {
                    error_log('Redirecting to VNPay...');
                    redirect(SITE_URL . '/payment/vnpay-return.php?id=' . (int)$result['id']);
                } elseif ($shipping['payment_method'] === 'MOMO') {
                    error_log('Redirecting to MoMo...');
                    redirect(SITE_URL . '/payment/momo-return.php?id=' . (int)$result['id']);
                } else {
                    // COD: chuyển sang màn hình thành công tại checkout
                    error_log('COD order - Redirecting to success page...');
                    Session::set('last_order_id', (int)$result['id']);
                    redirect(SITE_URL . '/checkout.php?order_id=' . (int)$result['id']);
                }
            } else {
                error_log('Order creation FAILED! Result: ' . var_export($result, true));
                $errors[] = 'Không thể tạo đơn hàng. Vui lòng thử lại.';
            }
        }
    }
}

$pageTitle = 'Thanh toán';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <!-- Tiêu đề -->
    <div class="mb-4">
        <h2><i class="bi bi-credit-card"></i> Thanh toán</h2>
        <hr>
    </div>

    <!-- ✅ TRƯỜNG HỢP: ĐẶT HÀNG THÀNH CÔNG -->
    <?php if ($orderSuccess): ?>
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-success shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="mb-3">Đặt hàng thành công!</h3>
                    <p class="text-muted mb-4">
                        Cảm ơn bạn đã mua hàng. Vui lòng kiểm tra email hoặc theo dõi đơn hàng.
                    </p>
                    <p class="mb-4">
                        <strong>Mã đơn hàng:</strong><br>
                        <span class="fs-5 badge bg-primary"><?= escape($orderNumber) ?></span>
                    </p>

                    <!-- Nút hành động -->
                    <?php if (!empty($successOrderId)): ?>
                    <a href="<?= SITE_URL ?>/account/order-detail.php?id=<?= (int)$successOrderId ?>" class="btn btn-outline-primary mb-2 w-100">
                        <i class="bi bi-eye"></i> Xem chi tiết đơn hàng
                    </a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/account/orders.php" class="btn btn-success mb-2 w-100">
                        <i class="bi bi-list-check"></i> Xem đơn hàng của tôi
                    </a>
                    <a href="<?= SITE_URL ?>/products.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-shop"></i> Tiếp tục mua sắm
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ❌ TRƯỜNG HỢP: CÓ LỖI -->
    <?php else: ?>
    <div class="row">
        <!-- Cột trái: Form nhập thông tin -->
        <div class="col-lg-7">
            <!-- Thông báo lỗi -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Vui lòng sửa các lỗi sau:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $err): ?>
                        <li><?= escape($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Form đặt hàng -->
            <form method="POST" action="" class="needs-validation">
                <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">

                <!-- 📍 Thông tin giao hàng -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">📍 Thông tin giao hàng</h5>
                    </div>
                    <div class="card-body">
                        <!-- Danh sách địa chỉ đã lưu -->
                        <div id="savedAddressesList" class="mb-4"></div>

                        <div class="row g-3">
                            <!-- Họ tên -->
                            <div class="col-md-6">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="recipient_name" 
                                       value="<?= escape(Auth::user()['full_name'] ?? '') ?>" required>
                            </div>

                            <!-- Số điện thoại -->
                            <div class="col-md-6">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="recipient_phone" 
                                       value="<?= escape(Auth::user()['phone'] ?? '') ?>" required>
                            </div>

                            <!-- Địa chỉ chi tiết -->
                            <div class="col-12">
                                <label class="form-label">Địa chỉ chi tiết <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="shipping_address" 
                                       placeholder="Số nhà, tên đường..." required>
                            </div>

                            <!-- Tỉnh/Thành phố -->
                            <div class="col-md-4">
                                <label class="form-label">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                                <select class="form-select" id="citySelect" name="city" 
                                        onchange="loadDistricts()" required>
                                    <option value="">-- Chọn --</option>
                                    <option value="Hà Nội">Hà Nội</option>
                                    <option value="Hải Phòng">Hải Phòng</option>
                                    <option value="TP Hồ Chí Minh">TP Hồ Chí Minh</option>
                                </select>
                            </div>

                            <!-- Quận/Huyện -->
                            <div class="col-md-4">
                                <label class="form-label">Quận/Huyện</label>
                                <select class="form-select" id="districtSelect" name="district" onchange="loadWards()">
                                    <option value="">-- Chọn --</option>
                                </select>
                            </div>

                            <!-- Phường/Xã -->
                            <div class="col-md-4">
                                <label class="form-label">Phường/Xã</label>
                                <select class="form-select" id="wardSelect" name="ward">
                                    <option value="">-- Chọn --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 💳 Phương thức thanh toán -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">💳 Phương thức thanh toán</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- COD - Thanh toán khi nhận hàng -->
                            <div class="col-lg-6">
                                <div class="payment-method-card border rounded-3 p-3 position-relative cursor-pointer" 
                                     onclick="document.getElementById('pmCOD').click()" 
                                     style="cursor: pointer; transition: all 0.3s ease; border: 2px solid #e0e0e0;">
                                    <input class="form-check-input position-absolute" type="radio" name="payment_method" 
                                           id="pmCOD" value="COD" checked style="top: 15px; right: 15px;">
                                    <div class="d-flex align-items-start">
                                        <div class="fs-3 me-3">🚚</div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold">Thanh toán khi nhận hàng</h6>
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-check-circle"></i> Thanh toán an toàn
                                            </p>
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-check-circle"></i> Không cần trả tiền trước
                                            </p>
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-check-circle"></i> Kiểm tra hàng trước khi trả tiền
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- MOMO - Ví MoMo -->
                            <div class="col-lg-6">
                                <div class="payment-method-card border rounded-3 p-3 position-relative cursor-pointer" 
                                     onclick="document.getElementById('pmMOMO').click()" 
                                     style="cursor: pointer; transition: all 0.3s ease; border: 2px solid #e0e0e0;">
                                    <input class="form-check-input position-absolute" type="radio" name="payment_method" 
                                           id="pmMOMO" value="MOMO" style="top: 15px; right: 15px;">
                                    <div class="d-flex align-items-start">
                                        <div class="fs-3 me-3">💳</div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold">Ví MoMo</h6>
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-check-circle"></i> Thanh toán nhanh chóng
                                            </p>
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-check-circle"></i> Bảo mật cao
                                            </p>
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-check-circle"></i> Hỗ trợ quốc tế
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- VNPAY - Thanh toán qua VNPAY -->
                            <div class="col-lg-6">
                                <div class="payment-method-card border rounded-3 p-3 position-relative cursor-pointer" 
                                     onclick="document.getElementById('pmVNPAY').click()" 
                                     style="cursor: pointer; transition: all 0.3s ease; border: 2px solid #e0e0e0;">
                                    <input class="form-check-input position-absolute" type="radio" name="payment_method" 
                                           id="pmVNPAY" value="VNPAY" style="top: 15px; right: 15px;">
                                    <div class="d-flex align-items-start">
                                        <div class="fs-3 me-3">🏦</div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold">VNPAY</h6>
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-check-circle"></i> Ngân hàng trực tuyến
                                            </p>
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-check-circle"></i> Hoạt động 24/7
                                            </p>
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-check-circle"></i> Được tin tưởng
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    .payment-method-card {
                        transition: all 0.3s ease;
                    }
                    .payment-method-card:hover {
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                        transform: translateY(-2px);
                    }
                    input[type="radio"]:checked ~ * .payment-method-card,
                    #pmCOD:checked ~ .payment-method-card,
                    #pmMOMO:checked ~ .payment-method-card,
                    #pmVNPAY:checked ~ .payment-method-card {
                        border-color: #007bff !important;
                        background-color: #f0f8ff;
                    }
                </style>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const cards = document.querySelectorAll('.payment-method-card');
                        const radios = document.querySelectorAll('input[name="payment_method"]');
                        
                        function updateCardStyles() {
                            cards.forEach(card => {
                                card.style.borderColor = '#e0e0e0';
                                card.style.backgroundColor = 'transparent';
                            });
                            
                            radios.forEach(radio => {
                                if (radio.checked) {
                                    radio.closest('.col-lg-6').querySelector('.payment-method-card').style.borderColor = '#007bff';
                                    radio.closest('.col-lg-6').querySelector('.payment-method-card').style.backgroundColor = '#f0f8ff';
                                }
                            });
                        }
                        
                        radios.forEach(radio => {
                            radio.addEventListener('change', updateCardStyles);
                        });
                        
                        updateCardStyles();
                    });
                </script>

                <!-- 🎟️ Mã giảm giá -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">🎟️ Mã giảm giá</h5>
                    </div>
                    <div class="card-body">
                        <div class="input-group">
                            <input type="text" class="form-control" id="couponCode" placeholder="Nhập mã giảm giá..." 
                                   value="<?= escape($couponCode ?? '') ?>">
                            <button class="btn btn-outline-primary" type="button" onclick="applyCoupon()"><i class="bi bi-tag"></i> Áp dụng</button>
                        </div>
                        <div id="couponMessage" class="mt-2"></div>
                    </div>
                </div>

                <!-- 📝 Ghi chú -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">📝 Ghi chú thêm</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="Ghi chú cho người giao (tuỳ chọn)"></textarea>
                    </div>
                </div>

                <!-- Nút hành động -->
                <button type="submit" class="btn btn-success btn-lg w-100">
                    <i class="bi bi-check2-circle"></i> Đặt hàng
                </button>
            </form>
        </div>

        <!-- Cột phải: Tóm tắt đơn hàng -->
        <div class="col-lg-5">
            <!-- Tóm tắt -->
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Tóm tắt đơn hàng</h5>
                </div>
                <div class="card-body">
                    <!-- Danh sách sản phẩm -->
                    <div class="mb-4" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($items as $item):
                            $price = getDisplayPrice($item['price'], $item['sale_price']);
                            $img = image_url($item['main_image'] ?? '');
                        ?>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <img src="<?= $img ?>" alt="" class="rounded" 
                                 style="width: 60px; height: 60px; object-fit: cover;">
                            <div class="flex-grow-1 small">
                                <div class="fw-bold"><?= escape($item['name']) ?></div>
                                <div class="text-muted">x<?= (int)$item['quantity'] ?></div>
                            </div>
                            <div class="text-danger fw-bold"><?= formatPrice($price * $item['quantity']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>

                    <!-- Chi tiết tiền -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <strong id="summarySubtotal"><?= formatPrice($amounts['subtotal']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Phí vận chuyển:</span>
                            <strong><?= formatPrice($amounts['shipping_fee']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between text-success">
                            <span>Giảm giá:</span>
                            <strong id="summaryDiscount">-<?= formatPrice($amounts['discount_amount']) ?></strong>
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Tổng cộng -->
                    <div class="d-flex justify-content-between fs-5 fw-bold">
                        <span>Tổng cộng</span>
                        <span class="text-danger" id="summaryTotal"><?= formatPrice($amounts['total_amount']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- JavaScript: Region Dropdown -->
<script>
const regions = {
    'Hà Nội': {
        'Hoàn Kiếm': ['Cửa Đông', 'Cửa Nam', 'Thanh Nhan'],
        'Ba Đình': ['Phúc Tân', 'Trúc Bạch', 'Cầu Giấy'],
        'Đống Đa': ['Láng Hạ', 'Ngã Tư Sở', 'Phương Mai'],
    },
    'Hải Phòng': {
        'Hồng Bàng': ['Máy Tơ', 'Máy Chai'],
        'Ngô Quyền': ['Chợ Mới', 'Cát Dài'],
    },
    'TP Hồ Chí Minh': {
        'Quận 1': ['Bến Nghé', 'Bến Thành', 'Cầu Kho'],
        'Quận 2': ['An Khánh', 'An Phú', 'Bình An'],
        'Quận 3': ['Võ Thị Sáu', 'Phường 1', 'Phường 9'],
    }
};

function loadDistricts() {
    const city = document.getElementById('citySelect').value;
    const districtSelect = document.getElementById('districtSelect');
    const wardSelect = document.getElementById('wardSelect');
    
    districtSelect.innerHTML = '<option value="">-- Chọn --</option>';
    wardSelect.innerHTML = '<option value="">-- Chọn --</option>';
    
    if (city && regions[city]) {
        Object.keys(regions[city]).forEach(district => {
            districtSelect.innerHTML += `<option value="${district}">${district}</option>`;
        });
    }
}

function loadWards() {
    const city = document.getElementById('citySelect').value;
    const district = document.getElementById('districtSelect').value;
    const wardSelect = document.getElementById('wardSelect');
    
    wardSelect.innerHTML = '<option value="">-- Chọn --</option>';
    
    if (city && district && regions[city] && regions[city][district]) {
        regions[city][district].forEach(ward => {
            wardSelect.innerHTML += `<option value="${ward}">${ward}</option>`;
        });
    }
}

// Load saved addresses
function loadSavedAddresses() {
    const siteUrl = '<?= SITE_URL ?>';
    fetch(siteUrl + '/ajax/address-action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: new URLSearchParams({
            action: 'get_list',
            csrf_token: document.querySelector('input[name="csrf_token"]').value
        })
    })
    .then(r => {
        if (!r.ok) {
            console.warn('Address fetch failed:', r.status);
            return { success: false, addresses: [] };
        }
        return r.json();
    })
    .then(res => {
        if (!res || !res.success) {
            console.log('No saved addresses or table not exists');
            return;
        }
        const container = document.getElementById('savedAddressesList');
        if (res.addresses && res.addresses.length > 0) {
            let html = '<div class="mb-3"><label class="form-label">Hoặc chọn địa chỉ đã lưu</label><div class="row g-2">';
            res.addresses.forEach(addr => {
                html += `<div class="col-md-6">
                    <div class="border rounded p-3 cursor-pointer" onclick="selectAddress(event, ${addr.id}, '${addr.recipient_name.replace(/'/g,"\\'")}', '${addr.phone.replace(/'/g,"\\'")}', '${addr.address_line.replace(/'/g,"\\'")}', '${addr.city.replace(/'/g,"\\'")}', '${(addr.district || '').replace(/'/g,"\\'")}', '${(addr.ward || '').replace(/'/g,"\\'")}')" style="cursor:pointer">
                        <div class="fw-bold">${addr.recipient_name}</div>
                        <div class="small text-muted">${addr.phone}</div>
                        <div class="small">${addr.address_line}</div>
                    </div>
                </div>`;
            });
            html += '</div></div><hr>';
            container.innerHTML = html;
        }
    })
    .catch(e => {
        console.warn('Address load error:', e.message);
        // Silent fail - không ảnh hưởng checkout
    });
}

function selectAddress(e, id, name, phone, addr, city, dist, ward) {
    document.querySelector('input[name="recipient_name"]').value = name;
    document.querySelector('input[name="recipient_phone"]').value = phone;
    document.querySelector('input[name="shipping_address"]').value = addr;
    document.getElementById('citySelect').value = city;
    loadDistricts();
    setTimeout(() => {
        document.getElementById('districtSelect').value = dist;
        loadWards();
        setTimeout(() => {
            document.getElementById('wardSelect').value = ward;
        }, 50);
    }, 50);
}

document.addEventListener('DOMContentLoaded', loadSavedAddresses);

// Coupon validation & apply
function applyCoupon() {
    const code = document.getElementById('couponCode').value.trim();
    const subtotal = parseFloat(<?= json_encode($amounts['subtotal'] ?? 0) ?>);
    const msgDiv = document.getElementById('couponMessage');
    const siteUrl = '<?= SITE_URL ?>';
    
    if (!code) {
        msgDiv.innerHTML = '<div class="alert alert-warning alert-sm py-2">Vui lòng nhập mã</div>';
        return;
    }
    
    fetch(siteUrl + '/ajax/validate-coupon.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: new URLSearchParams({
            code: code,
            subtotal: subtotal,
            csrf_token: document.querySelector('input[name="csrf_token"]').value
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            msgDiv.innerHTML = '<div class="alert alert-success alert-sm py-2"><i class="bi bi-check-circle"></i> ' + (res.message || 'Mã hợp lệ') + '</div>';
            document.getElementById('summaryDiscount').textContent = '-' + new Intl.NumberFormat('vi-VN', {style:'currency',currency:'VND'}).format(res.discount);
            document.getElementById('summaryTotal').textContent = new Intl.NumberFormat('vi-VN', {style:'currency',currency:'VND'}).format(subtotal - res.discount);
            // Lưu coupon vào session server-side thông qua hidden field
            document.querySelector('form').insertAdjacentHTML('beforeend', '<input type="hidden" name="applied_coupon_code" value="' + code.replace(/"/g,'&quot;') + '"><input type="hidden" name="applied_discount" value="' + res.discount + '">');
        } else {
            msgDiv.innerHTML = '<div class="alert alert-danger alert-sm py-2"><i class="bi bi-exclamation-circle"></i> ' + res.message + '</div>';
        }
    })
    .catch(e => {
        msgDiv.innerHTML = '<div class="alert alert-danger alert-sm py-2">Lỗi: ' + e.message + '</div>';
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
