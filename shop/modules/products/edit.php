<?php
require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_SHOP, '/login.php');

$db = Database::getInstance();
$shopId = Auth::getShopId();

if (!$shopId) {
    Session::setFlash('error', 'Cửa hàng không tồn tại');
    redirect(SITE_URL . '/shop/');
}

$productId = intval($_GET['id'] ?? 0);

if ($productId <= 0) {
    Session::setFlash('error', 'Sản phẩm không tồn tại');
    redirect(SITE_URL . '/shop/modules/products/');
}

// Xác minh sản phẩm thuộc cửa hàng này
$product = $db->queryOne(
    "SELECT id, name, price, sale_price, stock_quantity, description, status FROM products WHERE id = :id AND shop_id = :sid",
    ['id' => $productId, 'sid' => $shopId]
);

if (!$product) {
    Session::setFlash('error', 'Sản phẩm không tồn tại');
    redirect(SITE_URL . '/shop/modules/products/');
}

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'CSRF token không hợp lệ');
        redirect(SITE_URL . '/shop/modules/products/edit.php?id=' . $productId);
    }
    
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $salePrice = floatval($_POST['sale_price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    
    if (!$name || $price <= 0) {
        Session::setFlash('error', 'Vui lòng điền đầy đủ thông tin hợp lệ');
    } else {
        try {
            $db->execute(
                "UPDATE products SET name = :name, price = :price, sale_price = :salePrice, 
                 stock_quantity = :stock, description = :desc, status = :status 
                 WHERE id = :id AND shop_id = :sid",
                [
                    'name' => $name,
                    'price' => $price,
                    'salePrice' => $salePrice,
                    'stock' => $stock,
                    'desc' => $description,
                    'status' => $status,
                    'id' => $productId,
                    'sid' => $shopId
                ]
            );
            Session::setFlash('success', 'Cập nhật sản phẩm thành công');
            redirect(SITE_URL . '/shop/modules/products/');
        } catch (Exception $e) {
            Session::setFlash('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Sửa sản phẩm: ' . escape($product['name']);
include __DIR__ . '/../../../includes/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil"></i> Sửa sản phẩm</h2>
        <a href="<?php echo SITE_URL; ?>/shop/modules/products/" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= escape($product['name']) ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Giá gốc <span class="text-danger">*</span></label>
                                    <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= (float)$product['price'] ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Giá bán</label>
                                    <input type="number" name="sale_price" class="form-control" step="0.01" min="0" value="<?= (float)$product['sale_price'] ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tồn kho <span class="text-danger">*</span></label>
                                    <input type="number" name="stock" class="form-control" min="0" value="<?= (int)$product['stock_quantity'] ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="4"><?= escape($product['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                                <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Tạm ẩn</option>
                            </select>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="bi bi-check-lg"></i> Lưu thay đổi
                            </button>
                            <a href="<?php echo SITE_URL; ?>/shop/modules/products/" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i> Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
