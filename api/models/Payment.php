<?php
/**
 * Payment Model
 * Hotel Senang Hati - Payment Management
 */

require_once '../config/database.php';

class Payment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all payments with optional filters
     */
    public function getAllPayments($filters = []) {
        $sql = "
            SELECT 
                p.*,
                r.reservation_code,
                g.first_name, g.last_name, g.email
            FROM payments p
            JOIN reservations r ON p.reservation_id = r.reservation_id
            JOIN guests g ON r.guest_id = g.guest_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['payment_method'])) {
            $sql .= " AND p.payment_method = :payment_method";
            $params['payment_method'] = $filters['payment_method'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(p.paid_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(p.paid_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['reservation_code'])) {
            $sql .= " AND r.reservation_code LIKE :reservation_code";
            $params['reservation_code'] = '%' . $filters['reservation_code'] . '%';
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get payment by ID
     */
    public function getPaymentById($paymentId) {
        $sql = "
            SELECT 
                p.*,
                r.reservation_code,
                g.first_name, g.last_name, g.email
            FROM payments p
            JOIN reservations r ON p.reservation_id = r.reservation_id
            JOIN guests g ON r.guest_id = g.guest_id
            WHERE p.payment_id = :payment_id
        ";
        
        return $this->db->fetch($sql, ['payment_id' => $paymentId]);
    }
    
    /**
     * Get payments by reservation ID
     */
    public function getPaymentsByReservation($reservationId) {
        $sql = "SELECT * FROM payments WHERE reservation_id = :reservation_id ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, ['reservation_id' => $reservationId]);
    }
    
    /**
     * Create new payment
     */
    public function createPayment($data) {
        // Validate required fields
        Validator::required($data['reservation_id'], 'reservation_id');
        Validator::required($data['amount'], 'amount');
        Validator::required($data['payment_method'], 'payment_method');
        
        // Validate numeric fields
        Validator::numeric($data['reservation_id'], 'reservation_id');
        Validator::numeric($data['amount'], 'amount');
        
        // Validate payment method
        $allowedMethods = ['cash', 'credit_card', 'debit_card', 'bank_transfer', 'ewallet', 'qris'];
        Validator::in($data['payment_method'], $allowedMethods, 'payment_method');
        
        // Validate payment type if provided
        if (isset($data['payment_type'])) {
            $allowedTypes = ['deposit', 'full_payment', 'additional_charge', 'refund'];
            Validator::in($data['payment_type'], $allowedTypes, 'payment_type');
        }
        
        // Generate payment code if not provided
        if (empty($data['payment_code'])) {
            $data['payment_code'] = $this->generatePaymentCode();
        }
        
        // Prepare data for insertion
        $insertData = [
            'reservation_id' => (int)$data['reservation_id'],
            'payment_code' => $data['payment_code'],
            'amount' => (float)$data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_type' => $data['payment_type'] ?? 'full_payment',
            'currency' => $data['currency'] ?? 'IDR',
            'exchange_rate' => (float)($data['exchange_rate'] ?? 1.0000),
            'transaction_id' => $data['transaction_id'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'payment_gateway' => $data['payment_gateway'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null
        ];
        
        // Handle JSON fields
        if (isset($data['gateway_response']) && is_array($data['gateway_response'])) {
            $insertData['gateway_response'] = json_encode($data['gateway_response']);
        }
        
        return $this->db->insert('payments', $insertData);
    }
    
    /**
     * Update payment
     */
    public function updatePayment($paymentId, $data) {
        $payment = $this->getPaymentById($paymentId);
        if (!$payment) {
            throw new InvalidArgumentException("Payment not found");
        }
        
        // Validate payment method if provided
        if (isset($data['payment_method'])) {
            $allowedMethods = ['cash', 'credit_card', 'debit_card', 'bank_transfer', 'ewallet', 'qris'];
            Validator::in($data['payment_method'], $allowedMethods, 'payment_method');
        }
        
        // Validate payment type if provided
        if (isset($data['payment_type'])) {
            $allowedTypes = ['deposit', 'full_payment', 'additional_charge', 'refund'];
            Validator::in($data['payment_type'], $allowedTypes, 'payment_type');
        }
        
        // Validate status if provided
        if (isset($data['status'])) {
            $allowedStatuses = ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'];
            Validator::in($data['status'], $allowedStatuses, 'status');
        }
        
        // Handle numeric fields
        if (isset($data['amount'])) {
            Validator::numeric($data['amount'], 'amount');
            $data['amount'] = (float)$data['amount'];
        }
        
        if (isset($data['exchange_rate'])) {
            $data['exchange_rate'] = (float)$data['exchange_rate'];
        }
        
        // Handle JSON fields
        if (isset($data['gateway_response']) && is_array($data['gateway_response'])) {
            $data['gateway_response'] = json_encode($data['gateway_response']);
        }
        
        // Set paid_at timestamp if status changed to completed
        if (isset($data['status']) && $data['status'] === 'completed' && $payment['status'] !== 'completed') {
            $data['paid_at'] = date('Y-m-d H:i:s');
        }
        
        // Set refunded_at timestamp if status changed to refunded
        if (isset($data['status']) && $data['status'] === 'refunded' && $payment['status'] !== 'refunded') {
            $data['refunded_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update('payments', $data, ['payment_id' => $paymentId]);
    }
    
    /**
     * Update payment status
     */
    public function updatePaymentStatus($paymentId, $status, $notes = null) {
        $allowedStatuses = ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new InvalidArgumentException("Invalid payment status");
        }
        
        $updateData = ['status' => $status];
        
        if ($notes) {
            $updateData['notes'] = $notes;
        }
        
        // Set appropriate timestamps
        if ($status === 'completed') {
            $updateData['paid_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'refunded') {
            $updateData['refunded_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update('payments', $updateData, ['payment_id' => $paymentId]);
    }
    
    /**
     * Process payment (mark as completed)
     */
    public function processPayment($paymentId, $transactionId = null, $gatewayResponse = null) {
        $updateData = [
            'status' => 'completed',
            'paid_at' => date('Y-m-d H:i:s')
        ];
        
        if ($transactionId) {
            $updateData['transaction_id'] = $transactionId;
        }
        
        if ($gatewayResponse && is_array($gatewayResponse)) {
            $updateData['gateway_response'] = json_encode($gatewayResponse);
        }
        
        return $this->db->update('payments', $updateData, ['payment_id' => $paymentId]);
    }
    
    /**
     * Refund payment
     */
    public function refundPayment($paymentId, $reason = null) {
        $updateData = [
            'status' => 'refunded',
            'refunded_at' => date('Y-m-d H:i:s')
        ];
        
        if ($reason) {
            $updateData['notes'] = $reason;
        }
        
        return $this->db->update('payments', $updateData, ['payment_id' => $paymentId]);
    }
    
    /**
     * Get payment statistics
     */
    public function getPaymentStats($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                COUNT(*) as total_payments,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_payments,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_payments,
                COUNT(CASE WHEN status = 'refunded' THEN 1 END) as refunded_payments,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END) as average_payment,
                SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END) as total_refunded
            FROM payments 
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($startDate) {
            $sql .= " AND DATE(created_at) >= :start_date";
            $params['start_date'] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND DATE(created_at) <= :end_date";
            $params['end_date'] = $endDate;
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    /**
     * Get payment method statistics
     */
    public function getPaymentMethodStats($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                payment_method,
                COUNT(*) as payment_count,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount,
                AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END) as average_amount
            FROM payments 
            WHERE status = 'completed'
        ";
        
        $params = [];
        
        if ($startDate) {
            $sql .= " AND DATE(paid_at) >= :start_date";
            $params['start_date'] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND DATE(paid_at) <= :end_date";
            $params['end_date'] = $endDate;
        }
        
        $sql .= " GROUP BY payment_method ORDER BY total_amount DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get daily revenue report
     */
    public function getDailyRevenue($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                DATE(paid_at) as revenue_date,
                COUNT(*) as payment_count,
                SUM(amount) as total_revenue,
                AVG(amount) as average_payment
            FROM payments 
            WHERE status = 'completed'
        ";
        
        $params = [];
        
        if ($startDate) {
            $sql .= " AND DATE(paid_at) >= :start_date";
            $params['start_date'] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND DATE(paid_at) <= :end_date";
            $params['end_date'] = $endDate;
        }
        
        $sql .= " GROUP BY DATE(paid_at) ORDER BY revenue_date DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Generate unique payment code
     */
    private function generatePaymentCode() {
        do {
            $code = 'PAY' . date('ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $this->db->fetch("SELECT payment_id FROM payments WHERE payment_code = :code", ['code' => $code]);
        } while ($exists);
        
        return $code;
    }
    
    /**
     * Get pending payments (requires action)
     */
    public function getPendingPayments() {
        $sql = "
            SELECT 
                p.*,
                r.reservation_code,
                g.first_name, g.last_name, g.email
            FROM payments p
            JOIN reservations r ON p.reservation_id = r.reservation_id
            JOIN guests g ON r.guest_id = g.guest_id
            WHERE p.status = 'pending'
            ORDER BY p.created_at ASC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get failed payments (requires attention)
     */
    public function getFailedPayments() {
        $sql = "
            SELECT 
                p.*,
                r.reservation_code,
                g.first_name, g.last_name, g.email
            FROM payments p
            JOIN reservations r ON p.reservation_id = r.reservation_id
            JOIN guests g ON r.guest_id = g.guest_id
            WHERE p.status = 'failed'
            ORDER BY p.created_at DESC
        ";
        
        return $this->db->fetchAll($sql);
    }
}

?>
