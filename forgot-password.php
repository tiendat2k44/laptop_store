<?php
require_once __DIR__ . '/includes/init.php';

if (Auth::check()) {
    redirect('/');
}

$errors = [];
$sent = false;
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token bảo mật không hợp lệ';
    } else {
        $email = trim($_POST['email'] ?? '');
        $emailVal = $email;
        if ($email === '' || !isValidEmail($email)) {
            $errors[] = 'Email không hợp lệ';
        } else {
            $db = Database::getInstance();
            $user = $db->queryOne("SELECT id, email, full_name, status, email_verified FROM users WHERE email = :email", ['email' => $email]);
            if (!$user) {
                // Không tiết lộ tồn tại email, vẫn báo gửi OK
                $sent = true;
            } else {
                if ($user['status'] !== 'active' || !$user['email_verified']) {
                    $sent = true; // không tiết lộ chi tiết
                } else {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', time() + 3600); // 60 phút
                    $ok = $db->execute(
                        "UPDATE users SET password_reset_token = :t, password_reset_expires = :e WHERE id = :id",
                        ['t' => $token, 'e' => $expires, 'id' => $user['id']]
                    );
                    if ($ok) {
                        $resetUrl = SITE_URL . '/reset-password.php?token=' . urlencode($token);
                        $body = tpl_password_reset($user, $resetUrl);
                        @send_mail($user['email'], '['.SITE_NAME.'] Đặt lại mật khẩu', $body);
                        $sent = true;
                    } else {
                        $errors[] = 'Không thể tạo yêu cầu đặt lại mật khẩu, vui lòng thử lại.';
                    }
                }
            }
        }
    }
}

$pageTitle = 'Quên mật khẩu';
include __DIR__ . '/includes/header.php';
?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-3"><i class="bi bi-key"></i> Quên mật khẩu</h3>
                    <?php if ($sent): ?>
                        <div class="alert alert-success">Nếu email tồn tại, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu.</div>
                        <a href="<?= SITE_URL ?>/login.php" class="btn btn-primary w-100">Quay lại đăng nhập</a>
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                            <div class="mb-3">
                                <label class="form-label">Email đã đăng ký</label>
                                <input type="email" class="form-control" name="email" required value="<?= escape($emailVal) ?>" placeholder="you@example.com">
                            </div>
                            <button class="btn btn-primary w-100">Gửi liên kết đặt lại</button>
                            <div class="text-center mt-3">
                                <a href="<?= SITE_URL ?>/login.php">Quay lại đăng nhập</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
