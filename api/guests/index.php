<?php
/**
 * Guests API Endpoint
 * Hotel Senang Hati - Guest Management API
 */

require_once '../config/database.php';
require_once '../models/Guest.php';
require_once '../auth/middleware.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class ApiResponse {
    public static function success($data = null, $message = 'Success') {
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
    
    public static function error($message = 'Error', $code = 500) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
        exit;
    }
    
    public static function validation($data = null, $message = 'Validation error') {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}

class GuestAPI {
    private $guest;
    
    public function __construct() {
        $this->guest = new Guest();
    }
    
    /**
     * Handle HTTP requests
     */
    public function handleRequest() {
        // Check authentication for all guest management operations
        // checkAuth();
        
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet($pathParts);
                    break;
                case 'POST':
                    $this->handlePost();
                    break;
                case 'PUT':
                    $this->handlePut($pathParts);
                    break;
                case 'DELETE':
                    $this->handleDelete($pathParts);
                    break;
                default:
                    ApiResponse::error('Method not allowed', 405);
            }
        } catch (InvalidArgumentException $e) {
            ApiResponse::validation(null, $e->getMessage());
        } catch (Exception $e) {
            error_log("Guest API Error: " . $e->getMessage());
            ApiResponse::error('Internal server error', 500);
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($pathParts) {
        // Check if specific guest ID is requested
        if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
            $guestId = intval($pathParts[2]);
            $guest = $this->guest->getById($guestId);
            
            if ($guest) {
                ApiResponse::success($guest);
            } else {
                ApiResponse::error('Guest not found', 404);
            }
        } else {
            // Get all guests with optional filtering
            $filters = [];
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            if (isset($_GET['email']) && !empty($_GET['email'])) {
                $filters['email'] = $_GET['email'];
            }
            
            $guests = $this->guest->getAll($filters);
            
            // Get guest statistics
            $stats = $this->guest->getStatistics();
            
            ApiResponse::success([
                'guests' => $guests,
                'statistics' => $stats
            ]);
        }
    }
    
    /**
     * Handle POST requests (Create new guest)
     */
    private function handlePost() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ApiResponse::validation(null, 'Invalid JSON data');
        }
        
        // Validate required fields
        $required = ['first_name', 'last_name', 'email', 'phone'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                ApiResponse::validation(null, "Field '$field' is required");
            }
        }
        
        // Validate email format
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            ApiResponse::validation(null, 'Invalid email format');
        }
        
        // Check if email already exists
        if ($this->guest->emailExists($input['email'])) {
            ApiResponse::validation(null, 'Email already exists');
        }
        
        // Prepare guest data
        $guestData = [
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'address' => isset($input['address']) ? $input['address'] : '',
            'city' => isset($input['city']) ? $input['city'] : '',
            'country' => isset($input['country']) ? $input['country'] : '',
            'id_number' => isset($input['id_number']) ? $input['id_number'] : '',
            'date_of_birth' => isset($input['date_of_birth']) ? $input['date_of_birth'] : null
        ];
        
        $guestId = $this->guest->create($guestData);
        
        if ($guestId) {
            $newGuest = $this->guest->getById($guestId);
            ApiResponse::success($newGuest, 'Guest created successfully');
        } else {
            ApiResponse::error('Failed to create guest', 500);
        }
    }
    
    /**
     * Handle PUT requests (Update guest)
     */
    private function handlePut($pathParts) {
        if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
            ApiResponse::validation(null, 'Guest ID is required');
        }
        
        $guestId = intval($pathParts[2]);
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ApiResponse::validation(null, 'Invalid JSON data');
        }
        
        // Check if guest exists
        $existingGuest = $this->guest->getById($guestId);
        if (!$existingGuest) {
            ApiResponse::error('Guest not found', 404);
        }
        
        // Validate email format if provided
        if (isset($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            ApiResponse::validation(null, 'Invalid email format');
        }
        
        // Check email uniqueness (if changed)
        if (isset($input['email']) && $input['email'] !== $existingGuest['email']) {
            if ($this->guest->emailExists($input['email'])) {
                ApiResponse::validation(null, 'Email already exists');
            }
        }
        
        // Prepare update data
        $updateData = [];
        $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'country', 'id_number', 'date_of_birth'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }
        
        if ($this->guest->update($guestId, $updateData)) {
            $updatedGuest = $this->guest->getById($guestId);
            ApiResponse::success($updatedGuest, 'Guest updated successfully');
        } else {
            ApiResponse::error('Failed to update guest', 500);
        }
    }
    
    /**
     * Handle DELETE requests
     */
    private function handleDelete($pathParts) {
        if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
            ApiResponse::validation(null, 'Guest ID is required');
        }
        
        $guestId = intval($pathParts[2]);
        
        // Check if guest exists
        $guest = $this->guest->getById($guestId);
        if (!$guest) {
            ApiResponse::error('Guest not found', 404);
        }
        
        // Check if guest has active reservations
        if ($this->guest->hasActiveReservations($guestId)) {
            ApiResponse::validation(null, 'Cannot delete guest with active reservations');
        }
        
        if ($this->guest->delete($guestId)) {
            ApiResponse::success(null, 'Guest deleted successfully');
        } else {
            ApiResponse::error('Failed to delete guest', 500);
        }
    }
}

// Initialize and handle request
$guestAPI = new GuestAPI();
$guestAPI->handleRequest();
?>
