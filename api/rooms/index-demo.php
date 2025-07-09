<?php
// api/rooms/index-demo.php - Room Management API (Demo Version - Tanpa Database)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Cek autentikasi session
session_start();
if (!isset($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Silakan login terlebih dahulu.'
    ]);
    exit;
}

class RoomDemoAPI {
    private $demoRooms;
    
    public function __construct() {
        // Data demo rooms - normaly akan dari database
        $this->demoRooms = [
            [
                'id' => 1,
                'room_number' => '101',
                'type' => 'standard',
                'floor' => 1,
                'capacity' => 2,
                'price_per_night' => 500000,
                'status' => 'available',
                'amenities' => 'WiFi, AC, TV, Mini Bar',
                'description' => 'Kamar standard dengan fasilitas lengkap',
                'last_cleaned' => '2025-07-09',
                'created_at' => '2025-01-01 00:00:00'
            ],
            [
                'id' => 2,
                'room_number' => '102',
                'type' => 'standard',
                'floor' => 1,
                'capacity' => 2,
                'price_per_night' => 500000,
                'status' => 'occupied',
                'amenities' => 'WiFi, AC, TV, Mini Bar',
                'description' => 'Kamar standard dengan fasilitas lengkap',
                'last_cleaned' => '2025-07-08',
                'created_at' => '2025-01-01 00:00:00'
            ],
            [
                'id' => 3,
                'room_number' => '201',
                'type' => 'deluxe',
                'floor' => 2,
                'capacity' => 3,
                'price_per_night' => 750000,
                'status' => 'available',
                'amenities' => 'WiFi, AC, TV, Mini Bar, Balcony',
                'description' => 'Kamar deluxe dengan pemandangan kota',
                'last_cleaned' => '2025-07-09',
                'created_at' => '2025-01-01 00:00:00'
            ],
            [
                'id' => 4,
                'room_number' => '301',
                'type' => 'suite',
                'floor' => 3,
                'capacity' => 4,
                'price_per_night' => 1200000,
                'status' => 'maintenance',
                'amenities' => 'WiFi, AC, TV, Mini Bar, Jacuzzi, Living Room',
                'description' => 'Suite mewah dengan ruang tamu terpisah',
                'last_cleaned' => '2025-07-07',
                'created_at' => '2025-01-01 00:00:00'
            ],
            [
                'id' => 5,
                'room_number' => '401',
                'type' => 'presidential',
                'floor' => 4,
                'capacity' => 6,
                'price_per_night' => 2500000,
                'status' => 'available',
                'amenities' => 'WiFi, AC, TV, Mini Bar, Jacuzzi, Living Room, Kitchen, Butler Service',
                'description' => 'Presidential suite dengan layanan butler',
                'last_cleaned' => '2025-07-09',
                'created_at' => '2025-01-01 00:00:00'
            ]
        ];
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet();
                    break;
                case 'POST':
                    $this->handlePost();
                    break;
                case 'PUT':
                    $this->handlePut();
                    break;
                case 'DELETE':
                    $this->handleDelete();
                    break;
                default:
                    $this->error('Method tidak diizinkan', 405);
            }
        } catch (Exception $e) {
            error_log("Room Demo API Error: " . $e->getMessage());
            $this->error('Terjadi kesalahan sistem', 500);
        }
    }
    
    private function handleGet() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        // Check if specific room ID is requested
        if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
            $roomId = intval($pathParts[2]);
            $room = $this->getRoomById($roomId);
            
            if ($room) {
                $this->success($room, 'Data kamar berhasil dimuat');
            } else {
                $this->error('Kamar tidak ditemukan', 404);
            }
        } else {
            // Get all rooms with optional filtering
            $rooms = $this->getAllRooms();
            $stats = $this->getRoomStatistics();
            
            $this->success([
                'rooms' => $rooms,
                'statistics' => $stats
            ], 'Data kamar berhasil dimuat');
        }
    }
    
    private function handlePost() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $this->error('Data JSON tidak valid', 400);
        }
        
        // Validate required fields
        $required = ['room_number', 'type', 'floor', 'capacity', 'price_per_night'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                $this->error("Field '$field' harus diisi", 400);
            }
        }
        
        // Check if room number exists
        if ($this->roomNumberExists($input['room_number'])) {
            $this->error('Nomor kamar sudah ada', 400);
        }
        
        // Create new room (demo - tidak menyimpan ke database)
        $newRoom = [
            'id' => count($this->demoRooms) + 1,
            'room_number' => $input['room_number'],
            'type' => $input['type'],
            'floor' => intval($input['floor']),
            'capacity' => intval($input['capacity']),
            'price_per_night' => floatval($input['price_per_night']),
            'status' => isset($input['status']) ? $input['status'] : 'available',
            'amenities' => isset($input['amenities']) ? $input['amenities'] : '',
            'description' => isset($input['description']) ? $input['description'] : '',
            'last_cleaned' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->success($newRoom, 'Kamar berhasil ditambahkan');
    }
    
    private function handlePut() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
            $this->error('ID kamar harus disertakan', 400);
        }
        
        $roomId = intval($pathParts[2]);
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $this->error('Data JSON tidak valid', 400);
        }
        
        $room = $this->getRoomById($roomId);
        if (!$room) {
            $this->error('Kamar tidak ditemukan', 404);
        }
        
        // Update room data (demo)
        $updatedRoom = array_merge($room, $input);
        $updatedRoom['updated_at'] = date('Y-m-d H:i:s');
        
        $this->success($updatedRoom, 'Kamar berhasil diperbarui');
    }
    
    private function handleDelete() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        if (!isset($pathParts[2]) || !is_numeric($pathParts[2])) {
            $this->error('ID kamar harus disertakan', 400);
        }
        
        $roomId = intval($pathParts[2]);
        $room = $this->getRoomById($roomId);
        
        if (!$room) {
            $this->error('Kamar tidak ditemukan', 404);
        }
        
        $this->success(null, 'Kamar berhasil dihapus');
    }
    
    private function getAllRooms() {
        $rooms = $this->demoRooms;
        
        // Apply filters if provided
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $rooms = array_filter($rooms, function($room) {
                return $room['status'] === $_GET['status'];
            });
        }
        
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $rooms = array_filter($rooms, function($room) {
                return $room['type'] === $_GET['type'];
            });
        }
        
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = strtolower($_GET['search']);
            $rooms = array_filter($rooms, function($room) use ($search) {
                return strpos(strtolower($room['room_number']), $search) !== false ||
                       strpos(strtolower($room['type']), $search) !== false;
            });
        }
        
        return array_values($rooms);
    }
    
    private function getRoomById($id) {
        foreach ($this->demoRooms as $room) {
            if ($room['id'] == $id) {
                return $room;
            }
        }
        return null;
    }
    
    private function getRoomStatistics() {
        $total = count($this->demoRooms);
        $available = count(array_filter($this->demoRooms, function($room) {
            return $room['status'] === 'available';
        }));
        $occupied = count(array_filter($this->demoRooms, function($room) {
            return $room['status'] === 'occupied';
        }));
        $maintenance = count(array_filter($this->demoRooms, function($room) {
            return $room['status'] === 'maintenance';
        }));
        
        return [
            'total_rooms' => $total,
            'available_rooms' => $available,
            'occupied_rooms' => $occupied,
            'maintenance_rooms' => $maintenance
        ];
    }
    
    private function roomNumberExists($roomNumber) {
        foreach ($this->demoRooms as $room) {
            if ($room['room_number'] === $roomNumber) {
                return true;
            }
        }
        return false;
    }
    
    private function success($data = null, $message = 'Sukses') {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
    
    private function error($message = 'Error', $code = 500) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
}

// Initialize and handle request
$roomAPI = new RoomDemoAPI();
$roomAPI->handleRequest();
?>
