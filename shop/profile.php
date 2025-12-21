<?php
require_once __DIR__ . '/../includes/init.php';
Auth::requireRole(ROLE_SHOP, '/login.php');

$db = Database::getInstance();
$shopId = Auth::getShopId();

if (!$shopId) {
    Session::setFlash('error', 'Cửa hàng không tồn tại');
    redirect(SITE_URL . '/shop/');
}

// Lấy thông tin shop
$shop = $db->queryOne(
    "SELECT id, shop_name, description, logo, banner, phone, email, address, status FROM shops WHERE id = :id",
    ['id' => $shopId]
);

if (!$shop) {
    Session::setFlash('error', 'Không tìm thấy cửa hàng');
    redirect(SITE_URL . '/shop/');
}

// Xử lý cập nhật cấu hình cửa hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'CSRF token không hợp lệ');
        redirect(SITE_URL . '/shop/profile.php');
    }

    $shopName = trim($_POST['shop_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $logo = trim($_POST['logo'] ?? '');
    $banner = trim($_POST['banner'] ?? '');

    if ($shopName === '') {
        Session::setFlash('error', 'Tên cửa hàng không được để trống');
        redirect(SITE_URL . '/shop/profile.php');
    }

    try {
        $db->execute(
            "UPDATE shops SET shop_name = :shop_name, description = :description, phone = :phone, email = :email,
             address = :address, logo = :logo, banner = :banner, updated_at = NOW() WHERE id = :id",
            [
                'shop_name' => $shopName,
                'description' => $description,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'logo' => $logo,
                'banner' => $banner,
                'id' => $shopId,
            ]
        );
        Session::setFlash('success', 'Cập nhật cửa hàng thành công');
        redirect(SITE_URL . '/shop/profile.php');
    } catch (Exception $e) {
        Session::setFlash('error', 'Lỗi: ' . $e->getMessage());
        redirect(SITE_URL . '/shop/profile.php');
    }
}

$pageTitle = 'Cài đặt cửa hàng';
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-gear"></i> Cài đặt cửa hàng</h2>
        <a href="<?php echo SITE_URL; ?>/shop/" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại bảng điều khiển
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">

                        <div class="mb-3">
                            <label class="form-label">Tên cửa hàng <span class="text-danger">*</span></label>
                            <input type="text" name="shop_name" class="form-control" value="<?= escape($shop['shop_name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="4"><?= escape($shop['description'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Số điện thoại</label>
                                    <input type="tel" name="phone" class="form-control" value="<?= escape($shop['phone'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= escape($shop['email'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <textarea name="address" class="form-control" rows="2"><?= escape($shop['address'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Logo URL</label>
                                    <input type="url" name="logo" class="form-control" value="<?= escape($shop['logo'] ?? '') ?>" placeholder="https://...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Banner URL</label>
                                    <input type="url" name="banner" class="form-control" value="<?= escape($shop['banner'] ?? '') ?>" placeholder="https://...">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="bi bi-check-lg"></i> Lưu thay đổi
                            </button>
                            <a href="<?php echo SITE_URL; ?>/shop/" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i> Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Xem trước</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 text-center">
                        <?php if (!empty($shop['logo'])): ?>
                            <img src="<?= escape($shop['logo']) ?>" alt="Logo" style="max-height:80px;">
                        <?php else: ?>
                            <div class="text-muted">Chưa có logo</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3 text-center">
                        <?php if (!empty($shop['banner'])): ?>
                            <img src="<?= escape($shop['banner']) ?>" alt="Banner" style="max-width:100%; max-height:120px; object-fit:cover;">
                        <?php else: ?>
                            <div class="text-muted">Chưa có banner</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
