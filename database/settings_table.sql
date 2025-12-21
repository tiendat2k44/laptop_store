-- Create settings table for admin configuration
-- Run this if settings table doesn't exist

CREATE TABLE IF NOT EXISTS settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_settings_key ON settings(setting_key);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
    ('site_name', 'Laptop Store'),
    ('site_email', 'support@laptopstore.com'),
    ('items_per_page', '12'),
    ('enable_registration', '1'),
    ('enable_shop_registration', '1'),
    ('maintenance_mode', '0')
ON CONFLICT (setting_key) DO NOTHING;

-- For MySQL, use this instead:
/*
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_settings_key ON settings(setting_key);

INSERT INTO settings (setting_key, setting_value) VALUES
    ('site_name', 'Laptop Store'),
    ('site_email', 'support@laptopstore.com'),
    ('items_per_page', '12'),
    ('enable_registration', '1'),
    ('enable_shop_registration', '1'),
    ('maintenance_mode', '0')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
*/
