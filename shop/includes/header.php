<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? escape($pageTitle) . ' - ' : ''; ?>Shop - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 56px 0 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, .05);
            width: 260px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 56px);
            padding-top: 1rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, .8);
            padding: 1rem 1.5rem;
            transition: all .3s ease;
            border-left: 3px solid transparent;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, .08);
            border-left-color: #667eea;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(102, 126, 234, 0.2);
            border-left-color: #667eea;
        }
        
        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: .75rem;
            font-size: 1.1rem;
        }
        
        main {
            margin-left: 260px;
            padding-top: 70px;
            min-height: 100vh;
        }
        
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: var(--primary-gradient) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,.15);
            padding: 0.8rem 0;
        }
        
        .stat-card {
            border: none;
            border-radius: 12px;
            transition: all .3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            transition: width .3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0,0,0,.15) !important;
        }
        
        .stat-card:hover::before {
            width: 100%;
            opacity: 0.05;
        }
        
        .stat-card.primary::before {
            background: var(--primary-gradient);
        }
        
        .stat-card.success::before {
            background: var(--success-gradient);
        }
        
        .stat-card.warning::before {
            background: var(--warning-gradient);
        }
        
        .stat-card.info::before {
            background: var(--info-gradient);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }
        
        .card-header {
            border-radius: 12px 12px 0 0 !important;
            background: #fff !important;
            border-bottom: 2px solid #f0f0f0 !important;
            padding: 1.25rem 1.5rem;
        }
        
        .btn {
            border-radius: 8px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all .3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,.15);
        }
        
        .badge {
            padding: 0.5rem 0.75rem;
            font-weight: 500;
            border-radius: 6px;
        }
        
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
    </style>
    
    <?php if (isset($additionalCSS)): ?>
        <?php echo $additionalCSS; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/shop/dashboard.php">
                <i class="bi bi-shop"></i> Quản lý cửa hàng - <?php echo SITE_NAME; ?>
            </a>
            
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle"></i> <?php echo escape(Auth::user()['full_name']); ?>
                </span>
                <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-light btn-sm me-2" target="_blank">
                    <i class="bi bi-globe"></i> Xem trang chủ
                </a>
                <a href="/logout.php" class="btn btn-outline-light btn-sm">
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
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="/shop/dashboard.php">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="/shop/modules/products/">
                        <i class="bi bi-box-seam"></i> Sản phẩm
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="/shop/modules/orders/">
                        <i class="bi bi-cart-check"></i> Đơn hàng
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="/shop/reports.php">
                        <i class="bi bi-graph-up"></i> Báo cáo
                    </a>
                </li>
                
                <li class="nav-item mt-3">
                    <small class="nav-link text-muted text-uppercase fw-bold">Cài đặt</small>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="/shop/settings.php">
                        <i class="bi bi-gear"></i> Cửa hàng
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
