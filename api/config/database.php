<?php
/**
 * Database Configuration
 * Hotel Senang Hati - MySQL Connection Settings
 */

class Database {
    // Database configuration
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'hotel_senang_hati';
    private const DB_USER = 'root';  // Change this to your MySQL username
    private const DB_PASS = '';     // Change this to your MySQL password
    private const DB_CHARSET = 'utf8mb4';
    
    private static $instance = null;
    private $pdo;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::DB_HOST,
                self::DB_NAME,
                self::DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->pdo = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            
            // Set timezone
            $this->pdo->exec("SET time_zone = '+07:00'");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $stmt = $this->pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Execute query with parameters
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch single row
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert data and return last insert ID
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update data
     */
    public function update($table, $data, $conditions) {
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClause);
        
        $whereClause = [];
        foreach ($conditions as $key => $value) {
            $whereClause[] = "{$key} = :where_{$key}";
            $data["where_{$key}"] = $value;
        }
        $whereClause = implode(' AND ', $whereClause);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
        $stmt = $this->query($sql, $data);
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete data
     */
    public function delete($table, $conditions) {
        $whereClause = [];
        foreach ($conditions as $key => $value) {
            $whereClause[] = "{$key} = :{$key}";
        }
        $whereClause = implode(' AND ', $whereClause);
        
        $sql = "UPDATE {$table} SET {$whereClause}";
        $stmt = $this->query($sql, $conditions);
        
        return $stmt->rowCount();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($tableName) {
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get database version
     */
    public function getVersion() {
        try {
            $stmt = $this->pdo->query("SELECT VERSION() as version");
            $result = $stmt->fetch();
            return $result['version'];
        } catch (PDOException $e) {
            return 'Unknown';
        }
    }
}

/**
 * Response helper class
 */
class ApiResponse {
    
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    public static function error($message = 'An error occurred', $code = 400, $errors = null) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }
    
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, 401);
    }
    
    public static function forbidden($message = 'Access forbidden') {
        self::error($message, 403);
    }
    
    public static function validation($errors, $message = 'Validation failed') {
        self::error($message, 422, $errors);
    }
}

/**
 * Input validation helper
 */
class Validator {
    
    public static function required($value, $field) {
        if (empty($value) && $value !== '0') {
            throw new InvalidArgumentException("Field '{$field}' is required");
        }
        return true;
    }
    
    public static function email($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format");
        }
        return true;
    }
    
    public static function date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        if (!$d || $d->format($format) !== $date) {
            throw new InvalidArgumentException("Invalid date format");
        }
        return true;
    }
    
    public static function numeric($value, $field) {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException("Field '{$field}' must be numeric");
        }
        return true;
    }
    
    public static function length($value, $min, $max, $field) {
        $length = strlen($value);
        if ($length < $min || $length > $max) {
            throw new InvalidArgumentException("Field '{$field}' must be between {$min} and {$max} characters");
        }
        return true;
    }
    
    public static function in($value, $allowed, $field) {
        if (!in_array($value, $allowed)) {
            throw new InvalidArgumentException("Field '{$field}' must be one of: " . implode(', ', $allowed));
        }
        return true;
    }
}

/**
 * Authentication helper
 */
class Auth {
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    public static function validateSession() {
        session_start();
        if (!isset($_SESSION['staff_id'])) {
            ApiResponse::unauthorized('Please login to access this resource');
        }
        return $_SESSION;
    }
    
    public static function hasPermission($permission) {
        $session = self::validateSession();
        $permissions = json_decode($session['permissions'] ?? '{}', true);
        
        return isset($permissions['all']) || isset($permissions[$permission]);
    }
}

// Set JSON response header
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

header('Content-Type: application/json');

?>
