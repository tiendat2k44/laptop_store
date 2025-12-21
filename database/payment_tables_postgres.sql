/**
 * Payment System Database Tables for PostgreSQL
 * Tạo các bảng hỗ trợ thanh toán VNPay và MoMo
 */

-- =====================================================
-- 1. BẢNG CẤU HÌNH THANH TOÁN (payment_config)
-- =====================================================
CREATE TABLE IF NOT EXISTS payment_config (
  id SERIAL PRIMARY KEY,
  config_name VARCHAR(100) NOT NULL,
  config_key VARCHAR(100) NOT NULL UNIQUE,
  config_value TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payment_config_key ON payment_config(config_key);

COMMENT ON TABLE payment_config IS 'Lưu trữ cấu hình thanh toán VNPay và MoMo';
COMMENT ON COLUMN payment_config.config_name IS 'Tên cấu hình';
COMMENT ON COLUMN payment_config.config_key IS 'Khóa cấu hình (ví dụ: VNPAY_TMN_CODE)';
COMMENT ON COLUMN payment_config.config_value IS 'Giá trị cấu hình';

-- =====================================================
-- 2. BẢNG GHI LỊ SỬ GIAO DỊCH (payment_transactions)
-- =====================================================
CREATE TABLE IF NOT EXISTS payment_transactions (
  id SERIAL PRIMARY KEY,
  order_id INTEGER NOT NULL,
  gateway VARCHAR(20) NOT NULL,
  status VARCHAR(20) NOT NULL,
  transaction_id VARCHAR(255) NOT NULL,
  amount DECIMAL(12, 2) NOT NULL,
  message TEXT,
  ip_address VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE INDEX idx_payment_txn_order ON payment_transactions(order_id);
CREATE INDEX idx_payment_txn_gateway ON payment_transactions(gateway);
CREATE INDEX idx_payment_txn_status ON payment_transactions(status);
CREATE INDEX idx_payment_txn_transaction_id ON payment_transactions(transaction_id);
CREATE INDEX idx_payment_txn_created_at ON payment_transactions(created_at);

COMMENT ON TABLE payment_transactions IS 'Ghi lại tất cả các giao dịch thanh toán';
COMMENT ON COLUMN payment_transactions.gateway IS 'Cổng thanh toán (vnpay, momo)';
COMMENT ON COLUMN payment_transactions.status IS 'Trạng thái (pending, success, failed)';
COMMENT ON COLUMN payment_transactions.transaction_id IS 'ID giao dịch từ gateway';
COMMENT ON COLUMN payment_transactions.amount IS 'Số tiền (VND)';

-- =====================================================
-- 3. INSERT DỮ LIỆU MẪU (nếu cần)
-- =====================================================
INSERT INTO payment_config (config_name, config_key, config_value) VALUES
('VNPay TMN Code', 'VNPAY_TMN_CODE', 'your_tmn_code_here'),
('VNPay Hash Secret', 'VNPAY_HASH_SECRET', 'your_hash_secret_here'),
('MoMo Partner Code', 'MOMO_PARTNER_CODE', 'your_partner_code_here'),
('MoMo Access Key', 'MOMO_ACCESS_KEY', 'your_access_key_here'),
('MoMo Secret Key', 'MOMO_SECRET_KEY', 'your_secret_key_here')
ON CONFLICT (config_key) DO NOTHING;

-- =====================================================
-- 4. KI ỂM TRA
-- =====================================================
SELECT 'Payment tables created successfully!' as message;
SELECT COUNT(*) as payment_config_count FROM payment_config;
SELECT COUNT(*) as payment_transactions_count FROM payment_transactions;
