<?php
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isAjax()) {
    jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
}

if (!Session::verifyToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'CSRF invalid'], 403);
}

$code = trim($_POST['code'] ?? '');
$subtotal = (float)($_POST['subtotal'] ?? 0);

if ($code === '') {
    jsonResponse(['success' => false, 'discount' => 0]);
}

$db = Database::getInstance();
require_once __DIR__ . '/../../includes/services/CouponService.php';
$coupon = new CouponService($db);

$result = $coupon->validateCoupon($code, $subtotal);
if ($result) {
    jsonResponse(['success' => true, 'discount' => $result['discount'], 'message' => $result['description']]);
} else {
    jsonResponse(['success' => false, 'message' => 'Mã giảm giá không hợp lệ']);
}
