<?php

/**
 * Core Application Bootstrap
 * Monastery Healthcare System v2.0
 */

namespace App\Core;

class Application
{
    private static $instance = null;
    private $config;
    private $database;
    private $router;
    private $session;
    
    private function __construct()
    {
        $this->loadConfig();
        $this->initializeSession();
        $this->initializeDatabase();
        $this->initializeRouter();
        $this->registerErrorHandlers();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function run()
    {
        try {
            // Handle CORS for API requests
            $this->handleCORS();
            
            // Route the request
            $this->router->dispatch();
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    private function loadConfig()
    {
        $this->config = new Config();
        
        // Load environment variables
        if (file_exists(__DIR__ . '/../../.env')) {
            $env = parse_ini_file(__DIR__ . '/../../.env');
            foreach ($env as $key => $value) {
                $_ENV[$key] = $value;
            }
        }
    }
    
    private function initializeSession()
    {
        $this->session = new SessionManager();
        $this->session->start();
    }
    
    private function initializeDatabase()
    {
        $this->database = new Database([
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'database' => $_ENV['DB_NAME'] ?? 'monastery_healthcare_v2',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => 'utf8mb4'
        ]);
    }
    
    private function initializeRouter()
    {
        $this->router = new Router();
        $this->loadRoutes();
    }
    
    private function loadRoutes()
    {
        // Load route definitions
        require_once __DIR__ . '/routes.php';
    }
    
    private function registerErrorHandlers()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    private function handleCORS()
    {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            }
            
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }
            
            exit(0);
        }
    }
    
    public function handleError($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            return;
        }
        
        Logger::error("PHP Error: {$message}", [
            'file' => $file,
            'line' => $line,
            'severity' => $severity
        ]);
        
        if ($this->config->get('debug', false)) {
            echo "<pre>Error: {$message} in {$file}:{$line}</pre>";
        }
    }
    
    public function handleException($exception)
    {
        Logger::error("Uncaught Exception: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        if ($this->config->get('debug', false)) {
            echo "<pre>" . $exception . "</pre>";
        } else {
            http_response_code(500);
            require_once __DIR__ . '/../../templates/errors/500.php';
        }
    }
    
    public function handleShutdown()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
    
    // Getters
    public function getConfig() { return $this->config; }
    public function getDatabase() { return $this->database; }
    public function getRouter() { return $this->router; }
    public function getSession() { return $this->session; }
}