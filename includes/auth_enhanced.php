<?php
/**
 * Enhanced Authentication System
 * Implements the complete authentication flow from the system document
 */

// Session configuration
function configureSession() {
    // Only configure if session hasn't been started yet
    if (session_status() == PHP_SESSION_NONE) {
        // Secure session settings - must be set before session_start()
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
        ini_set('session.cookie_samesite', 'Strict');
        
        // Session timeout (30 minutes)
        ini_set('session.gc_maxlifetime', 1800);
        
        session_start();
    }
}

/**
 * Enhanced login function with role verification
 */
function authenticateUser($email, $password) {
    require_once __DIR__ . '/db_config.php';
    $conn = getDBConnection();
    
    // Input validation and sanitization
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    if (empty($password)) {
        return ['success' => false, 'message' => 'Password is required'];
    }
    
    try {
        // Get user with role information
        $stmt = $conn->prepare("
            SELECT u.user_id, u.name, u.email, u.password_hash, u.role_id, 
                   r.role_name, u.status, u.phone 
            FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.email = ? AND u.status = 'active'
            LIMIT 1
        ");
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Log failed attempt
            logActivity('login_failed', $email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Successful authentication - create secure session
        regenerateSession();
        createUserSession($user);
        
        // Log successful login  
        logActivity('login_success', $user['email'], $user['user_id']);
        
        return [
            'success' => true, 
            'message' => 'Login successful',
            'redirect' => getRoleBasedDashboard($user['role_name'])
        ];
        
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'System error. Please try again.'];
    } finally {
        $conn->close();
    }
}

/**
 * Create secure user session
 */
function createUserSession($user) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['phone'] = $user['phone'] ?? '';
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Generate session token for additional security
    $_SESSION['session_token'] = bin2hex(random_bytes(32));
}

/**
 * Regenerate session ID for security
 */
function regenerateSession() {
    session_regenerate_id(true);
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    configureSession();
    
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
        logoutUser('timeout');
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Check role-based access
 */
function hasRole($required_roles) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $user_role = $_SESSION['role_name'] ?? '';
    
    if (is_array($required_roles)) {
        return in_array(strtolower($user_role), array_map('strtolower', $required_roles));
    }
    
    return strtolower($user_role) === strtolower($required_roles);
}

/**
 * Logout user
 */
function logoutUser($reason = 'manual') {
    if (isset($_SESSION['user_id'])) {
        logActivity('logout', $_SESSION['email'] ?? 'unknown', $_SESSION['user_id'], $reason);
    }
    
    // Clear all session data
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Get role-based dashboard URL
 */
function getRoleBasedDashboard($role_name) {
    switch(strtolower($role_name)) {
        case 'admin':
            return 'dashboard.php';
        case 'doctor':
            return 'dashboard.php';
        case 'monk':
            return 'dashboard.php';
        case 'donor':
            return 'dashboard.php';
        case 'helper':
            return 'dashboard.php';
        default:
            return 'dashboard.php';
    }
}

/**
 * Require authentication for page access
 */
function requireAuth($allowed_roles = null) {
    if (!isAuthenticated()) {
        header("Location: login.php?timeout=1");
        exit();
    }
    
    if ($allowed_roles && !hasRole($allowed_roles)) {
        header("Location: dashboard.php?error=access_denied");
        exit();
    }
}

/**
 * Log system activities
 */
function logActivity($action, $email = null, $user_id = null, $details = null) {
    try {
        require_once __DIR__ . '/db_config.php';
        $conn = getDBConnection();
        
        // Create activity_logs table if not exists
        $conn->query("
            CREATE TABLE IF NOT EXISTS activity_logs (
                log_id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NULL,
                email VARCHAR(150) NULL,
                action VARCHAR(50) NOT NULL,
                details TEXT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_action (action),
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB
        ");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, email, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("isssss", $user_id, $email, $action, $details, $ip_address, $user_agent);
        $stmt->execute();
        
        $conn->close();
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>