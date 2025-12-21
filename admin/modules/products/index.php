<?php
require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();

$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Xử lý POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf_token']) ? trim($_POST['csrf_token']) : '';
    if (!Session::verifyToken($csrf)) {
        Session::setFlash('error', 'Invalid token');
        redirect(SITE_URL . '/admin/modules/products/');
    }
    
    if (isset($_POST['add_product'])) {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $sale_price = isset($_POST['sale_price']) ? floatval($_POST['sale_price']) : 0;
        $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        
        if ($name && $price > 0 && $stock >= 0) {
            $result = $db->insert(
                "INSERT INTO products (name, price, sale_price, stock_quantity, description, status, created_at) 
                 VALUES (:name, :price, :sale_price, :stock, :desc, 'active', NOW())",
                [
                    'name' => $name,
                    'price' => $price,
                    'sale_price' => $sale_price ?? $price,
                    'stock' => $stock,
                    'desc' => $description
                ]
            );
            if ($result) {
                Session::setFlash('success', 'Sản phẩm được thêm thành công');
            }
        } else {
            Session::setFlash('error', 'Vui lòng điền đầy đủ thông tin hợp lệ');
        }
        redirect(SITE_URL . '/admin/modules/products/');
    }
    
    if (isset($_POST['delete_product'])) {
        $productId = intval($_POST['product_id'] ?? 0);
        if ($productId > 0) {
            try {
                $db->execute("DELETE FROM products WHERE id = :id", ['id' => $productId]);
                Session::setFlash('success', 'Xóa sản phẩm thành công');
            } catch (Exception $e) {
                Session::setFlash('error', 'Lỗi: ' . $e->getMessage());
            }
        }
        redirect(SITE_URL . '/admin/modules/products/');
    }
}

// Lấy danh sách sản phẩm
$where = "1=1";
$params = [];
if ($status) {
    $where .= " AND status = :status";
    $params['status'] = $status;
}
if ($keyword) {
    $where .= " AND name ILIKE :keyword";
    $params['keyword'] = '%' . $keyword . '%';
}

$products = $db->query(
    "SELECT id, name, price, sale_price, stock_quantity, status, created_at 
     FROM products 
     WHERE $where 
     ORDER BY created_at DESC 
     LIMIT 100",
    $params
);

$pageTitle = 'Quản lý sản phẩm';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box"></i> Sản phẩm</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="bi bi-plus-circle"></i> Thêm sản phẩm
    </button>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-6">
                <input type="text" name="keyword" class="form-control" placeholder="Tìm sản phẩm..." value="<?= escape($keyword) ?>">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Tạm ẩn</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">Tìm kiếm</button>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Tên sản phẩm</th>
                <th>Giá</th>
                <th>Giá bán</th>
                <th>Tồn kho</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($products): ?>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td>#<?= (int)$product['id'] ?></td>
                    <td><?= escape($product['name']) ?></td>
                    <td><?= formatPrice($product['price']) ?></td>
                    <td><?= formatPrice($product['sale_price']) ?></td>
                    <td>
                        <span class="badge bg-<?= $product['stock_quantity'] > 0 ? 'success' : 'danger' ?>">
                            <?= (int)$product['stock_quantity'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'secondary' ?>">
                            <?= $product['status'] === 'active' ? 'Hoạt động' : 'Tạm ẩn' ?>
                        </span>
                    </td>
                    <td><small><?= formatDate($product['created_at']) ?></small></td>
                    <td>
                        <a href="<?php echo SITE_URL; ?>/admin/modules/products/edit.php?id=<?= (int)$product['id'] ?>" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-pencil"></i> Sửa
                        </a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn chắc chắn muốn xóa?');">
                            <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                            <button type="submit" name="delete_product" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-inbox"></i> Không có sản phẩm nào
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm sản phẩm mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Tên sản phẩm *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Giá gốc *</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Giá bán</label>
                        <input type="number" name="sale_price" class="form-control" step="0.01" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tồn kho *</label>
                        <input type="number" name="stock" class="form-control" min="0" value="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="add_product" class="btn btn-primary">Thêm sản phẩm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
