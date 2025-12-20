<?php
/**
 * Email templates builder
 */

function tpl_wrap($title, $contentHtml) {
    $style = 'font-family:Arial,Helvetica,sans-serif;color:#222;line-height:1.6;';
    $box = 'max-width:640px;margin:0 auto;border:1px solid #eee;border-radius:8px;overflow:hidden;';
    return "<div style='$style'><div style='$box'>"
        ."<div style=\"background:#0d6efd;color:#fff;padding:16px 20px;font-weight:700\">".escape(SITE_NAME)."</div>"
        ."<div style=\"padding:20px\"><h2 style=\"margin-top:0\">".escape($title)."</h2>".$contentHtml."</div>"
        ."<div style=\"background:#f8f9fa;color:#6c757d;padding:12px 20px;font-size:12px\">Đây là email tự động, vui lòng không trả lời.</div>"
        ."</div></div>";
}

function tpl_order_created($order, $items) {
    $rows = '';
    foreach ($items as $it) {
        $rows .= '<tr>'
              . '<td style="padding:8px 12px;border-bottom:1px solid #eee">'.escape($it['product_name']).'</td>'
              . '<td style="padding:8px 12px;border-bottom:1px solid #eee">'.(int)$it['quantity'].'</td>'
              . '<td style="padding:8px 12px;border-bottom:1px solid #eee;color:#dc3545;font-weight:700">'.formatPrice($it['subtotal']).'</td>'
              . '</tr>';
    }
    $content = '<p>Chào '.escape($order['recipient_name']).',</p>'
             . '<p>Đơn hàng '.escape($order['order_number']).' của bạn đã được tạo thành công.</p>'
             . '<table style="width:100%;border-collapse:collapse">'
             . '<thead><tr>'
             . '<th style="text-align:left;padding:8px 12px;border-bottom:2px solid #ddd">Sản phẩm</th>'
             . '<th style="text-align:left;padding:8px 12px;border-bottom:2px solid #ddd">SL</th>'
             . '<th style="text-align:left;padding:8px 12px;border-bottom:2px solid #ddd">Tạm tính</th>'
             . '</tr></thead><tbody>'.$rows.'</tbody></table>'
             . '<p style="margin-top:12px">Tổng thanh toán: <strong style="color:#dc3545">'.formatPrice($order['total_amount']).'</strong></p>'
             . '<p><a href="'.SITE_URL.'/account/order-detail.php?id='.(int)$order['id'].'" style="display:inline-block;padding:10px 16px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px">Xem đơn hàng</a></p>';
    return tpl_wrap('Đơn hàng đã tạo', $content);
}

function tpl_order_status_changed($order, $old, $new) {
    $content = '<p>Đơn hàng '.escape($order['order_number']).' đã thay đổi trạng thái từ <strong>'.escape($old).'</strong> sang <strong>'.escape($new).'</strong>.</p>'
             . '<p><a href="'.SITE_URL.'/account/order-detail.php?id='.(int)$order['id'].'">Xem chi tiết đơn</a></p>';
    return tpl_wrap('Cập nhật trạng thái đơn hàng', $content);
}

function tpl_payment_status_changed($order, $old, $new) {
    $content = '<p>Trạng thái thanh toán của đơn '.escape($order['order_number']).' đã thay đổi từ <strong>'.escape($old).'</strong> sang <strong>'.escape($new).'</strong>.</p>'
             . '<p><a href="'.SITE_URL.'/account/order-detail.php?id='.(int)$order['id'].'">Xem chi tiết đơn</a></p>';
    return tpl_wrap('Cập nhật thanh toán', $content);
}

function tpl_order_cancelled($order) {
    $content = '<p>Đơn hàng '.escape($order['order_number']).' của bạn đã được hủy.</p>'
             . (!empty($order['cancel_reason']) ? '<p>Lý do: '.escape($order['cancel_reason']).'</p>' : '')
             . '<p>Nếu có thắc mắc, vui lòng liên hệ hỗ trợ.</p>';
    return tpl_wrap('Đơn hàng đã hủy', $content);
}

function tpl_password_reset($user, $resetUrl) {
    $content = '<p>Chào '.escape($user['full_name'] ?? $user['email']).',</p>'
             . '<p>Bạn vừa yêu cầu đặt lại mật khẩu. Nhấp vào nút bên dưới để đặt mật khẩu mới (liên kết có hiệu lực 60 phút):</p>'
             . '<p><a href="'.escape($resetUrl).'" style="display:inline-block;padding:10px 16px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:6px">Đặt lại mật khẩu</a></p>'
             . '<p>Nếu bạn không yêu cầu, hãy bỏ qua email này.</p>';
    return tpl_wrap('Đặt lại mật khẩu', $content);
}
