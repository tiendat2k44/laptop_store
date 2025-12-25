<?php
/**
 * Trang Hồ Sơ Người Dùng
 * Xem và chỉnh sửa thông tin cá nhân, đổi mật khẩu, xem lịch sử
 */

require_once __DIR__ . '/../includes/init.php';

// Kiểm tra đăng nhập
if (!Auth::check()) {
    Session::setFlash('error', 'Vui lòng đăng nhập để xem hồ sơ');
    redirect('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$db = Database::getInstance();
$userId = Auth::id();
$errorMsg = null;
$successMsg = null;

// Lấy thông tin người dùng hiện tại (sử dụng Auth::user để đảm bảo tính nhất quán phiên)
$user = Auth::user();

if (!$user) {
    Auth::logout();
    Session::setFlash('error', 'Phiên đăng nhập không hợp lệ, vui lòng đăng nhập lại');
    redirect('/login.php');
}

// **QUAN TRỌNG: Xử lý upload ảnh đại diện TRƯỚC các hành động khác**
// Điều này đảm bảo avatar được cập nhật trước khi hiển thị
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && !empty($_FILES['avatar']['name'])) {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'CSRF token không hợp lệ';
    } elseif ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'Lỗi upload ảnh';
    } else {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errorMsg = 'Chỉ hỗ trợ ảnh JPG, PNG, GIF, WebP';
        } elseif ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
            $errorMsg = 'Kích thước ảnh không được vượt quá 5MB';
        } else {
            try {
                // Tạo tên file duy nhất
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
                $uploadPath = __DIR__ . '/../assets/uploads/avatars/';
                
                // Tạo thư mục nếu chưa có
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Xóa ảnh cũ nếu có
                if ($user['avatar'] && file_exists($uploadPath . basename($user['avatar']))) {
                    @unlink($uploadPath . basename($user['avatar']));
                }
                
                // Upload ảnh mới
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath . $filename)) {
                    $avatarPath = '/assets/uploads/avatars/' . $filename;
                    $db->execute(
                        "UPDATE users SET avatar = :avatar, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                        ['avatar' => $avatarPath, 'id' => $userId]
                    );
                    $user['avatar'] = $avatarPath;
                    $user['updated_at'] = date('Y-m-d H:i:s');
                    $successMsg = 'Cập nhật ảnh đại diện thành công';
                } else {
                    $errorMsg = 'Không thể lưu ảnh';
                }
            } catch (Exception $e) {
                $errorMsg = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
}

