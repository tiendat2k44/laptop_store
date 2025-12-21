<?php
require_once __DIR__ . '/includes/init.php';

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (Auth::check()) {
    if (Auth::isAdmin()) {
        redirect('/admin/');
    } elseif (Auth::isShop()) {
        redirect('/shop/');
    } else {
        redirect('/');
    }
}

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting - chống brute force (5 attempts per 5 minutes)
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $limiter = new RateLimiter('login_' . $ipAddress);
    
    if (!$limiter->isAllowed(5, 300)) {
        $remaining = $limiter->getRemainingAttempts(5, 300);
        $errors[] = 'Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau 5 phút.';
    }
    
    // Kiểm tra CSRF token
    elseif (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token bảo mật không hợp lệ';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Validation
        if (empty($email)) {
            $errors[] = 'Email không được để trống';
        } elseif (!isValidEmail($email)) {
            $errors[] = 'Email không hợp lệ';
        }

        if (empty($password)) {
            $errors[] = 'Mật khẩu không được để trống';
        }

        // Đăng nhập
        if (empty($errors)) {
            $result = Auth::login($email, $password, $remember);

            if ($result['success']) {
                // Xử lý redirect URL
                $redirectUrl = $_GET['redirect'] ?? '/';
                
                // Kiểm tra redirect URL hợp lệ (chặn URL tuyệt đối)
                if (strpos($redirectUrl, '://') !== false || !is_string($redirectUrl)) {
                    $redirectUrl = '/';
                }
                
                $redirectUrl = '/' . ltrim($redirectUrl, '/');

                // Chuyển hướng theo role
                if (Auth::isAdmin()) {
                    redirect('/admin/');
                } elseif (Auth::isShop()) {
                    redirect('/shop/');
                } else {
                    redirect($redirectUrl);
                }
            } else {
                $errors[] = $result['message'];
                $formData = ['email' => $email];
            }
        } else {
            $formData = ['email' => $email];
        }
    }
}

$pageTitle = 'Đăng nhập';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <!-- Tiêu đề -->
                    <div class="text-center mb-4">
                        <h2><i class="bi bi-person-circle"></i> Đăng nhập</h2>
                        <p class="text-muted">Chào mừng bạn trở lại!</p>
                    </div>

                    <!-- Thông báo lỗi -->
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <strong>Có lỗi xảy ra:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                            <li><?= escape($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Form đăng nhập -->
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= escape($formData['email'] ?? '') ?>" 
                                   placeholder="user@example.com"
                                   required autofocus>
                        </div>

                        <!-- Mật khẩu -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="••••••••"
                                   required>
                        </div>

                        <!-- Ghi nhớ -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                        </div>

                        <!-- Nút đăng nhập -->
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                        </button>

                        <!-- Quên mật khẩu -->
                        <div class="text-center">
                            <a href="<?= SITE_URL ?>/forgot-password.php" class="text-decoration-none">
                                Quên mật khẩu?
                            </a>
                        </div>
                    </form>

                    <hr class="my-4">

                    <!-- Đăng ký -->
                    <div class="text-center">
                        <p class="mb-0">
                            Chưa có tài khoản? 
                            <a href="<?= SITE_URL ?>/register.php" class="fw-bold">Đăng ký ngay</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
