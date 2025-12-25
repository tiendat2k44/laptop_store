<?php
/**
 * Admin - Sửa Sản Phẩm
 * Chỉnh sửa đầy đủ thông tin, ảnh đại diện và bộ sưu tập (tối đa 10 ảnh chi tiết)
 */

require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();
$productId = intval($_GET['id'] ?? 0);

if ($productId <= 0) {
    Session::setFlash('error', 'Sản phẩm không tồn tại');
    redirect(SITE_URL . '/admin/modules/products/');
}

// Lấy thông tin chi tiết sản phẩm
$product = $db->queryOne(
    "SELECT id, shop_id, category_id, brand_id, name, price, sale_price, stock_quantity, description, status,
            cpu, ram, storage, screen_size, graphics, weight, battery, os, featured, thumbnail
       FROM products WHERE id = :id",
    ['id' => $productId]
);

if (!$product) {
    Session::setFlash('error', 'Sản phẩm không tồn tại');
    redirect(SITE_URL . '/admin/modules/products/');
}

// Tham chiếu dropdown
$categories = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
$brands = $db->query("SELECT id, name FROM brands WHERE status = 'active' ORDER BY name");
$shops = $db->query("SELECT id, shop_name FROM shops WHERE status = 'active' ORDER BY shop_name");

// Ảnh hiện có
$images = $db->query(
    "SELECT id, image_url, display_order FROM product_images WHERE product_id = :pid ORDER BY display_order ASC, id ASC",
    ['pid' => $productId]
);
$thumbRecord = null;
$galleryExisting = [];
foreach ($images as $img) {
    if ((int)$img['display_order'] === 0 && $thumbRecord === null) {
        $thumbRecord = $img;
    } else {
        $galleryExisting[] = $img;
    }
}

