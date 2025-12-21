<?php
/**
 * Debug form submission
 * Tệp này sẽ capture POST data từ cart.php form
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<title>Debug POST Data</title>
<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
.success { border-left-color: #28a745; }
.error { border-left-color: #dc3545; }
.warning { border-left-color: #ffc107; }
pre { background: #222; color: #0f0; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>
</head>
<body>
<h1>🔍 Debug: POST Data từ Cart Form</h1>
<p>Trang này ghi lại POST data được gửi từ cart.php</p>
<hr>";

// Check if coming from cart form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='box success'>";
    echo "<h3>✅ POST Request Received</h3>";
    echo "<p><strong>Source:</strong> " . ($_SERVER['HTTP_REFERER'] ?? 'unknown') . "</p>";
    echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "</div>";
    
    // Check for selected_items
    if (isset($_POST['selected_items'])) {
        echo "<div class='box success'>";
        echo "<h3>✅ selected_items[] Received</h3>";
        echo "<p><strong>Type:</strong> " . gettype($_POST['selected_items']) . "</p>";
        echo "<p><strong>Count:</strong> " . count($_POST['selected_items']) . "</p>";
        echo "<p><strong>Values:</strong></p>";
        echo "<pre>";
        foreach ($_POST['selected_items'] as $i => $val) {
            echo "[$i] = " . htmlspecialchars($val) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<div class='box error'>";
        echo "<h3>❌ selected_items[] NOT Received!</h3>";
        echo "<p>Form có thể không gửi checkbox data</p>";
        echo "</div>";
    }
    
    // Show all POST data
    echo "<div class='box'>";
    echo "<h3>📋 All POST Data:</h3>";
    echo "<pre>";
    foreach ($_POST as $key => $val) {
        if (is_array($val)) {
            echo "$key = [ARRAY with " . count($val) . " items]\n";
        } else {
            echo "$key = " . htmlspecialchars(substr($val, 0, 100)) . "\n";
        }
    }
    echo "</pre>";
    echo "</div>";
    
    // Check CSRF token
    echo "<div class='box'>";
    echo "<h3>🔐 CSRF Token Check:</h3>";
    if (isset($_POST['csrf_token'])) {
        $valid = Session::verifyToken($_POST['csrf_token']);
        echo "<p><strong>Token Exists:</strong> YES</p>";
        echo "<p><strong>Token Valid:</strong> " . ($valid ? "✅ YES" : "❌ NO") . "</p>";
    } else {
        echo "<p><strong>Token Exists:</strong> ❌ NO</p>";
    }
    echo "</div>";
    
    // Show next steps
    echo "<div class='box warning'>";
    echo "<h3>📌 Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Quay lại <a href='/cart.php'>cart.php</a></li>";
    echo "<li>Mở DevTools (F12) → Network tab</li>";
    echo "<li>Chọn sản phẩm</li>";
    echo "<li>Click 'Tiến hành thanh toán'</li>";
    echo "<li>Xem POST request có chứa `selected_items[]` không</li>";
    echo "</ol>";
    echo "</div>";
    
} else {
    echo "<div class='box warning'>";
    echo "<h3>⚠️ No POST Data</h3>";
    echo "<p>Truy cập GET request. Vui lòng submit form từ cart.php để xem POST data.</p>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h3>🔧 Cách test:</h3>";
    echo "<ol>";
    echo "<li>Vào <a href='/cart.php'>/cart.php</a></li>";
    echo "<li>Chọn sản phẩm (tích checkbox)</li>";
    echo "<li>Sửa form action thành: <code>action='/diagnostics/debug_post.php'</code></li>";
    echo "<li>Click submit</li>";
    echo "</ol>";
    echo "</div>";
}

echo "</body></html>";
?>
