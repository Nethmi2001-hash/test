<?php

namespace App\Core;

class SessionManager
{
    private $config;
    
    public function __construct()
    {
        $this->config = Application::getInstance()->getConfig();
    }
    
    public function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure session settings
            ini_set('session.cookie_httponly', $this->config->get('session.httponly', true));
            ini_set('session.cookie_secure', $this->config->get('session.secure', false));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.gc_maxlifetime', $this->config->get('session.timeout', 1800));
            
            session_name($this->config->get('session.name', 'MHS_SESSION'));
            session_start();
            
            $this->validateSession();
        }
    }
    
    private function validateSession()
    {
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $timeout = $this->config->get('session.timeout', 1800);
            if (time() - $_SESSION['last_activity'] > $timeout) {
                $this->destroy();
                return;
            }
        }
        
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 3600) { // 1 hour
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }
    
    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
    
    public function has($key)
    {
        return isset($_SESSION[$key]);
    }
    
    public function remove($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public function destroy()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            session_destroy();
        }
    }
    
    public function regenerate()
    {
        session_regenerate_id(true);
    }
    
    public function flash($key, $value = null)
    {
        if ($value === null) {
            $flash = $this->get('_flash_' . $key);
            $this->remove('_flash_' . $key);
            return $flash;
        } else {
            $this->set('_flash_' . $key, $value);
        }
    }
}