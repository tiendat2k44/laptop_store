<?php
/**
 * EasyPay Integration Verification
 * Kiểm tra xem tích hợp EasyPay đã hoàn thành đúng cách
 */

// Không yêu cầu init.php, chỉ kiểm tra file tồn tại
$checks = [
    'PHP Files' => [
        'includes/payment/EasyPayGateway.php' => __DIR__ . '/includes/payment/EasyPayGateway.php',
        'payment/easy-pay-return.php' => __DIR__ . '/payment/easy-pay-return.php',
        'payment/easy-pay-ipn.php' => __DIR__ . '/payment/easy-pay-ipn.php',
        'diagnostics/test-easypay.php' => __DIR__ . '/diagnostics/test-easypay.php',
    ],
    'Documentation' => [
        'EASYPAY_SETUP.md' => __DIR__ . '/EASYPAY_SETUP.md',
        'EASYPAY_INTEGRATION.md' => __DIR__ . '/EASYPAY_INTEGRATION.md',
        'EASYPAY_README.txt' => __DIR__ . '/EASYPAY_README.txt',
    ]
];

$allPassed = true;
$results = [];

foreach ($checks as $category => $files) {
    $results[$category] = [];
    foreach ($files as $name => $path) {
        $exists = file_exists($path);
        $results[$category][$name] = $exists;
        if (!$exists) {
            $allPassed = false;
        }
    }
}

// HTML Output
?>
<!DOCTYPE html>
<html>
<head>
    <title>EasyPay Integration Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .category { margin: 20px 0; }
        .category h3 { color: #555; margin-top: 0; }
        .item { padding: 8px 12px; margin: 5px 0; border-radius: 4px; display: flex; justify-content: space-between; }
        .passed { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .failed { background: #f8d7da; color: #721c24; border-left: 4px solid #f5222d; }
        .status-icon { font-size: 18px; font-weight: bold; }
        .summary { margin: 20px 0; padding: 15px; border-radius: 4px; font-weight: bold; }
        .summary.pass { background: #d4edda; color: #155724; border: 1px solid #28a745; }
        .summary.fail { background: #f8d7da; color: #721c24; border: 1px solid #f5222d; }
        .code-block { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; margin: 10px 0; }
        code { font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
<div class="container">
    <h1>✅ EasyPay Integration Verification</h1>
    
    <div class="summary <?= $allPassed ? 'pass' : 'fail' ?>">
        <?= $allPassed ? '✓ All checks passed!' : '✗ Some checks failed - see details below' ?>
    </div>

    <?php foreach ($results as $category => $items): ?>
    <div class="category">
        <h3><?= htmlspecialchars($category) ?></h3>
        <?php foreach ($items as $name => $passed): ?>
        <div class="item <?= $passed ? 'passed' : 'failed' ?>">
            <span><?= htmlspecialchars($name) ?></span>
            <span class="status-icon"><?= $passed ? '✓' : '✗' ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <div class="category">
        <h3>Configuration Checklist</h3>
        <div class="item">
            <span>Update EASYPAY_PARTNER_CODE in includes/config/config.php</span>
            <span class="status-icon">⚠️</span>
        </div>
        <div class="item">
            <span>Update EASYPAY_API_KEY in includes/config/config.php</span>
            <span class="status-icon">⚠️</span>
        </div>
        <div class="item">
            <span>Configure webhook URL in EasyPay dashboard</span>
            <span class="status-icon">⚠️</span>
        </div>
    </div>

    <div class="category">
        <h3>Next Steps</h3>
        <ol>
            <li>Sign up at <a href="https://sepay.vn/" target="_blank">https://sepay.vn/</a></li>
            <li>Get Partner Code and API Key from merchant dashboard</li>
            <li>Update <code>includes/config/config.php</code> with your credentials</li>
            <li>Configure webhook URL: <code><?= $_SERVER['HTTP_HOST'] ?? 'your-site.com' ?>/payment/easy-pay-ipn.php</code></li>
            <li>Test payment at <code>/diagnostics/test-easypay.php</code></li>
            <li>Read <a href="./EASYPAY_SETUP.md">EASYPAY_SETUP.md</a> for detailed guide</li>
        </ol>
    </div>

    <div class="category">
        <h3>Payment Methods Available</h3>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
            <div style="padding: 10px; background: #e3f2fd; border-radius: 4px;">
                <strong>💳 EasyPay</strong><br>
                <small>NEW - Sepay integration</small>
            </div>
            <div style="padding: 10px; background: #e3f2fd; border-radius: 4px;">
                <strong>💰 MoMo</strong><br>
                <small>E-wallet</small>
            </div>
            <div style="padding: 10px; background: #e3f2fd; border-radius: 4px;">
                <strong>🏦 VNPay</strong><br>
                <small>Bank transfer</small>
            </div>
            <div style="padding: 10px; background: #e3f2fd; border-radius: 4px;">
                <strong>🚚 COD</strong><br>
                <small>Cash on delivery</small>
            </div>
        </div>
    </div>

    <div class="category">
        <h3>Integration Files</h3>
        <ul>
            <li><strong>Gateway:</strong> includes/payment/EasyPayGateway.php</li>
            <li><strong>Return Handler:</strong> payment/easy-pay-return.php</li>
            <li><strong>Webhook Handler:</strong> payment/easy-pay-ipn.php</li>
            <li><strong>Test Page:</strong> diagnostics/test-easypay.php</li>
            <li><strong>Documentation:</strong> EASYPAY_SETUP.md, EASYPAY_INTEGRATION.md</li>
        </ul>
    </div>

    <div style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 4px;">
        <strong>📞 Support</strong><br>
        For issues with EasyPay integration:
        <ul>
            <li>Check error.log for PHP errors</li>
            <li>Visit <a href="/diagnostics/test-easypay.php">test page</a></li>
            <li>Read <a href="./EASYPAY_SETUP.md">setup guide</a></li>
            <li>Contact EasyPay support: <a href="https://sepay.vn/" target="_blank">https://sepay.vn/</a></li>
        </ul>
    </div>
</div>
</body>
</html>
