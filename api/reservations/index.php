<?php
/**
 * Reservations API Endpoint
 * Hotel Senang Hati - Reservation Management API
 */

require_once '../config/database.php';
require_once '../models/Reservation.php';
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

class ReservationAPI {
    private $reservation;
    
    public function __construct() {
        $this->reservation = new Reservation();
    }
    
    /**
     * Handle HTTP requests
     */
    public function handleRequest() {
        // Check authentication for all reservation management operations
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
            error_log("Reservation API Error: " . $e->getMessage());
            ApiResponse::error('Internal server error', 500);
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($pathParts) {
        // Check if specific reservation ID is requested
        if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
            $reservationId = intval($pathParts[2]);
            $reservation = $this->reservation->getById($reservationId);
            
            if ($reservation) {
                ApiResponse::success($reservation);
            } else {
                ApiResponse::error('Reservation not found', 404);
            }
        } else {
            // Get all reservations with optional filtering
            $filters = [];
            
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            
            if (isset($_GET['room_id']) && !empty($_GET['room_id'])) {
                $filters['room_id'] = intval($_GET['room_id']);
            }
            
            if (isset($_GET['guest_id']) && !empty($_GET['guest_id'])) {
                $filters['guest_id'] = intval($_GET['guest_id']);
            }
            
            if (isset($_GET['check_in_date']) && !empty($_GET['check_in_date'])) {
                $filters['check_in_date'] = $_GET['check_in_date'];
            }
            
            if (isset($_GET['check_out_date']) && !empty($_GET['check_out_date'])) {
                $filters['check_out_date'] = $_GET['check_out_date'];
            }
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            $reservations = $this->reservation->getAll($filters);
            
            // Get reservation statistics
            $stats = $this->reservation->getStatistics();
            
            ApiResponse::success([
                'reservations' => $reservations,
                'statistics' => $stats
            ]);
        }
    }
    
    /**
     * Handle POST requests (Create new reservation)
     */
    private function handlePost() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ApiResponse::validation(null, 'Invalid JSON data');
        }
        
        // Validate required fields
        $required = ['guest_id', 'room_id', 'check_in_date', 'check_out_date', 'total_amount'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                ApiResponse::validation(null, "Field '$field' is required");
            }
        }
        
        // Validate dates
        if (strtotime($input['check_in_date']) >= strtotime($input['check_out_date'])) {
            ApiResponse::validation(null, 'Check-out date must be after check-in date');
        }
        
        // Check room availability
        if (!$this->reservation->isRoomAvailable($input['room_id'], $input['check_in_date'], $input['check_out_date'])) {
            ApiResponse::validation(null, 'Room is not available for the selected dates');
        }
        
        // Prepare reservation data
        $reservationData = [
            'guest_id' => intval($input['guest_id']),
            'room_id' => intval($input['room_id']),
            'check_in_date' => $input['check_in_date'],
            'check_out_date' => $input['check_out_date'],
            'total_amount' => floatval($input['total_amount']),
            'status' => isset($input['status']) ? $input['status'] : 'confirmed',
            'special_requests' => isset($input['special_requests']) ? $input['special_requests'] : '',
            'payment_status' => isset($input['payment_status']) ? $input['payment_status'] : 'pending'
        ];
        
        $reservationId = $this->reservation->create($reservationData);
        
        if ($reservationId) {
            $newReservation = $this->reservation->getById($reservationId);
            ApiResponse::success($newReservation, 'Reservation created successfully');
        } else {
            ApiResponse::error('Failed to create reservation', 500);
        }
    }
    
    /**
     * Handle PUT requests (Update reservation)
     */
    private function handlePut($pathParts) {
        if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
            ApiResponse::validation(null, 'Reservation ID is required');
        }
        
        $reservationId = intval($pathParts[2]);
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ApiResponse::validation(null, 'Invalid JSON data');
        }
        
        // Check if reservation exists
        $existingReservation = $this->reservation->getById($reservationId);
        if (!$existingReservation) {
            ApiResponse::error('Reservation not found', 404);
        }
        
        // Validate dates if provided
        if (isset($input['check_in_date']) && isset($input['check_out_date'])) {
            if (strtotime($input['check_in_date']) >= strtotime($input['check_out_date'])) {
                ApiResponse::validation(null, 'Check-out date must be after check-in date');
            }
        }
        
        // Check room availability if room or dates are being changed
        if (isset($input['room_id']) || isset($input['check_in_date']) || isset($input['check_out_date'])) {
            $roomId = isset($input['room_id']) ? intval($input['room_id']) : $existingReservation['room_id'];
            $checkIn = isset($input['check_in_date']) ? $input['check_in_date'] : $existingReservation['check_in_date'];
            $checkOut = isset($input['check_out_date']) ? $input['check_out_date'] : $existingReservation['check_out_date'];
            
            if (!$this->reservation->isRoomAvailable($roomId, $checkIn, $checkOut, $reservationId)) {
                ApiResponse::validation(null, 'Room is not available for the selected dates');
            }
        }
        
        // Prepare update data
        $updateData = [];
        $allowedFields = ['guest_id', 'room_id', 'check_in_date', 'check_out_date', 'total_amount', 'status', 'special_requests', 'payment_status'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }
        
        // Convert numeric fields
        if (isset($updateData['guest_id'])) {
            $updateData['guest_id'] = intval($updateData['guest_id']);
        }
        if (isset($updateData['room_id'])) {
            $updateData['room_id'] = intval($updateData['room_id']);
        }
        if (isset($updateData['total_amount'])) {
            $updateData['total_amount'] = floatval($updateData['total_amount']);
        }
        
        if ($this->reservation->update($reservationId, $updateData)) {
            $updatedReservation = $this->reservation->getById($reservationId);
            ApiResponse::success($updatedReservation, 'Reservation updated successfully');
        } else {
            ApiResponse::error('Failed to update reservation', 500);
        }
    }
    
    /**
     * Handle DELETE requests
     */
    private function handleDelete($pathParts) {
        if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
            ApiResponse::validation(null, 'Reservation ID is required');
        }
        
        $reservationId = intval($pathParts[2]);
        
        // Check if reservation exists
        $reservation = $this->reservation->getById($reservationId);
        if (!$reservation) {
            ApiResponse::error('Reservation not found', 404);
        }
        
        // Check if reservation can be cancelled
        if ($reservation['status'] === 'checked_in' || $reservation['status'] === 'checked_out') {
            ApiResponse::validation(null, 'Cannot delete reservation that is already checked in or checked out');
        }
        
        if ($this->reservation->delete($reservationId)) {
            ApiResponse::success(null, 'Reservation deleted successfully');
        } else {
            ApiResponse::error('Failed to delete reservation', 500);
        }
    }
}

// Initialize and handle request
$reservationAPI = new ReservationAPI();
$reservationAPI->handleRequest();
?>
