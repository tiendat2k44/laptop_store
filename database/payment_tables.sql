/**
 * Payment System Database Tables
 * Các bảng hỗ trợ hệ thống thanh toán VNPay và MoMo
 */

-- =====================================================
-- 1. BẢNG CẤU HÌNH THANH TOÁN (payment_config)
-- =====================================================
CREATE TABLE IF NOT EXISTS `payment_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `config_name` VARCHAR(100) NOT NULL COMMENT 'Tên cấu hình',
  `config_key` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Khóa cấu hình (ví dụ: VNPAY_TMN_CODE)',
  `config_value` LONGTEXT NOT NULL COMMENT 'Giá trị cấu hình',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Lưu trữ cấu hình thanh toán VNPay và MoMo';

-- =====================================================
-- 2. BẢNG GHI LỊ SỬ GIAO DỊCH (payment_transactions)
-- =====================================================
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL COMMENT 'ID đơn hàng',
  `gateway` VARCHAR(20) NOT NULL COMMENT 'Cổng thanh toán (vnpay, momo)',
  `status` VARCHAR(20) NOT NULL COMMENT 'Trạng thái (pending, success, failed)',
  `transaction_id` VARCHAR(255) NOT NULL COMMENT 'ID giao dịch từ gateway',
  `amount` DECIMAL(12, 2) NOT NULL COMMENT 'Số tiền (VND)',
  `message` TEXT COMMENT 'Thông điệp kết quả',
  `ip_address` VARCHAR(50) COMMENT 'IP của người gửi request',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_gateway` (`gateway`),
  INDEX `idx_status` (`status`),
  INDEX `idx_transaction_id` (`transaction_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Ghi lại tất cả các giao dịch thanh toán';

-- =====================================================
-- 3. BẢNG THANH TOÁN (payments) - Tùy chọn
-- =====================================================
-- Nếu muốn lưu trữ chi tiết hơn, có thể thêm bảng này
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `gateway` VARCHAR(20) NOT NULL COMMENT 'Cổng thanh toán (vnpay, momo, cod)',
  `amount` DECIMAL(12, 2) NOT NULL,
  `status` VARCHAR(20) NOT NULL COMMENT 'pending, processing, success, failed, refunded',
  `transaction_id` VARCHAR(255) COMMENT 'Transaction ID từ gateway',
  `payment_date` DATETIME COMMENT 'Ngày thanh toán',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Chi tiết thanh toán';

-- =====================================================
-- 4. CẤU HÌNH MẶC ĐỊNH
-- =====================================================
-- Chèn cấu hình VNPay (SANDBOX)
INSERT IGNORE INTO `payment_config` (`config_name`, `config_key`, `config_value`)
VALUES (
  'VNPay Terminal Code',
  'VNPAY_TMN_CODE',
  '1XXXXX' -- Thay thế bằng code thực của bạn
);

INSERT IGNORE INTO `payment_config` (`config_name`, `config_key`, `config_value`)
VALUES (
  'VNPay Hash Secret',
  'VNPAY_HASH_SECRET',
  'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX' -- Thay thế bằng secret thực của bạn
);

INSERT IGNORE INTO `payment_config` (`config_name`, `config_key`, `config_value`)
VALUES (
  'VNPay URL',
  'VNPAY_URL',
  'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'
);

-- Chèn cấu hình MoMo (SANDBOX)
INSERT IGNORE INTO `payment_config` (`config_name`, `config_key`, `config_value`)
VALUES (
  'MoMo Partner Code',
  'MOMO_PARTNER_CODE',
  'MXXXXXXXX' -- Thay thế bằng code thực của bạn
);

INSERT IGNORE INTO `payment_config` (`config_name`, `config_key`, `config_value`)
VALUES (
  'MoMo Access Key',
  'MOMO_ACCESS_KEY',
  'XXXXXXXXXXXXXXXX' -- Thay thế bằng access key thực của bạn
);

INSERT IGNORE INTO `payment_config` (`config_name`, `config_key`, `config_value`)
VALUES (
  'MoMo Secret Key',
  'MOMO_SECRET_KEY',
  'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX' -- Thay thế bằng secret key thực của bạn
);

INSERT IGNORE INTO `payment_config` (`config_name`, `config_key`, `config_value`)
VALUES (
  'MoMo Endpoint',
  'MOMO_ENDPOINT',
  'https://test-payment.momo.vn/v2/gateway/api/create'
);

-- =====================================================
-- 5. LOGS FOLDER (Tùy chọn)
-- =====================================================
-- Tạo folder logs nếu chưa có:
-- mkdir -p /path/to/logs
-- chmod 777 /path/to/logs
