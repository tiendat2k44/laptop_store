<?php
require_once __DIR__ . '/includes/init.php';

if (Auth::check()) {
    redirect('/');
}

$errors = [];
$done = false;
$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));

$db = Database::getInstance();
$user = null;
if ($token !== '') {
    $user = $db->queryOne(
        "SELECT id, email, full_name, password_reset_expires FROM users WHERE password_reset_token = :t",
        ['t' => $token]
    );
}

if (!$user) {
    $errors[] = 'Liên kết không hợp lệ hoặc đã sử dụng.';
} elseif (!empty($user['password_reset_expires']) && strtotime($user['password_reset_expires']) < time()) {
    $errors[] = 'Liên kết đã hết hạn. Vui lòng yêu cầu lại.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token bảo mật không hợp lệ';
    } else {
        $pass = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        if (strlen($pass) < 6) {
            $errors[] = 'Mật khẩu phải từ 6 ký tự trở lên';
        } elseif ($pass !== $confirm) {
            $errors[] = 'Xác nhận mật khẩu không khớp';
        } else {
            $hash = Auth::hashPassword($pass);
            $ok = $db->execute(
                "UPDATE users SET password_hash = :h, password_reset_token = NULL, password_reset_expires = NULL WHERE id = :id",
                ['h' => $hash, 'id' => $user['id']]
            );
            if ($ok) {
                $done = true;
                Session::setFlash('success', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập.');
                redirect('/login.php');
            } else {
                $errors[] = 'Không thể đặt lại mật khẩu, vui lòng thử lại.';
            }
        }
    }
}

$pageTitle = 'Đặt lại mật khẩu';
include __DIR__ . '/includes/header.php';
?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-3"><i class="bi bi-shield-lock"></i> Đặt lại mật khẩu</h3>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <?php if ($user && empty($errors)): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                        <input type="hidden" name="token" value="<?= escape($token) ?>">
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control" name="password" required placeholder="••••••••">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Xác nhận mật khẩu</label>
                            <input type="password" class="form-control" name="password_confirm" required placeholder="••••••••">
                        </div>
                        <button class="btn btn-primary w-100">Cập nhật mật khẩu</button>
                        <div class="text-center mt-3">
                            <a href="<?= SITE_URL ?>/login.php">Quay lại đăng nhập</a>
                        </div>
                    </form>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/forgot-password.php" class="btn btn-outline-primary w-100">Yêu cầu lại liên kết</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
