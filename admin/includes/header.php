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
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8f9fa;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 70px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .15);
            width: 260px;
            background: linear-gradient(135deg, #1a1d29 0%, #2d3142 100%);
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .sidebar-sticky {
            position: relative;
            height: calc(100vh - 70px);
            padding: 1rem 0;
            overflow-y: auto;
        }
        
        .sidebar .nav {
            margin: 0;
            padding: 0 0.5rem;
        }
        
        .sidebar .nav-item {
            margin-bottom: 0.3rem;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(4px);
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background: linear-gradient(90deg, #0d6efd 0%, #0a5cdb 100%);
            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
        }
        
        .sidebar .nav-link i {
            width: 22px;
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .sidebar .nav-label {
            padding: 0.75rem 1rem;
            margin-top: 1rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.5);
            font-weight: 700;
        }
        
        /* Main Content */
        main {
            margin-left: 260px;
            padding-top: 70px;
            min-height: 100vh;
        }
        
        /* Navbar */
        .navbar-admin {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            height: 70px;
            display: flex;
            align-items: center;
        }
        
        .navbar-admin .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
            color: #0d6efd;
            margin-left: 20px;
        }
        
        /* Stat Cards */
        .stat-card {
            border-left: 4px solid;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.primary {
            border-left-color: #0d6efd;
        }
        
        .stat-card.success {
            border-left-color: #198754;
        }
        
        .stat-card.warning {
            border-left-color: #ffc107;
        }
        
        .stat-card.info {
            border-left-color: #0dcaf0;
        }
        
        .stat-card.danger {
            border-left-color: #dc3545;
        }
        
        /* Table Improvements */
        .table {
            background: #fff;
            margin-bottom: 0;
        }
        
        .table thead {
            background: #f8f9fa;
        }
        
        .table tbody tr {
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 8px;
            background: #fff;
        }
        
        .card-header {
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa !important;
        }
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        /* Badges */
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 500;
        }
        
        /* Buttons */
        .btn {
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        /* Scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 999;
                width: 280px;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            main {
                margin-left: 0;
            }
        }
    </style>
    
    <?php if (isset($additionalCSS)): ?>
        <?php echo $additionalCSS; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-admin">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/admin/">
                <i class="bi bi-speedometer2"></i> <?php echo SITE_NAME; ?> Admin
            </a>
            
            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-circle text-primary me-2 fs-5"></i>
                    <span class="text-dark fw-500"><?php echo escape(Auth::user()['full_name']); ?></span>
                </div>
                <div class="vr"></div>
                <a href="<?php echo SITE_URL; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                    <i class="bi bi-globe"></i> Xem trang chủ
                </a>
                <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar Navigation -->
    <nav class="sidebar">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], '/admin/index.php') !== false ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/">
                        <i class="bi bi-house-door"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/modules/orders/index.php">
                        <i class="bi bi-cart-check"></i>
                        <span>Đơn hàng</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/modules/products/index.php">
                        <i class="bi bi-box-seam"></i>
                        <span>Sản phẩm</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/modules/categories/index.php">
                        <i class="bi bi-folder"></i>
                        <span>Danh mục</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/modules/shops/index.php">
                        <i class="bi bi-shop"></i>
                        <span>Cửa hàng</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/modules/users/index.php">
                        <i class="bi bi-people"></i>
                        <span>Người dùng</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/modules/payments/index.php">
                        <i class="bi bi-credit-card"></i>
                        <span>Thanh toán</span>
                    </a>
                </li>
                
                <li class="nav-label">Quản lý</li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/settings.php">
                        <i class="bi bi-gear"></i>
                        <span>Cấu hình</span>
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
