<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo Session::getToken(); ?>">
    
    <?php
    // Initialize Database if not already done
    if (!isset($db)) {
        $db = Database::getInstance();
    }
    
    // SEO Meta Tags
    $seoTitle = $pageTitle ?? '';
    $seoDescription = $pageDescription ?? '';
    $seoImage = $pageImage ?? '';
    $seoUrl = $pageUrl ?? '';
    seo_meta_tags($seoTitle, $seoDescription, $seoImage, $seoUrl);
    ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <?php if (Auth::check()): ?>
    <script>
        window.userLoggedIn = true;
    </script>
    <?php endif; ?>
    
    <?php if (isset($additionalCSS)): ?>
        <?php echo $additionalCSS; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar bg-dark text-white py-2">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small>
                        <i class="bi bi-envelope"></i> <?php echo SITE_EMAIL; ?>
                        <span class="ms-3"><i class="bi bi-telephone"></i> 1900-xxxx</span>
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <?php 
                    $currentUser = null;
                    if (Auth::check()) {
                        $currentUser = Auth::user();
                    }
                    if ($currentUser): ?>
                        <span>Xin chào, <strong><?php echo escape($currentUser['full_name'] ?? 'Người dùng'); ?></strong></span>
                        <a href="<?php echo SITE_URL; ?>/account/profile.php" class="text-white ms-2"><i class="bi bi-person-circle"></i> Tài khoản</a>
                        <a href="<?php echo SITE_URL; ?>/logout.php" class="text-white ms-2"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="text-white"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập</a>
                        <a href="<?php echo SITE_URL; ?>/register.php" class="text-white ms-2"><i class="bi bi-person-plus"></i> Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="<?php echo SITE_URL; ?>">
                <i class="bi bi-laptop"></i> <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/products.php">Sản phẩm</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoryDropdown" role="button" data-bs-toggle="dropdown">
                            Danh mục
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="categoryDropdown">
                            <?php
                            $db = Database::getInstance();
                            $categories = $db->query("SELECT * FROM categories WHERE parent_id IS NULL AND status = 'active' ORDER BY display_order, name");
                            foreach ($categories as $category):
                            ?>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['id']; ?>"><?php echo escape($category['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                </ul>
                
                <!-- Search Form -->
                <form class="d-flex me-3 position-relative" method="GET" action="<?php echo SITE_URL; ?>/products.php" style="min-width:340px;">
                    <div class="input-group w-100">
                        <input id="searchInput" class="form-control" type="search" name="keyword" placeholder="Tìm kiếm laptop..." value="<?php 
                            $kw = $_GET['keyword'] ?? ($_GET['search'] ?? '');
                            echo escape($kw);
                        ?>">
                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    <div id="searchSuggestions" class="w-100" style="display:none;"></div>
                </form>
                
                <!-- Cart & Wishlist -->
                <div class="d-flex">
                    <?php if (Auth::check()): ?>
                        <a href="<?php echo SITE_URL; ?>/wishlist.php" class="btn btn-outline-secondary position-relative me-2">
                            <i class="bi bi-heart"></i>
                            <?php
                            $wishlistCount = $db->queryOne("SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id", ['user_id' => Auth::id()]);
                            if ($wishlistCount && $wishlistCount['count'] > 0):
                            ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $wishlistCount['count']; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo SITE_URL; ?>/cart.php" class="btn btn-primary position-relative">
                        <i class="bi bi-cart3"></i> Giỏ hàng
                        <?php
                        $cartCount = 0;
                        if (Auth::check()) {
                            $cartResult = $db->queryOne("SELECT COUNT(*) as count FROM cart_items WHERE user_id = :user_id", ['user_id' => Auth::id()]);
                            $cartCount = $cartResult ? $cartResult['count'] : 0;
                        }
                        if ($cartCount > 0):
                        ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cartCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (Session::hasFlash('success')): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo escape(Session::getFlash('success')); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (Session::hasFlash('error')): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo escape(Session::getFlash('error')); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (Session::hasFlash('info')): ?>
        <div class="container mt-3">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo escape(Session::getFlash('info')); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="py-4">
