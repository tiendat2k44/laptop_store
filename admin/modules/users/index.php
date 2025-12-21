<?php
require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Xử lý xóa người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'CSRF token không hợp lệ');
        redirect(SITE_URL . '/admin/modules/users/');
    }
    
    $userId = intval($_POST['user_id'] ?? 0);
    if ($userId > 0 && $userId !== Auth::id()) {
        try {
            $db->execute("DELETE FROM users WHERE id = :id", ['id' => $userId]);
            Session::setFlash('success', 'Người dùng đã được xóa');
        } catch (Exception $e) {
            Session::setFlash('error', 'Lỗi: ' . $e->getMessage());
        }
    }
    redirect(SITE_URL . '/admin/modules/users/');
}

// Lấy danh sách người dùng
$where = "1=1";
$params = [];
if ($keyword) {
    $where .= " AND (email ILIKE :kw OR full_name ILIKE :kw)";
    $params['kw'] = '%' . $keyword . '%';
}

$users = $db->query(
    "SELECT id, email, full_name, phone, status, created_at 
     FROM users 
     WHERE $where 
     ORDER BY created_at DESC 
     LIMIT 100",
    $params
);

$pageTitle = 'Quản lý người dùng';
include __DIR__ . '/../../includes/header.php';

// Get statistics
$stats = [
    'total' => $db->queryOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
    'active' => $db->queryOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'] ?? 0,
    'pending' => $db->queryOne("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")['count'] ?? 0,
    'shops' => $db->queryOne("SELECT COUNT(*) as count FROM users WHERE role = 'shop_owner'")['count'] ?? 0,
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-people"></i> Quản lý người dùng</h2>
        <p class="text-muted mb-0">Tổng cộng: <strong><?= number_format($stats['total']) ?></strong> người dùng</p>
    </div>
    <a href="<?php echo SITE_URL; ?>/admin/modules/users/add.php" class="btn btn-primary btn-lg">
        <i class="bi bi-plus-circle"></i> Thêm người dùng
    </a>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card primary shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Tổng người dùng</h6>
                        <h3 class="mb-0"><?= number_format($stats['total']) ?></h3>
                    </div>
                    <i class="bi bi-people fs-3 text-primary opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Đang hoạt động</h6>
                        <h3 class="mb-0"><?= number_format($stats['active']) ?></h3>
                    </div>
                    <i class="bi bi-person-check fs-3 text-success opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Chờ phê duyệt</h6>
                        <h3 class="mb-0"><?= number_format($stats['pending']) ?></h3>
                    </div>
                    <i class="bi bi-clock-history fs-3 text-warning opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Shop owners</h6>
                        <h3 class="mb-0"><?= number_format($stats['shops']) ?></h3>
                    </div>
                    <i class="bi bi-shop fs-3 text-info opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter & Search -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="bi bi-funnel"></i> Tìm kiếm & lọc</h5>
    </div>
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Tìm kiếm (Email/Tên/Điện thoại)</label>
                <input type="text" name="keyword" class="form-control" 
                       placeholder="Nhập email, tên hoặc số điện thoại..." 
                       value="<?= escape($keyword) ?>">
            </div>
            <div class="col-md-6 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search"></i> Tìm kiếm
                </button>
                <a href="<?php echo SITE_URL; ?>/admin/modules/users/" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Đặt lại
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th width="60">ID</th>
                        <th>Email</th>
                        <th>Tên</th>
                        <th>Điện thoại</th>
                        <th>Loại tài khoản</th>
                        <th>Trạng thái</th>
                        <th width="160">Ngày đăng ký</th>
                        <th width="120" class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark">#<?= (int)$user['id'] ?></span>
                            </td>
                            <td>
                                <strong><?= escape($user['email']) ?></strong>
                            </td>
                            <td><?= escape($user['full_name']) ?></td>
                            <td><?= escape($user['phone'] ?? '-') ?></td>
                            <td>
                                <small class="badge bg-secondary">
                                    <?= strpos($user['email'], '@shop') !== false ? 'Shop Owner' : 'Khách hàng' ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : ($user['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                    <i class="bi bi-<?= $user['status'] === 'active' ? 'check-circle' : 'clock' ?>"></i>
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= formatDate($user['created_at']) ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="<?php echo SITE_URL; ?>/admin/modules/users/edit.php?id=<?= (int)$user['id'] ?>" 
                                       class="btn btn-outline-warning" title="Sửa">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" onclick="deleteUser(<?= (int)$user['id'] ?>, '<?= escape($user['full_name']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3"></i>
                            <p class="mt-2">Không có người dùng nào</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Form (Hidden) -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
    <input type="hidden" name="user_id" id="deleteUserId">
    <input type="hidden" name="delete_user" value="1">
</form>

<script>
function deleteUser(userId, userName) {
    if (confirm('Bạn chắc chắn muốn xóa người dùng "' + userName + '"? Hành động này không thể hoàn tác.')) {
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
