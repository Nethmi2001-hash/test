<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Logger;

class AuthController
{
    private $db;
    private $session;
    
    public function __construct()
    {
        $app = Application::getInstance();
        $this->db = $app->getDatabase();
        $this->session = $app->getSession();
    }
    
    public function showLogin()
    {
        // Redirect if already logged in
        if ($this->session->get('user_id')) {
            header('Location: /dashboard');
            exit;
        }
        
        ob_start();
        include __DIR__ . '/../../templates/pages/auth/login.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../../templates/layouts/base.php';
    }
    
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }
        
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validate input
        if (!$email) {
            $this->addFlashMessage('error', 'Please enter a valid email address');
            header('Location: /login');
            exit;
        }
        
        if (empty($password)) {
            $this->addFlashMessage('error', 'Please enter your password');
            header('Location: /login');
            exit;
        }
        
        try {
            // Get user from database
            $user = $this->db->fetch(
                "SELECT u.*, r.name as role_name, r.slug as role_slug 
                 FROM sys_users u 
                 JOIN sys_roles r ON u.role_id = r.id 
                 WHERE u.email = ? AND u.status = 'active'",
                [$email]
            );
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                Logger::info('Failed login attempt', ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR']]);
                $this->addFlashMessage('error', 'Invalid email or password');
                header('Location: /login');
                exit;
            }
            
            // Create session
            $this->createUserSession($user);
            
            // Log successful login
            Logger::info('Successful login', ['user_id' => $user['id'], 'email' => $email]);
            
            // Update last login
            $this->db->update(
                'sys_users',
                [
                    'last_login_at' => date('Y-m-d H:i:s'),
                    'last_login_ip' => $_SERVER['REMOTE_ADDR']
                ],
                'id = ?',
                [$user['id']]
            );
            
            // Redirect to dashboard
            $this->addFlashMessage('success', 'Welcome back, ' . $user['first_name'] . '!');
            header('Location: /dashboard');
            exit;
            
        } catch (\Exception $e) {
            Logger::error('Login error: ' . $e->getMessage());
            $this->addFlashMessage('error', 'System error. Please try again.');
            header('Location: /login');
            exit;
        }
    }
    
    public function showRegister()
    {
        // Redirect if already logged in
        if ($this->session->get('user_id')) {
            header('Location: /dashboard');
            exit;
        }
        
        ob_start();
        include __DIR__ . '/../../templates/pages/auth/register.php';
        $content = ob_get_clean();
        
        include __DIR__ . '/../../templates/layouts/base.php';
    }
    
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /register');
            exit;
        }
        
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $roleId = (int)($_POST['role_id'] ?? 0);
        
        // Validation
        $errors = [];
        
        if (empty($firstName)) $errors[] = 'First name is required';
        if (empty($lastName)) $errors[] = 'Last name is required';
        if (!$email) $errors[] = 'Valid email is required';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
        if ($password !== $confirmPassword) $errors[] = 'Passwords do not match';
        if (!in_array($roleId, [3, 4, 5])) $errors[] = 'Invalid role selected'; // Only Monk, Donor, Helper can self-register
        
        if (!empty($errors)) {
            $_SESSION['validation_errors'] = $errors;
            header('Location: /register');
            exit;
        }
        
        try {
            // Check if email already exists
            $existing = $this->db->fetch('SELECT id FROM sys_users WHERE email = ?', [$email]);
            if ($existing) {
                $this->addFlashMessage('error', 'An account with this email address already exists');
                header('Location: /register');
                exit;
            }
            
            // Create user
            $userId = $this->db->insert('sys_users', [
                'uuid' => $this->generateUUID(),
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'role_id' => $roleId,
                'status' => 'active'
            ]);
            
            Logger::info('New user registered', ['user_id' => $userId, 'email' => $email]);
            
            $this->addFlashMessage('success', 'Account created successfully! You can now log in.');
            header('Location: /login');
            exit;
            
        } catch (\Exception $e) {
            Logger::error('Registration error: ' . $e->getMessage());
            $this->addFlashMessage('error', 'System error. Please try again.');
            header('Location: /register');
            exit;
        }
    }
    
    public function logout()
    {
        $userId = $this->session->get('user_id');
        if ($userId) {
            Logger::info('User logged out', ['user_id' => $userId]);
        }
        
        $this->session->destroy();
        $this->addFlashMessage('info', 'You have been logged out successfully');
        header('Location: /login');
        exit;
    }
    
    private function createUserSession($user)
    {
        $this->session->regenerate();
        
        $this->session->set('user_id', $user['id']);
        $this->session->set('user_uuid', $user['uuid']);
        $this->session->set('user_email', $user['email']);
        $this->session->set('user_name', $user['first_name'] . ' ' . $user['last_name']);
        $this->session->set('user_role_id', $user['role_id']);
        $this->session->set('user_role_name', $user['role_name']);
        $this->session->set('user_role_slug', $user['role_slug']);
        $this->session->set('login_time', time());
    }
    
    private function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
    
    private function addFlashMessage($type, $message)
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        if (!isset($_SESSION['flash_messages'][$type])) {
            $_SESSION['flash_messages'][$type] = [];
        }
        $_SESSION['flash_messages'][$type][] = $message;
    }
}