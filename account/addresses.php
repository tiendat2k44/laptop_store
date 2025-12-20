<?php
require_once __DIR__ . '/../../includes/init.php';
Auth::requireLogin();

$db = Database::getInstance();
require_once __DIR__ . '/../../includes/services/AddressService.php';
$service = new AddressService($db, Auth::id());

$action = trim($_GET['action'] ?? '');
$id = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id > 0) {
    if (!Session::verifyToken($_GET['csrf_token'] ?? '')) {
        Session::setFlash('error', 'Token không hợp lệ');
    } else {
        if ($service->deleteAddress($id)) {
            Session::setFlash('success', 'Xóa địa chỉ thành công');
        } else {
            Session::setFlash('error', 'Không thể xóa địa chỉ');
        }
    }
    redirect('/account/addresses.php');
}

$addresses = $service->getAddresses();
$pageTitle = 'Sổ địa chỉ';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="list-group">
                <a href="/account/profile.php" class="list-group-item list-group-item-action"><i class="bi bi-person"></i> Hồ sơ</a>
                <a href="/account/addresses.php" class="list-group-item list-group-item-action active"><i class="bi bi-map"></i> Sổ địa chỉ</a>
                <a href="/account/orders.php" class="list-group-item list-group-item-action"><i class="bi bi-receipt"></i> Đơn hàng</a>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-map"></i> Sổ địa chỉ</h2>
                <a href="/account/address-form.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Thêm địa chỉ</a>
            </div>

            <?php if (Session::hasFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> <?= escape(Session::getFlash('success')) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if (Session::hasFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle"></i> <?= escape(Session::getFlash('error')) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="row g-3">
                <?php if (empty($addresses)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-inbox" style="font-size:3rem;color:#ccc"></i>
                        <p class="text-muted mt-2">Chưa có địa chỉ nào. <a href="/account/address-form.php">Thêm ngay</a></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($addresses as $addr): ?>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <?php if ($addr['is_default']): ?>
                                    <span class="badge bg-success mb-2">Mặc định</span>
                                <?php endif; ?>
                                <h6 class="card-title"><?= escape($addr['recipient_name']) ?> — <?= escape($addr['phone']) ?></h6>
                                <p class="card-text small text-muted">
                                    <?= escape($addr['address_line']) ?><br>
                                    <?php if (!empty($addr['ward'])) echo escape($addr['ward']) . ', '; ?>
                                    <?php if (!empty($addr['district'])) echo escape($addr['district']) . ', '; ?>
                                    <?= escape($addr['city']) ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white border-top">
                                <a href="/account/address-form.php?id=<?= (int)$addr['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Sửa</a>
                                <a href="?action=delete&id=<?= (int)$addr['id'] ?>&csrf_token=<?= Session::getToken() ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa địa chỉ này?')"><i class="bi bi-trash"></i> Xóa</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
