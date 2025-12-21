<?php
/**
 * Rate Limiter - Chống brute force attacks
 * 
 * Sử dụng filesystem hoặc APCu (cache nhanh trong memory)
 * Nếu có Redis, dùng Redis sẽ tốt hơn
 * 
 * Usage:
 * $limiter = new RateLimiter('login_' . $_SERVER['REMOTE_ADDR']);
 * if (!$limiter->isAllowed(5, 300)) {  // 5 attempts in 300 seconds
 *     die('Too many attempts. Try again later.');
 * }
 */

class RateLimiter {
    private $identifier;
    private $useApc;

    /**
     * Constructor
     * 
     * @param string $identifier Unique identifier (user IP, email, etc.)
     */
    public function __construct($identifier) {
        $this->identifier = hash('sha256', $identifier);
        $this->useApc = function_exists('apcu_store');
    }

    /**
     * Check if action is allowed
     * 
     * @param int $limit Maximum attempts allowed
     * @param int $window Time window in seconds
     * @return bool
     */
    public function isAllowed($limit = 5, $window = 300) {
        if ($this->useApc) {
            return $this->checkWithApc($limit, $window);
        } else {
            return $this->checkWithFilesystem($limit, $window);
        }
    }

    /**
     * Check with APCu (faster, in-memory)
     * 
     * @param int $limit
     * @param int $window
     * @return bool
     */
    private function checkWithApc($limit, $window) {
        $key = 'rate_limit_' . $this->identifier;
        $current = apcu_fetch($key);

        if ($current === false) {
            apcu_store($key, 1, $window);
            return true;
        }

        if ($current < $limit) {
            apcu_inc($key);
            return true;
        }

        return false;
    }

    /**
     * Check with Filesystem (fallback)
     * 
     * @param int $limit
     * @param int $window
     * @return bool
     */
    private function checkWithFilesystem($limit, $window) {
        $dir = sys_get_temp_dir() . '/rate_limit';
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $file = $dir . '/' . $this->identifier . '.json';
        $now = time();

        // Read existing data
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            
            // Check if window expired
            if ($now - $data['first_attempt'] > $window) {
                // Window expired, reset
                $data = ['count' => 1, 'first_attempt' => $now];
            } else {
                // Window still active
                if ($data['count'] < $limit) {
                    $data['count']++;
                } else {
                    return false;
                }
            }
        } else {
            // First attempt
            $data = ['count' => 1, 'first_attempt' => $now];
        }

        // Write back
        file_put_contents($file, json_encode($data), LOCK_EX);
        return true;
    }

    /**
     * Reset counter for identifier
     * 
     * @return void
     */
    public function reset() {
        if ($this->useApc) {
            apcu_delete('rate_limit_' . $this->identifier);
        } else {
            $dir = sys_get_temp_dir() . '/rate_limit';
            $file = $dir . '/' . $this->identifier . '.json';
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Get remaining attempts
     * 
     * @param int $limit Maximum attempts
     * @param int $window Time window
     * @return int
     */
    public function getRemainingAttempts($limit = 5, $window = 300) {
        if ($this->useApc) {
            $key = 'rate_limit_' . $this->identifier;
            $current = apcu_fetch($key);
            if ($current === false) {
                return $limit;
            }
            return max(0, $limit - $current);
        } else {
            $dir = sys_get_temp_dir() . '/rate_limit';
            $file = $dir . '/' . $this->identifier . '.json';
            $now = time();

            if (!file_exists($file)) {
                return $limit;
            }

            $data = json_decode(file_get_contents($file), true);
            
            // Check if window expired
            if ($now - $data['first_attempt'] > $window) {
                return $limit;
            }

            return max(0, $limit - $data['count']);
        }
    }
}

?>
