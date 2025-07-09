<?php
/**
 * Authentication Controller
 * Hotel Senang Hati - Staff Authentication
 */

require_once '../config/database.php';

class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Handle HTTP requests
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        try {
            switch ($method) {
                case 'POST':
                    $this->handlePost($pathParts);
                    break;
                case 'GET':
                    $this->handleGet($pathParts);
                    break;
                case 'PUT':
                    $this->handlePut($pathParts);
                    break;
                default:
                    ApiResponse::error('Method not allowed', 405);
            }
        } catch (InvalidArgumentException $e) {
            ApiResponse::validation(null, $e->getMessage());
        } catch (Exception $e) {
            error_log("Auth Controller Error: " . $e->getMessage());
            ApiResponse::error('Internal server error', 500);
        }
    }
    
    /**
     * Handle POST requests
     */
    private function handlePost($pathParts) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ApiResponse::validation(null, 'Invalid JSON input');
        }
        
        // POST /api/auth/login - Staff login
        if (count($pathParts) == 3 && $pathParts[1] == 'auth' && $pathParts[2] == 'login') {
            $this->login($input);
        }
        
        // POST /api/auth/logout - Staff logout
        elseif (count($pathParts) == 3 && $pathParts[1] == 'auth' && $pathParts[2] == 'logout') {
            $this->logout();
        }
        
        // POST /api/auth/register - Register new staff (admin only)
        elseif (count($pathParts) == 3 && $pathParts[1] == 'auth' && $pathParts[2] == 'register') {
            $this->register($input);
        }
        
        else {
            ApiResponse::notFound('Endpoint not found');
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($pathParts) {
        // GET /api/auth/profile - Get current staff profile
        if (count($pathParts) == 3 && $pathParts[1] == 'auth' && $pathParts[2] == 'profile') {
            $this->getProfile();
        }
        
        // GET /api/auth/check - Check if user is authenticated
        elseif (count($pathParts) == 3 && $pathParts[1] == 'auth' && $pathParts[2] == 'check') {
            $this->checkAuth();
        }
        
        else {
            ApiResponse::notFound('Endpoint not found');
        }
    }
    
    /**
     * Handle PUT requests
     */
    private function handlePut($pathParts) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ApiResponse::validation(null, 'Invalid JSON input');
        }
        
        // PUT /api/auth/password - Change password
        if (count($pathParts) == 3 && $pathParts[1] == 'auth' && $pathParts[2] == 'password') {
            $this->changePassword($input);
        }
        
        // PUT /api/auth/profile - Update profile
        elseif (count($pathParts) == 3 && $pathParts[1] == 'auth' && $pathParts[2] == 'profile') {
            $this->updateProfile($input);
        }
        
        else {
            ApiResponse::notFound('Endpoint not found');
        }
    }
    
    /**
     * Staff login
     */
    private function login($data) {
        // Validate required fields
        Validator::required($data['username'], 'username');
        Validator::required($data['password'], 'password');
        
        // Get staff by username
        $sql = "SELECT * FROM staff WHERE username = :username AND is_active = 1";
        $staff = $this->db->fetch($sql, ['username' => $data['username']]);
        
        if (!$staff) {
            // Increment login attempts for security
            $this->incrementLoginAttempts($data['username']);
            ApiResponse::error('Invalid credentials', 401);
        }
        
        // Check password
        if (!Auth::verifyPassword($data['password'], $staff['password_hash'])) {
            $this->incrementLoginAttempts($data['username']);
            ApiResponse::error('Invalid credentials', 401);
        }
        
        // Check if account is locked (too many failed attempts)
        if ($staff['login_attempts'] >= 5) {
            ApiResponse::error('Account temporarily locked due to too many failed attempts', 423);
        }
        
        // Reset login attempts on successful login
        $this->resetLoginAttempts($staff['staff_id']);
        
        // Update last login
        $this->updateLastLogin($staff['staff_id']);
        
        // Start session
        session_start();
        $_SESSION['staff_id'] = $staff['staff_id'];
        $_SESSION['username'] = $staff['username'];
        $_SESSION['role'] = $staff['role'];
        $_SESSION['first_name'] = $staff['first_name'];
        $_SESSION['last_name'] = $staff['last_name'];
        $_SESSION['permissions'] = $staff['permissions'];
        
        // Remove sensitive data from response
        unset($staff['password_hash']);
        unset($staff['login_attempts']);
        
        ApiResponse::success($staff, 'Login successful');
    }
    
    /**
     * Staff logout
     */
    private function logout() {
        session_start();
        session_destroy();
        
        ApiResponse::success(null, 'Logout successful');
    }
    
    /**
     * Register new staff (admin only)
     */
    private function register($data) {
        // Check if user is authenticated and has admin role
        $session = Auth::validateSession();
        if ($session['role'] !== 'admin') {
            ApiResponse::forbidden('Only admin can register new staff');
        }
        
        // Validate required fields
        Validator::required($data['username'], 'username');
        Validator::required($data['password'], 'password');
        Validator::required($data['first_name'], 'first_name');
        Validator::required($data['last_name'], 'last_name');
        Validator::required($data['email'], 'email');
        Validator::required($data['role'], 'role');
        
        // Validate email format
        Validator::email($data['email']);
        
        // Validate role
        $allowedRoles = ['admin', 'manager', 'receptionist', 'housekeeping', 'maintenance', 'accounting'];
        Validator::in($data['role'], $allowedRoles, 'role');
        
        // Check if username already exists
        $existingUser = $this->db->fetch("SELECT staff_id FROM staff WHERE username = :username", ['username' => $data['username']]);
        if ($existingUser) {
            throw new InvalidArgumentException("Username already exists");
        }
        
        // Check if email already exists
        $existingEmail = $this->db->fetch("SELECT staff_id FROM staff WHERE email = :email", ['email' => $data['email']]);
        if ($existingEmail) {
            throw new InvalidArgumentException("Email already exists");
        }
        
        // Hash password
        $passwordHash = Auth::hashPassword($data['password']);
        
        // Prepare data for insertion
        $insertData = [
            'username' => $data['username'],
            'password_hash' => $passwordHash,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'department' => $data['department'] ?? null,
            'hire_date' => $data['hire_date'] ?? date('Y-m-d'),
            'salary' => isset($data['salary']) ? (float)$data['salary'] : null,
            'permissions' => isset($data['permissions']) ? json_encode($data['permissions']) : json_encode([])
        ];
        
        $staffId = $this->db->insert('staff', $insertData);
        
        // Get the created staff (without password)
        $newStaff = $this->db->fetch("SELECT * FROM staff WHERE staff_id = :staff_id", ['staff_id' => $staffId]);
        unset($newStaff['password_hash']);
        
        ApiResponse::success($newStaff, 'Staff registered successfully', 201);
    }
    
    /**
     * Get current staff profile
     */
    private function getProfile() {
        $session = Auth::validateSession();
        
        $sql = "SELECT * FROM staff WHERE staff_id = :staff_id";
        $staff = $this->db->fetch($sql, ['staff_id' => $session['staff_id']]);
        
        if (!$staff) {
            ApiResponse::error('Staff not found', 404);
        }
        
        // Remove sensitive data
        unset($staff['password_hash']);
        unset($staff['login_attempts']);
        
        ApiResponse::success($staff, 'Profile retrieved successfully');
    }
    
    /**
     * Check authentication status
     */
    private function checkAuth() {
        session_start();
        
        if (isset($_SESSION['staff_id'])) {
            $sessionData = [
                'authenticated' => true,
                'staff_id' => $_SESSION['staff_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'],
                'first_name' => $_SESSION['first_name'],
                'last_name' => $_SESSION['last_name']
            ];
            
            ApiResponse::success($sessionData, 'User is authenticated');
        } else {
            ApiResponse::success(['authenticated' => false], 'User is not authenticated');
        }
    }
    
    /**
     * Change password
     */
    private function changePassword($data) {
        $session = Auth::validateSession();
        
        // Validate required fields
        Validator::required($data['current_password'], 'current_password');
        Validator::required($data['new_password'], 'new_password');
        
        // Get current staff data
        $sql = "SELECT password_hash FROM staff WHERE staff_id = :staff_id";
        $staff = $this->db->fetch($sql, ['staff_id' => $session['staff_id']]);
        
        // Verify current password
        if (!Auth::verifyPassword($data['current_password'], $staff['password_hash'])) {
            ApiResponse::error('Current password is incorrect', 400);
        }
        
        // Validate new password strength
        if (strlen($data['new_password']) < 6) {
            ApiResponse::validation(null, 'New password must be at least 6 characters long');
        }
        
        // Hash new password
        $newPasswordHash = Auth::hashPassword($data['new_password']);
        
        // Update password
        $this->db->update('staff', 
            ['password_hash' => $newPasswordHash], 
            ['staff_id' => $session['staff_id']]
        );
        
        ApiResponse::success(null, 'Password changed successfully');
    }
    
    /**
     * Update profile
     */
    private function updateProfile($data) {
        $session = Auth::validateSession();
        
        // Fields that can be updated
        $allowedFields = ['first_name', 'last_name', 'email', 'phone'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        // Validate email if provided
        if (isset($updateData['email'])) {
            Validator::email($updateData['email']);
            $updateData['email'] = strtolower(trim($updateData['email']));
            
            // Check if email already exists for another staff
            $existingEmail = $this->db->fetch(
                "SELECT staff_id FROM staff WHERE email = :email AND staff_id != :staff_id", 
                ['email' => $updateData['email'], 'staff_id' => $session['staff_id']]
            );
            if ($existingEmail) {
                throw new InvalidArgumentException("Email already exists");
            }
        }
        
        if (empty($updateData)) {
            ApiResponse::validation(null, 'No valid fields to update');
        }
        
        // Update profile
        $this->db->update('staff', $updateData, ['staff_id' => $session['staff_id']]);
        
        // Get updated profile
        $updatedStaff = $this->db->fetch("SELECT * FROM staff WHERE staff_id = :staff_id", ['staff_id' => $session['staff_id']]);
        unset($updatedStaff['password_hash']);
        unset($updatedStaff['login_attempts']);
        
        ApiResponse::success($updatedStaff, 'Profile updated successfully');
    }
    
    /**
     * Increment login attempts
     */
    private function incrementLoginAttempts($username) {
        $sql = "UPDATE staff SET login_attempts = login_attempts + 1 WHERE username = :username";
        $this->db->query($sql, ['username' => $username]);
    }
    
    /**
     * Reset login attempts
     */
    private function resetLoginAttempts($staffId) {
        $sql = "UPDATE staff SET login_attempts = 0 WHERE staff_id = :staff_id";
        $this->db->query($sql, ['staff_id' => $staffId]);
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($staffId) {
        $sql = "UPDATE staff SET last_login = NOW() WHERE staff_id = :staff_id";
        $this->db->query($sql, ['staff_id' => $staffId]);
    }
}

// Create controller and handle request
$controller = new AuthController();
$controller->handleRequest();

?>
