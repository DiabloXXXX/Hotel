<?php
/**
 * Guest Controller
 * Hotel Senang Hati - Guest API Endpoints
 */

require_once '../config/database.php';
require_once '../models/Guest.php';

class GuestController {
    private $guest;
    
    public function __construct() {
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
            error_log("Guest Controller Error: " . $e->getMessage());
            ApiResponse::error('Internal server error', 500);
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($pathParts) {
        // GET /api/guests - Get all guests
        if (count($pathParts) == 2 && $pathParts[1] == 'guests') {
            $filters = [
                'search' => $_GET['search'] ?? null,
                'nationality' => $_GET['nationality'] ?? null,
                'is_vip' => $_GET['is_vip'] ?? null
            ];
            
            $guests = $this->guest->getAllGuests(array_filter($filters));
            ApiResponse::success($guests, 'Guests retrieved successfully');
        }
        
        // GET /api/guests/{id} - Get guest by ID
        elseif (count($pathParts) == 3 && $pathParts[1] == 'guests' && is_numeric($pathParts[2])) {
            $guestId = (int)$pathParts[2];
            $guest = $this->guest->getGuestById($guestId);
            
            if (!$guest) {
                ApiResponse::notFound('Guest not found');
            }
            
            ApiResponse::success($guest, 'Guest retrieved successfully');
        }
        
        // GET /api/guests/email/{email} - Get guest by email
        elseif (count($pathParts) == 4 && $pathParts[1] == 'guests' && $pathParts[2] == 'email') {
            $email = urldecode($pathParts[3]);
            $guest = $this->guest->getGuestByEmail($email);
            
            if (!$guest) {
                ApiResponse::notFound('Guest not found');
            }
            
            ApiResponse::success($guest, 'Guest retrieved successfully');
        }
        
        // GET /api/guests/vip - Get VIP guests
        elseif (count($pathParts) == 3 && $pathParts[1] == 'guests' && $pathParts[2] == 'vip') {
            $vipGuests = $this->guest->getVipGuests();
            ApiResponse::success($vipGuests, 'VIP guests retrieved successfully');
        }
        
        // GET /api/guests/top - Get top guests by spending
        elseif (count($pathParts) == 3 && $pathParts[1] == 'guests' && $pathParts[2] == 'top') {
            $limit = $_GET['limit'] ?? 10;
            $topGuests = $this->guest->getTopGuests($limit);
            ApiResponse::success($topGuests, 'Top guests retrieved successfully');
        }
        
        // GET /api/guests/search - Search guests
        elseif (count($pathParts) == 3 && $pathParts[1] == 'guests' && $pathParts[2] == 'search') {
            $query = $_GET['q'] ?? '';
            if (empty($query)) {
                ApiResponse::validation(null, 'Search query is required');
            }
            
            $results = $this->guest->searchGuests($query);
            ApiResponse::success($results, 'Search results retrieved successfully');
        }
        
        // GET /api/guests/{id}/reservations - Get guest reservation history
        elseif (count($pathParts) == 4 && $pathParts[1] == 'guests' && is_numeric($pathParts[2]) && $pathParts[3] == 'reservations') {
            $guestId = (int)$pathParts[2];
            $reservations = $this->guest->getGuestReservations($guestId);
            ApiResponse::success($reservations, 'Guest reservations retrieved successfully');
        }
        
        // GET /api/guests/{id}/stats - Get guest statistics
        elseif (count($pathParts) == 4 && $pathParts[1] == 'guests' && is_numeric($pathParts[2]) && $pathParts[3] == 'stats') {
            $guestId = (int)$pathParts[2];
            $stats = $this->guest->getGuestStats($guestId);
            ApiResponse::success($stats, 'Guest statistics retrieved successfully');
        }
        
        // GET /api/guests/nationality/stats - Get nationality statistics
        elseif (count($pathParts) == 4 && $pathParts[1] == 'guests' && $pathParts[2] == 'nationality' && $pathParts[3] == 'stats') {
            $stats = $this->guest->getNationalityStats();
            ApiResponse::success($stats, 'Nationality statistics retrieved successfully');
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
        
        // Create new guest
        $guestId = $this->guest->createGuest($input);
        $newGuest = $this->guest->getGuestById($guestId);
        
        ApiResponse::success($newGuest, 'Guest created successfully', 201);
    }
    
    /**
     * Handle PUT requests
     */
    private function handlePut($pathParts) {
        if (count($pathParts) < 3 || $pathParts[1] != 'guests' || !is_numeric($pathParts[2])) {
            ApiResponse::notFound('Endpoint not found');
        }
        
        $guestId = (int)$pathParts[2];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ApiResponse::validation(null, 'Invalid JSON input');
        }
        
        // PUT /api/guests/{id}/loyalty - Update loyalty points
        if (count($pathParts) == 4 && $pathParts[3] == 'loyalty') {
            if (!isset($input['action']) || !isset($input['points'])) {
                ApiResponse::validation(null, 'Action and points are required');
            }
            
            $action = $input['action']; // 'add' or 'deduct'
            $points = (int)$input['points'];
            $reason = $input['reason'] ?? null;
            
            if ($action === 'add') {
                $this->guest->addLoyaltyPoints($guestId, $points, $reason);
                $message = 'Loyalty points added successfully';
            } elseif ($action === 'deduct') {
                $this->guest->deductLoyaltyPoints($guestId, $points, $reason);
                $message = 'Loyalty points deducted successfully';
            } else {
                ApiResponse::validation(null, 'Invalid action. Use "add" or "deduct"');
            }
            
            $updatedGuest = $this->guest->getGuestById($guestId);
            ApiResponse::success($updatedGuest, $message);
        }
        
        // PUT /api/guests/{id}/preferences - Update guest preferences
        elseif (count($pathParts) == 4 && $pathParts[3] == 'preferences') {
            if (!isset($input['preferences'])) {
                ApiResponse::validation(null, 'Preferences are required');
            }
            
            $this->guest->updateGuestPreferences($guestId, $input['preferences']);
            $updatedGuest = $this->guest->getGuestById($guestId);
            
            ApiResponse::success($updatedGuest, 'Guest preferences updated successfully');
        }
        
        // PUT /api/guests/{id} - Update guest
        else {
            $this->guest->updateGuest($guestId, $input);
            $updatedGuest = $this->guest->getGuestById($guestId);
            
            ApiResponse::success($updatedGuest, 'Guest updated successfully');
        }
    }
    
    /**
     * Handle DELETE requests
     */
    private function handleDelete($pathParts) {
        if (count($pathParts) != 3 || $pathParts[1] != 'guests' || !is_numeric($pathParts[2])) {
            ApiResponse::notFound('Endpoint not found');
        }
        
        $guestId = (int)$pathParts[2];
        $this->guest->deleteGuest($guestId);
        
        ApiResponse::success(null, 'Guest deleted successfully');
    }
}

// Create controller and handle request
$controller = new GuestController();
$controller->handleRequest();

?>
