<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? escape($pageTitle) . ' - ' : ''; ?>Admin - <?php echo SITE_NAME; ?></title>
    <meta name="csrf-token" content="<?php echo Session::getToken(); ?>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 56px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            width: 250px;
            background: #212529;
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 56px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, .75);
            padding: .75rem 1rem;
            transition: all .3s;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, .1);
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background: #0d6efd;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: .5rem;
        }
        
        main {
            margin-left: 250px;
            padding-top: 56px;
        }
        
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        
        .stat-card {
            border-left: 4px solid;
            transition: transform .3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.primary {
            border-color: #0d6efd;
        }
        
        .stat-card.success {
            border-color: #198754;
        }
        
        .stat-card.warning {
            border-color: #ffc107;
        }
        
        .stat-card.info {
            border-color: #0dcaf0;
        }
    </style>
    
    <?php if (isset($additionalCSS)): ?>
        <?php echo $additionalCSS; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/admin/">
                <i class="bi bi-speedometer2"></i> Admin Panel - <?php echo SITE_NAME; ?>
            </a>
            
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle"></i> <?php echo escape(Auth::user()['full_name']); ?>
                </span>
                <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-light btn-sm me-2" target="_blank">
                    <i class="bi bi-globe"></i> Xem trang chủ
                </a>
                <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/modules/orders/index.php">
                        <i class="bi bi-cart-check"></i> Đơn hàng
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/modules/products/index.php">
                        <i class="bi bi-box-seam"></i> Sản phẩm
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/modules/categories/index.php">
                        <i class="bi bi-folder"></i> Danh mục
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/modules/shops/index.php">
                        <i class="bi bi-shop"></i> Cửa hàng
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/modules/users/index.php">
                        <i class="bi bi-people"></i> Người dùng
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/modules/payments/index.php">
                        <i class="bi bi-credit-card"></i> Thanh toán
                    </a>
                </li>
                
                <li class="nav-item mt-3">
                    <small class="nav-link text-muted text-uppercase fw-bold">Cài đặt</small>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/settings.php">
                        <i class="bi bi-gear"></i> Cấu hình
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="p-4">
        <!-- Flash Messages -->
        <?php if (Session::hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo escape(Session::getFlash('success')); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (Session::hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?php echo escape(Session::getFlash('error')); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (Session::hasFlash('info')): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle"></i> <?php echo escape(Session::getFlash('info')); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
