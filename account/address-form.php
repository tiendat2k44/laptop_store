<?php
require_once __DIR__ . '/../../includes/init.php';
Auth::requireLogin();

$db = Database::getInstance();
require_once __DIR__ . '/../../includes/services/AddressService.php';
$service = new AddressService($db, Auth::id());

$id = (int)($_GET['id'] ?? 0);
$addr = null;
$errors = [];
$mode = 'add';

if ($id > 0) {
    $addr = $service->getAddress($id);
    if (!$addr) {
        Session::setFlash('error', 'Địa chỉ không tồn tại');
        redirect('/account/addresses.php');
    }
    $mode = 'edit';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token bảo mật không hợp lệ';
    } else {
        $data = [
            'recipient_name' => trim($_POST['recipient_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address_line' => trim($_POST['address_line'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'district' => trim($_POST['district'] ?? ''),
            'ward' => trim($_POST['ward'] ?? ''),
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
        ];

        // Validate
        if ($data['recipient_name'] === '') {
            $errors[] = 'Tên người nhận không được để trống';
        }
        if (!isValidPhone($data['phone'])) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }
        if ($data['address_line'] === '') {
            $errors[] = 'Địa chỉ không được để trống';
        }
        if ($data['city'] === '') {
            $errors[] = 'Tỉnh/thành phố không được để trống';
        }

        if (empty($errors)) {
            if ($mode === 'add') {
                $newId = $service->addAddress($data);
                if ($newId) {
                    Session::setFlash('success', 'Thêm địa chỉ thành công');
                } else {
                    $errors[] = 'Không thể thêm địa chỉ';
                }
            } else {
                if ($service->updateAddress($id, $data)) {
                    Session::setFlash('success', 'Cập nhật địa chỉ thành công');
                } else {
                    $errors[] = 'Không thể cập nhật địa chỉ';
                }
            }

            if (empty($errors)) {
                redirect('/account/addresses.php');
            }
        }
    }
}

// Prefill từ edit hoặc POST
if ($mode === 'edit' && $addr && empty($_POST)) {
    $data = $addr;
} else {
    $data = $_POST ?: [];
}

$pageTitle = ($mode === 'add' ? 'Thêm' : 'Sửa') . ' địa chỉ';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="mb-4"><i class="bi bi-geo-alt"></i> <?= $mode === 'add' ? 'Thêm' : 'Sửa' ?> địa chỉ</h3>

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
                            <label class="form-label">Tên người nhận *</label>
                            <input type="text" class="form-control" name="recipient_name" required value="<?= escape($data['recipient_name'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số điện thoại *</label>
                            <input type="tel" class="form-control" name="phone" required value="<?= escape($data['phone'] ?? '') ?>" placeholder="0901234567">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Địa chỉ *</label>
                            <input type="text" class="form-control" name="address_line" required value="<?= escape($data['address_line'] ?? '') ?>" placeholder="Số nhà, tên đường...">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phường/Xã</label>
                                <input type="text" class="form-control" name="ward" value="<?= escape($data['ward'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Quận/Huyện</label>
                                <input type="text" class="form-control" name="district" value="<?= escape($data['district'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tỉnh/Thành phố *</label>
                            <input type="text" class="form-control" name="city" required value="<?= escape($data['city'] ?? '') ?>">
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_default" name="is_default" <?= ($data['is_default'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_default">Đặt làm địa chỉ mặc định</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><?= $mode === 'add' ? 'Thêm' : 'Cập nhật' ?> địa chỉ</button>
                        <a href="/account/addresses.php" class="btn btn-outline-secondary w-100 mt-2">Quay lại</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
