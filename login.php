<?php
require_once __DIR__ . '/includes/init.php';

// If already logged in, redirect based on role
if (Auth::check()) {
    if (Auth::isAdmin()) {
        redirect('/admin/');
    } elseif (Auth::isShop()) {
        redirect('/shop/');
    } else {
        redirect('/');
    }
}

$pageTitle = 'Đăng nhập';
$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validation
        if (empty($email)) {
            $errors[] = 'Email không được để trống';
        } elseif (!validateEmail($email)) {
            $errors[] = 'Email không hợp lệ';
        }
        
        if (empty($password)) {
            $errors[] = 'Mật khẩu không được để trống';
        }
        
        // Attempt login
        if (empty($errors)) {
            $result = Auth::login($email, $password, $remember);
            
            if ($result['success']) {
                $redirectUrl = $_GET['redirect'] ?? '/';
                
                // Redirect based on role
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

include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4"><i class="bi bi-person-circle"></i> Đăng nhập</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo escape($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo escape($formData['email'] ?? ''); ?>" required autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                        </button>
                        
                        <div class="text-center">
                            <a href="/forgot-password.php" class="text-decoration-none">Quên mật khẩu?</a>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">Chưa có tài khoản? <a href="/register.php" class="fw-bold">Đăng ký ngay</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
