<?php
/**
 * Security & Performance Test Suite
 * Test file to verify all security improvements
 * 
 * Access: http://localhost/.../diagnostics/security-test.php
 */

require_once __DIR__ . '/../includes/init.php';

// Only allow in development/local
$localIPs = ['127.0.0.1', '::1', 'localhost'];
if (!in_array($_SERVER['REMOTE_ADDR'], $localIPs)) {
    header('HTTP/1.1 403 Forbidden');
    die('This page is only available locally');
}

$tests = [];
$passed = 0;
$failed = 0;

// Test 1: Environment Variables
$test = [
    'name' => 'Environment Variables Loader',
    'description' => 'Check if Env class is loaded',
    'result' => class_exists('Env'),
];
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 2: Rate Limiter
$test = [
    'name' => 'Rate Limiter Class',
    'description' => 'Check if RateLimiter class is available',
    'result' => class_exists('RateLimiter'),
];
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 3: Security Headers Class
$test = [
    'name' => 'Security Headers Class',
    'description' => 'Check if SecurityHeaders class is available',
    'result' => class_exists('SecurityHeaders'),
];
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 4: Database Connection
$test = [
    'name' => 'Database Connection',
    'description' => 'Check if database connection works',
    'result' => Database::getInstance() !== null,
];
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 5: Prepared Statements
try {
    $db = Database::getInstance();
    $sql = "SELECT 1 WHERE 1 = :test";
    $result = $db->queryOne($sql, ['test' => 1]);
    $test = [
        'name' => 'Prepared Statements',
        'description' => 'Check if prepared statements work',
        'result' => true,
    ];
} catch (Exception $e) {
    $test = [
        'name' => 'Prepared Statements',
        'description' => 'Check if prepared statements work',
        'result' => false,
        'error' => $e->getMessage(),
    ];
}
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 6: Escape Function
$test = [
    'name' => 'XSS Protection - escape() Function',
    'description' => 'Check if escape function properly escapes HTML',
    'result' => escape('<script>alert("XSS")</script>') === '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;',
];
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 7: CSRF Token
$test = [
    'name' => 'CSRF Protection',
    'description' => 'Check if CSRF token is generated',
    'result' => !empty(Session::getToken()),
];
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 8: Password Hash
$testPassword = 'TestPassword123!';
$hash = password_hash($testPassword, PASSWORD_BCRYPT);
$test = [
    'name' => 'Password Hashing',
    'description' => 'Check if bcrypt password hashing works',
    'result' => password_verify($testPassword, $hash),
];
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 9: Check for .env file
$envFile = dirname(dirname(__FILE__)) . '/.env';
$test = [
    'name' => '.env File',
    'description' => 'Check if .env file exists',
    'result' => file_exists($envFile),
    'file' => $envFile,
    'warning' => !file_exists($envFile) ? 'Create .env from .env.example' : null,
];
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 10: .env in gitignore
$gitignoreFile = dirname(dirname(__FILE__)) . '/.gitignore';
$gitignore = file_exists($gitignoreFile) ? file_get_contents($gitignoreFile) : '';
$test = [
    'name' => '.env in .gitignore',
    'description' => 'Check if .env is ignored by git',
    'result' => strpos($gitignore, '.env') !== false,
    'warning' => strpos($gitignore, '.env') === false ? '.env file will be committed!' : null,
];
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 11: Check PHP version
$test = [
    'name' => 'PHP Version',
    'description' => 'Check if PHP version is 8.0+',
    'result' => version_compare(PHP_VERSION, '8.0', '>='),
    'version' => PHP_VERSION,
];
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 12: Check for APCu
$test = [
    'name' => 'APCu Extension',
    'description' => 'Check if APCu is installed (for caching)',
    'result' => extension_loaded('apcu'),
    'note' => !extension_loaded('apcu') ? 'Optional but recommended for caching' : 'Ready for caching',
];
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 13: Session security
$test = [
    'name' => 'Session Configuration',
    'description' => 'Check if session cookies are httponly',
    'result' => ini_get('session.cookie_httponly') == 1,
];
$tests[] = $test;
if ($test['result']) $passed++; else $failed++;

