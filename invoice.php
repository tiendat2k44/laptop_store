<?php
require_once __DIR__ . '/includes/init.php';

Auth::requireLogin();

$db = Database::getInstance();
require_once __DIR__ . '/includes/services/OrderService.php';
$orderService = new OrderService($db, Auth::id());

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    http_response_code(404);
    die('Order not found');
}

$order = $orderService->getOrderDetail($orderId);
if (!$order) {
    http_response_code(404);
    die('Order not found');
}

$items = $orderService->getOrderItems($orderId);
$user = Auth::user();

// Determine if PDF or HTML view
$format = trim($_GET['format'] ?? 'html');
$filename = 'hoadon-' . $order['order_number'] . '.html';

$html = buildInvoiceHTML($order, $items, $user);

if ($format === 'pdf') {
    // Header PDF (client sẽ mở browser print dialog)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // Không thể generate PDF server-side dễ dàng; nhưng có thể suggest print-to-PDF
    echo $html;
} else {
    // HTML view
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
}

function buildInvoiceHTML($order, $items, $user) {
    $style = <<<'CSS'
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .invoice { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { border-bottom: 2px solid #0d6efd; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #0d6efd; margin-bottom: 10px; }
        .invoice-title { font-size: 28px; font-weight: bold; margin: 20px 0; }
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-box { flex: 1; }
        .info-label { font-weight: bold; color: #0d6efd; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f8f9fa; padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        tr:last-child td { border-bottom: 2px solid #dee2e6; }
        .text-right { text-align: right; }
        .summary { display: flex; justify-content: flex-end; margin: 20px 0; }
        .summary-item { width: 300px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .summary-total { font-size: 18px; font-weight: bold; border-top: 2px solid #dee2e6; padding-top: 10px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #666; }
        .print-btn { margin: 20px 0; text-align: center; }
        .print-btn button { padding: 10px 20px; background: #0d6efd; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        @media print {
            .print-btn, .no-print { display: none !important; }
            body { background: white; }
            .invoice { max-width: 100%; }
        }
    </style>
    CSS;

    $subtotal = 0;
    $rows = '';
    foreach ($items as $it) {
        $subtotal += (float)$it['subtotal'];
        $rows .= '<tr>'
               . '<td>' . escape($it['product_name']) . '</td>'
               . '<td class="text-right">' . number_format((float)$it['price'], 0, ',', '.') . ' ₫</td>'
               . '<td class="text-right">' . (int)$it['quantity'] . '</td>'
               . '<td class="text-right">' . number_format((float)$it['subtotal'], 0, ',', '.') . ' ₫</td>'
               . '</tr>';
    }

    $statuses = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'processing' => 'Đang xử lý',
        'shipping' => 'Đang giao',
        'delivered' => 'Đã giao',
        'cancelled' => 'Đã hủy',
    ];
    $payStatuses = [
        'pending' => 'Chờ thanh toán',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thất bại',
        'refunded' => 'Đã hoàn tiền',
    ];

    return <<<HTML
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <title>Hóa đơn {$order['order_number']}</title>
        $style
    </head>
    <body>
        <div class="invoice">
            <div class="print-btn no-print">
                <button onclick="window.print()"><i class="bi bi-printer"></i> In hóa đơn</button>
                <button onclick="window.close()" style="margin-left:10px;background:#6c757d">Đóng</button>
            </div>

            <div class="header">
                <div class="logo">📦 Laptop Store</div>
                <div style="font-size:12px;color:#666">Cũng cấp laptop chính hãng, giá tốt nhất thị trường</div>
            </div>

            <div class="invoice-title">HÓA ĐƠN BÁN HÀNG</div>

            <div class="invoice-info">
                <div class="info-box">
                    <div class="info-label">THÔNG TIN KHÁCH HÀNG</div>
                    <div>{$user['full_name']}</div>
                    <div>{$user['email']}</div>
                    <div>{$user['phone']}</div>
                </div>
                <div class="info-box">
                    <div class="info-label">THÔNG TIN HÓA ĐƠN</div>
                    <div><strong>Mã đơn:</strong> {$order['order_number']}</div>
                    <div><strong>Ngày:</strong> {$order['created_at']}</div>
                    <div><strong>Trạng thái:</strong> {$statuses[$order['status']]}</div>
                </div>
            </div>

            <div class="invoice-info">
                <div class="info-box">
                    <div class="info-label">ĐỊA CHỈ GIAO HÀNG</div>
                    <div>{$order['recipient_name']}</div>
                    <div>{$order['recipient_phone']}</div>
                    <div>{$order['shipping_address']}</div>
                    <div>{$order['ward']}, {$order['district']}, {$order['city']}</div>
                </div>
                <div class="info-box">
                    <div class="info-label">THÔNG TIN THANH TOÁN</div>
                    <div><strong>P/thức:</strong> {$order['payment_method']}</div>
                    <div><strong>Trạng thái:</strong> {$payStatuses[$order['payment_status']]}</div>
                    <?php if (!empty($order['paid_at'])): ?>
                    <div><strong>Thanh toán:</strong> {$order['paid_at']}</div>
                    <?php endif; ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th class="text-right">Đơn giá</th>
                        <th class="text-right">SL</th>
                        <th class="text-right">Tạm tính</th>
                    </tr>
                </thead>
                <tbody>
                    $rows
                </tbody>
            </table>

            <div class="summary">
                <div class="summary-item">
                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <span>{$order['subtotal']} ₫</span>
                    </div>
                    <div class="summary-row">
                        <span>Phí vận chuyển:</span>
                        <span>{$order['shipping_fee']} ₫</span>
                    </div>
                    <div class="summary-row" style="color:green">
                        <span>Giảm giá:</span>
                        <span>-{$order['discount_amount']} ₫</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Tổng cộng:</span>
                        <span style="color:#dc3545">{$order['total_amount']} ₫</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($order['notes'])): ?>
            <div style="margin:20px 0;padding:12px;background:#f8f9fa;border-radius:4px">
                <strong>Ghi chú:</strong> {$order['notes']}
            </div>
            <?php endif; ?>

            <div class="footer">
                <p>Cảm ơn bạn đã mua hàng. Vui lòng kiểm tra email để nhận thông tin chi tiết.</p>
                <p style="margin-top:10px">Cơ sở dữ liệu in hóa đơn này được tạo vào {gmdate('d/m/Y H:i:s')}. Giấy tờ này chỉ mang tính chất tham khảo.</p>
            </div>
        </div>
    </body>
    </html>
    HTML;
}
