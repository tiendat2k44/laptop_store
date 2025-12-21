<?php
/**
 * EasyPay Payment Integration Test
 * File để test tích hợp EasyPay
 */

require_once __DIR__ . '/includes/init.php';

use Includes\Core\Auth;

// Check admin access
if (!Auth::check() || Auth::user()['role'] !== 'admin') {
    die('Chỉ admin mới có thể truy cập trang này');
}

$db = Database::getInstance();

// Lấy một order để test
$testOrder = $db->queryOne(
    "SELECT * FROM orders ORDER BY id DESC LIMIT 1"
);

?>
<!DOCTYPE html>
<html>
<head>
    <title>EasyPay Payment Integration Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="mb-4">EasyPay Payment Integration Test</h1>
            
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Configuration Check</h5>
                </div>
                <div class="card-body">
                    <table class="table mb-0">
                        <tr>
                            <td><strong>EASYPAY_PARTNER_CODE:</strong></td>
                            <td>
                                <?php
                                $code = EASYPAY_PARTNER_CODE ?? 'not defined';
                                if (strpos($code, 'your_') === 0) {
                                    echo '<span class="badge bg-danger">NOT CONFIGURED</span>';
                                } else {
                                    echo '<span class="badge bg-success">CONFIGURED</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>EASYPAY_API_KEY:</strong></td>
                            <td>
                                <?php
                                $key = EASYPAY_API_KEY ?? 'not defined';
                                if (strpos($key, 'your_') === 0) {
                                    echo '<span class="badge bg-danger">NOT CONFIGURED</span>';
                                } else {
                                    echo '<span class="badge bg-success">CONFIGURED</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>EASYPAY_ENDPOINT:</strong></td>
                            <td><code><?= EASYPAY_ENDPOINT ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>EASYPAY_RETURN_URL:</strong></td>
                            <td><code><?= EASYPAY_RETURN_URL ?></code></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Configuration Guide</h5>
                </div>
                <div class="card-body">
                    <h6>Step 1: Register at Sepay</h6>
                    <ol>
                        <li>Go to <a href="https://sepay.vn/" target="_blank">https://sepay.vn/</a></li>
                        <li>Sign up and verify your account</li>
                        <li>Go to merchant dashboard and create API key</li>
                    </ol>

                    <h6 class="mt-3">Step 2: Update Configuration</h6>
                    <p>Edit <code>includes/config/config.php</code> and update:</p>
                    <pre><code>define('EASYPAY_PARTNER_CODE', 'your_actual_partner_code');
define('EASYPAY_API_KEY', 'your_actual_api_key');</code></pre>

                    <h6 class="mt-3">Step 3: Test Payment</h6>
                    <p>Once configured, users can select "EasyPay" as payment method in checkout page.</p>
                </div>
            </div>

            <?php if ($testOrder): ?>
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Test Order</h5>
                </div>
                <div class="card-body">
                    <p>
                        <strong>Order ID:</strong> <?= $testOrder['id'] ?><br>
                        <strong>Order Number:</strong> <?= escape($testOrder['order_number']) ?><br>
                        <strong>Total Amount:</strong> <?= formatPrice($testOrder['total_amount']) ?><br>
                        <strong>Status:</strong> <?= escape($testOrder['status']) ?>
                    </p>
                    
                    <?php if (strpos(EASYPAY_PARTNER_CODE, 'your_') === 0): ?>
                    <p class="text-danger mb-0">
                        <strong>Note:</strong> Configure EASYPAY_PARTNER_CODE and EASYPAY_API_KEY first before testing.
                    </p>
                    <?php else: ?>
                    <a href="<?= SITE_URL ?>/payment/easy-pay-return.php?id=<?= $testOrder['id'] ?>" 
                       class="btn btn-primary" target="_blank">
                        Test Payment
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="mt-5 text-muted small">
                <p>
                    <strong>File Locations:</strong>
                </p>
                <ul>
                    <li>Gateway: <code>includes/payment/EasyPayGateway.php</code></li>
                    <li>Return Handler: <code>payment/easy-pay-return.php</code></li>
                    <li>IPN Handler: <code>payment/easy-pay-ipn.php</code></li>
                    <li>Config: <code>includes/config/config.php</code></li>
                </ul>
            </div>
        </div>
    </div>
</div>
</body>
</html>
