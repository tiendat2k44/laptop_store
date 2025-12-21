<?php
require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Lấy danh sách cửa hàng
$where = "1=1";
$params = [];
if ($status) {
    $where .= " AND s.status = :status";
    $params['status'] = $status;
}

$shops = $db->query(
    "SELECT s.id, s.shop_name, s.status, s.created_at, u.full_name, u.email 
     FROM shops s 
     JOIN users u ON s.user_id = u.id 
     WHERE $where 
     ORDER BY s.created_at DESC 
     LIMIT 100",
    $params
);

$pageTitle = 'Quản lý cửa hàng';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-shop"></i> Cửa hàng</h2>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-6">
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Tạm ẩn</option>
                </select>
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-outline-primary w-100">Lọc</button>
            </div>
        </form>
    </div>
</div>

<!-- Shops Table -->
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Tên cửa hàng</th>
                <th>Chủ sở hữu</th>
                <th>Email</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($shops): ?>
                <?php foreach ($shops as $shop): ?>
                <tr>
                    <td>#<?= (int)$shop['id'] ?></td>
                    <td><?= escape($shop['shop_name']) ?></td>
                    <td><?= escape($shop['full_name']) ?></td>
                    <td><?= escape($shop['email']) ?></td>
                    <td>
                        <span class="badge bg-<?= 
                            $shop['status'] === 'active' ? 'success' : 
                            ($shop['status'] === 'pending' ? 'warning' : 'secondary') 
                        ?>">
                            <?= ucfirst($shop['status']) ?>
                        </span>
                    </td>
                    <td><small><?= formatDate($shop['created_at']) ?></small></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" title="Duyệt">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <a href="#" class="btn btn-sm btn-outline-danger" title="Xóa">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    Không có cửa hàng nào
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
