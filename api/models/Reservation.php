<?php
/**
 * Reservation Model
 * Hotel Senang Hati - Reservation Management
 */

require_once '../config/database.php';

class Reservation {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all reservations with optional filters
     */
    public function getAll($filters = []) {
        $sql = "
            SELECT 
                r.*,
                g.first_name, g.last_name, g.email, g.phone,
                rm.room_number, rm.type as room_type,
                DATE_FORMAT(r.check_in_date, '%Y-%m-%d') as check_in_formatted,
                DATE_FORMAT(r.check_out_date, '%Y-%m-%d') as check_out_formatted,
                DATEDIFF(r.check_out_date, r.check_in_date) as nights,
                r.total_amount
            FROM reservations r
            JOIN guests g ON r.guest_id = g.guest_id
            JOIN rooms rm ON r.room_id = rm.room_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['room_id'])) {
            $sql .= " AND r.room_id = :room_id";
            $params['room_id'] = $filters['room_id'];
        }
        
        if (!empty($filters['guest_id'])) {
            $sql .= " AND r.guest_id = :guest_id";
            $params['guest_id'] = $filters['guest_id'];
        }
        
        if (!empty($filters['check_in_date'])) {
            $sql .= " AND r.check_in_date >= :check_in_date";
            $params['check_in_date'] = $filters['check_in_date'];
        }
        
        if (!empty($filters['check_out_date'])) {
            $sql .= " AND r.check_out_date <= :check_out_date";
            $params['check_out_date'] = $filters['check_out_date'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (g.first_name LIKE :search OR g.last_name LIKE :search OR g.email LIKE :search OR rm.room_number LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY r.check_in_date DESC, r.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get reservation by ID
     */
    public function getById($reservationId) {
        $sql = "
            SELECT 
                r.*,
                g.first_name, g.last_name, g.email, g.phone,
                rm.room_number, rm.type as room_type,
                DATE_FORMAT(r.check_in_date, '%Y-%m-%d') as check_in_formatted,
                DATE_FORMAT(r.check_out_date, '%Y-%m-%d') as check_out_formatted,
                DATEDIFF(r.check_out_date, r.check_in_date) as nights
            FROM reservations r
            JOIN guests g ON r.guest_id = g.guest_id
            JOIN rooms rm ON r.room_id = rm.room_id
            WHERE r.reservation_id = :reservation_id
        ";
        
        return $this->db->fetch($sql, ['reservation_id' => $reservationId]);
    }
    
    /**
     * Create new reservation
     */
    public function create($reservationData) {
        $sql = "INSERT INTO reservations (guest_id, room_id, check_in_date, check_out_date, total_amount, status, special_requests, payment_status, created_at) 
                VALUES (:guest_id, :room_id, :check_in_date, :check_out_date, :total_amount, :status, :special_requests, :payment_status, NOW())";
        
        $params = [
            'guest_id' => $reservationData['guest_id'],
            'room_id' => $reservationData['room_id'],
            'check_in_date' => $reservationData['check_in_date'],
            'check_out_date' => $reservationData['check_out_date'],
            'total_amount' => $reservationData['total_amount'],
            'status' => $reservationData['status'],
            'special_requests' => $reservationData['special_requests'],
            'payment_status' => $reservationData['payment_status']
        ];
        
        if ($this->db->execute($sql, $params)) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    /**
     * Update reservation
     */
    public function update($reservationId, $data) {
        $setParts = [];
        $params = ['reservation_id' => $reservationId];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
            $params[$key] = $value;
        }
        
        if (empty($setParts)) {
            return false;
        }
        
        $sql = "UPDATE reservations SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE reservation_id = :reservation_id";
        
        $stmt = $this->db->query($sql, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Delete reservation
     */
    public function delete($reservationId) {
        $sql = "DELETE FROM reservations WHERE reservation_id = :reservation_id";
        $stmt = $this->db->query($sql, ['reservation_id' => $reservationId]);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Update reservation status
     */
    public function updateStatus($reservationId, $status) {
        $sql = "UPDATE reservations SET status = :status, updated_at = NOW() WHERE reservation_id = :reservation_id";
        $stmt = $this->db->query($sql, ['status' => $status, 'reservation_id' => $reservationId]);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Get reservation statistics
     */
    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_reservations,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_reservations,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reservations,
                    SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in_reservations,
                    SUM(CASE WHEN status = 'checked_out' THEN 1 ELSE 0 END) as checked_out_reservations,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_reservations,
                    SUM(CASE WHEN check_in_date = CURDATE() THEN 1 ELSE 0 END) as todays_checkins,
                    SUM(CASE WHEN check_out_date = CURDATE() THEN 1 ELSE 0 END) as todays_checkouts
                FROM reservations";
        
        return $this->db->fetch($sql);
    }

    /**
     * Get today's check-ins
     */
    public function getTodayCheckIns() {
        $sql = "SELECT r.*, g.first_name, g.last_name, rm.room_number
                FROM reservations r
                JOIN guests g ON r.guest_id = g.guest_id  
                JOIN rooms rm ON r.room_id = rm.room_id
                WHERE r.check_in_date = CURDATE() AND r.status = 'confirmed'
                ORDER BY r.created_at";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Get today's check-outs
     */
    public function getTodayCheckOuts() {
        $sql = "SELECT r.*, g.first_name, g.last_name, rm.room_number
                FROM reservations r
                JOIN guests g ON r.guest_id = g.guest_id
                JOIN rooms rm ON r.room_id = rm.room_id  
                WHERE r.check_out_date = CURDATE() AND r.status = 'checked_in'
                ORDER BY r.created_at";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Check if room is available for given dates
     */
    public function isRoomAvailable($roomId, $checkIn, $checkOut, $excludeReservationId = null) {
        $sql = "SELECT COUNT(*) as count FROM reservations 
                WHERE room_id = :room_id 
                AND status IN ('confirmed', 'checked_in')
                AND (
                    (check_in_date <= :check_in AND check_out_date > :check_in)
                    OR (check_in_date < :check_out AND check_out_date >= :check_out)
                    OR (check_in_date >= :check_in AND check_out_date <= :check_out)
                )";
        
        $params = [
            'room_id' => $roomId,
            'check_in' => $checkIn,
            'check_out' => $checkOut
        ];
        
        if ($excludeReservationId) {
            $sql .= " AND reservation_id != :exclude_id";
            $params['exclude_id'] = $excludeReservationId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] == 0;
    }
    
    /**
     * Get monthly revenue report
     */
    public function getMonthlyRevenue($year = null) {
        $year = $year ?? date('Y');
        
        $sql = "
            SELECT 
                MONTH(check_in_date) as month,
                MONTHNAME(check_in_date) as month_name,
                COUNT(*) as total_reservations,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_booking_value
            FROM reservations 
            WHERE YEAR(check_in_date) = :year
            AND status IN ('confirmed', 'checked_in', 'checked_out')
            GROUP BY MONTH(check_in_date), MONTHNAME(check_in_date)
            ORDER BY MONTH(check_in_date)
        ";
        
        return $this->db->fetchAll($sql, ['year' => $year]);
    }
}

?>
