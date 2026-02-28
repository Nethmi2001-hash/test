<?php

namespace App\Core;

class Config
{
    private $config = [];
    
    public function __construct()
    {
        $this->loadDefaults();
        $this->loadFromDatabase();
    }
    
    private function loadDefaults()
    {
        $this->config = [
            'app' => [
                'name' => 'Monastery Healthcare System',
                'version' => '2.0.0',
                'debug' => $_ENV['APP_DEBUG'] ?? false,
                'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Colombo',
                'url' => $_ENV['APP_URL'] ?? 'http://localhost'
            ],
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'name' => $_ENV['DB_NAME'] ?? 'monastery_healthcare_v2',
                'user' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASS'] ?? ''
            ],
            'session' => [
                'timeout' => 1800, // 30 minutes
                'name' => 'MHS_SESSION',
                'secure' => $_ENV['HTTPS'] ?? false,
                'httponly' => true
            ],
            'security' => [
                'csrf_token_name' => '_token',
                'hash_algorithm' => 'sha256',
                'encryption_key' => $_ENV['ENCRYPTION_KEY'] ?? 'default-key-change-this'
            ]
        ];
    }
    
    private function loadFromDatabase()
    {
        // Load settings from database if available
        // This will be implemented after database connection is established
    }
    
    public function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $k) {
            $config = &$config[$k];
        }
        
        $config = $value;
    }
    
    public function all()
    {
        return $this->config;
    }
}