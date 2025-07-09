<?php
// api/dashboard/stats.php - Dashboard Statistics API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../auth/middleware.php';

try {
    // Today's statistics
    $today = date('Y-m-d');
    
    // Total rooms
    $stmt = $pdo->query("SELECT COUNT(*) as total_rooms FROM rooms");
    $totalRooms = $stmt->fetch()['total_rooms'];
    
    // Available rooms
    $stmt = $pdo->query("SELECT COUNT(*) as available_rooms FROM rooms WHERE status = 'available'");
    $availableRooms = $stmt->fetch()['available_rooms'];
    
    // Occupied rooms
    $stmt = $pdo->query("SELECT COUNT(*) as occupied_rooms FROM rooms WHERE status = 'occupied'");
    $occupiedRooms = $stmt->fetch()['occupied_rooms'];
    
    // Today's check-ins
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as todays_checkins 
        FROM reservations 
        WHERE check_in_date = ? AND status IN ('confirmed', 'checked_in')
    ");
    $stmt->execute([$today]);
    $todaysCheckins = $stmt->fetch()['todays_checkins'];
    
    // Today's check-outs
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as todays_checkouts 
        FROM reservations 
        WHERE check_out_date = ? AND status = 'checked_out'
    ");
    $stmt->execute([$today]);
    $todaysCheckouts = $stmt->fetch()['todays_checkouts'];
    
    // New reservations today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as new_reservations 
        FROM reservations 
        WHERE DATE(created_at) = ?
    ");
    $stmt->execute([$today]);
    $newReservations = $stmt->fetch()['new_reservations'];
    
    // Total revenue this month
    $thisMonth = date('Y-m');
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as monthly_revenue 
        FROM reservations 
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ? 
        AND status IN ('confirmed', 'checked_in', 'checked_out')
    ");
    $stmt->execute([$thisMonth]);
    $monthlyRevenue = $stmt->fetch()['monthly_revenue'];
    
    // Occupancy rate
    $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
    
    // Recent reservations
    $stmt = $pdo->prepare("
        SELECT r.reservation_code, r.check_in_date, r.check_out_date, r.status, r.total_amount,
               CONCAT(g.first_name, ' ', g.last_name) as guest_name,
               ro.room_number, ro.room_type
        FROM reservations r
        JOIN guests g ON r.guest_id = g.guest_id
        JOIN rooms ro ON r.room_id = ro.room_id
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentReservations = $stmt->fetchAll();
    
    // Room status breakdown
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM rooms 
        GROUP BY status
    ");
    $roomStatus = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'overview' => [
                'total_rooms' => (int)$totalRooms,
                'available_rooms' => (int)$availableRooms,
                'occupied_rooms' => (int)$occupiedRooms,
                'occupancy_rate' => $occupancyRate,
                'todays_checkins' => (int)$todaysCheckins,
                'todays_checkouts' => (int)$todaysCheckouts,
                'new_reservations' => (int)$newReservations,
                'monthly_revenue' => (float)$monthlyRevenue
            ],
            'recent_reservations' => $recentReservations,
            'room_status' => $roomStatus
        ]
    ]);

} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>
