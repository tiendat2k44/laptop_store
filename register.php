<?php
require_once __DIR__ . '/includes/init.php';

// If already logged in, redirect
if (Auth::check()) {
    redirect('/');
}

$pageTitle = 'Đăng ký tài khoản';
$errors = [];
$formData = [];
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $accountType = $_POST['account_type'] ?? 'customer';
        
        // Validation
        if (empty($fullName)) {
            $errors[] = 'Họ tên không được để trống';
        } elseif (strlen($fullName) < 3) {
            $errors[] = 'Họ tên phải có ít nhất 3 ký tự';
        }
        
        if (empty($email)) {
            $errors[] = 'Email không được để trống';
        } elseif (!validateEmail($email)) {
            $errors[] = 'Email không hợp lệ';
        } else {
            // Check if email exists
            $existingUser = $db->queryOne("SELECT id FROM users WHERE email = :email", ['email' => $email]);
            if ($existingUser) {
                $errors[] = 'Email đã được sử dụng';
            }
        }
        
        if (!empty($phone) && !validatePhone($phone)) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }
        
        if (empty($password)) {
            $errors[] = 'Mật khẩu không được để trống';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Mật khẩu xác nhận không khớp';
        }
        
        // Register user
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                // Determine role
                $roleId = $accountType === 'shop' ? ROLE_SHOP : ROLE_CUSTOMER;
                $status = $accountType === 'shop' ? 'pending' : 'active';
                
                // Generate verification token
                $verificationToken = bin2hex(random_bytes(16));
                
                // Insert user
                $sql = "INSERT INTO users (role_id, email, password_hash, full_name, phone, status, email_verified, email_verification_token, created_at) 
                        VALUES (:role_id, :email, :password_hash, :full_name, :phone, :status, :email_verified, :verification_token, CURRENT_TIMESTAMP)
                        RETURNING id";
                
                $result = $db->queryOne($sql, [
                    'role_id' => $roleId,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'status' => $status,
                    'email_verified' => true,
                    'verification_token' => $verificationToken
                ]);
                
                $userId = $result['id'] ?? null;
                
                if ($userId) {
                    // If shop account, create shop entry
                    if ($accountType === 'shop') {
                        $shopName = trim($_POST['shop_name'] ?? '');
                        
                        if (empty($shopName)) {
                            throw new Exception('Tên cửa hàng không được để trống');
                        }
                        
                        $shopSlug = generateSlug($shopName);
                        
                        $shopSql = "INSERT INTO shops (user_id, shop_name, status, created_at) 
                                    VALUES (:user_id, :shop_name, :status, CURRENT_TIMESTAMP)";
                        
                        $db->insert($shopSql, [
                            'user_id' => $userId,
                            'shop_name' => $shopName,
                            'status' => 'pending'
                        ]);
                    }
                    
                    $db->commit();
                    
                    // TODO: Send verification email
                    // For now, we'll just show success message
                    
                    Session::setFlash('success', 'Đăng ký thành công! ' . ($accountType === 'shop' ? 'Vui lòng đợi admin phê duyệt.' : 'Vui lòng đăng nhập.'));
                    redirect('/login.php');
                } else {
                    throw new Exception('Không thể tạo tài khoản');
                }
                
            } catch (Exception $e) {
                $db->rollback();
                $errors[] = $e->getMessage();
                error_log("Registration error: " . $e->getMessage());
            }
        }
        
        $formData = [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'account_type' => $accountType,
            'shop_name' => $_POST['shop_name'] ?? ''
        ];
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4"><i class="bi bi-person-plus"></i> Đăng ký tài khoản</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo escape($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="registerForm">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getToken(); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Loại tài khoản <span class="text-danger">*</span></label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="account_type" id="typeCustomer" 
                                           value="customer" <?php echo ($formData['account_type'] ?? 'customer') === 'customer' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="typeCustomer">Khách hàng</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="account_type" id="typeShop" 
                                           value="shop" <?php echo ($formData['account_type'] ?? '') === 'shop' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="typeShop">Người bán (Shop)</label>
                                </div>
                            </div>
                        </div>
                        
                        <div id="shopNameField" style="display: none;">
                            <div class="mb-3">
                                <label for="shop_name" class="form-label">Tên cửa hàng <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="shop_name" name="shop_name" 
                                       value="<?php echo escape($formData['shop_name'] ?? ''); ?>">
                                <small class="text-muted">Tài khoản shop cần được admin phê duyệt trước khi sử dụng</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo escape($formData['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo escape($formData['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Số điện thoại</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo escape($formData['phone'] ?? ''); ?>" placeholder="0xxxxxxxxx">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Tối thiểu 6 ký tự</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-person-plus"></i> Đăng ký
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">Đã có tài khoản? <a href="/login.php" class="fw-bold">Đăng nhập ngay</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<JS
<script>
$(function() {
    function toggleShopField() {
        if ($('#typeShop').is(':checked')) {
            $('#shopNameField').show();
            $('#shop_name').attr('required', true);
        } else {
            $('#shopNameField').hide();
            $('#shop_name').attr('required', false);
        }
    }

    $('input[name="account_type"]').on('change', toggleShopField);
    toggleShopField();
});
</script>
JS;

include __DIR__ . '/includes/footer.php';
?>
