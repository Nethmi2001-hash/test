<?php
/**
 * Authentication Class - Monastery Healthcare System
 */

class Auth {
    private $db;
    private $sessionTimeout;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            $this->db = null; // DB not available yet (pre-setup)
        }
        $this->sessionTimeout = SESSION_TIMEOUT;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Authenticate user login
     */
    public function login($email, $password) {
        // Rate limiting check
        if ($this->isAccountLocked($email)) {
            throw new Exception('Account is temporarily locked due to multiple failed attempts.');
        }
        
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = ? AND status = 'active'",
            [$email]
        );
        
        if (!$user || !password_verify($password, $user['password'])) {
            $this->recordFailedAttempt($email);
            throw new Exception('Invalid email or password.');
        }
        
        // Clear failed attempts
        $this->clearFailedAttempts($email);
        
        // Update last login
        $this->db->update('users', 
            ['last_login' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$user['id']]
        );
        
        // Create session
        $this->createSession($user);
        
        // Log successful login
        $this->logActivity($user['id'], 'login', 'users', $user['id']);
        
        return $user;
    }
    
    /**
     * Create user session
     */
    private function createSession($user) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Get role-specific data
        switch ($user['role']) {
            case 'monk':
                $roleData = $this->db->fetch(
                    "SELECT monk_id, room_id FROM monks WHERE user_id = ?",
                    [$user['id']]
                );
                $_SESSION['monk_id'] = $roleData['monk_id'] ?? null;
                $_SESSION['room_id'] = $roleData['room_id'] ?? null;
                break;
                
            case 'doctor':
                $roleData = $this->db->fetch(
                    "SELECT doctor_id, specialization FROM doctors WHERE user_id = ?",
                    [$user['id']]
                );
                $_SESSION['doctor_id'] = $roleData['doctor_id'] ?? null;
                $_SESSION['specialization'] = $roleData['specialization'] ?? null;
                break;
                
            case 'donator':
                $roleData = $this->db->fetch(
                    "SELECT donator_id, total_donated FROM donators WHERE user_id = ?",
                    [$user['id']]
                );
                $_SESSION['donator_id'] = $roleData['donator_id'] ?? null;
                $_SESSION['total_donated'] = $roleData['total_donated'] ?? 0;
                break;
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }
        
        // Check session timeout
        if ((time() - $_SESSION['last_activity']) > $this->sessionTimeout) {
            $this->logout('Session expired');
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Check user role
     */
    public function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    /**
     * Check multiple roles
     */
    public function hasAnyRole($roles) {
        return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $roles);
    }
    
    /**
     * Get current user data
     */
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
        ];
    }
    
    /**
     * Logout user
     */
    public function logout($reason = 'User logout') {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id'], null, ['reason' => $reason]);
        }
        
        session_unset();
        session_destroy();
        
        // Start new session for flash messages
        session_start();
        session_regenerate_id(true);
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($email) {
        $key = 'failed_attempts_' . md5($email);
        $attempts = $_SESSION[$key] ?? [];
        $attempts[] = time();
        $_SESSION[$key] = $attempts;
    }
    
    /**
     * Clear failed login attempts
     */
    private function clearFailedAttempts($email) {
        $key = 'failed_attempts_' . md5($email);
        unset($_SESSION[$key]);
    }
    
    /**
     * Check if account is locked
     */
    private function isAccountLocked($email) {
        $key = 'failed_attempts_' . md5($email);
        $attempts = $_SESSION[$key] ?? [];
        
        // Remove attempts older than lockout time
        $cutoff = time() - LOGIN_LOCKOUT_TIME;
        $attempts = array_filter($attempts, function($time) use ($cutoff) {
            return $time > $cutoff;
        });
        
        $_SESSION[$key] = $attempts;
        
        return count($attempts) >= MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Log user activity
     */
    public function logActivity($userId, $action, $tableName, $recordId, $oldValues = null, $newValues = null) {
        try {
            $this->db->insert('audit_logs', [
                'user_id' => $userId,
                'action' => $action,
                'table_name' => $tableName,
                'record_id' => $recordId,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
    
    /**
     * Register new user
     */
    public function register($userData, $roleData = []) {
        $this->db->beginTransaction();
        
        try {
            // Hash password
            $userData['password'] = password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            
            // Insert user
            $userId = $this->db->insert('users', $userData);
            
            // Insert role-specific data
            if (!empty($roleData)) {
                $roleData['user_id'] = $userId;
                
                switch ($userData['role']) {
                    case 'monk':
                        $this->db->insert('monks', $roleData);
                        break;
                    case 'doctor':
                        $this->db->insert('doctors', $roleData);
                        break;
                    case 'donator':
                        $this->db->insert('donators', $roleData);
                        break;
                }
            }
            
            $this->db->commit();
            
            // Log registration
            $this->logActivity($userId, 'register', 'users', $userId, null, $userData);
            
            return $userId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new Exception('Current password is incorrect.');
        }
        
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        
        $this->db->update('users', 
            ['password' => $newHash], 
            'id = ?', 
            [$userId]
        );
        
        $this->logActivity($userId, 'password_change', 'users', $userId);
        
        return true;
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            http_response_code(403);
            throw new Exception('Access denied. Insufficient permissions.');
        }
    }
    
    /**
     * Require any of the specified roles
     */
    public function requireAnyRole($roles) {
        $this->requireAuth();
        
        if (!$this->hasAnyRole($roles)) {
            http_response_code(403);
            throw new Exception('Access denied. Insufficient permissions.');
        }
    }
}