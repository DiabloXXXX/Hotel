<?php
/**
 * Guest Model
 * Hotel Senang Hati - Guest Management
 */

require_once '../config/database.php';

class Guest {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all guests with optional filters
     */
    public function getAll($filters = []) {
        $sql = "SELECT g.*, 
                COUNT(r.reservation_id) as total_reservations,
                DATE_FORMAT(g.created_at, '%Y-%m-%d') as created_date
                FROM guests g
                LEFT JOIN reservations r ON g.guest_id = r.guest_id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (g.first_name LIKE :search OR g.last_name LIKE :search OR g.email LIKE :search OR g.phone LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['email'])) {
            $sql .= " AND g.email = :email";
            $params['email'] = $filters['email'];
        }
        
        if (!empty($filters['country'])) {
            $sql .= " AND g.country = :country";
            $params['country'] = $filters['country'];
        }
        
        $sql .= " GROUP BY g.guest_id ORDER BY g.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get guest by ID
     */
    public function getById($guestId) {
        $sql = "SELECT g.*, 
                COUNT(r.reservation_id) as total_reservations,
                DATE_FORMAT(g.created_at, '%Y-%m-%d') as created_date
                FROM guests g
                LEFT JOIN reservations r ON g.guest_id = r.guest_id
                WHERE g.guest_id = :guest_id
                GROUP BY g.guest_id";
        return $this->db->fetch($sql, ['guest_id' => $guestId]);
    }
    