// Xử lý form cập nhật sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'CSRF token không hợp lệ');
        redirect(SITE_URL . '/admin/modules/products/edit.php?id=' . $productId);
    }
    
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $salePrice = floatval($_POST['sale_price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $brandId = intval($_POST['brand_id'] ?? 0);
    $shopIdInput = intval($_POST['shop_id'] ?? 0);
    $cpu = trim($_POST['cpu'] ?? '');
    $ram = trim($_POST['ram'] ?? '');
    $storage = trim($_POST['storage'] ?? '');
    $screen = trim($_POST['screen_size'] ?? '');
    $graphics = trim($_POST['graphics'] ?? '');
    $weight = trim($_POST['weight'] ?? '');
    $battery = trim($_POST['battery'] ?? '');
    $os = trim($_POST['os'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $thumbnailUrl = trim($_POST['thumbnail_url'] ?? '');
    $galleryUrlInput = trim($_POST['gallery_urls'] ?? '');
    $removeIds = array_map('intval', $_POST['remove_images'] ?? []);

    // Helper upload ảnh đơn
    $uploadImage = function($file, &$error) {
        if (empty($file['name'])) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload ảnh lỗi';
            return null;
        }
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed)) {
            $error = 'Ảnh phải là JPG, PNG, GIF hoặc WebP';
            return null;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            $error = 'Ảnh không vượt quá 5MB';
            return null;
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $dir = __DIR__ . '/../../../assets/uploads/products/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'prd_' . time() . '_' . uniqid() . '.' . $ext;
        $dest = $dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return 'products/' . $filename;
        }
        $error = 'Không thể lưu ảnh';
        return null;
    };

    $errors = [];
    if (!$name || $price <= 0 || $stock < 0 || $categoryId <= 0 || $brandId <= 0 || $shopIdInput <= 0) {
        $errors[] = 'Vui lòng điền đầy đủ thông tin bắt buộc và chọn danh mục, thương hiệu, cửa hàng';
    }

    // Thumbnail: ưu tiên file, sau đó URL, nếu không chọn giữ nguyên
    $thumbnailPath = $product['thumbnail'];
    $thumbErrors = [];
    $thumbFileProvided = !empty($_FILES['thumbnail']['name'] ?? '');
    if ($thumbFileProvided) {
        $thumbnailPath = $uploadImage($_FILES['thumbnail'], $errThumb);
        if (!$thumbnailPath && $errThumb) {
            $thumbErrors[] = $errThumb;
        }
    }
    if (!$thumbFileProvided && $thumbnailUrl !== '') {
        $thumbnailPath = downloadImageFromUrl($thumbnailUrl, 'products', $errThumbUrl);
        if (!$thumbnailPath && $errThumbUrl) {
            $thumbErrors[] = $errThumbUrl;
        }
    }
    if (!$thumbnailPath) {
        $errors[] = $thumbErrors ? implode(' | ', $thumbErrors) : 'Vui lòng cung cấp ảnh đại diện (file hoặc URL)';
    }

    // Gallery (tối đa 10 ảnh chi tiết)
    $galleryPaths = [];
    $existingRemain = 0;
    foreach ($galleryExisting as $g) {
        if (!in_array((int)$g['id'], $removeIds, true)) {
            $existingRemain++;
        }
    }
    $maxGallery = 10;
    $allowedNew = max(0, $maxGallery - $existingRemain);

    if (isset($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
        $count = count($_FILES['gallery']['name']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($_FILES['gallery']['name'][$i])) {
                continue;
            }
            if (count($galleryPaths) >= $allowedNew) {
                $errors[] = 'Tối đa 10 ảnh chi tiết';
                break;
            }
            $file = [
                'name' => $_FILES['gallery']['name'][$i],
                'type' => $_FILES['gallery']['type'][$i],
                'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
                'error' => $_FILES['gallery']['error'][$i],
                'size' => $_FILES['gallery']['size'][$i],
            ];
            $path = $uploadImage($file, $errGallery);
            if ($path) {
                $galleryPaths[] = $path;
            } elseif ($errGallery) {
                $errors[] = $errGallery;
                break;
            }
        }
    }

    if ($galleryUrlInput !== '' && empty(array_filter($errors))) {
        $urlList = preg_split('/\r\n|\r|\n/', $galleryUrlInput);
        foreach ($urlList as $urlLine) {
            $urlLine = trim($urlLine);
            if ($urlLine === '') {
                continue;
            }
            if (count($galleryPaths) >= $allowedNew) {
                $errors[] = 'Tối đa 10 ảnh chi tiết';
                break;
            }
            $imgPath = downloadImageFromUrl($urlLine, 'products', $errGalleryUrl);
            if ($imgPath) {
                $galleryPaths[] = $imgPath;
            } elseif ($errGalleryUrl) {
                $errors[] = $errGalleryUrl;
                break;
            }
        }
    }

    if ($existingRemain + count($galleryPaths) > $maxGallery) {
        $errors[] = 'Tối đa 10 ảnh chi tiết';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $db->execute(
                "UPDATE products
                 SET name = :name, price = :price, sale_price = :salePrice, stock_quantity = :stock,
                     description = :desc, status = :status, category_id = :cid, brand_id = :bid, shop_id = :sid,
                     cpu = :cpu, ram = :ram, storage = :storage, screen_size = :screen, graphics = :graphics,
                     weight = :weight, battery = :battery, os = :os, featured = :featured, thumbnail = :thumb,
                     updated_at = NOW()
                 WHERE id = :id",
                [
                    'name' => $name,
                    'price' => $price,
                    'salePrice' => $salePrice > 0 ? $salePrice : null,
                    'stock' => $stock,
                    'desc' => $description,
                    'status' => $status,
                    'cid' => $categoryId,
                    'bid' => $brandId,
                    'sid' => $shopIdInput,
                    'cpu' => $cpu,
                    'ram' => $ram,
                    'storage' => $storage,
                    'screen' => $screen,
                    'graphics' => $graphics,
                    'weight' => $weight,
                    'battery' => $battery,
                    'os' => $os,
                    'featured' => $featured,
                    'thumb' => $thumbnailPath,
                    'id' => $productId
                ]
            );

            // Cập nhật thumbnail vào product_images
            $thumbRecord = $db->queryOne(
                "SELECT id FROM product_images WHERE product_id = :pid AND display_order = 0",
                ['pid' => $productId]
            );
            if ($thumbRecord) {
                $db->execute(
                    "UPDATE product_images SET image_url = :img WHERE id = :id",
                    ['img' => $thumbnailPath, 'id' => $thumbRecord['id']]
                );
            } else {
                $db->insert(
                    "INSERT INTO product_images (product_id, image_url, display_order) VALUES (:pid, :img, 0)",
                    ['pid' => $productId, 'img' => $thumbnailPath]
                );
            }

            // Xóa ảnh chi tiết được chọn
            foreach ($removeIds as $rid) {
                $db->execute(
                    "DELETE FROM product_images WHERE product_id = :pid AND id = :id AND display_order > 0",
                    ['pid' => $productId, 'id' => $rid]
                );
            }

            // Sắp xếp lại thứ tự ảnh chi tiết còn lại
            $galleryRemain = $db->query(
                "SELECT id FROM product_images WHERE product_id = :pid AND display_order > 0 ORDER BY display_order ASC, id ASC",
                ['pid' => $productId]
            );
            $order = 1;
            foreach ($galleryRemain as $g) {
                $db->execute(
                    "UPDATE product_images SET display_order = :ord WHERE id = :id",
                    ['ord' => $order, 'id' => $g['id']]
                );
                $order++;
            }

            // Thêm ảnh chi tiết mới
            foreach ($galleryPaths as $imgPath) {
                $db->insert(
                    "INSERT INTO product_images (product_id, image_url, display_order) VALUES (:pid, :img, :ord)",
                    ['pid' => $productId, 'img' => $imgPath, 'ord' => $order]
                );
                $order++;
            }

            $db->commit();
            Session::setFlash('success', 'Cập nhật sản phẩm thành công');
            redirect(SITE_URL . '/admin/modules/products/');
        } catch (Exception $e) {
            $db->rollback();
            Session::setFlash('error', 'Lỗi: ' . $e->getMessage());
        }
    } else {
        Session::setFlash('error', implode(' | ', $errors));
    }
}

