<?php
/**
 * Admin - Sửa Người Dùng
 * Chỉnh sửa thông tin người dùng (tên, số điện thoại, trạng thái)
 */

require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();
$userId = intval($_GET['id'] ?? 0);

if ($userId <= 0) {
    Session::setFlash('error', 'Người dùng không tồn tại');
    redirect(SITE_URL . '/admin/modules/users/');
}

// Lấy thông tin chi tiết người dùng
$user = $db->queryOne(
    "SELECT id, email, full_name, phone, status FROM users WHERE id = :id",
    ['id' => $userId]
);

if (!$user) {
    Session::setFlash('error', 'Người dùng không tồn tại');
    redirect(SITE_URL . '/admin/modules/users/');
}

// Xử lý form cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'CSRF token không hợp lệ');
        redirect(SITE_URL . '/admin/modules/users/edit.php?id=' . $userId);
    }
    
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    
    if (!$fullName) {
        Session::setFlash('error', 'Tên người dùng không được để trống');
    } elseif (!in_array($status, ['active', 'pending', 'locked'])) {
        Session::setFlash('error', 'Trạng thái không hợp lệ');
    } else {
        try {
            $db->execute(
                "UPDATE users SET full_name = :name, phone = :phone, status = :status WHERE id = :id",
                [
                    'name' => $fullName,
                    'phone' => $phone,
                    'status' => $status,
                    'id' => $userId
                ]
            );
            Session::setFlash('success', 'Cập nhật người dùng thành công');
            redirect(SITE_URL . '/admin/modules/users/');
        } catch (Exception $e) {
            Session::setFlash('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Sửa người dùng: ' . escape($user['email']);
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil"></i> Sửa người dùng</h2>
    <a href="<?php echo SITE_URL; ?>/admin/modules/users/" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Quay lại
    </a>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= escape($user['email']) ?>" readonly>
                        <small class="text-muted">Email không thể thay đổi</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tên người dùng <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?= escape($user['full_name']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control" value="<?= escape($user['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                            <option value="pending" <?= $user['status'] === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                            <option value="locked" <?= $user['status'] === 'locked' ? 'selected' : '' ?>>Bị khóa</option>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-check-lg"></i> Lưu thay đổi
                        </button>
                        <a href="<?php echo SITE_URL; ?>/admin/modules/users/" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
