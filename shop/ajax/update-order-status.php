<?php
/**
 * Shop AJAX Endpoint - Cập nhật trạng thái đơn hàng
 * Chỉ cho phép shop cập nhật đơn hàng thuộc sở hữu (single-shop orders)
 */

require_once __DIR__ . '/../../includes/init.php';

// Debug: hiển thị lỗi trong phản hồi (xóa/tắt khi triển khai)
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

/**
 * Helper function: gửi JSON response và dừng script
 */
function respond($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    // Chỉ cho phép yêu cầu AJAX
    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        respond(['success' => false, 'message' => 'Access denied'], 403);
    }

    // Yêu cầu quyền truy cập shop
    if (!Auth::check() || !Auth::isShop()) {
        respond(['success' => false, 'message' => 'Không có quyền truy cập'], 403);
    }

    try {
        $db = Database::getInstance();
        $shopId = Auth::getShopId();
    } catch (Exception $e) {
        respond(['success' => false, 'message' => 'Lỗi kết nối DB: ' . $e->getMessage()], 500);
    }

    if (!$shopId) {
        respond(['success' => false, 'message' => 'Cửa hàng không tồn tại'], 403);
    }

    // Lấy dữ liệu từ yêu cầu
    $orderId = intval($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');

    // Xác thực dữ liệu đầu vào
    if ($orderId <= 0) {
        respond(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
    }

    $allowedStatuses = getOrderStatusKeys();
    if (!in_array($newStatus, $allowedStatuses, true)) {
        respond(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
    }

    // Kiểm tra quyền sở hữu đơn hàng
    try {
        $orderCheck = $db->queryOne(
            "SELECT o.id, o.status, o.order_number,
                    COUNT(*) AS total_items,
                    SUM(CASE WHEN COALESCE(oi.shop_id, p.shop_id, -1) = :shop_id THEN 1 ELSE 0 END) AS owned_items,
                    COUNT(DISTINCT COALESCE(oi.shop_id, p.shop_id, -1)) AS shop_count
             FROM orders o
             JOIN order_items oi ON o.id = oi.order_id
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE o.id = :order_id
             GROUP BY o.id, o.status, o.order_number",
            ['order_id' => $orderId, 'shop_id' => $shopId]
        );
    } catch (Exception $e) {
        respond(['success' => false, 'message' => 'Lỗi truy vấn: ' . $e->getMessage()]);
    }

    if (!$orderCheck || (int)$orderCheck['owned_items'] === 0) {
        // Fallback: kiểm tra tồn tại item thuộc shop và đơn chỉ thuộc 1 shop (khớp logic view.php)
        $ownCnt = $db->queryOne(
            "SELECT COUNT(*) AS cnt
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = :oid AND COALESCE(oi.shop_id, p.shop_id, -1) = :sid",
            ['oid' => $orderId, 'sid' => $shopId]
        );
        $scope = $db->queryOne(
            "SELECT COUNT(DISTINCT COALESCE(oi.shop_id, p.shop_id, -1)) AS shop_count
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = :oid",
            ['oid' => $orderId]
        );

        $ownedOk = (int)($ownCnt['cnt'] ?? 0) > 0;
        $singleShop = (int)($scope['shop_count'] ?? 0) === 1;

        if (!$ownedOk || !$singleShop) {
            error_log('[SHOP STATUS][BLOCK] shop_id=' . $shopId . ' order_id=' . $orderId . ' owned_items=' . ($orderCheck['owned_items'] ?? 'null') . ' shop_count=' . ($orderCheck['shop_count'] ?? 'null') . ' fallback_owned=' . ($ownCnt['cnt'] ?? 'null') . ' fallback_scope=' . ($scope['shop_count'] ?? 'null'));
            respond([
                'success' => false,
                'message' => 'Đơn hàng không tồn tại hoặc không thuộc quyền quản lý',
                'debug' => [
                    'owned_items' => (int)($orderCheck['owned_items'] ?? 0),
                    'shop_count' => (int)($orderCheck['shop_count'] ?? 0),
                    'fallback_owned' => (int)($ownCnt['cnt'] ?? 0),
                    'fallback_scope' => (int)($scope['shop_count'] ?? 0)
                ]
            ]);
        }
        // Nếu fallback xác nhận đúng quyền sở hữu và đơn chỉ thuộc 1 shop, cho phép tiếp tục
    }

    if ((int)$orderCheck['shop_count'] > 1) {
        respond(['success' => false, 'message' => 'Đơn này chứa sản phẩm của nhiều cửa hàng. Liên hệ admin để xử lý.']);
    }

    // Không cho phép thay đổi trạng thái nếu đã hủy hoặc đã giao
    if (in_array($orderCheck['status'], ['cancelled', 'delivered']) && $newStatus !== $orderCheck['status']) {
        respond([
            'success' => false,
            'message' => 'Không thể thay đổi trạng thái của đơn hàng đã ' . ($orderCheck['status'] === 'cancelled' ? 'hủy' : 'giao')
        ]);
    }

    // Cập nhật trạng thái đơn hàng
    try {
        $affected = $db->execute(
            "UPDATE orders SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :order_id",
            ['status' => $newStatus, 'order_id' => $orderId]
        );

        // Xác minh trạng thái sau khi cập nhật
        $after = $db->queryOne("SELECT status FROM orders WHERE id = :id", ['id' => $orderId]);
        $ok = $affected || ($after && $after['status'] === $newStatus);

        if ($ok) {
            respond([
                'success' => true, 
                'message' => 'Cập nhật trạng thái đơn hàng thành công',
                'new_status' => $newStatus,
                'status_label' => getOrderStatusLabel($newStatus)
            ]);
        } else {
            respond(['success' => false, 'message' => 'Không thể cập nhật trạng thái (0 dòng bị ảnh hưởng)']);
        }
    } catch (Exception $e) {
        respond(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }

} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
}
