<?php
/**
 * Lớp Cơ Sở Dữ Liệu
 * Xử lý kết nối và thao tác cơ sở dữ liệu sử dụng PDO
 */

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Hàm khởi tạo private để ngăn khởi tạo trực tiếp
     */
    private function __construct() {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Lỗi Kết Nối Database: " . $e->getMessage());
            die("Kết nối cơ sở dữ liệu thất bại. Vui lòng thử lại sau.");
        }
    }
    
    /**
     * Lấy singleton instance
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Lấy kết nối PDO
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Thực thi truy vấn và trả về kết quả
     * @param string $sql Câu truy vấn SQL
     * @param array $params Tham số cho prepared statement
     * @return array|false
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Lỗi Truy Vấn: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * Thực thi truy vấn và trả về một dòng dữ liệu
     * @param string $sql Câu truy vấn SQL
     * @param array $params Tham số cho prepared statement
     * @return array|false
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Lỗi Truy Vấn: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * Thực thi câu lệnh insert/update/delete
     * @param string $sql Câu truy vấn SQL
     * @param array $params Tham số cho prepared statement
     * @return bool|int Trả về số dòng bị ảnh hưởng hoặc false nếu lỗi
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute($params);
            return $result ? $stmt->rowCount() : false;
        } catch (PDOException $e) {
            error_log("Lỗi Thực Thi: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * Chèn dữ liệu và trả về ID vừa chèn
     * @param string $sql Câu truy vấn SQL
     * @param array $params Tham số cho prepared statement
     * @return int|false ID vừa chèn hoặc false nếu lỗi
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log("Lỗi Chèn Dữ Liệu: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }
    
    /**
     * Bắt đầu giao dịch
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Xác nhận giao dịch
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Hủy bỏ giao dịch
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Escape chuỗi (tuy nhiên nên dùng prepared statements)
     * @param string $string
     * @return string
     */
    public function escape($string) {
        return $this->connection->quote($string);
    }
    
    /**
     * Ngăn chặn nhân bản
     */
    private function __clone() {}
    
    /**
     * Ngăn chặn deserialization
     */
    public function __wakeup() {
        throw new Exception("Không thể unserialize singleton");
    }
}
