<?php
/**
 * API Router
 * Hotel Senang Hati - Central API Routing
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON response header
header('Content-Type: application/json');

// Handle CORS
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

require_once 'config/database.php';

// Get request path and method
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$method = $_SERVER['REQUEST_METHOD'];

// Remove 'api' from path if present
if (isset($pathParts[0]) && $pathParts[0] === 'api') {
    array_shift($pathParts);
}

try {
    // Route to appropriate controller
    if (isset($pathParts[0])) {
        switch ($pathParts[0]) {
            case 'rooms':
                require_once 'controllers/RoomController.php';
                break;
                
            case 'reservations':
                require_once 'controllers/ReservationController.php';
                break;
                
            case 'guests':
                require_once 'controllers/GuestController.php';
                break;
                
            case 'payments':
                require_once 'controllers/PaymentController.php';
                break;
                
            case 'staff':
                require_once 'controllers/StaffController.php';
                break;
                
            case 'auth':
                require_once 'controllers/AuthController.php';
                break;
                
            case 'dashboard':
                require_once 'controllers/DashboardController.php';
                break;
                
            case 'test':
                // Test endpoint
                testDatabaseConnection();
                break;
                
            default:
                ApiResponse::notFound('API endpoint not found');
        }
    } else {
        // API root - show available endpoints
        showApiDocumentation();
    }
    
} catch (Exception $e) {
    error_log("API Router Error: " . $e->getMessage());
    ApiResponse::error('Internal server error', 500);
}

/**
 * Test database connection
 */
function testDatabaseConnection() {
    try {
        $db = Database::getInstance();
        $version = $db->getVersion();
        
        $response = [
            'status' => 'success',
            'message' => 'Database connection successful',
            'mysql_version' => $version,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        ApiResponse::success($response, 'Database test completed');
        
    } catch (Exception $e) {
        ApiResponse::error('Database connection failed: ' . $e->getMessage(), 500);
    }
}

/**
 * Show API documentation
 */
function showApiDocumentation() {
    $endpoints = [
        'Hotel Senang Hati API Documentation',
        '',
        'Base URL: /api',
        '',
        'Available Endpoints:',
        '',
        '=== ROOMS ===',
        'GET    /api/rooms                     - Get all rooms',
        'GET    /api/rooms/{id}                - Get room by ID',
        'GET    /api/rooms/available           - Check room availability',
        'GET    /api/rooms/occupancy           - Get occupancy statistics',
        'GET    /api/rooms/revenue             - Get revenue statistics',
        'GET    /api/rooms/{id}/maintenance    - Get maintenance history',
        'POST   /api/rooms                     - Create new room',
        'PUT    /api/rooms/{id}                - Update room',
        'PUT    /api/rooms/{id}/status         - Update room status',
        'DELETE /api/rooms/{id}                - Delete room',
        '',
        '=== RESERVATIONS ===',
        'GET    /api/reservations              - Get all reservations',
        'GET    /api/reservations/{id}         - Get reservation by ID',
        'GET    /api/reservations/checkins     - Get today\'s check-ins',
        'GET    /api/reservations/checkouts    - Get today\'s check-outs',
        'GET    /api/reservations/stats        - Get reservation statistics',
        'POST   /api/reservations              - Create new reservation',
        'PUT    /api/reservations/{id}         - Update reservation',
        'PUT    /api/reservations/{id}/status  - Update reservation status',
        'DELETE /api/reservations/{id}         - Cancel reservation',
        '',
        '=== GUESTS ===',
        'GET    /api/guests                    - Get all guests',
        'GET    /api/guests/{id}               - Get guest by ID',
        'GET    /api/guests/vip                - Get VIP guests',
        'GET    /api/guests/top                - Get top guests by spending',
        'GET    /api/guests/search             - Search guests',
        'GET    /api/guests/{id}/reservations  - Get guest reservation history',
        'GET    /api/guests/{id}/stats         - Get guest statistics',
        'POST   /api/guests                    - Create new guest',
        'PUT    /api/guests/{id}               - Update guest',
        'PUT    /api/guests/{id}/loyalty       - Update loyalty points',
        'DELETE /api/guests/{id}               - Delete guest',
        '',
        '=== AUTHENTICATION ===',
        'POST   /api/auth/login                - Staff login',
        'POST   /api/auth/logout               - Staff logout',
        'GET    /api/auth/profile              - Get current staff profile',
        '',
        '=== DASHBOARD ===',
        'GET    /api/dashboard/stats           - Get dashboard statistics',
        'GET    /api/dashboard/revenue         - Get revenue overview',
        'GET    /api/dashboard/occupancy       - Get occupancy overview',
        '',
        '=== TEST ===',
        'GET    /api/test                      - Test database connection',
        '',
        'Response Format:',
        '{',
        '  "success": true|false,',
        '  "message": "Response message",',
        '  "data": {...},',
        '  "timestamp": "2025-01-01 12:00:00"',
        '}',
        '',
        'Error Codes:',
        '200 - Success',
        '201 - Created',
        '400 - Bad Request',
        '401 - Unauthorized',
        '403 - Forbidden', 
        '404 - Not Found',
        '422 - Validation Error',
        '500 - Internal Server Error'
    ];
    
    $documentation = implode("\n", $endpoints);
    
    echo json_encode([
        'success' => true,
        'message' => 'Hotel Senang Hati API v1.0',
        'documentation' => $documentation,
        'endpoints_count' => 25,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}

?>
