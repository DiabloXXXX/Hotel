<?php
// api/dashboard/stats-demo.php - Dashboard Statistics API (Demo Version - Tanpa Database)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

try {
    // Data demo untuk dashboard
    $demoStats = [
        'total_rooms' => 50,
        'available_rooms' => 32,
        'occupied_rooms' => 15,
        'maintenance_rooms' => 3,
        'total_reservations' => 128,
        'active_reservations' => 18,
        'pending_reservations' => 7,
        'completed_reservations' => 103,
        'total_guests' => 256,
        'checked_in_guests' => 28,
        'checked_out_guests' => 12,
        'total_revenue' => 125000000, // IDR
        'monthly_revenue' => 15000000, // IDR
        'daily_revenue' => 2500000, // IDR
        'average_occupancy' => 68.5, // percentage
        'guest_satisfaction' => 4.6 // out of 5
    ];

    // Recent activities demo
    $recentActivities = [
        [
            'time' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
            'activity' => 'Check-in tamu baru',
            'details' => 'Budi Santoso - Kamar 301',
            'type' => 'checkin'
        ],
        [
            'time' => date('Y-m-d H:i:s', strtotime('-32 minutes')),
            'activity' => 'Pembayaran reservasi',
            'details' => 'Siti Nurhaliza - Rp 1.500.000',
            'type' => 'payment'
        ],
        [
            'time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'activity' => 'Kamar selesai maintenance',
            'details' => 'Kamar 205 siap digunakan',
            'type' => 'maintenance'
        ],
        [
            'time' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'activity' => 'Reservasi baru',
            'details' => 'Ahmad Wijaya - 3 hari, 2 kamar',
            'type' => 'reservation'
        ],
        [
            'time' => date('Y-m-d H:i:s', strtotime('-3 hours')),
            'activity' => 'Check-out',
            'details' => 'Keluarga Sutanto - Kamar 105, 106',
            'type' => 'checkout'
        ]
    ];

    // Room status distribution
    $roomStatusChart = [
        ['status' => 'Tersedia', 'count' => 32, 'color' => '#28a745'],
        ['status' => 'Terisi', 'count' => 15, 'color' => '#007bff'],
        ['status' => 'Maintenance', 'count' => 3, 'color' => '#ffc107']
    ];

    // Monthly revenue chart data (last 6 months)
    $monthlyRevenueChart = [
        ['month' => 'Jan', 'revenue' => 12000000],
        ['month' => 'Feb', 'revenue' => 13500000],
        ['month' => 'Mar', 'revenue' => 11800000],
        ['month' => 'Apr', 'revenue' => 14200000],
        ['month' => 'May', 'revenue' => 15800000],
        ['month' => 'Jun', 'revenue' => 16500000]
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Dashboard data berhasil dimuat',
        'data' => [
            'statistics' => $demoStats,
            'recent_activities' => $recentActivities,
            'room_status_chart' => $roomStatusChart,
            'revenue_chart' => $monthlyRevenueChart,
            'last_updated' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    error_log("Demo Dashboard Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.'
    ]);
}
?>
