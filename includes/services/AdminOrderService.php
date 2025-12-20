<?php

class AdminOrderService {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }

    public function listOrders($filters = [], $limit = 20, $offset = 0) {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'o.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['keyword'])) {
            $where[] = '(o.order_number ILIKE :kw OR o.recipient_name ILIKE :kw OR o.recipient_phone ILIKE :kw)';
            $params['kw'] = '%' . $filters['keyword'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'o.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'o.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $sql = "SELECT o.id, o.order_number, o.total_amount, o.status, o.payment_status, o.created_at,
                       u.full_name AS customer_name, u.email AS customer_email
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY o.created_at DESC
                LIMIT :limit OFFSET :offset";
        $params['limit'] = (int)$limit;
        $params['offset'] = (int)$offset;
        return $this->db->query($sql, $params);
    }

    public function countOrders($filters = []) {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['keyword'])) {
            $where[] = '(order_number ILIKE :kw OR recipient_name ILIKE :kw OR recipient_phone ILIKE :kw)';
            $params['kw'] = '%' . $filters['keyword'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        $row = $this->db->queryOne("SELECT COUNT(*) AS cnt FROM orders WHERE " . implode(' AND ', $where), $params);
        return $row ? (int)$row['cnt'] : 0;
    }

    public function getCountsByStatus() {
        $rows = $this->db->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status");
        $counts = [
            'all' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'processing' => 0,
            'shipping' => 0,
            'delivered' => 0,
            'cancelled' => 0,
        ];
        foreach ($rows as $r) {
            $counts[$r['status']] = (int)$r['cnt'];
            $counts['all'] += (int)$r['cnt'];
        }
        return $counts;
    }

    public function getOrder($orderId) {
        return $this->db->queryOne(
            "SELECT o.*, u.full_name AS customer_name, u.email AS customer_email
             FROM orders o JOIN users u ON o.user_id = u.id
             WHERE o.id = :id",
            ['id' => $orderId]
        );
    }

    public function getOrderItems($orderId) {
        return $this->db->query(
            "SELECT * FROM order_items WHERE order_id = :oid ORDER BY id",
            ['oid' => $orderId]
        );
    }

    public function updateStatus($orderId, $newStatus) {
        $allowed = ['pending','confirmed','processing','shipping','delivered','cancelled'];
        if (!in_array($newStatus, $allowed, true)) {
            throw new Exception('Trạng thái không hợp lệ');
        }
        $order = $this->getOrder($orderId);
        if (!$order) return false;
        if ($newStatus === 'cancelled') {
            $ok = $this->cancelOrder($orderId);
            return $ok;
        }
        $ok = $this->db->execute(
            "UPDATE orders SET status = :st, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
            ['st' => $newStatus, 'id' => $orderId]
        );
        if ($ok) {
            try {
                $body = tpl_order_status_changed(array_merge($order, ['id' => $orderId]), $order['status'], $newStatus);
                @send_mail($order['customer_email'], '[".SITE_NAME."] Cập nhật trạng thái '. $order['order_number'], $body);
            } catch (Throwable $e) {
                error_log('AdminOrderService::mail updateStatus - ' . $e->getMessage());
            }
        }
        return $ok;
    }

    public function updatePaymentStatus($orderId, $newStatus, $transactionId = null) {
        $allowed = ['pending','paid','failed','refunded'];
        if (!in_array($newStatus, $allowed, true)) {
            throw new Exception('Trạng thái thanh toán không hợp lệ');
        }
        $order = $this->getOrder($orderId);
        if (!$order) return false;
        $old = $order['payment_status'];
        $params = ['ps' => $newStatus, 'id' => $orderId];
        $set = 'payment_status = :ps, updated_at = CURRENT_TIMESTAMP';
        if ($transactionId !== null && $transactionId !== '') {
            $set .= ', payment_transaction_id = :tid';
            $params['tid'] = $transactionId;
        }
        if ($newStatus === 'paid') {
            $set .= ', paid_at = CURRENT_TIMESTAMP';
        }
        $ok = $this->db->execute("UPDATE orders SET $set WHERE id = :id", $params);
        if ($ok) {
            try {
                $order['payment_status'] = $newStatus;
                $body = tpl_payment_status_changed(array_merge($order, ['id' => $orderId]), $old, $newStatus);
                @send_mail($order['customer_email'], '[".SITE_NAME."] Cập nhật thanh toán '. $order['order_number'], $body);
            } catch (Throwable $e) {
                error_log('AdminOrderService::mail paymentStatus - ' . $e->getMessage());
            }
        }
        return $ok;
    }

    public function cancelOrder($orderId, $reason = null) {
        try {
            $order = $this->getOrder($orderId);
            if (!$order) throw new Exception('Đơn hàng không tồn tại');

            $this->db->beginTransaction();

            // hoàn kho
            $items = $this->db->query("SELECT product_id, quantity FROM order_items WHERE order_id = :oid", ['oid' => $orderId]);
            foreach ($items as $it) {
                $this->db->execute(
                    "UPDATE products SET stock_quantity = stock_quantity + :qty, sold_count = GREATEST(sold_count - :qty, 0) WHERE id = :pid",
                    ['qty' => (int)$it['quantity'], 'pid' => (int)$it['product_id']]
                );
            }

            // cập nhật trạng thái
            $this->db->execute("UPDATE order_items SET status = 'cancelled' WHERE order_id = :oid", ['oid' => $orderId]);
            $this->db->execute(
                "UPDATE orders SET status = 'cancelled', cancel_reason = COALESCE(:reason, cancel_reason), updated_at = CURRENT_TIMESTAMP WHERE id = :oid",
                ['reason' => $reason, 'oid' => $orderId]
            );

            $this->db->commit();

            // Email thông báo hủy
            try {
                $order['cancel_reason'] = $reason ?? $order['cancel_reason'];
                $body = tpl_order_cancelled(array_merge($order, ['id' => $orderId]));
                @send_mail($order['customer_email'], '[".SITE_NAME."] Đơn hàng đã hủy '. $order['order_number'], $body);
            } catch (Throwable $e) {
                error_log('AdminOrderService::mail cancelOrder - ' . $e->getMessage());
            }

            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('AdminOrderService::cancelOrder - ' . $e->getMessage());
            return false;
        }
    }
}

?>
