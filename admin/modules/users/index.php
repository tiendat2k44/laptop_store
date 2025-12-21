<?php
require_once __DIR__ . '/../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Lấy danh sách người dùng
$where = "1=1";
$params = [];
if ($keyword) {
    $where .= " AND (email ILIKE :kw OR full_name ILIKE :kw)";
    $params['kw'] = '%' . $keyword . '%';
}

$users = $db->query(
    "SELECT id, email, full_name, phone, is_admin, created_at 
     FROM users 
     WHERE $where 
     ORDER BY created_at DESC 
     LIMIT 100",
    $params
);

$pageTitle = 'Quản lý người dùng';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Người dùng</h2>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-8">
                <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm email hoặc tên..." value="<?= escape($keyword) ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary w-100">Tìm kiếm</button>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Tên</th>
                <th>Điện thoại</th>
                <th>Vai trò</th>
                <th>Ngày đăng ký</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($users): ?>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>#<?= (int)$user['id'] ?></td>
                    <td><?= escape($user['email']) ?></td>
                    <td><?= escape($user['full_name']) ?></td>
                    <td><?= escape($user['phone'] ?? '-') ?></td>
                    <td>
                        <span class="badge bg-<?= $user['is_admin'] ? 'danger' : 'secondary' ?>">
                            <?= $user['is_admin'] ? 'Admin' : 'Customer' ?>
                        </span>
                    </td>
                    <td><small><?= formatDate($user['created_at']) ?></small></td>
                    <td>
                        <a href="#" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    Không có người dùng nào
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
