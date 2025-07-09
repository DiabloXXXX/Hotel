<?php
// api/auth/middleware.php - Authentication Middleware
session_start();

function requireAuth() {
    if (!isset($_SESSION['staff_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
        exit;
    }
}

function requireRole($allowedRoles = []) {
    requireAuth();
    
    if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda tidak memiliki hak akses yang diperlukan.']);
        exit;
    }
}

// Auto-require authentication for API calls
requireAuth();
?>
