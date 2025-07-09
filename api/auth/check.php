<?php
// api/auth/check.php - Check Staff Authentication Status
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

if (!isset($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Tidak terautentikasi']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => [
        'staff_id' => $_SESSION['staff_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role'],
        'department' => $_SESSION['department']
    ]
]);
?>
