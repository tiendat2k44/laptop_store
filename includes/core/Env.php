<?php
/**
 * Environment Variable Loader
 * Loads configuration from .env file safely
 * 
 * Lợi ích:
 * - Credentials không hardcode trong code
 * - Dễ dàng thay đổi config giữa dev/staging/production
 * - Tránh commit sensitive data lên git
 */

class Env {
    private static $loaded = false;
    private static $values = [];

    /**
     * Load .env file
     * 
     * @param string $path Path to .env file
     * @return void
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }

        if ($path === null) {
            $path = dirname(dirname(dirname(__FILE__))) . '/.env';
        }

        // If .env doesn't exist, use defaults (for development)
        if (!file_exists($path)) {
            error_log("Warning: .env file not found at $path");
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Loại bỏ dấu ngoặc nếu có
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }

                self::$values[$key] = $value;
                // Cũng đặt làm biến môi trường
                putenv("$key=$value");
            }
        }

        self::$loaded = true;
    }

    /**
     * Lấy biến môi trường
     * 
     * @param string $key Tên biến
     * @param string $default Giá trị mặc định nếu không tìm thấy
     * @return mixed
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }

        // Kiểm tra xem có tồn tại trong các giá trị đã tải
        if (isset(self::$values[$key])) {
            return self::$values[$key];
        }

        // Kiểm tra trong $_ENV
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        // Kiểm tra trong $_SERVER
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        // Sử dụng getenv làm fallback
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        // Trả về mặc định
        return $default;
    }

    /**
     * Get required variable (throw if not found)
     * 
     * @param string $key Variable name
     * @return string
     * @throws Exception
     */
    public static function require($key) {
        $value = self::get($key);
        if ($value === null) {
            throw new Exception("Required environment variable not set: $key");
        }
        return $value;
    }

    /**
     * Check if variable is set
     * 
     * @param string $key Variable name
     * @return bool
     */
    public static function has($key) {
        return self::get($key) !== null;
    }
}

// Auto-load .env on include
Env::load();
?>
