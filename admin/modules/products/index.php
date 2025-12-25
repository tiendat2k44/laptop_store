<?php
/**
 * Admin - Quản lý Sản Phẩm
 * Danh sách, thêm, sửa, xóa sản phẩm
 */

require_once __DIR__ . '/../../../includes/init.php';
Auth::requireRole(ROLE_ADMIN, '/login.php');

$db = Database::getInstance();

$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Danh sách tham chiếu để hiển thị/nhập liệu
$categories = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
$brands = $db->query("SELECT id, name FROM brands WHERE status = 'active' ORDER BY name");
$shops = $db->query("SELECT id, shop_name FROM shops WHERE status = 'active' ORDER BY shop_name");

// Xử lý các hành động POST (thêm/xóa sản phẩm)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf_token']) ? trim($_POST['csrf_token']) : '';
    if (!Session::verifyToken($csrf)) {
        Session::setFlash('error', 'Token bảo mật không hợp lệ');
        redirect(SITE_URL . '/admin/modules/products/');
    }
    
    if (isset($_POST['add_product'])) {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $sale_price = isset($_POST['sale_price']) ? floatval($_POST['sale_price']) : 0;
        $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
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
        $statusProduct = trim($_POST['status'] ?? 'active');
        $thumbnailUrl = trim($_POST['thumbnail_url'] ?? '');
        $galleryUrlInput = trim($_POST['gallery_urls'] ?? '');
        
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

        // Upload thumbnail (chọn file hoặc URL)
        $thumbnailPath = null;
        $thumbErrors = [];
        $thumbFileProvided = !empty($_FILES['thumbnail']['name'] ?? '');

        if ($thumbFileProvided) {
            $thumbnailPath = $uploadImage($_FILES['thumbnail'], $errThumb);
            if (!$thumbnailPath && $errThumb) {
                $thumbErrors[] = $errThumb;
            }
        }

        if (!$thumbnailPath && $thumbnailUrl !== '') {
            $thumbnailPath = downloadImageFromUrl($thumbnailUrl, 'products', $errThumbUrl);
            if (!$thumbnailPath && $errThumbUrl) {
                $thumbErrors[] = $errThumbUrl;
            }
        }

        if (!$thumbnailPath) {
            $errors[] = $thumbErrors ? implode(' | ', $thumbErrors) : 'Vui lòng chọn ảnh đại diện sản phẩm hoặc nhập URL ảnh';
        }

        // Upload gallery (tùy chọn)
        $galleryPaths = [];
        if (isset($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
            $count = count($_FILES['gallery']['name']);
            for ($i = 0; $i < $count; $i++) {
                if (empty($_FILES['gallery']['name'][$i])) {
                    continue;
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

        if ($galleryUrlInput !== '') {
            $urlList = preg_split('/\r\n|\r|\n/', $galleryUrlInput);
            foreach ($urlList as $urlLine) {
                $urlLine = trim($urlLine);
                if ($urlLine === '') {
                    continue;
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

        if (empty($errors)) {
            try {
                $db->beginTransaction();
                $slugBase = generateSlug($name);
                $slug = $slugBase;
                $counter = 1;
                while ($db->queryOne("SELECT id FROM products WHERE slug = :slug", ['slug' => $slug])) {
                    $slug = $slugBase . '-' . $counter;
                    $counter++;
                }

                $productId = $db->insert(
                    "INSERT INTO products (shop_id, category_id, brand_id, name, slug, description, cpu, ram, storage, screen_size, graphics, weight, battery, os, price, sale_price, stock_quantity, status, featured, thumbnail, created_at, updated_at) 
                     VALUES (:shop_id, :category_id, :brand_id, :name, :slug, :desc, :cpu, :ram, :storage, :screen, :graphics, :weight, :battery, :os, :price, :sale, :stock, :status, :featured, :thumb, NOW(), NOW())",
                    [
                        'shop_id' => $shopIdInput,
                        'category_id' => $categoryId,
                        'brand_id' => $brandId,
                        'name' => $name,
                        'slug' => $slug,
                        'desc' => $description,
                        'cpu' => $cpu,
                        'ram' => $ram,
                        'storage' => $storage,
                        'screen' => $screen,
                        'graphics' => $graphics,
                        'weight' => $weight,
                        'battery' => $battery,
                        'os' => $os,
                        'price' => $price,
                        'sale' => $sale_price > 0 ? $sale_price : null,
                        'stock' => $stock,
                        'status' => $statusProduct,
                        'featured' => $featured,
                        'thumb' => $thumbnailPath,
                    ]
                );

                if (!$productId) {
                    throw new Exception('Không thể tạo sản phẩm');
                }

                // Lưu ảnh vào product_images (thumbnail là ảnh đầu tiên)
                $db->insert(
                    "INSERT INTO product_images (product_id, image_url, display_order) VALUES (:pid, :img, 0)",
                    ['pid' => $productId, 'img' => $thumbnailPath]
                );
                $order = 1;
                foreach ($galleryPaths as $imgPath) {
                    $db->insert(
                        "INSERT INTO product_images (product_id, image_url, display_order) VALUES (:pid, :img, :ord)",
                        ['pid' => $productId, 'img' => $imgPath, 'ord' => $order]
                    );
                    $order++;
                }

                $db->commit();
                Session::setFlash('success', 'Sản phẩm được thêm thành công');
            } catch (Exception $e) {
                $db->rollback();
                Session::setFlash('error', 'Lỗi: ' . $e->getMessage());
            }
        } else {
            Session::setFlash('error', implode(' | ', $errors));
        }
        redirect(SITE_URL . '/admin/modules/products/');
    }
    
    if (isset($_POST['delete_product'])) {
        $productId = intval($_POST['product_id'] ?? 0);
        if ($productId > 0) {
            try {
                $db->execute("DELETE FROM products WHERE id = :id", ['id' => $productId]);
                Session::setFlash('success', 'Xóa sản phẩm thành công');
            } catch (Exception $e) {
                Session::setFlash('error', 'Lỗi: ' . $e->getMessage());
            }
        }
        redirect(SITE_URL . '/admin/modules/products/');
    }
}

// Lấy danh sách sản phẩm
$where = "1=1";
$params = [];
if ($status) {
    $where .= " AND status = :status";
    $params['status'] = $status;
}
if ($keyword) {
    $where .= " AND name ILIKE :keyword";
    $params['keyword'] = '%' . $keyword . '%';
}

$products = $db->query(
    "SELECT id, name, price, sale_price, stock_quantity, status, created_at 
     FROM products 
     WHERE $where 
     ORDER BY created_at DESC 
     LIMIT 100",
    $params
);

$pageTitle = 'Quản lý sản phẩm';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box"></i> Sản phẩm</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="bi bi-plus-circle"></i> Thêm sản phẩm
    </button>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-6">
                <input type="text" name="keyword" class="form-control" placeholder="Tìm sản phẩm..." value="<?= escape($keyword) ?>">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Tạm ẩn</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">Tìm kiếm</button>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Tên sản phẩm</th>
                <th>Giá</th>
                <th>Giá bán</th>
                <th>Tồn kho</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($products): ?>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td>#<?= (int)$product['id'] ?></td>
                    <td><?= escape($product['name']) ?></td>
                    <td><?= formatPrice($product['price']) ?></td>
                    <td><?= formatPrice($product['sale_price']) ?></td>
                    <td>
                        <span class="badge bg-<?= $product['stock_quantity'] > 0 ? 'success' : 'danger' ?>">
                            <?= (int)$product['stock_quantity'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'secondary' ?>">
                            <?= $product['status'] === 'active' ? 'Hoạt động' : 'Tạm ẩn' ?>
                        </span>
                    </td>
                    <td><small><?= formatDate($product['created_at']) ?></small></td>
                    <td>
                        <a href="<?php echo SITE_URL; ?>/admin/modules/products/edit.php?id=<?= (int)$product['id'] ?>" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-pencil"></i> Sửa
                        </a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn chắc chắn muốn xóa?');">
                            <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                            <button type="submit" name="delete_product" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-inbox"></i> Không có sản phẩm nào
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm sản phẩm mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= Session::getToken() ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tên sản phẩm *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cửa hàng *</label>
                            <select name="shop_id" class="form-select" required>
                                <option value="">-- Chọn cửa hàng --</option>
                                <?php foreach ($shops as $shop): ?>
                                    <option value="<?= (int)$shop['id'] ?>"><?= escape($shop['shop_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Danh mục *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>"><?= escape($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Thương hiệu *</label>
                            <select name="brand_id" class="form-select" required>
                                <option value="">-- Chọn thương hiệu --</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= (int)$brand['id'] ?>"><?= escape($brand['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Giá gốc *</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Giá bán</label>
                            <input type="number" name="sale_price" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tồn kho *</label>
                            <input type="number" name="stock" class="form-control" min="0" value="0" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CPU</label>
                            <input type="text" name="cpu" class="form-control" placeholder="VD: Intel Core i7-1360P">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RAM</label>
                            <input type="text" name="ram" class="form-control" placeholder="VD: 16GB DDR5">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ổ cứng</label>
                            <input type="text" name="storage" class="form-control" placeholder="VD: 512GB SSD NVMe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Màn hình</label>
                            <input type="text" name="screen_size" class="form-control" placeholder="VD: 14\" FHD 60Hz">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Card đồ họa</label>
                            <input type="text" name="graphics" class="form-control" placeholder="VD: RTX 4060 8GB">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Trọng lượng</label>
                            <input type="text" name="weight" class="form-control" placeholder="VD: 1.2kg">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pin</label>
                            <input type="text" name="battery" class="form-control" placeholder="VD: 70Wh">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hệ điều hành</label>
                            <input type="text" name="os" class="form-control" placeholder="VD: Windows 11">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ảnh đại diện (bắt buộc)</label>
                            <input type="file" name="thumbnail" class="form-control" accept="image/*">
                            <div class="form-text">Ảnh JPG/PNG/GIF/WebP, tối đa 5MB</div>
                            <input type="url" name="thumbnail_url" class="form-control mt-2" placeholder="Hoặc dán URL ảnh (https://...)">
                            <div class="form-text">Nếu chọn file và nhập URL, hệ thống ưu tiên file tải lên.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ảnh bổ sung (tùy chọn)</label>
                            <input type="file" name="gallery[]" class="form-control" accept="image/*" multiple>
                            <div class="form-text">Có thể chọn nhiều ảnh</div>
                            <textarea name="gallery_urls" class="form-control mt-2" rows="3" placeholder="Mỗi dòng một URL ảnh (https://...)"></textarea>
                            <div class="form-text">Hệ thống sẽ tải ảnh về và lưu cùng thư mục uploads.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="active">Hoạt động</option>
                                <option value="inactive">Tạm ẩn</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="featured" id="featuredAdd">
                                <label class="form-check-label" for="featuredAdd">Đánh dấu nổi bật</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" name="add_product" class="btn btn-primary">Thêm sản phẩm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
