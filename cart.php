<?php
require_once __DIR__ . '/includes/init.php';

// Kiểm tra đăng nhập
if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để xem giỏ hàng');
    redirect('/login.php?redirect=/cart.php');
}

// Khởi tạo service
$db = Database::getInstance();
require_once __DIR__ . '/includes/services/CartService.php';

$cart = new CartService($db, Auth::id());
$items = $cart->getItems();
$total = $cart->getTotal();

$pageTitle = 'Giỏ hàng';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <div class="mb-4">
        <h2><i class="bi bi-cart"></i> Giỏ hàng</h2>
        <hr>
    </div>

    <!-- Trường hợp: Giỏ hàng trống -->
    <?php if (empty($items)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Giỏ hàng của bạn đang trống.
        <a href="<?= SITE_URL ?>/products.php" class="alert-link fw-bold">Tiếp tục mua sắm →</a>
    </div>

    <!-- Trường hợp: Có sản phẩm trong giỏ -->
    <?php else: ?>
    <form id="checkoutForm" method="POST" action="/checkout.php">
    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
    <div class="row">
        <!-- Cột trái: Danh sách sản phẩm -->
        <div class="col-lg-8 mb-4">
            <!-- Nút chọn tất cả -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll" checked>
                    <label class="form-check-label fw-bold" for="selectAll">
                        Chọn tất cả (<span id="selectedCount"><?= count($items) ?></span> sản phẩm)
                    </label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" id="deleteSelected">
                    <i class="bi bi-trash"></i> Xóa đã chọn
                </button>
            </div>
            
            <div class="list-group">
                <?php foreach ($items as $item):
                    $price = getDisplayPrice($item['price'], $item['sale_price']);
                    $itemTotal = $price * $item['quantity'];
                ?>
                <div class="list-group-item d-flex align-items-center gap-3 p-3">
                    <!-- Checkbox chọn -->
                    <div class="form-check">
                        <input class="form-check-input item-checkbox" 
                               type="checkbox" 
                               name="selected_items[]" 
                               value="<?= (int)$item['item_id'] ?>" 
                               data-product-id="<?= (int)$item['product_id'] ?>"
                               checked>
                    </div>
                    <!-- Ảnh sản phẩm -->
                    <img src="<?= image_url($item['main_image'] ?? '') ?>" 
                         alt="<?= escape($item['name'] ?? '') ?>" 
                         class="rounded flex-shrink-0" 
                         style="width: 80px; height: 80px; object-fit: cover;">

                    <!-- Thông tin sản phẩm -->
                    <div class="flex-grow-1">
                        <a href="<?= SITE_URL ?>/product-detail.php?id=<?= (int)$item['product_id'] ?>" 
                           class="text-decoration-none fw-bold text-dark">
                            <?= escape($item['name'] ?? '') ?>
                        </a>
                        <div class="text-muted small mt-1">
                            Còn <?= (int)$item['stock_quantity'] ?> trong kho
                        </div>
                        <div class="text-danger fw-bold mt-2">
                            <?= formatPrice($price) ?>
                        </div>
                    </div>

                    <!-- Số lượng & xóa -->
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <input type="number" 
                               class="form-control form-control-sm cart-quantity-input" 
                               style="width: 80px;" 
                               value="<?= (int)$item['quantity'] ?>" 
                               min="1" 
                               max="<?= (int)$item['stock_quantity'] ?>" 
                               data-item-id="<?= (int)$item['item_id'] ?>">
                        
                        <button class="btn btn-sm btn-outline-danger btn-remove-cart-item" 
                                data-item-id="<?= (int)$item['item_id'] ?>" 
                                title="Xóa khỏi giỏ">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    <!-- Tổng cộng sản phẩm -->
                    <div class="text-end flex-shrink-0" style="min-width: 100px;">
                        <small class="text-muted">Tổng</small>
                        <p class="mb-0 fw-bold text-danger">
                            <?= formatPrice($itemTotal) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cột phải: Tóm tắt tiền -->
        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h5 class="card-title mb-4">Tóm tắt giỏ hàng</h5>

                    <!-- Chi tiết -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <strong><?= formatPrice($total) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-muted small">
                            <span>Vận chuyển:</span>
                            <span>Tính khi thanh toán</span>
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Tổng cộng -->
                    <div class="d-flex justify-content-between fs-5 fw-bold mb-4">
                        <span>Tổng cộng</span>
                        <span class="text-danger"><?= formatPrice($total) ?></span>
                    </div>

                    <!-- Nút thanh toán -->
                    <button type="submit" class="btn btn-success w-100 mb-2" id="checkoutBtn">
                        <i class="bi bi-credit-card"></i> Tiến hành thanh toán
                    </button>

                    <!-- Nút tiếp tục mua -->
                    <a href="<?= SITE_URL ?>/products.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-shop"></i> Tiếp tục mua sắm
                    </a>
                </div>
            </div>
        </div>
    </div>
    </form>
    <?php endif; ?>
</div>

<script>
// Xử lý checkbox chọn tất cả
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
    updateTotal();
});

// Cập nhật số lượng đã chọn
document.querySelectorAll('.item-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        updateSelectedCount();
        updateTotal();
    });
});

function updateSelectedCount() {
    const count = document.querySelectorAll('.item-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count;
}

function updateTotal() {
    // Tính lại tổng cộng chỉ của items được chọn
    let total = 0;
    document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
        const itemDiv = cb.closest('.list-group-item');
        // Lấy tổng tiền sản phẩm (đã nhân quantity)
        const totalPriceText = itemDiv.querySelector('.text-end .text-danger').textContent;
        const price = parseInt(totalPriceText.replace(/[^0-9]/g, ''));
        total += price;
    });
    
    // Cập nhật hiển thị tổng cộng
    const summaryTotal = document.querySelector('.card-body .fs-5.fw-bold .text-danger');
    if (summaryTotal) {
        summaryTotal.textContent = 
            new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(total);
    }
}

// Kiểm tra before submit
document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
    console.log('Form submit event triggered');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const selected = document.querySelectorAll('.item-checkbox:checked');
    
    console.log('Total checkboxes:', checkboxes.length);
    console.log('Checked checkboxes:', selected.length);
    
    if (selected.length === 0) {
        console.log('No items selected, preventing submit');
        e.preventDefault();
        alert('Vui lòng chọn ít nhất một sản phẩm để thanh toán');
        return false;
    }
    
    console.log('Form will submit with', selected.length, 'items');
    // Log selected item IDs
    selected.forEach(cb => {
        console.log('Selected item:', cb.value);
    });
});

// Xóa các items đã chọn
document.getElementById('deleteSelected')?.addEventListener('click', function() {
    const selected = document.querySelectorAll('.item-checkbox:checked');
    if (selected.length === 0) {
        alert('Vui lòng chọn sản phẩm cần xóa');
        return;
    }
    if (!confirm(`Xóa ${selected.length} sản phẩm đã chọn?`)) return;
    
    const itemIds = Array.from(selected).map(cb => cb.value);
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    
    fetch('<?= SITE_URL ?>/ajax/cart-remove.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            item_ids: itemIds.join(','),
            csrf_token: csrfToken
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Không thể xóa');
        }
    })
    .catch(() => alert('Có lỗi xảy ra'));
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
