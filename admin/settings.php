<?php
require_once __DIR__ . '/../includes/init.php';

// Kiểm tra quyền admin
if (!Auth::check() || !Auth::isAdmin()) {
    Session::setFlash('error', 'Bạn không có quyền truy cập trang này');
    redirect('/login.php');
}

$db = Database::getInstance();
$errors = [];
$success = false;

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token bảo mật không hợp lệ';
    } else {
        // Lấy dữ liệu từ form
        $siteName = trim($_POST['site_name'] ?? '');
        $siteEmail = trim($_POST['site_email'] ?? '');
        $itemsPerPage = intval($_POST['items_per_page'] ?? 12);
        $enableRegistration = isset($_POST['enable_registration']) ? 1 : 0;
        $enableShopRegistration = isset($_POST['enable_shop_registration']) ? 1 : 0;
        $maintenanceMode = isset($_POST['maintenance_mode']) ? 1 : 0;
        
        // Validation
        if (empty($siteName)) {
            $errors[] = 'Tên website không được để trống';
        }
        
        if (empty($siteEmail)) {
            $errors[] = 'Email website không được để trống';
        } elseif (!isValidEmail($siteEmail)) {
            $errors[] = 'Email không hợp lệ';
        }
        
        if ($itemsPerPage < 1 || $itemsPerPage > 100) {
            $errors[] = 'Số sản phẩm mỗi trang phải từ 1-100';
        }
        
        if (empty($errors)) {
            // Cập nhật hoặc thêm mới settings
            $settings = [
                ['key' => 'site_name', 'value' => $siteName],
                ['key' => 'site_email', 'value' => $siteEmail],
                ['key' => 'items_per_page', 'value' => $itemsPerPage],
                ['key' => 'enable_registration', 'value' => $enableRegistration],
                ['key' => 'enable_shop_registration', 'value' => $enableShopRegistration],
                ['key' => 'maintenance_mode', 'value' => $maintenanceMode],
            ];
            
            try {
                $db->beginTransaction();
                
                foreach ($settings as $setting) {
                    // Kiểm tra xem setting đã tồn tại chưa
                    $existing = $db->queryOne(
                        "SELECT id FROM settings WHERE setting_key = :key",
                        ['key' => $setting['key']]
                    );
                    
                    if ($existing) {
                        // Update
                        $db->execute(
                            "UPDATE settings SET setting_value = :value WHERE setting_key = :key",
                            ['value' => $setting['value'], 'key' => $setting['key']]
                        );
                    } else {
                        // Insert
                        $db->execute(
                            "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)",
                            ['key' => $setting['key'], 'value' => $setting['value']]
                        );
                    }
                }
                
                $db->commit();
                $success = true;
                Session::setFlash('success', 'Cập nhật cài đặt thành công');
            } catch (Exception $e) {
                $db->rollback();
                $errors[] = 'Có lỗi xảy ra: ' . $e->getMessage();
            }
        }
    }
}

// Lấy settings hiện tại
$currentSettings = [];
$settingsData = $db->query("SELECT setting_key, setting_value FROM settings");
foreach ($settingsData as $row) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$siteName = $currentSettings['site_name'] ?? SITE_NAME;
$siteEmail = $currentSettings['site_email'] ?? SITE_EMAIL;
$itemsPerPage = $currentSettings['items_per_page'] ?? ITEMS_PER_PAGE;
$enableRegistration = isset($currentSettings['enable_registration']) ? (int)$currentSettings['enable_registration'] : 1;
$enableShopRegistration = isset($currentSettings['enable_shop_registration']) ? (int)$currentSettings['enable_shop_registration'] : 1;
$maintenanceMode = isset($currentSettings['maintenance_mode']) ? (int)$currentSettings['maintenance_mode'] : 0;

$pageTitle = 'Cài đặt hệ thống';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">
            <i class="bi bi-gear"></i> Cài đặt hệ thống
        </h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Có lỗi xảy ra:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?= escape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-sliders"></i> Cấu hình chung</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Tên website <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="site_name" 
                                   value="<?= escape($siteName) ?>" required>
                            <small class="text-muted">Tên hiển thị trên website</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email website <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="site_email" 
                                   value="<?= escape($siteEmail) ?>" required>
                            <small class="text-muted">Email nhận thông báo từ hệ thống</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số sản phẩm mỗi trang <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="items_per_page" 
                                   value="<?= $itemsPerPage ?>" min="1" max="100" required>
                            <small class="text-muted">Số sản phẩm hiển thị trên mỗi trang (1-100)</small>
                        </div>

                        <hr>

                        <h5 class="mb-3">Chức năng</h5>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enable_registration" 
                                       id="enableRegistration" <?= $enableRegistration ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enableRegistration">
                                    Cho phép đăng ký tài khoản
                                </label>
                            </div>
                            <small class="text-muted">Bật/tắt chức năng đăng ký tài khoản khách hàng</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enable_shop_registration" 
                                       id="enableShopRegistration" <?= $enableShopRegistration ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enableShopRegistration">
                                    Cho phép đăng ký cửa hàng
                                </label>
                            </div>
                            <small class="text-muted">Bật/tắt chức năng đăng ký tài khoản bán hàng</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                                       id="maintenanceMode" <?= $maintenanceMode ? 'checked' : '' ?>>
                                <label class="form-check-label" for="maintenanceMode">
                                    <span class="badge bg-warning text-dark">Chế độ bảo trì</span>
                                </label>
                            </div>
                            <small class="text-muted text-danger">
                                Khi bật, website sẽ không thể truy cập (chỉ admin có thể truy cập)
                            </small>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Lưu cài đặt
                            </button>
                            <a href="/admin/" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Quay lại
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Thông tin hệ thống</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td><strong>PHP Version:</strong></td>
                                <td><?= phpversion() ?></td>
                            </tr>
                            <tr>
                                <td><strong>Database:</strong></td>
                                <td><?= $db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Server:</strong></td>
                                <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                            </tr>
                            <tr>
                                <td><strong>Memory Limit:</strong></td>
                                <td><?= ini_get('memory_limit') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Upload Max:</strong></td>
                                <td><?= ini_get('upload_max_filesize') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Lưu ý</h5>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li>Cài đặt này ảnh hưởng đến toàn bộ website</li>
                        <li>Chế độ bảo trì sẽ chặn tất cả truy cập (trừ admin)</li>
                        <li>Thay đổi cài đặt có thể ảnh hưởng đến hiệu suất</li>
                        <li>Nên backup database trước khi thay đổi quan trọng</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
