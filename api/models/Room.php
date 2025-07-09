<?php
/**
 * Room Model
 * Hotel Senang Hati - Room Management
 */

require_once '../config/database.php';

class Room {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all rooms with optional filters
     */
    public function getAll($filters = []) {
        $sql = "SELECT r.*, 
                CASE 
                    WHEN r.last_cleaned IS NULL THEN 'Never'
                    ELSE DATE_FORMAT(r.last_cleaned, '%Y-%m-%d')
                END as last_cleaned_formatted
                FROM rooms r WHERE 1=1";
        $params = [];
        
        if (!empty($filters['type'])) {
            $sql .= " AND r.type = :type";
            $params['type'] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['floor'])) {
            $sql .= " AND r.floor = :floor";
            $params['floor'] = $filters['floor'];
        }
        
        if (!empty($filters['capacity'])) {
            $sql .= " AND r.capacity >= :capacity";
            $params['capacity'] = $filters['capacity'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (r.room_number LIKE :search OR r.type LIKE :search OR r.description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY r.room_number";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get all rooms (legacy method for compatibility)
     */
    public function getAllRooms($filters = []) {
        return $this->getAll($filters);
    }
    
    /**
     * Get room by ID
     */
    public function getById($roomId) {
        $sql = "SELECT r.*, 
                CASE 
                    WHEN r.last_cleaned IS NULL THEN 'Never'
                    ELSE DATE_FORMAT(r.last_cleaned, '%Y-%m-%d')
                END as last_cleaned_formatted
                FROM rooms r WHERE r.room_id = :room_id";
        return $this->db->fetch($sql, ['room_id' => $roomId]);
    }
      /**
     * Get room by room number
     */
    public function getRoomByNumber($roomNumber) {
        $sql = "SELECT * FROM rooms WHERE room_number = :room_number";
        return $this->db->fetch($sql, ['room_number' => $roomNumber]);
    }

    /**
     * Check if room number exists
     */
    public function roomNumberExists($roomNumber) {
        $sql = "SELECT COUNT(*) as count FROM rooms WHERE room_number = :room_number";
        $result = $this->db->fetch($sql, ['room_number' => $roomNumber]);
        return $result['count'] > 0;
    }

    /**
     * Create new room
     */
    public function create($data) {
        $sql = "INSERT INTO rooms (room_number, type, floor, capacity, price_per_night, status, amenities, description, created_at) 
                VALUES (:room_number, :type, :floor, :capacity, :price_per_night, :status, :amenities, :description, NOW())";
        
        $stmt = $this->db->query($sql, $data);
        if ($stmt) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Update room
     */
    public function update($roomId, $data) {
        $setParts = [];
        $params = ['room_id' => $roomId];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
            $params[$key] = $value;
        }
        
        if (empty($setParts)) {
            return false;
        }
        
        $sql = "UPDATE rooms SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE room_id = :room_id";
        
        $stmt = $this->db->query($sql, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Delete room
     */
    public function delete($roomId) {
        $sql = "DELETE FROM rooms WHERE room_id = :room_id";
        $stmt = $this->db->query($sql, ['room_id' => $roomId]);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Check if room has active reservations
     */
    public function hasActiveReservations($roomId) {
        $sql = "SELECT COUNT(*) as count FROM reservations 
                WHERE room_id = :room_id 
                AND status IN ('confirmed', 'checked_in') 
                AND check_out_date >= CURDATE()";
        $result = $this->db->fetch($sql, ['room_id' => $roomId]);
        return $result['count'] > 0;
    }

    /**
     * Get room statistics
     */
    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_rooms,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms,
                    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms,
                    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms,
                    SUM(CASE WHEN status = 'cleaning' THEN 1 ELSE 0 END) as cleaning_rooms,
                    SUM(CASE WHEN status = 'out-of-order' THEN 1 ELSE 0 END) as out_of_order_rooms
                FROM rooms";
        
        return $this->db->fetch($sql);
    }

    /**
     * Check room availability for specific dates
     */
    public function checkAvailability($checkIn, $checkOut, $roomType = null, $capacity = null) {
        $sql = "CALL CheckRoomAvailability(:check_in, :check_out, :room_type)";
        $params = [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'room_type' => $roomType
        ];
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get available rooms for specific dates and criteria
     */
    public function getAvailableRooms($checkIn, $checkOut, $roomType = null, $capacity = null) {
        $sql = "
            SELECT r.* 
            FROM rooms r 
            WHERE r.status = 'available'
        ";
        
        $params = [];
        
        if ($roomType) {
            $sql .= " AND r.room_type = :room_type";
            $params['room_type'] = $roomType;
        }
        
        if ($capacity) {
            $sql .= " AND r.capacity >= :capacity";
            $params['capacity'] = $capacity;
        }
        
        $sql .= "
            AND r.room_id NOT IN (
                SELECT res.room_id 
                FROM reservations res 
                WHERE res.status IN ('confirmed', 'checked_in')
                AND (
                    (res.check_in_date <= :check_in AND res.check_out_date > :check_in)
                    OR (res.check_in_date < :check_out AND res.check_out_date >= :check_out)
                    OR (res.check_in_date >= :check_in AND res.check_out_date <= :check_out)
                )
            )
            ORDER BY r.room_type, r.room_number
        ";
        
        $params['check_in'] = $checkIn;
        $params['check_out'] = $checkOut;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get rooms by type
     */
    public function getRoomsByType($roomType) {
        $sql = "SELECT * FROM rooms WHERE room_type = :room_type ORDER BY room_number";
        return $this->db->fetchAll($sql, ['room_type' => $roomType]);
    }
    
    /**
     * Update room status
     */
    public function updateRoomStatus($roomId, $status) {
        $allowedStatuses = ['available', 'occupied', 'maintenance', 'cleaning'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new InvalidArgumentException("Invalid room status");
        }
        
        $sql = "UPDATE rooms SET status = :status, updated_at = NOW() WHERE room_id = :room_id";
        return $this->db->query($sql, [
            'status' => $status,
            'room_id' => $roomId
        ]);
    }
    
    /**
     * Create new room
     */
    public function create($roomData) {
        $sql = "INSERT INTO rooms (room_number, type, floor, capacity, price_per_night, status, amenities, description, created_at) 
                VALUES (:room_number, :type, :floor, :capacity, :price_per_night, :status, :amenities, :description, NOW())";
        
        $params = [
            'room_number' => $roomData['room_number'],
            'type' => $roomData['type'],
            'floor' => $roomData['floor'],
            'capacity' => $roomData['capacity'],
            'price_per_night' => $roomData['price_per_night'],
            'status' => $roomData['status'],
            'amenities' => $roomData['amenities'],
            'description' => $roomData['description']
        ];
        
        if ($this->db->execute($sql, $params)) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    /**
     * Update room
     */
    public function update($roomId, $updateData) {
        $setParts = [];
        $params = ['room_id' => $roomId];
        
        $allowedFields = ['room_number', 'type', 'floor', 'capacity', 'price_per_night', 'status', 'amenities', 'description'];
        
        foreach ($allowedFields as $field) {
            if (isset($updateData[$field])) {
                $setParts[] = "$field = :$field";
                $params[$field] = $updateData[$field];
            }
        }
        
        if (empty($setParts)) {
            return false;
        }
        
        $sql = "UPDATE rooms SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE room_id = :room_id";
        
        return $this->db->execute($sql, $params);
    }
    
    /**
     * Delete room
     */
    public function delete($roomId) {
        $sql = "DELETE FROM rooms WHERE room_id = :room_id";
        return $this->db->execute($sql, ['room_id' => $roomId]);
    }
    
    /**
     * Check if room number exists
     */
    public function roomNumberExists($roomNumber) {
        $sql = "SELECT COUNT(*) as count FROM rooms WHERE room_number = :room_number";
        $result = $this->db->fetch($sql, ['room_number' => $roomNumber]);
        return $result['count'] > 0;
    }
    
    /**
     * Check if room has active reservations
     */
    public function hasActiveReservations($roomId) {
        $sql = "SELECT COUNT(*) as count FROM reservations 
                WHERE room_id = :room_id AND status IN ('confirmed', 'checked_in')";
        $result = $this->db->fetch($sql, ['room_id' => $roomId]);
        return $result['count'] > 0;
    }
    
    /**
     * Get room statistics
     */
    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_rooms,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms,
                    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms,
                    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms,
                    SUM(CASE WHEN status = 'cleaning' THEN 1 ELSE 0 END) as cleaning_rooms,
                    SUM(CASE WHEN status = 'out-of-order' THEN 1 ELSE 0 END) as out_of_order_rooms
                FROM rooms";
        
        return $this->db->fetch($sql);
    }
    
    /**
     * Mark room as cleaned
     */
    public function markCleaned($roomId) {
        $sql = "UPDATE rooms SET last_cleaned = NOW(), 
                status = CASE WHEN status = 'cleaning' THEN 'available' ELSE status END 
                WHERE room_id = :room_id";
        return $this->db->execute($sql, ['room_id' => $roomId]);
    }
}

?>