// Xử lý cập nhật thông tin cá nhân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_info') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'CSRF token không hợp lệ';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        if (!$fullName) {
            $errorMsg = 'Tên đầy đủ không được để trống';
        } else {
            try {
                $db->execute(
                    "UPDATE users SET full_name = :name, phone = :phone, address = :address, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :id",
                    [
                        'name' => $fullName,
                        'phone' => $phone,
                        'address' => $address,
                        'id' => $userId
                    ]
                );
                $successMsg = 'Cập nhật thông tin thành công';
                // Cập nhật lại session
                $user['full_name'] = $fullName;
                $user['phone'] = $phone;
                $user['address'] = $address;
            } catch (Exception $e) {
                $errorMsg = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
}

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'CSRF token không hợp lệ';
    } else {
        $currentPassword = trim($_POST['current_password'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        
        if (!$currentPassword || !$newPassword || !$confirmPassword) {
            $errorMsg = 'Vui lòng điền đầy đủ các trường';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = 'Mật khẩu xác nhận không khớp';
        } elseif (strlen($newPassword) < 6) {
            $errorMsg = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        } else {
            // Xác thực mật khẩu hiện tại
            $currentUser = $db->queryOne(
                "SELECT password_hash FROM users WHERE id = :id",
                ['id' => $userId]
            );
            
            if (!password_verify($currentPassword, $currentUser['password_hash'])) {
                $errorMsg = 'Mật khẩu hiện tại không chính xác';
            } else {
                try {
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                    $db->execute(
                        "UPDATE users SET password_hash = :pass, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                        ['pass' => $hashedPassword, 'id' => $userId]
                    );
                    $successMsg = 'Đổi mật khẩu thành công';
                } catch (Exception $e) {
                    $errorMsg = 'Lỗi: ' . $e->getMessage();
                }
            }
        }
    }
}

// Lấy danh sách đơn hàng gần đây để hiển thị lịch sử
$recentOrders = $db->query(
    "SELECT id, order_number, total_amount, status, payment_status, created_at 
     FROM orders 
     WHERE user_id = :uid 
     ORDER BY created_at DESC 
     LIMIT 10",
    ['uid' => $userId]
);

// Avatar URL có cache-busting để tránh hiển thị ảnh cũ
$avatarCacheBust = isset($user['updated_at']) ? strtotime($user['updated_at']) : time();
$avatarUrl = !empty($user['avatar'])
    ? image_url($user['avatar']) . '?v=' . $avatarCacheBust
    : 'https://via.placeholder.com/150?text=No+Avatar';

$pageTitle = 'Hồ Sơ Người Dùng';
$activeTab = $_GET['tab'] ?? 'info';
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Trang chủ</a></li>
            <li class="breadcrumb-item active">Hồ sơ cá nhân</li>
        </ol>
    </nav>

    <!-- Flash Messages -->
    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo escape($successMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?php echo escape($errorMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Sidebar - Avatar và tên -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <!-- Avatar -->
                    <div class="mb-3">
                        <form id="formAvatarUpload" method="POST" enctype="multipart/form-data" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo Session::getToken(); ?>">
                            <div id="imgAvatar" class="position-relative d-inline-block">
                                  <img src="<?php echo $avatarUrl; ?>" 
                                     alt="<?php echo escape($user['full_name']); ?>" 
                                     class="rounded-circle" 
                                     style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #0d6efd;">
                                <label for="avatarInput" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle" 
                                       style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                    <i class="bi bi-pencil text-white"></i>
                                </label>
                                <input type="file" id="avatarInput" name="avatar" class="d-none" accept="image/*">
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnChooseAvatar">
                                    <i class="bi bi-upload"></i> Chọn ảnh đại diện
                                </button>
                                <div class="form-text">JPG, PNG, GIF, WebP; tối đa 5MB.</div>
                            </div>
                        </form>
                    </div>

                    <!-- Tên người dùng -->
                    <h5 id="lblFullname" class="mb-1"><?php echo escape($user['full_name']); ?></h5>
                    <p class="text-muted small mb-3"><?php echo escape($user['email']); ?></p>

                    <!-- Thông tin đăng ký -->
                    <div class="bg-light p-3 rounded mb-3">
                        <small class="text-muted d-block">Thành viên từ</small>
                        <p class="mb-0"><strong><?php echo formatDate($user['created_at']); ?></strong></p>
                    </div>

                    <!-- Nút đăng xuất -->
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline-danger w-100">
                        <i class="bi bi-box-arrow-right"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content - Tabs -->
        <div class="col-lg-9">
            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $activeTab === 'info' ? 'active' : ''; ?>" 
                            id="tabinfo-tab" data-bs-toggle="tab" data-bs-target="#tabinfo" 
                            type="button" role="tab">
                        <i class="bi bi-person"></i> Thông tin cá nhân
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $activeTab === 'password' ? 'active' : ''; ?>" 
                            id="tabchangepass-tab" data-bs-toggle="tab" data-bs-target="#tabchangepass" 
                            type="button" role="tab">
                        <i class="bi bi-lock"></i> Đổi mật khẩu
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $activeTab === 'history' ? 'active' : ''; ?>" 
                            id="tabhistory-tab" data-bs-toggle="tab" data-bs-target="#tabhistory" 
                            type="button" role="tab">
                        <i class="bi bi-clock-history"></i> Lịch sử đơn hàng
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Tab 1: Thông tin cá nhân -->
                <div class="tab-pane fade <?php echo $activeTab === 'info' ? 'show active' : ''; ?>" 
                     id="tabinfo" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-person"></i> Thông tin cá nhân</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="formUserInfo">
                                <input type="hidden" name="csrf_token" value="<?php echo Session::getToken(); ?>">
                                <input type="hidden" name="action" value="update_info">

                                <!-- Username (Readonly) -->
                                <div class="mb-3">
                                    <label class="form-label"><strong>Tên đăng nhập</strong></label>
                                    <input type="text" class="form-control" id="txtUsername" 
                                           value="<?php echo escape($user['email']); ?>" 
                                           readonly>
                                    <small class="text-muted">Tên đăng nhập không thể thay đổi</small>
                                </div>

                                <!-- Fullname -->
                                <div class="mb-3">
                                    <label class="form-label"><strong>Tên đầy đủ</strong> <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="txtFullname" 
                                           name="full_name" 
                                           value="<?php echo escape($_POST['full_name'] ?? $user['full_name']); ?>" 
                                           required>
                                </div>

                                <!-- Phone -->
                                <div class="mb-3">
                                    <label class="form-label"><strong>Số điện thoại</strong></label>
                                    <input type="tel" class="form-control" id="txtPhone" 
                                           name="phone" 
                                           value="<?php echo escape($_POST['phone'] ?? $user['phone'] ?? ''); ?>"
                                           placeholder="Nhập số điện thoại">
                                </div>

                                <!-- Address -->
                                <div class="mb-3">
                                    <label class="form-label"><strong>Địa chỉ</strong></label>
                                    <textarea class="form-control" id="txtAddress" 
                                              name="address" 
                                              rows="3"
                                              placeholder="Nhập địa chỉ của bạn"><?php echo escape($_POST['address'] ?? $user['address'] ?? ''); ?></textarea>
                                </div>

                                <!-- Email -->
                                <div class="mb-3">
                                    <label class="form-label"><strong>Email</strong></label>
                                    <input type="email" class="form-control" 
                                           value="<?php echo escape($user['email']); ?>" 
                                           readonly>
                                    <small class="text-muted">Email không thể thay đổi</small>
                                </div>

                                <!-- Button Update -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="btnUpdate">
                                        <i class="bi bi-check-circle"></i> Cập nhật thông tin
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i> Hủy
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Đổi mật khẩu -->
                <div class="tab-pane fade <?php echo $activeTab === 'password' ? 'show active' : ''; ?>" 
                     id="tabchangepass" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-lock"></i> Đổi mật khẩu</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="formChangePassword">
                                <input type="hidden" name="csrf_token" value="<?php echo Session::getToken(); ?>">
                                <input type="hidden" name="action" value="change_password">

                                <!-- Mật khẩu hiện tại -->
                                <div class="mb-3">
                                    <label class="form-label"><strong>Mật khẩu hiện tại</strong> <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="current_password" 
                                           placeholder="Nhập mật khẩu hiện tại" required>
                                </div>

                                <!-- Mật khẩu mới -->
                                <div class="mb-3">
                                    <label class="form-label"><strong>Mật khẩu mới</strong> <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="new_password" 
                                           placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)" required>
                                    <small class="text-muted">Mật khẩu phải có ít nhất 6 ký tự</small>
                                </div>

                                <!-- Xác nhận mật khẩu -->
                                <div class="mb-3">
                                    <label class="form-label"><strong>Xác nhận mật khẩu mới</strong> <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="confirm_password" 
                                           placeholder="Nhập lại mật khẩu mới" required>
                                </div>

                                <!-- Alert -->
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle"></i> Mật khẩu mới phải khác mật khẩu cũ
                                </div>

                                <!-- Button -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Đổi mật khẩu
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i> Hủy
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Lịch sử đơn hàng -->
                <div class="tab-pane fade <?php echo $activeTab === 'history' ? 'show active' : ''; ?>" 
                     id="tabhistory" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Lịch sử đơn hàng</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recentOrders)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    <p>Bạn chưa có đơn hàng nào</p>
                                    <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-sm">
                                        <i class="bi bi-shop"></i> Bắt đầu mua sắm
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Mã đơn hàng</th>
                                                <th>Ngày đặt</th>
                                                <th>Tổng tiền</th>
                                                <th>Trạng thái</th>
                                                <th>Thanh toán</th>
                                                <th>Hành động</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo escape($order['order_number']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($order['created_at']); ?></td>
                                                <td class="fw-bold text-danger">
                                                    <?php echo formatPrice($order['total_amount']); ?>
                                                </td>
                                                <td>
                                                    <?php echo getOrderStatusBadge($order['status']); ?>
                                                </td>
                                                <td>
                                                    <?php echo getPaymentStatusBadge($order['payment_status']); ?>
                                                </td>
                                                <td>
                                                    <a href="<?php echo SITE_URL; ?>/account/order-detail.php?id=<?php echo (int)$order['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> Xem
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Link to full order history -->
                    <div class="mt-3 text-center">
                        <a href="<?php echo SITE_URL; ?>/account/orders.php" class="btn btn-outline-primary">
                            <i class="bi bi-list"></i> Xem tất cả đơn hàng
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý upload avatar
    const avatarInput = document.getElementById('avatarInput');
    const formAvatarUpload = document.getElementById('formAvatarUpload');
    const btnChooseAvatar = document.getElementById('btnChooseAvatar');

    if (btnChooseAvatar && avatarInput) {
        btnChooseAvatar.addEventListener('click', () => avatarInput.click());
    }

    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                
                // Kiểm tra loại file
                if (!allowedTypes.includes(file.type)) {
                    alert('Chỉ hỗ trợ ảnh JPG, PNG, GIF, WebP');
                    avatarInput.value = '';
                    return;
                }
                
                // Kiểm tra kích thước
                if (file.size > 5 * 1024 * 1024) {
                    alert('Kích thước ảnh không được vượt quá 5MB');
                    avatarInput.value = '';
                    return;
                }
                
                // Submit form upload avatar
                if (formAvatarUpload) {
                    formAvatarUpload.submit();
                }
            }
        });
    }

    // Validation form thông tin cá nhân
    const formUserInfo = document.getElementById('formUserInfo');
    if (formUserInfo) {
        formUserInfo.addEventListener('submit', function(e) {
            const fullname = document.getElementById('txtFullname').value.trim();
            if (!fullname) {
                e.preventDefault();
                alert('Vui lòng nhập tên đầy đủ');
                document.getElementById('txtFullname').focus();
                return false;
            }
        });
    }

    // Validation form đổi mật khẩu
    const formChangePassword = document.getElementById('formChangePassword');
    if (formChangePassword) {
        formChangePassword.addEventListener('submit', function(e) {
            const current = document.querySelector('input[name="current_password"]').value;
            const newPass = document.querySelector('input[name="new_password"]').value;
            const confirm = document.querySelector('input[name="confirm_password"]').value;

            if (!current || !newPass || !confirm) {
                e.preventDefault();
                alert('Vui lòng điền đầy đủ tất cả các trường');
                return false;
            }

            if (newPass !== confirm) {
                e.preventDefault();
                alert('Mật khẩu xác nhận không khớp');
                return false;
            }

            if (newPass.length < 6) {
                e.preventDefault();
                alert('Mật khẩu phải có ít nhất 6 ký tự');
                return false;
            }
        });
    }

    // Auto dismiss alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            setTimeout(() => bsAlert.close(), 5000);
        });
    }, 100);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
