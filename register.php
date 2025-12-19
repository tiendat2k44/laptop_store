<?php
require_once __DIR__ . '/includes/init.php';

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (Auth::check()) {
    redirect('/');
}

$errors = [];
$formData = [];
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF token
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token bảo mật không hợp lệ';
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
        } elseif (!isValidEmail($email)) {
            $errors[] = 'Email không hợp lệ';
        } else {
            // Kiểm tra email đã tồn tại
            $existingUser = $db->queryOne("SELECT id FROM users WHERE email = :email", ['email' => $email]);
            if ($existingUser) {
                $errors[] = 'Email đã được sử dụng';
            }
        }

        if (!empty($phone) && !isValidPhone($phone)) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }

        if (empty($password)) {
            $errors[] = 'Mật khẩu không được để trống';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Mật khẩu xác nhận không khớp';
        }

        // Tạo tài khoản
        if (empty($errors)) {
            try {
                $db->beginTransaction();

                // Xác định role và status
                $roleId = $accountType === 'shop' ? ROLE_SHOP : ROLE_CUSTOMER;
                $status = $accountType === 'shop' ? 'pending' : 'active';

                // Tạo token xác thực email
                $verificationToken = bin2hex(random_bytes(16));

                // Thêm user
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
                    // Nếu là tài khoản shop, tạo shop
                    if ($accountType === 'shop') {
                        $shopName = trim($_POST['shop_name'] ?? '');

                        if (empty($shopName)) {
                            throw new Exception('Tên cửa hàng không được để trống');
                        }

                        $shopSql = "INSERT INTO shops (user_id, shop_name, status, created_at) 
                                    VALUES (:user_id, :shop_name, :status, CURRENT_TIMESTAMP)";

                        $db->insert($shopSql, [
                            'user_id' => $userId,
                            'shop_name' => $shopName,
                            'status' => 'pending'
                        ]);
                    }

                    $db->commit();

                    $successMsg = $accountType === 'shop' 
                        ? 'Đăng ký thành công! Vui lòng đợi admin phê duyệt tài khoản.' 
                        : 'Đăng ký thành công! Vui lòng đăng nhập.';
                    
                    Session::setFlash('success', $successMsg);
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

$pageTitle = 'Đăng ký tài khoản';
include __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <!-- Tiêu đề -->
                    <div class="text-center mb-4">
                        <h2><i class="bi bi-person-plus"></i> Đăng ký tài khoản</h2>
                        <p class="text-muted">Tạo tài khoản mới để bắt đầu mua sắm</p>
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

                    <!-- Form đăng ký -->
                    <form method="POST" action="" id="registerForm">
                        <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">

                        <!-- Loại tài khoản -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Loại tài khoản</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="account_type" 
                                           id="typeCustomer" value="customer" 
                                           <?= ($formData['account_type'] ?? 'customer') === 'customer' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="typeCustomer">
                                        <i class="bi bi-person"></i> Khách hàng
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="account_type" 
                                           id="typeShop" value="shop" 
                                           <?= ($formData['account_type'] ?? '') === 'shop' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="typeShop">
                                        <i class="bi bi-shop"></i> Người bán
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Tên cửa hàng (ẩn mặc định) -->
                        <div id="shopNameField" class="mb-3" style="display: none;">
                            <label for="shop_name" class="form-label">Tên cửa hàng</label>
                            <input type="text" class="form-control" id="shop_name" name="shop_name" 
                                   value="<?= escape($formData['shop_name'] ?? '') ?>">
                            <small class="text-muted">Tài khoản shop cần được admin phê duyệt</small>
                        </div>

                        <!-- Họ tên -->
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Họ và tên</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?= escape($formData['full_name'] ?? '') ?>" 
                                   placeholder="Nguyễn Văn A"
                                   required>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= escape($formData['email'] ?? '') ?>" 
                                   placeholder="user@example.com"
                                   required>
                        </div>

                        <!-- Số điện thoại -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">Số điện thoại</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= escape($formData['phone'] ?? '') ?>" 
                                   placeholder="0xxxxxxxxx">
                        </div>

                        <!-- Mật khẩu -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="••••••••"
                                   required>
                            <small class="text-muted">Tối thiểu 6 ký tự</small>
                        </div>

                        <!-- Xác nhận mật khẩu -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="••••••••"
                                   required>
                        </div>

                        <!-- Nút đăng ký -->
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-person-plus"></i> Đăng ký
                        </button>
                    </form>

                    <hr class="my-4">

                    <!-- Đăng nhập -->
                    <div class="text-center">
                        <p class="mb-0">
                            Đã có tài khoản? 
                            <a href="<?= SITE_URL ?>/login.php" class="fw-bold">Đăng nhập ngay</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle shop name field
document.addEventListener('DOMContentLoaded', function() {
    const shopRadio = document.getElementById('typeShop');
    const customerRadio = document.getElementById('typeCustomer');
    const shopNameField = document.getElementById('shopNameField');
    const shopNameInput = document.getElementById('shop_name');

    function toggleShopField() {
        if (shopRadio.checked) {
            shopNameField.style.display = 'block';
            shopNameInput.required = true;
        } else {
            shopNameField.style.display = 'none';
            shopNameInput.required = false;
        }
    }

    shopRadio.addEventListener('change', toggleShopField);
    customerRadio.addEventListener('change', toggleShopField);
    
    toggleShopField();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
