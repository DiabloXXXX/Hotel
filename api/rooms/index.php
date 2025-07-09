<?php
/**
 * Rooms API Endpoint
 * Hotel Senang Hati - Room Management API
 */

require_once '../config/database.php';
require_once '../models/Room.php';
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

class RoomAPI {
    private $room;
    
    public function __construct() {
        $this->room = new Room();
    }
    
    /**
     * Handle HTTP requests
     */
    public function handleRequest() {
        // Check authentication for all room management operations
        checkAuth();
        
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
            error_log("Room API Error: " . $e->getMessage());
            ApiResponse::error('Internal server error', 500);
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($pathParts) {
        // Check if specific room ID is requested
        if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
            $roomId = intval($pathParts[2]);
            $room = $this->room->getById($roomId);
            
            if ($room) {
                ApiResponse::success($room);
            } else {
                ApiResponse::error('Room not found', 404);
            }
        } else {
            // Get all rooms with optional filtering
            $filters = [];
            
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            
            if (isset($_GET['type']) && !empty($_GET['type'])) {
                $filters['type'] = $_GET['type'];
            }
            
            if (isset($_GET['floor']) && !empty($_GET['floor'])) {
                $filters['floor'] = intval($_GET['floor']);
            }
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            $rooms = $this->room->getAll($filters);
            
            // Get room statistics
            $stats = $this->room->getStatistics();
            
            ApiResponse::success([
                'rooms' => $rooms,
                'statistics' => $stats
            ]);
        }
    }
    
    /**
     * Handle POST requests (Create new room)
     */
    private function handlePost() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ApiResponse::validation(null, 'Invalid JSON data');
        }
        
        // Validate required fields
        $required = ['room_number', 'type', 'floor', 'capacity', 'price_per_night'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                ApiResponse::validation(null, "Field '$field' is required");
            }
        }
        
        // Validate room number uniqueness
        if ($this->room->roomNumberExists($input['room_number'])) {
            ApiResponse::validation(null, 'Room number already exists');
        }
        
        // Prepare room data
        $roomData = [
            'room_number' => $input['room_number'],
            'type' => $input['type'],
            'floor' => intval($input['floor']),
            'capacity' => intval($input['capacity']),
            'price_per_night' => floatval($input['price_per_night']),
            'status' => isset($input['status']) ? $input['status'] : 'available',
            'amenities' => isset($input['amenities']) ? $input['amenities'] : '',
            'description' => isset($input['description']) ? $input['description'] : ''
        ];
        
        $roomId = $this->room->create($roomData);
        
        if ($roomId) {
            $newRoom = $this->room->getById($roomId);
            ApiResponse::success($newRoom, 'Room created successfully');
        } else {
            ApiResponse::error('Failed to create room', 500);
        }
    }
    
    /**
     * Handle PUT requests (Update room)
     */
    private function handlePut($pathParts) {
        if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
            ApiResponse::validation(null, 'Room ID is required');
        }
        
        $roomId = intval($pathParts[2]);
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ApiResponse::validation(null, 'Invalid JSON data');
        }
        
        // Check if room exists
        $existingRoom = $this->room->getById($roomId);
        if (!$existingRoom) {
            ApiResponse::error('Room not found', 404);
        }
        
        // Validate room number uniqueness (if changed)
        if (isset($input['room_number']) && $input['room_number'] !== $existingRoom['room_number']) {
            if ($this->room->roomNumberExists($input['room_number'])) {
                ApiResponse::validation(null, 'Room number already exists');
            }
        }
        
        // Prepare update data
        $updateData = [];
        $allowedFields = ['room_number', 'type', 'floor', 'capacity', 'price_per_night', 'status', 'amenities', 'description'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }
        
        // Convert numeric fields
        if (isset($updateData['floor'])) {
            $updateData['floor'] = intval($updateData['floor']);
        }
        if (isset($updateData['capacity'])) {
            $updateData['capacity'] = intval($updateData['capacity']);
        }
        if (isset($updateData['price_per_night'])) {
            $updateData['price_per_night'] = floatval($updateData['price_per_night']);
        }
        
        if ($this->room->update($roomId, $updateData)) {
            $updatedRoom = $this->room->getById($roomId);
            ApiResponse::success($updatedRoom, 'Room updated successfully');
        } else {
            ApiResponse::error('Failed to update room', 500);
        }
    }
    
    /**
     * Handle DELETE requests
     */
    private function handleDelete($pathParts) {
        if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
            ApiResponse::validation(null, 'Room ID is required');
        }
        
        $roomId = intval($pathParts[2]);
        
        // Check if room exists
        $room = $this->room->getById($roomId);
        if (!$room) {
            ApiResponse::error('Room not found', 404);
        }
        
        // Check if room has active reservations
        if ($this->room->hasActiveReservations($roomId)) {
            ApiResponse::validation(null, 'Cannot delete room with active reservations');
        }
        
        if ($this->room->delete($roomId)) {
            ApiResponse::success(null, 'Room deleted successfully');
        } else {
            ApiResponse::error('Failed to delete room', 500);
        }
    }
}

// Initialize and handle request
$roomAPI = new RoomAPI();
$roomAPI->handleRequest();
?>