// Test 14: Error reporting
$test = [
    'name' => 'Error Reporting',
    'description' => 'Check if errors are hidden from users (production-safe)',
    'result' => ini_get('display_errors') == 0 || ini_get('display_errors') == '',
    'note' => ini_get('display_errors') ? 'WARNING: Errors visible (OK for dev, not for prod)' : 'Good - errors hidden',
];
$tests[] = $test;

// Test 15: Headers sent (verify headers were applied)
$test = [
    'name' => 'Security Headers Applied',
    'description' => 'Check if X-Frame-Options header is sent',
    'result' => in_array('X-Frame-Options: SAMEORIGIN', headers_list()),
    'note' => 'Headers should be: X-Frame-Options, X-Content-Type-Options, CSP, HSTS',
];
$tests[] = $test;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security & Performance Test Suite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .test-container { max-width: 1000px; margin: 0 auto; }
        .test-item { 
            background: white; 
            border-left: 4px solid #ddd; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 4px;
        }
        .test-item.pass { border-left-color: #28a745; background: #f0f9f5; }
        .test-item.fail { border-left-color: #dc3545; background: #fff5f5; }
        .test-item.warn { border-left-color: #ffc107; background: #fffbf0; }
        .badge-pass { background: #28a745; }
        .badge-fail { background: #dc3545; }
        .badge-warn { background: #ffc107; color: #000; }
        .progress-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .score { font-size: 36px; font-weight: bold; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="test-container">
    <div class="progress-section">
        <h2>🔒 Security & Performance Test Suite</h2>
        <p class="text-muted">Verify all security improvements are in place</p>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="text-center">
                    <div class="score"><?= $passed ?>/<?= count($tests) ?></div>
                    <p class="text-muted">Tests Passed</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="progress">
                    <div class="progress-bar bg-success" style="width: <?= ($passed/count($tests))*100 ?>%">
                        <?= round(($passed/count($tests))*100) ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mt-4">Test Results</h3>

    <?php foreach ($tests as $test): 
        $status = $test['result'] ? 'pass' : ($test['warning'] ?? false ? 'warn' : 'fail');
        $statusBadge = $status === 'pass' ? 'success' : ($status === 'warn' ? 'warning' : 'danger');
    ?>
    <div class="test-item <?= $status ?>">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5><?= htmlspecialchars($test['name']) ?></h5>
                <p class="text-muted mb-0"><?= htmlspecialchars($test['description']) ?></p>
                <?php if (!empty($test['error'])): ?>
                    <code class="text-danger"><?= htmlspecialchars($test['error']) ?></code>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-end">
                <span class="badge badge-<?= $statusBadge ?>">
                    <?= $status === 'pass' ? '✅ PASS' : ($status === 'warn' ? '⚠️ WARNING' : '❌ FAIL') ?>
                </span>
                <?php if (!empty($test['version'])): ?>
                    <div class="small text-muted mt-2">Version: <?= $test['version'] ?></div>
                <?php endif; ?>
                <?php if (!empty($test['note'])): ?>
                    <div class="small text-muted mt-2"><?= $test['note'] ?></div>
                <?php endif; ?>
                <?php if (!empty($test['warning'])): ?>
                    <div class="small text-warning mt-2">⚠️ <?= $test['warning'] ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="alert alert-info mt-4">
        <h5>📋 Next Steps:</h5>
        <ol>
            <li>If any test FAILS, check the error message above</li>
            <li>Verify <code>.env</code> file is created (from <code>.env.example</code>)</li>
            <li>Test login rate limiting: Try login 6 times in quick succession</li>
            <li>Verify security headers: Use browser DevTools Network tab</li>
            <li>For production: Review <code>CRITICAL_SECURITY_FIXES.md</code></li>
        </ol>
    </div>

    <div class="alert alert-warning">
        <h5>⚠️ Important Reminders:</h5>
        <ul>
            <li>This page should only be accessed in development (local IP check enforced)</li>
            <li>Never commit <code>.env</code> file to git</li>
            <li>Always enable HTTPS in production</li>
            <li>Disable <code>display_errors</code> in production</li>
            <li>Keep security patches updated</li>
        </ul>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
