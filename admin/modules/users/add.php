<?php
/**
 * Admin - Thêm Người Dùng Mới
 * Tạo tài khoản người dùng mới vào hệ thống
 */

require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();

// Xử lý form thêm người dùng mới
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'CSRF token không hợp lệ');
        redirect(SITE_URL . '/admin/modules/users/add.php');
    }
    
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    
    // Kiểm tra dữ liệu hợp lệ
    if (!$email || !$fullName || !$password) {
        Session::setFlash('error', 'Vui lòng điền đầy đủ thông tin bắt buộc');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Session::setFlash('error', 'Email không hợp lệ');
    } elseif (strlen($password) < 6) {
        Session::setFlash('error', 'Mật khẩu phải có ít nhất 6 ký tự');
    } else {
        // Kiểm tra email đã tồn tại trong hệ thống chưa
        $existing = $db->queryOne(
            "SELECT id FROM users WHERE email = :email",
            ['email' => $email]
        );
        
        if ($existing) {
            Session::setFlash('error', 'Email này đã được sử dụng');
        } else {
            try {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $db->insert(
                    "INSERT INTO users (email, full_name, phone, password_hash, status, email_verified, created_at) 
                     VALUES (:email, :name, :phone, :pass, :status, true, NOW())",
                    [
                        'email' => $email,
                        'name' => $fullName,
                        'phone' => $phone,
                        'pass' => $hashedPassword,
                        'status' => $status
                    ]
                );
                Session::setFlash('success', 'Tạo người dùng thành công');
                redirect(SITE_URL . '/admin/modules/users/');
            } catch (Exception $e) {
                Session::setFlash('error', 'Lỗi: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = 'Thêm người dùng';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-plus"></i> Thêm người dùng</h2>
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
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tên người dùng <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <small class="text-muted">Tối thiểu 6 ký tự</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="active">Hoạt động</option>
                            <option value="pending">Chờ duyệt</option>
                            <option value="locked">Bị khóa</option>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-check-lg"></i> Tạo người dùng
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
