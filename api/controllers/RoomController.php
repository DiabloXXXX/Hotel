<?php
/**
 * Room Controller
 * Hotel Senang Hati - Room API Endpoints
 */

require_once '../config/database.php';
require_once '../models/Room.php';

header('Content-Type: application/json');

class RoomController {
    private $room;
    
    public function __construct() {
        $this->room = new Room();
    }
    
    /**
     * Handle HTTP requests
     */
    public function handleRequest() {
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
            error_log("Room Controller Error: " . $e->getMessage());
            ApiResponse::error('Internal server error', 500);
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($pathParts) {
        // GET /api/rooms - Get all rooms
        if (count($pathParts) == 2 && $pathParts[1] == 'rooms') {
            $filters = [
                'room_type' => $_GET['room_type'] ?? null,
                'status' => $_GET['status'] ?? null,
                'capacity' => $_GET['capacity'] ?? null,
                'max_price' => $_GET['max_price'] ?? null
            ];
            
            $rooms = $this->room->getAllRooms(array_filter($filters));
            ApiResponse::success($rooms, 'Rooms retrieved successfully');
        }
        
        // GET /api/rooms/{id} - Get room by ID
        elseif (count($pathParts) == 3 && $pathParts[1] == 'rooms' && is_numeric($pathParts[2])) {
            $roomId = (int)$pathParts[2];
            $room = $this->room->getRoomById($roomId);
            
            if (!$room) {
                ApiResponse::notFound('Room not found');
            }
            
            ApiResponse::success($room, 'Room retrieved successfully');
        }
        
        // GET /api/rooms/available - Check availability
        elseif (count($pathParts) == 3 && $pathParts[1] == 'rooms' && $pathParts[2] == 'available') {
            $checkIn = $_GET['check_in'] ?? null;
            $checkOut = $_GET['check_out'] ?? null;
            $roomType = $_GET['room_type'] ?? null;
            $capacity = $_GET['capacity'] ?? null;
            
            if (!$checkIn || !$checkOut) {
                ApiResponse::validation(null, 'Check-in and check-out dates are required');
            }
            
            $availableRooms = $this->room->getAvailableRooms($checkIn, $checkOut, $roomType, $capacity);
            ApiResponse::success($availableRooms, 'Available rooms retrieved successfully');
        }
        
        // GET /api/rooms/occupancy - Get occupancy statistics
        elseif (count($pathParts) == 3 && $pathParts[1] == 'rooms' && $pathParts[2] == 'occupancy') {
            $stats = $this->room->getOccupancyStats();
            ApiResponse::success($stats, 'Occupancy statistics retrieved successfully');
        }
        
        // GET /api/rooms/revenue - Get revenue statistics
        elseif (count($pathParts) == 3 && $pathParts[1] == 'rooms' && $pathParts[2] == 'revenue') {
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $revenue = $this->room->getRoomRevenue($startDate, $endDate);
            ApiResponse::success($revenue, 'Revenue statistics retrieved successfully');
        }
        
        // GET /api/rooms/{id}/maintenance - Get maintenance history
        elseif (count($pathParts) == 4 && $pathParts[1] == 'rooms' && is_numeric($pathParts[2]) && $pathParts[3] == 'maintenance') {
            $roomId = (int)$pathParts[2];
            $maintenance = $this->room->getMaintenanceHistory($roomId);
            ApiResponse::success($maintenance, 'Maintenance history retrieved successfully');
        }
        
        else {
            ApiResponse::notFound('Endpoint not found');
        }
    }
    
    /**
     * Handle POST requests
     */
    private function handlePost() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ApiResponse::validation(null, 'Invalid JSON input');
        }
        
        // Create new room
        $roomId = $this->room->createRoom($input);
        $newRoom = $this->room->getRoomById($roomId);
        
        ApiResponse::success($newRoom, 'Room created successfully', 201);
    }
    
    /**
     * Handle PUT requests
     */
    private function handlePut($pathParts) {
        if (count($pathParts) < 3 || $pathParts[1] != 'rooms' || !is_numeric($pathParts[2])) {
            ApiResponse::notFound('Endpoint not found');
        }
        
        $roomId = (int)$pathParts[2];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ApiResponse::validation(null, 'Invalid JSON input');
        }
        
        // PUT /api/rooms/{id}/status - Update room status
        if (count($pathParts) == 4 && $pathParts[3] == 'status') {
            if (!isset($input['status'])) {
                ApiResponse::validation(null, 'Status is required');
            }
            
            $this->room->updateRoomStatus($roomId, $input['status']);
            $updatedRoom = $this->room->getRoomById($roomId);
            
            ApiResponse::success($updatedRoom, 'Room status updated successfully');
        }
        
        // PUT /api/rooms/{id} - Update room
        else {
            $this->room->updateRoom($roomId, $input);
            $updatedRoom = $this->room->getRoomById($roomId);
            
            ApiResponse::success($updatedRoom, 'Room updated successfully');
        }
    }
    
    /**
     * Handle DELETE requests
     */
    private function handleDelete($pathParts) {
        if (count($pathParts) != 3 || $pathParts[1] != 'rooms' || !is_numeric($pathParts[2])) {
            ApiResponse::notFound('Endpoint not found');
        }
        
        $roomId = (int)$pathParts[2];
        $this->room->deleteRoom($roomId);
        
        ApiResponse::success(null, 'Room deleted successfully');
    }
}

// Create controller and handle request
$controller = new RoomController();
$controller->handleRequest();

?>
