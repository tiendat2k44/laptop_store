<?php

class AddressService {
    private $db;
    private $userId;
    
    public function __construct($database, $userId) {
        $this->db = $database;
        $this->userId = $userId;
    }
    
    /**
     * Lấy tất cả địa chỉ của người dùng
     */
    public function getAddresses() {
        return $this->db->query(
            "SELECT id, recipient_name, phone, address_line, city, district, ward, is_default, created_at
             FROM addresses
             WHERE user_id = :uid
             ORDER BY is_default DESC, created_at DESC",
            ['uid' => $this->userId]
        );
    }
    
    /**
     * Lấy một địa chỉ
     */
    public function getAddress($id) {
        return $this->db->queryOne(
            "SELECT * FROM addresses WHERE id = :id AND user_id = :uid",
            ['id' => (int)$id, 'uid' => $this->userId]
        );
    }
    
    /**
     * Thêm địa chỉ mới
     */
    public function addAddress($data) {
        $cols = [];
        $vals = [];
        $params = ['uid' => $this->userId];
        
        foreach (['recipient_name', 'phone', 'address_line', 'city', 'district', 'ward'] as $k) {
            if (!empty($data[$k])) {
                $cols[] = $k;
                $vals[] = ':' . $k;
                $params[$k] = $data[$k];
            }
        }
        
        if (empty($cols)) return null;
        
        // Nếu đây là địa chỉ đầu tiên, set is_default = true
        $count = $this->db->queryOne(
            "SELECT COUNT(*) as c FROM addresses WHERE user_id = :uid",
            ['uid' => $this->userId]
        );
        $isDefault = ((int)($count['c'] ?? 0)) === 0 ? 1 : (int)($data['is_default'] ?? 0);
        
        // Nếu set is_default = true, unset các địa chỉ khác
        if ($isDefault) {
            $this->db->execute(
                "UPDATE addresses SET is_default = FALSE WHERE user_id = :uid",
                ['uid' => $this->userId]
            );
        }
        
        $cols[] = 'user_id';
        $vals[] = ':uid';
        $cols[] = 'is_default';
        $vals[] = ':is_default';
        $params['is_default'] = $isDefault;
        
        $id = $this->db->insert(
            "INSERT INTO addresses (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ") RETURNING id",
            $params
        );
        return $id;
    }
    
    /**
     * Cập nhật địa chỉ
     */
    public function updateAddress($id, $data) {
        $addr = $this->getAddress($id);
        if (!$addr) return false;
        
        $set = [];
        $params = ['id' => (int)$id, 'uid' => $this->userId];
        
        foreach (['recipient_name', 'phone', 'address_line', 'city', 'district', 'ward'] as $k) {
            if (isset($data[$k])) {
                $set[] = "$k = :$k";
                $params[$k] = $data[$k] ?? '';
            }
        }
        
        if (isset($data['is_default'])) {
            $set[] = 'is_default = :is_default';
            $params['is_default'] = (int)($data['is_default'] ?? 0);
            // Nếu set default, unset những cái khác
            if ($data['is_default']) {
                $this->db->execute(
                    "UPDATE addresses SET is_default = FALSE WHERE user_id = :uid AND id != :id",
                    ['uid' => $this->userId, 'id' => (int)$id]
                );
            }
        }
        
        if (empty($set)) return true;
        
        $set[] = 'updated_at = CURRENT_TIMESTAMP';
        return $this->db->execute(
            "UPDATE addresses SET " . implode(',', $set) . " WHERE id = :id AND user_id = :uid",
            $params
        );
    }
    
    /**
     * Xóa địa chỉ
     */
    public function deleteAddress($id) {
        return $this->db->execute(
            "DELETE FROM addresses WHERE id = :id AND user_id = :uid",
            ['id' => (int)$id, 'uid' => $this->userId]
        );
    }
    
    /**
     * Lấy địa chỉ mặc định
     */
    public function getDefaultAddress() {
        return $this->db->queryOne(
            "SELECT * FROM addresses WHERE user_id = :uid AND is_default = TRUE LIMIT 1",
            ['uid' => $this->userId]
        );
    }
}
