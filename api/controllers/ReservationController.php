<?php
/**
 * Reservation Controller
 * Hotel Senang Hati - Reservation API Endpoints
 */

require_once '../config/database.php';
require_once '../models/Reservation.php';
require_once '../models/Guest.php';

class ReservationController {
    private $reservation;
    private $guest;
    
    public function __construct() {
        $this->reservation = new Reservation();
        $this->guest = new Guest();
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
            error_log("Reservation Controller Error: " . $e->getMessage());
            ApiResponse::error('Internal server error', 500);
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($pathParts) {
        // GET /api/reservations - Get all reservations
        if (count($pathParts) == 2 && $pathParts[1] == 'reservations') {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'check_in_from' => $_GET['check_in_from'] ?? null,
                'check_in_to' => $_GET['check_in_to'] ?? null,
                'guest_email' => $_GET['guest_email'] ?? null,
                'reservation_code' => $_GET['reservation_code'] ?? null
            ];
            
            $reservations = $this->reservation->getAllReservations(array_filter($filters));
            ApiResponse::success($reservations, 'Reservations retrieved successfully');
        }
        
        // GET /api/reservations/{id} - Get reservation by ID
        elseif (count($pathParts) == 3 && $pathParts[1] == 'reservations' && is_numeric($pathParts[2])) {
            $reservationId = (int)$pathParts[2];
            $reservation = $this->reservation->getReservationById($reservationId);
            
            if (!$reservation) {
                ApiResponse::notFound('Reservation not found');
            }
            
            ApiResponse::success($reservation, 'Reservation retrieved successfully');
        }
        
        // GET /api/reservations/code/{code} - Get reservation by code
        elseif (count($pathParts) == 4 && $pathParts[1] == 'reservations' && $pathParts[2] == 'code') {
            $reservationCode = $pathParts[3];
            $reservation = $this->reservation->getReservationByCode($reservationCode);
            
            if (!$reservation) {
                ApiResponse::notFound('Reservation not found');
            }
            
            ApiResponse::success($reservation, 'Reservation retrieved successfully');
        }
        
        // GET /api/reservations/checkins - Get today's check-ins
        elseif (count($pathParts) == 3 && $pathParts[1] == 'reservations' && $pathParts[2] == 'checkins') {
            $checkins = $this->reservation->getTodayCheckIns();
            ApiResponse::success($checkins, "Today's check-ins retrieved successfully");
        }
        
        // GET /api/reservations/checkouts - Get today's check-outs
        elseif (count($pathParts) == 3 && $pathParts[1] == 'reservations' && $pathParts[2] == 'checkouts') {
            $checkouts = $this->reservation->getTodayCheckOuts();
            ApiResponse::success($checkouts, "Today's check-outs retrieved successfully");
        }
        
        // GET /api/reservations/stats - Get reservation statistics
        elseif (count($pathParts) == 3 && $pathParts[1] == 'reservations' && $pathParts[2] == 'stats') {
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $stats = $this->reservation->getReservationStats($startDate, $endDate);
            ApiResponse::success($stats, 'Reservation statistics retrieved successfully');
        }
        
        // GET /api/reservations/revenue/monthly - Get monthly revenue
        elseif (count($pathParts) == 4 && $pathParts[1] == 'reservations' && $pathParts[2] == 'revenue' && $pathParts[3] == 'monthly') {
            $year = $_GET['year'] ?? date('Y');
            $revenue = $this->reservation->getMonthlyRevenue($year);
            ApiResponse::success($revenue, 'Monthly revenue retrieved successfully');
        }
        
        else {
            ApiResponse::notFound('Endpoint not found');
        }
    }
    
    /**
     * Handle POST requests
     */
    private function handlePost() {
        // Support both JSON and form-data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            // Convert form-data to array structure expected by model
            $input = $_POST;
            // Optionally, handle file uploads here if needed
        }

        if (!$input) {
            ApiResponse::validation(null, 'Invalid input');
        }

        // If guest doesn't exist, create new guest
        if (!isset($input['guest_id']) && (isset($input['guest']) || isset($input['email']))) {
            try {
                // If guest data is nested (from JSON), use it; else build from flat POST
                $guestData = $input['guest'] ?? [
                    'nama' => ($input['firstName'] ?? '') . ' ' . ($input['lastName'] ?? ''),
                    'email' => $input['email'] ?? '',
                    'no_hp' => $input['phone'] ?? ''
                ];
                $guestId = $this->guest->createGuest($guestData);
                $input['guest_id'] = $guestId;
            } catch (Exception $e) {
                // Guest might already exist, try to find by email
                $email = $guestData['email'] ?? ($input['email'] ?? null);
                if ($email) {
                    $existingGuest = $this->guest->getGuestByEmail($email);
                    if ($existingGuest) {
                        $input['guest_id'] = $existingGuest['guest_id'];
                    } else {
                        throw $e;
                    }
                } else {
                    throw $e;
                }
            }
        }

        // Create reservation
        $reservationId = $this->reservation->createReservation($input);
        $newReservation = $this->reservation->getReservationById($reservationId);

        ApiResponse::success($newReservation, 'Reservation created successfully', 201);
    }
    
    /**
     * Handle PUT requests
     */
    private function handlePut($pathParts) {
        if (count($pathParts) < 3 || $pathParts[1] != 'reservations' || !is_numeric($pathParts[2])) {
            ApiResponse::notFound('Endpoint not found');
        }
        
        $reservationId = (int)$pathParts[2];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ApiResponse::validation(null, 'Invalid JSON input');
        }
        
        // PUT /api/reservations/{id}/status - Update reservation status
        if (count($pathParts) == 4 && $pathParts[3] == 'status') {
            if (!isset($input['status'])) {
                ApiResponse::validation(null, 'Status is required');
            }
            
            $notes = $input['notes'] ?? null;
            $this->reservation->updateReservationStatus($reservationId, $input['status'], $notes);
            $updatedReservation = $this->reservation->getReservationById($reservationId);
            
            ApiResponse::success($updatedReservation, 'Reservation status updated successfully');
        }
        
        // PUT /api/reservations/{id}/cancel - Cancel reservation
        else if (count($pathParts) == 4 && $pathParts[3] == 'cancel') {
            $reason = $input['reason'] ?? null;
            $this->reservation->cancelReservation($reservationId, $reason);
            $updatedReservation = $this->reservation->getReservationById($reservationId);
            
            ApiResponse::success($updatedReservation, 'Reservation cancelled successfully');
        }
        
        // PUT /api/reservations/{id} - Update reservation
        else {
            $this->reservation->updateReservation($reservationId, $input);
            $updatedReservation = $this->reservation->getReservationById($reservationId);
            
            ApiResponse::success($updatedReservation, 'Reservation updated successfully');
        }
    }
    
    /**
     * Handle DELETE requests
     */
    private function handleDelete($pathParts) {
        if (count($pathParts) != 3 || $pathParts[1] != 'reservations' || !is_numeric($pathParts[2])) {
            ApiResponse::notFound('Endpoint not found');
        }
        
        $reservationId = (int)$pathParts[2];
        $this->reservation->cancelReservation($reservationId, 'Deleted by admin');
        
        ApiResponse::success(null, 'Reservation cancelled successfully');
    }
}

// Create controller and handle request
$controller = new ReservationController();
$controller->handleRequest();

?>
