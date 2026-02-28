<?php

namespace App\Middleware;

use App\Core\Application;

class AuthMiddleware
{
    public function handle()
    {
        $session = Application::getInstance()->getSession();
        
        // Check if user is authenticated
        if (!$session->get('user_id')) {
            // Store intended URL for redirect after login
            $session->set('intended_url', $_SERVER['REQUEST_URI']);
            
            // Redirect to login
            header('Location: /login');
            exit;
        }
        
        // Check session timeout
        $loginTime = $session->get('login_time');
        if ($loginTime && (time() - $loginTime) > 86400) { // 24 hours
            $session->destroy();
            header('Location: /login?timeout=1');
            exit;
        }
        
        return true;
    }
}