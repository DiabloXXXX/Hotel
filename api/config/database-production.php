<?php
// api/config/database.php - Updated for Production Deployment

// Load environment variables from .env or system environment
function loadEnv() {
    $envFile = __DIR__ . '/../../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }
}

// Load environment variables
loadEnv();

// Database configuration with fallbacks
$host = $_ENV['DB_HOST'] ?? $_ENV['PLANETSCALE_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? $_ENV['PLANETSCALE_DATABASE'] ?? 'hotel_senang_hati';
$username = $_ENV['DB_USER'] ?? $_ENV['PLANETSCALE_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? $_ENV['PLANETSCALE_PASSWORD'] ?? '';
$port = $_ENV['DB_PORT'] ?? 3306;
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

// SSL configuration for production databases
$ssl_options = [];
if (isset($_ENV['PLANETSCALE_HOST']) || isset($_ENV['RAILWAY_MYSQL_URL'])) {
    $ssl_options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    $ssl_options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/cert.pem';
}

try {
    // Database connection
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_PERSISTENT => false
    ];
    
    // Add SSL options if available
    $options = array_merge($options, $ssl_options);
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Set timezone
    $pdo->exec("SET time_zone = '+07:00'");
    
    // Success message (for development only)
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        error_log("Database connected successfully to {$host}:{$port}/{$dbname}");
    }
    
} catch (PDOException $e) {
    // Log error securely
    error_log("Database connection failed: " . $e->getMessage());
    
    // Different error handling for production vs development
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Database connection failed. Please try again later.");
    }
}

// Database helper functions
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
}

// Global database instance
$database = Database::getInstance();

// Export PDO connection for backward compatibility
return $pdo;
?>