$pageTitle = 'Sửa sản phẩm: ' . escape($product['name']);
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil"></i> Sửa sản phẩm</h2>
    <a href="<?php echo SITE_URL; ?>/admin/modules/products/" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Quay lại
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= escape($product['name']) ?>" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cửa hàng *</label>
                            <select name="shop_id" class="form-select" required>
                                <option value="">-- Chọn cửa hàng --</option>
                                <?php foreach ($shops as $shop): ?>
                                    <option value="<?= (int)$shop['id'] ?>" <?= (int)$product['shop_id'] === (int)$shop['id'] ? 'selected' : '' ?>><?= escape($shop['shop_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Danh mục *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>" <?= (int)$product['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= escape($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Thương hiệu *</label>
                            <select name="brand_id" class="form-select" required>
                                <option value="">-- Chọn thương hiệu --</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= (int)$brand['id'] ?>" <?= (int)$product['brand_id'] === (int)$brand['id'] ? 'selected' : '' ?>><?= escape($brand['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="featured" id="featuredEdit" <?= $product['featured'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="featuredEdit">Đánh dấu nổi bật</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Giá gốc <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= (float)$product['price'] ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Giá bán</label>
                                <input type="number" name="sale_price" class="form-control" step="0.01" min="0" value="<?= (float)$product['sale_price'] ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tồn kho <span class="text-danger">*</span></label>
                                <input type="number" name="stock" class="form-control" min="0" value="<?= (int)$product['stock_quantity'] ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">CPU</label>
                            <input type="text" name="cpu" class="form-control" value="<?= escape($product['cpu']) ?>" placeholder="VD: Intel Core i7-1360P">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RAM</label>
                            <input type="text" name="ram" class="form-control" value="<?= escape($product['ram']) ?>" placeholder="VD: 16GB DDR5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ổ cứng</label>
                            <input type="text" name="storage" class="form-control" value="<?= escape($product['storage']) ?>" placeholder="VD: 512GB SSD NVMe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Màn hình</label>
                            <input type="text" name="screen_size" class="form-control" value="<?= escape($product['screen_size']) ?>" placeholder="VD: 14\" FHD 60Hz">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Card đồ họa</label>
                            <input type="text" name="graphics" class="form-control" value="<?= escape($product['graphics']) ?>" placeholder="VD: RTX 4060 8GB">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Trọng lượng</label>
                            <input type="text" name="weight" class="form-control" value="<?= escape($product['weight']) ?>" placeholder="VD: 1.2kg">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pin</label>
                            <input type="text" name="battery" class="form-control" value="<?= escape($product['battery']) ?>" placeholder="VD: 70Wh">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hệ điều hành</label>
                            <input type="text" name="os" class="form-control" value="<?= escape($product['os']) ?>" placeholder="VD: Windows 11">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="4"><?= escape($product['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                            <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Tạm ẩn</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Ảnh đại diện</label>
                        <div class="mb-2">
                            <img src="<?= image_url($product['thumbnail']) ?>" alt="Thumbnail" class="img-thumbnail" style="max-width:160px;">
                        </div>
                        <input type="file" name="thumbnail" class="form-control" accept="image/*">
                        <div class="form-text">Ảnh JPG/PNG/GIF/WebP, tối đa 5MB</div>
                        <input type="url" name="thumbnail_url" class="form-control mt-2" placeholder="Hoặc dán URL ảnh (https://...)">
                        <div class="form-text">Nếu chọn file và nhập URL, hệ thống ưu tiên file tải lên. Nếu bỏ trống sẽ giữ ảnh hiện tại.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Ảnh chi tiết (tối đa 10)</label>
                        <?php if ($galleryExisting): ?>
                            <div class="row g-3 mb-2">
                                <?php foreach ($galleryExisting as $g): ?>
                                    <div class="col-6 col-md-4 col-lg-3 text-center">
                                        <div class="border rounded p-2 h-100">
                                            <img src="<?= image_url($g['image_url']) ?>" alt="Gallery" class="img-fluid mb-2" style="max-height:120px; object-fit:contain;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remove_images[]" value="<?= (int)$g['id'] ?>" id="rm<?= (int)$g['id'] ?>">
                                                <label class="form-check-label" for="rm<?= (int)$g['id'] ?>">Xóa ảnh</label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-muted mb-2">Chưa có ảnh chi tiết.</div>
                        <?php endif; ?>

                        <input type="file" name="gallery[]" class="form-control" accept="image/*" multiple>
                        <div class="form-text">Có thể chọn nhiều ảnh, tối đa 10 ảnh chi tiết sau khi lưu.</div>
                        <textarea name="gallery_urls" class="form-control mt-2" rows="3" placeholder="Mỗi dòng một URL ảnh (https://...)"></textarea>
                        <div class="form-text">Hệ thống sẽ tải ảnh về và lưu cùng thư mục uploads.</div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-check-lg"></i> Lưu thay đổi
                        </button>
                        <a href="<?php echo SITE_URL; ?>/admin/modules/products/" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
