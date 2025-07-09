<?php
// api/auth/login.php - Staff Authentication API (Demo Version - Tanpa Database)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username dan password harus diisi']);
    exit;
}

$username = trim($input['username']);
$password = trim($input['password']);

// Demo accounts - tidak menggunakan database untuk sementara
$demoAccounts = [
    'admin' => [
        'password' => 'admin123',
        'staff_id' => 1,
        'username' => 'admin',
        'first_name' => 'Admin',
        'last_name' => 'Hotel',
        'email' => 'admin@hotelsenanghati.com',
        'role' => 'admin',
        'department' => 'Management'
    ],
    'manager' => [
        'password' => 'manager123',
        'staff_id' => 2,
        'username' => 'manager',
        'first_name' => 'Manager',
        'last_name' => 'Hotel',
        'email' => 'manager@hotelsenanghati.com',
        'role' => 'manager',
        'department' => 'Operations'
    ],
    'staff' => [
        'password' => 'staff123',
        'staff_id' => 3,
        'username' => 'staff',
        'first_name' => 'Staff',
        'last_name' => 'Hotel',
        'email' => 'staff@hotelsenanghati.com',
        'role' => 'staff',
        'department' => 'Front Desk'
    ]
];

try {
    // Cek kredensial demo
    if (!isset($demoAccounts[$username]) || $demoAccounts[$username]['password'] !== $password) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Username atau password salah']);
        exit;
    }

    $staff = $demoAccounts[$username];

    // Start session
    session_start();
    $_SESSION['staff_id'] = $staff['staff_id'];
    $_SESSION['username'] = $staff['username'];
    $_SESSION['role'] = $staff['role'];
    $_SESSION['full_name'] = $staff['first_name'] . ' ' . $staff['last_name'];
    $_SESSION['department'] = $staff['department'];
    $_SESSION['login_time'] = time();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Login berhasil! Selamat datang di Hotel Senang Hati.',
        'data' => [
            'staff_id' => $staff['staff_id'],
            'username' => $staff['username'],
            'full_name' => $staff['first_name'] . ' ' . $staff['last_name'],
            'email' => $staff['email'],
            'role' => $staff['role'],
            'department' => $staff['department'],
            'session_id' => session_id()
        ]
    ]);

} catch (Exception $e) {
    error_log("Demo Login Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.'
    ]);
}
?>