    /**
     * Create new guest
     */
    public function create($guestData) {
        $sql = "INSERT INTO guests (first_name, last_name, email, phone, address, city, country, id_number, date_of_birth, created_at) 
                VALUES (:first_name, :last_name, :email, :phone, :address, :city, :country, :id_number, :date_of_birth, NOW())";
        
        $params = [
            'first_name' => $guestData['first_name'],
            'last_name' => $guestData['last_name'],
            'email' => $guestData['email'],
            'phone' => $guestData['phone'],
            'address' => $guestData['address'],
            'city' => $guestData['city'],
            'country' => $guestData['country'],
            'id_number' => $guestData['id_number'],
            'date_of_birth' => $guestData['date_of_birth']
        ];
        
        if ($this->db->query($sql, $params)) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    /**
     * Update guest
     */
    public function update($guestId, $updateData) {
        $setParts = [];
        $params = ['guest_id' => $guestId];
        
        $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'country', 'id_number', 'date_of_birth'];
        
        foreach ($allowedFields as $field) {
            if (isset($updateData[$field])) {
                $setParts[] = "$field = :$field";
                $params[$field] = $updateData[$field];
            }
        }
        
        if (empty($setParts)) {
            return false;
        }
        
        $sql = "UPDATE guests SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE guest_id = :guest_id";
        
        $stmt = $this->db->query($sql, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Delete guest
     */
    public function delete($guestId) {
        // Check if guest has active reservations
        if ($this->hasActiveReservations($guestId)) {
            return false;
        }
        
        $sql = "DELETE FROM guests WHERE guest_id = :guest_id";
        $stmt = $this->db->query($sql, ['guest_id' => $guestId]);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Check if guest exists by email
     */
    public function emailExists($email, $excludeGuestId = null) {
        $sql = "SELECT COUNT(*) as count FROM guests WHERE email = :email";
        $params = ['email' => $email];
        
        if ($excludeGuestId) {
            $sql .= " AND guest_id != :guest_id";
            $params['guest_id'] = $excludeGuestId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Check if guest has active reservations
     */
    public function hasActiveReservations($guestId) {
        $sql = "SELECT COUNT(*) as count FROM reservations 
                WHERE guest_id = :guest_id 
                AND status IN ('confirmed', 'checked_in') 
                AND check_out_date >= CURDATE()";
        $result = $this->db->fetch($sql, ['guest_id' => $guestId]);
        return $result['count'] > 0;
    }

    /**
     * Get guest statistics
     */
    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_guests,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today,
                    COUNT(DISTINCT country) as countries_count,
                    AVG(YEAR(CURDATE()) - YEAR(date_of_birth)) as average_age
                FROM guests";
        
        return $this->db->fetch($sql);
    }

    /**
     * Get guest reservations
     */
    public function getReservations($guestId) {
        $sql = "SELECT r.*, rm.room_number, rm.type as room_type
                FROM reservations r
                JOIN rooms rm ON r.room_id = rm.room_id
                WHERE r.guest_id = :guest_id
                ORDER BY r.check_in_date DESC";
        
        return $this->db->fetchAll($sql, ['guest_id' => $guestId]);
    }

    /**
     * Get guest by email
     */
    public function getGuestByEmail($email) {
        $sql = "SELECT * FROM guests WHERE email = :email";
        return $this->db->fetch($sql, ['email' => $email]);
    }
    
    /**
     * Get guest by phone
     */
    public function getGuestByPhone($phone) {
        $sql = "SELECT * FROM guests WHERE phone = :phone";
        return $this->db->fetch($sql, ['phone' => $phone]);
    }
    
    /**
     * Get guest by ID number
     */
    public function getGuestByIdNumber($idNumber) {
        $sql = "SELECT * FROM guests WHERE id_number = :id_number";
        return $this->db->fetch($sql, ['id_number' => $idNumber]);
    }
    
    /**
     * Create new guest
     */
    public function createGuest($data) {
        // Validate required fields
        Validator::required($data['first_name'], 'first_name');
        Validator::required($data['last_name'], 'last_name');
        Validator::required($data['email'], 'email');
        
        // Validate email format
        Validator::email($data['email']);
        
        // Validate name lengths
        Validator::length($data['first_name'], 1, 100, 'first_name');
        Validator::length($data['last_name'], 1, 100, 'last_name');
        
        // Check if email already exists
        if ($this->getGuestByEmail($data['email'])) {
            throw new InvalidArgumentException("Email already exists");
        }
        
        // Check if phone already exists (if provided)
        if (!empty($data['phone']) && $this->getGuestByPhone($data['phone'])) {
            throw new InvalidArgumentException("Phone number already exists");
        }
        
        // Check if ID number already exists (if provided)
        if (!empty($data['id_number']) && $this->getGuestByIdNumber($data['id_number'])) {
            throw new InvalidArgumentException("ID number already exists");
        }
        
        // Validate ID type if provided
        if (isset($data['id_type'])) {
            $allowedIdTypes = ['ktp', 'passport', 'sim'];
            Validator::in($data['id_type'], $allowedIdTypes, 'id_type');
        }
        
        // Validate gender if provided
        if (isset($data['gender'])) {
            $allowedGenders = ['male', 'female', 'other'];
            Validator::in($data['gender'], $allowedGenders, 'gender');
        }
        
        // Validate date of birth if provided
        if (!empty($data['date_of_birth'])) {
            Validator::date($data['date_of_birth']);
        }
        
        // Prepare data for insertion
        $insertData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'id_type' => $data['id_type'] ?? 'ktp',
            'id_number' => $data['id_number'] ?? null,
            'nationality' => $data['nationality'] ?? 'Indonesia',
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'loyalty_points' => (int)($data['loyalty_points'] ?? 0),
            'is_vip' => isset($data['is_vip']) ? (bool)$data['is_vip'] : false
        ];
        
        // Handle JSON preferences
        if (isset($data['preferences']) && is_array($data['preferences'])) {
            $insertData['preferences'] = json_encode($data['preferences']);
        }
        
        return $this->db->insert('guests', $insertData);
    }
    
    /**
     * Update guest
     */
    public function updateGuest($guestId, $data) {
        $guest = $this->getGuestById($guestId);
        if (!$guest) {
            throw new InvalidArgumentException("Guest not found");
        }
        
        // Validate email format if provided
        if (isset($data['email'])) {
            Validator::email($data['email']);
            $data['email'] = strtolower(trim($data['email']));
            
            // Check if email already exists for another guest
            $existingGuest = $this->getGuestByEmail($data['email']);
            if ($existingGuest && $existingGuest['guest_id'] != $guestId) {
                throw new InvalidArgumentException("Email already exists");
            }
        }
        
        // Validate phone if provided
        if (isset($data['phone']) && !empty($data['phone'])) {
            $existingGuest = $this->getGuestByPhone($data['phone']);
            if ($existingGuest && $existingGuest['guest_id'] != $guestId) {
                throw new InvalidArgumentException("Phone number already exists");
            }
        }
        
        // Validate ID number if provided
        if (isset($data['id_number']) && !empty($data['id_number'])) {
            $existingGuest = $this->getGuestByIdNumber($data['id_number']);
            if ($existingGuest && $existingGuest['guest_id'] != $guestId) {
                throw new InvalidArgumentException("ID number already exists");
            }
        }
        
        // Validate name lengths if provided
        if (isset($data['first_name'])) {
            Validator::length($data['first_name'], 1, 100, 'first_name');
        }
        
        if (isset($data['last_name'])) {
            Validator::length($data['last_name'], 1, 100, 'last_name');
        }
        
        // Validate ID type if provided
        if (isset($data['id_type'])) {
            $allowedIdTypes = ['ktp', 'passport', 'sim'];
            Validator::in($data['id_type'], $allowedIdTypes, 'id_type');
        }
        
        // Validate gender if provided
        if (isset($data['gender'])) {
            $allowedGenders = ['male', 'female', 'other'];
            Validator::in($data['gender'], $allowedGenders, 'gender');
        }
        
        // Validate date of birth if provided
        if (isset($data['date_of_birth']) && !empty($data['date_of_birth'])) {
            Validator::date($data['date_of_birth']);
        }
        
        // Handle numeric fields
        if (isset($data['loyalty_points'])) {
            $data['loyalty_points'] = (int)$data['loyalty_points'];
        }
        
        // Handle boolean fields
        if (isset($data['is_vip'])) {
            $data['is_vip'] = (bool)$data['is_vip'];
        }
        
        // Handle JSON preferences
        if (isset($data['preferences']) && is_array($data['preferences'])) {
            $data['preferences'] = json_encode($data['preferences']);
        }
        
        return $this->db->update('guests', $data, ['guest_id' => $guestId]);
    }
    
    /**
     * Delete guest
     */
    public function deleteGuest($guestId) {
        $guest = $this->getGuestById($guestId);
        if (!$guest) {
            throw new InvalidArgumentException("Guest not found");
        }
        
        // Check if guest has any active reservations
        $sql = "SELECT COUNT(*) as count FROM reservations WHERE guest_id = :guest_id AND status IN ('confirmed', 'checked_in')";
        $result = $this->db->fetch($sql, ['guest_id' => $guestId]);
        
        if ($result['count'] > 0) {
            throw new InvalidArgumentException("Cannot delete guest with active reservations");
        }
        
        return $this->db->delete('guests', ['guest_id' => $guestId]);
    }
    
    /**
     * Add loyalty points to guest
     */
    public function addLoyaltyPoints($guestId, $points, $reason = null) {
        $guest = $this->getGuestById($guestId);
        if (!$guest) {
            throw new InvalidArgumentException("Guest not found");
        }
        
        $newPoints = $guest['loyalty_points'] + $points;
        
        return $this->db->update('guests', 
            ['loyalty_points' => $newPoints], 
            ['guest_id' => $guestId]
        );
    }
    
    /**
     * Deduct loyalty points from guest
     */
    public function deductLoyaltyPoints($guestId, $points, $reason = null) {
        $guest = $this->getGuestById($guestId);
        if (!$guest) {
            throw new InvalidArgumentException("Guest not found");
        }
        
        if ($guest['loyalty_points'] < $points) {
            throw new InvalidArgumentException("Insufficient loyalty points");
        }
        
        $newPoints = $guest['loyalty_points'] - $points;
        
        return $this->db->update('guests', 
            ['loyalty_points' => $newPoints], 
            ['guest_id' => $guestId]
        );
    }
    
    /**
     * Get guest reservation history
     */
    public function getGuestReservations($guestId) {
        $sql = "
            SELECT 
                r.*,
                rm.room_number, rm.room_type
            FROM reservations r
            JOIN rooms rm ON r.room_id = rm.room_id
            WHERE r.guest_id = :guest_id
            ORDER BY r.check_in_date DESC
        ";
        
        return $this->db->fetchAll($sql, ['guest_id' => $guestId]);
    }
    
    /**
     * Get guest statistics
     */
    public function getGuestStats($guestId) {
        $sql = "
            SELECT 
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN status = 'checked_out' THEN 1 END) as completed_bookings,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
                SUM(CASE WHEN status = 'checked_out' THEN total_amount ELSE 0 END) as total_spent,
                AVG(CASE WHEN status = 'checked_out' THEN total_amount ELSE NULL END) as average_booking_value,
                MAX(check_out_date) as last_visit,
                MIN(check_in_date) as first_visit
            FROM reservations 
            WHERE guest_id = :guest_id
        ";
        
        return $this->db->fetch($sql, ['guest_id' => $guestId]);
    }
    
    /**
     * Get VIP guests
     */
    public function getVipGuests() {
        $sql = "
            SELECT 
                g.*,
                COUNT(r.reservation_id) as total_bookings,
                SUM(CASE WHEN r.status = 'checked_out' THEN r.total_amount ELSE 0 END) as total_spent
            FROM guests g
            LEFT JOIN reservations r ON g.guest_id = r.guest_id
            WHERE g.is_vip = 1
            GROUP BY g.guest_id
            ORDER BY total_spent DESC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get top guests by spending
     */
    public function getTopGuests($limit = 10) {
        $sql = "
            SELECT 
                g.*,
                COUNT(r.reservation_id) as total_bookings,
                SUM(CASE WHEN r.status = 'checked_out' THEN r.total_amount ELSE 0 END) as total_spent
            FROM guests g
            LEFT JOIN reservations r ON g.guest_id = r.guest_id
            GROUP BY g.guest_id
            ORDER BY total_spent DESC
            LIMIT :limit
        ";
        
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }
    
    /**
     * Search guests
     */
    public function searchGuests($query) {
        $sql = "
            SELECT 
                g.*,
                COUNT(r.reservation_id) as total_bookings
            FROM guests g
            LEFT JOIN reservations r ON g.guest_id = r.guest_id
            WHERE g.first_name LIKE :query
            OR g.last_name LIKE :query
            OR g.email LIKE :query
            OR g.phone LIKE :query
            OR g.id_number LIKE :query
            GROUP BY g.guest_id
            ORDER BY g.first_name, g.last_name
        ";
        
        return $this->db->fetchAll($sql, ['query' => '%' . $query . '%']);
    }
    
    /**
     * Update guest preferences
     */
    public function updateGuestPreferences($guestId, $preferences) {
        $guest = $this->getGuestById($guestId);
        if (!$guest) {
            throw new InvalidArgumentException("Guest not found");
        }
        
        $currentPreferences = json_decode($guest['preferences'] ?? '{}', true);
        $updatedPreferences = array_merge($currentPreferences, $preferences);
        
        return $this->db->update('guests', 
            ['preferences' => json_encode($updatedPreferences)], 
            ['guest_id' => $guestId]
        );
    }
    
    /**
     * Get guest nationality statistics
     */
    public function getNationalityStats() {
        $sql = "
            SELECT 
                nationality,
                COUNT(*) as guest_count,
                COUNT(CASE WHEN is_vip = 1 THEN 1 END) as vip_count
            FROM guests 
            GROUP BY nationality 
            ORDER BY guest_count DESC
        ";
        
        return $this->db->fetchAll($sql);
    }
}

?>
