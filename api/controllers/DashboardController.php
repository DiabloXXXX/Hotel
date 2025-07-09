<?php
/**
 * Dashboard Controller
 * Hotel Senang Hati - Dashboard Statistics & Analytics
 */

require_once '../config/database.php';
require_once '../models/Room.php';
require_once '../models/Reservation.php';
require_once '../models/Guest.php';
require_once '../models/Payment.php';

class DashboardController {
    private $db;
    private $room;
    private $reservation;
    private $guest;
    private $payment;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->room = new Room();
        $this->reservation = new Reservation();
        $this->guest = new Guest();
        $this->payment = new Payment();
    }
    
    /**
     * Handle HTTP requests
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        try {
            // Validate authentication
            Auth::validateSession();
            
            switch ($method) {
                case 'GET':
                    $this->handleGet($pathParts);
                    break;
                default:
                    ApiResponse::error('Method not allowed', 405);
            }
        } catch (InvalidArgumentException $e) {
            ApiResponse::validation(null, $e->getMessage());
        } catch (Exception $e) {
            error_log("Dashboard Controller Error: " . $e->getMessage());
            ApiResponse::error('Internal server error', 500);
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($pathParts) {
        // GET /api/dashboard/stats - Get overall dashboard statistics
        if (count($pathParts) == 3 && $pathParts[1] == 'dashboard' && $pathParts[2] == 'stats') {
            $this->getDashboardStats();
        }
        
        // GET /api/dashboard/revenue - Get revenue overview
        elseif (count($pathParts) == 3 && $pathParts[1] == 'dashboard' && $pathParts[2] == 'revenue') {
            $this->getRevenueOverview();
        }
        
        // GET /api/dashboard/occupancy - Get occupancy overview
        elseif (count($pathParts) == 3 && $pathParts[1] == 'dashboard' && $pathParts[2] == 'occupancy') {
            $this->getOccupancyOverview();
        }
        
        // GET /api/dashboard/checkins - Get today's activity (check-ins/check-outs)
        elseif (count($pathParts) == 3 && $pathParts[1] == 'dashboard' && $pathParts[2] == 'activity') {
            $this->getTodayActivity();
        }
        
        // GET /api/dashboard/charts - Get chart data
        elseif (count($pathParts) == 3 && $pathParts[1] == 'dashboard' && $pathParts[2] == 'charts') {
            $this->getChartData();
        }
        
        else {
            ApiResponse::notFound('Endpoint not found');
        }
    }
    
    /**
     * Get overall dashboard statistics
     */
    private function getDashboardStats() {
        // Get today's date
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');
        
        // Total rooms
        $totalRooms = $this->db->fetch("SELECT COUNT(*) as count FROM rooms")['count'];
        
        // Available rooms
        $availableRooms = $this->db->fetch("SELECT COUNT(*) as count FROM rooms WHERE status = 'available'")['count'];
        
        // Occupied rooms
        $occupiedRooms = $this->db->fetch("SELECT COUNT(*) as count FROM rooms WHERE status = 'occupied'")['count'];
        
        // Today's check-ins
        $todayCheckins = $this->db->fetch("
            SELECT COUNT(*) as count 
            FROM reservations 
            WHERE check_in_date = :today AND status IN ('confirmed', 'checked_in')
        ", ['today' => $today])['count'];
        
        // Today's check-outs
        $todayCheckouts = $this->db->fetch("
            SELECT COUNT(*) as count 
            FROM reservations 
            WHERE check_out_date = :today AND status = 'checked_in'
        ", ['today' => $today])['count'];
        
        // Total guests
        $totalGuests = $this->db->fetch("SELECT COUNT(*) as count FROM guests")['count'];
        
        // VIP guests
        $vipGuests = $this->db->fetch("SELECT COUNT(*) as count FROM guests WHERE is_vip = 1")['count'];
        
        // This month's reservations
        $monthReservations = $this->db->fetch("
            SELECT COUNT(*) as count 
            FROM reservations 
            WHERE DATE_FORMAT(check_in_date, '%Y-%m') = :month
        ", ['month' => $thisMonth])['count'];
        
        // This month's revenue
        $monthRevenue = $this->db->fetch("
            SELECT COALESCE(SUM(amount), 0) as revenue 
            FROM payments 
            WHERE status = 'completed' 
            AND DATE_FORMAT(paid_at, '%Y-%m') = :month
        ", ['month' => $thisMonth])['revenue'];
        
        // Pending payments
        $pendingPayments = $this->db->fetch("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")['count'];
        
        // Maintenance requests
        $maintenanceRequests = $this->db->fetch("
            SELECT COUNT(*) as count 
            FROM room_maintenance 
            WHERE status IN ('scheduled', 'in_progress')
        ")['count'];
        
        // Occupancy rate
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;
        
        $stats = [
            'rooms' => [
                'total' => (int)$totalRooms,
                'available' => (int)$availableRooms,
                'occupied' => (int)$occupiedRooms,
                'occupancy_rate' => $occupancyRate
            ],
            'today' => [
                'checkins' => (int)$todayCheckins,
                'checkouts' => (int)$todayCheckouts
            ],
            'guests' => [
                'total' => (int)$totalGuests,
                'vip' => (int)$vipGuests
            ],
            'this_month' => [
                'reservations' => (int)$monthReservations,
                'revenue' => (float)$monthRevenue
            ],
            'alerts' => [
                'pending_payments' => (int)$pendingPayments,
                'maintenance_requests' => (int)$maintenanceRequests
            ]
        ];
        
        ApiResponse::success($stats, 'Dashboard statistics retrieved successfully');
    }
    
    /**
     * Get revenue overview
     */
    private function getRevenueOverview() {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $thisMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));
        $thisYear = date('Y');
        
        // Today's revenue
        $todayRevenue = $this->db->fetch("
            SELECT COALESCE(SUM(amount), 0) as revenue 
            FROM payments 
            WHERE status = 'completed' AND DATE(paid_at) = :today
        ", ['today' => $today])['revenue'];
        
        // Yesterday's revenue
        $yesterdayRevenue = $this->db->fetch("
            SELECT COALESCE(SUM(amount), 0) as revenue 
            FROM payments 
            WHERE status = 'completed' AND DATE(paid_at) = :yesterday
        ", ['yesterday' => $yesterday])['revenue'];
        
        // This month's revenue
        $thisMonthRevenue = $this->db->fetch("
            SELECT COALESCE(SUM(amount), 0) as revenue 
            FROM payments 
            WHERE status = 'completed' AND DATE_FORMAT(paid_at, '%Y-%m') = :month
        ", ['month' => $thisMonth])['revenue'];
        
        // Last month's revenue
        $lastMonthRevenue = $this->db->fetch("
            SELECT COALESCE(SUM(amount), 0) as revenue 
            FROM payments 
            WHERE status = 'completed' AND DATE_FORMAT(paid_at, '%Y-%m') = :month
        ", ['month' => $lastMonth])['revenue'];
        
        // This year's revenue
        $thisYearRevenue = $this->db->fetch("
            SELECT COALESCE(SUM(amount), 0) as revenue 
            FROM payments 
            WHERE status = 'completed' AND YEAR(paid_at) = :year
        ", ['year' => $thisYear])['revenue'];
        
        // Calculate growth percentages
        $dailyGrowth = $yesterdayRevenue > 0 ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 2) : 0;
        $monthlyGrowth = $lastMonthRevenue > 0 ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2) : 0;
        
        // Get revenue by payment method
        $paymentMethods = $this->payment->getPaymentMethodStats($thisMonth . '-01', $today);
        
        $revenue = [
            'today' => (float)$todayRevenue,
            'yesterday' => (float)$yesterdayRevenue,
            'daily_growth' => $dailyGrowth,
            'this_month' => (float)$thisMonthRevenue,
            'last_month' => (float)$lastMonthRevenue,
            'monthly_growth' => $monthlyGrowth,
            'this_year' => (float)$thisYearRevenue,
            'payment_methods' => $paymentMethods
        ];
        
        ApiResponse::success($revenue, 'Revenue overview retrieved successfully');
    }
    
    /**
     * Get occupancy overview
     */
    private function getOccupancyOverview() {
        // Current occupancy by room type
        $occupancy = $this->room->getOccupancyStats();
        
        // Weekly occupancy trend (last 7 days)
        $weeklyOccupancy = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $totalRooms = $this->db->fetch("SELECT COUNT(*) as count FROM rooms")['count'];
            $occupiedRooms = $this->db->fetch("
                SELECT COUNT(DISTINCT r.room_id) as count
                FROM reservations res
                JOIN rooms r ON res.room_id = r.room_id
                WHERE res.status = 'checked_in'
                AND :date BETWEEN res.check_in_date AND res.check_out_date
            ", ['date' => $date])['count'];
            
            $rate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;
            
            $weeklyOccupancy[] = [
                'date' => $date,
                'rate' => $rate,
                'occupied_rooms' => (int)$occupiedRooms,
                'total_rooms' => (int)$totalRooms
            ];
        }
        
        // Future reservations (next 30 days)
        $futureReservations = $this->db->fetch("
            SELECT COUNT(*) as count
            FROM reservations
            WHERE check_in_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            AND status IN ('confirmed', 'pending')
        ")['count'];
        
        $data = [
            'current_occupancy' => $occupancy,
            'weekly_trend' => $weeklyOccupancy,
            'future_reservations_30_days' => (int)$futureReservations
        ];
        
        ApiResponse::success($data, 'Occupancy overview retrieved successfully');
    }
    
    /**
     * Get today's activity
     */
    private function getTodayActivity() {
        $todayCheckins = $this->reservation->getTodayCheckIns();
        $todayCheckouts = $this->reservation->getTodayCheckOuts();
        
        // Recent reservations (last 24 hours)
        $recentReservations = $this->db->fetchAll("
            SELECT 
                r.*,
                g.first_name, g.last_name, g.email,
                rm.room_number, rm.room_type
            FROM reservations r
            JOIN guests g ON r.guest_id = g.guest_id
            JOIN rooms rm ON r.room_id = rm.room_id
            WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        
        // Pending check-ins (late arrivals)
        $lateArrivals = $this->db->fetchAll("
            SELECT 
                r.*,
                g.first_name, g.last_name, g.phone,
                rm.room_number, rm.room_type
            FROM reservations r
            JOIN guests g ON r.guest_id = g.guest_id
            JOIN rooms rm ON r.room_id = rm.room_id
            WHERE r.check_in_date = CURDATE()
            AND r.status = 'confirmed'
            AND TIME(NOW()) > r.check_in_time
        ");
        
        $activity = [
            'checkins' => $todayCheckins,
            'checkouts' => $todayCheckouts,
            'recent_reservations' => $recentReservations,
            'late_arrivals' => $lateArrivals
        ];
        
        ApiResponse::success($activity, 'Today\'s activity retrieved successfully');
    }
    
    /**
     * Get chart data for dashboard
     */
    private function getChartData() {
        $chartType = $_GET['type'] ?? 'revenue';
        $period = $_GET['period'] ?? '30'; // days
        
        switch ($chartType) {
            case 'revenue':
                $data = $this->getRevenueChartData($period);
                break;
            case 'occupancy':
                $data = $this->getOccupancyChartData($period);
                break;
            case 'reservations':
                $data = $this->getReservationsChartData($period);
                break;
            case 'room_types':
                $data = $this->getRoomTypesChartData($period);
                break;
            default:
                ApiResponse::validation(null, 'Invalid chart type');
        }
        
        ApiResponse::success($data, 'Chart data retrieved successfully');
    }
    
    /**
     * Get revenue chart data
     */
    private function getRevenueChartData($days) {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $revenue = $this->db->fetch("
                SELECT COALESCE(SUM(amount), 0) as revenue
                FROM payments
                WHERE status = 'completed' AND DATE(paid_at) = :date
            ", ['date' => $date])['revenue'];
            
            $data[] = [
                'date' => $date,
                'value' => (float)$revenue
            ];
        }
        
        return $data;
    }
    
    /**
     * Get occupancy chart data
     */
    private function getOccupancyChartData($days) {
        $data = [];
        $totalRooms = $this->db->fetch("SELECT COUNT(*) as count FROM rooms")['count'];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $occupiedRooms = $this->db->fetch("
                SELECT COUNT(DISTINCT r.room_id) as count
                FROM reservations res
                JOIN rooms r ON res.room_id = r.room_id
                WHERE res.status IN ('checked_in', 'checked_out')
                AND :date BETWEEN res.check_in_date AND res.check_out_date
            ", ['date' => $date])['count'];
            
            $rate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;
            
            $data[] = [
                'date' => $date,
                'value' => $rate
            ];
        }
        
        return $data;
    }
    
    /**
     * Get reservations chart data
     */
    private function getReservationsChartData($days) {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $count = $this->db->fetch("
                SELECT COUNT(*) as count
                FROM reservations
                WHERE DATE(created_at) = :date
            ", ['date' => $date])['count'];
            
            $data[] = [
                'date' => $date,
                'value' => (int)$count
            ];
        }
        
        return $data;
    }
    
    /**
     * Get room types chart data
     */
    private function getRoomTypesChartData($days) {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $data = $this->db->fetchAll("
            SELECT 
                rm.room_type,
                COUNT(r.reservation_id) as bookings,
                SUM(r.total_amount) as revenue
            FROM reservations r
            JOIN rooms rm ON r.room_id = rm.room_id
            WHERE r.created_at >= :start_date
            AND r.status IN ('confirmed', 'checked_in', 'checked_out')
            GROUP BY rm.room_type
            ORDER BY revenue DESC
        ", ['start_date' => $startDate]);
        
        return array_map(function($item) {
            return [
                'label' => ucfirst($item['room_type']),
                'bookings' => (int)$item['bookings'],
                'revenue' => (float)$item['revenue']
            ];
        }, $data);
    }
}

// Create controller and handle request
$controller = new DashboardController();
$controller->handleRequest();

?>
