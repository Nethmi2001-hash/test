<?php

namespace App\Middleware;

use App\Core\Application;

class RoleMiddleware
{
    private $allowedRoles;
    
    public function __construct($allowedRoles = [])
    {
        // Parse roles from string format like "admin,doctor"
        if (is_string($allowedRoles)) {
            $this->allowedRoles = array_map('trim', explode(',', $allowedRoles));
        } else {
            $this->allowedRoles = (array)$allowedRoles;
        }
    }
    
    public function handle()
    {
        $session = Application::getInstance()->getSession();
        
        // First check authentication
        if (!$session->get('user_id')) {
            header('Location: /login');
            exit;
        }
        
        // Check role permissions
        $userRole = $session->get('user_role_slug');
        
        if (!empty($this->allowedRoles) && !in_array($userRole, $this->allowedRoles)) {
            // User doesn't have required role
            http_response_code(403);
            
            ob_start();
            include __DIR__ . '/../../templates/errors/403.php';
            $content = ob_get_clean();
            
            include __DIR__ . '/../../templates/layouts/base.php';
            exit;
        }
        
        return true;
    }
    
    public static function hasRole($roles)
    {
        $session = Application::getInstance()->getSession();
        $userRole = $session->get('user_role_slug');
        
        if (is_string($roles)) {
            $roles = array_map('trim', explode(',', $roles));
        }
        
        return in_array($userRole, (array)$roles);
    }
    
    public static function getUserRole()
    {
        $session = Application::getInstance()->getSession();
        return $session->get('user_role_slug');
    }
}