<?php

namespace App\Core;

class Router
{
    private $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => []
    ];
    
    private $middlewares = [];
    
    public function get($path, $handler, $middlewares = [])
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }
    
    public function post($path, $handler, $middlewares = [])
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }
    
    public function put($path, $handler, $middlewares = [])
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }
    
    public function delete($path, $handler, $middlewares = [])
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }
    
    private function addRoute($method, $path, $handler, $middlewares)
    {
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }
    
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path if running in subdirectory
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        
        $path = $path ?: '/';
        
        // Try exact match first
        if (isset($this->routes[$method][$path])) {
            $route = $this->routes[$method][$path];
            return $this->executeRoute($route, []);
        }
        
        // Try pattern matching
        foreach ($this->routes[$method] as $pattern => $route) {
            $params = $this->matchRoute($pattern, $path);
            if ($params !== false) {
                return $this->executeRoute($route, $params);
            }
        }
        
        // Route not found
        http_response_code(404);
        require_once __DIR__ . '/../templates/errors/404.php';
    }
    
    private function matchRoute($pattern, $path)
    {
        // Convert route pattern to regex
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        if (preg_match($regex, $path, $matches)) {
            array_shift($matches); // Remove full match
            return $matches;
        }
        
        return false;
    }
    
    private function executeRoute($route, $params)
    {
        // Execute middlewares
        foreach ($route['middlewares'] as $middleware) {
            $middlewareInstance = new $middleware();
            if (!$middlewareInstance->handle()) {
                return; // Middleware blocked the request
            }
        }
        
        $handler = $route['handler'];
        
        if (is_string($handler)) {
            // Handle Controller@method format
            if (strpos($handler, '@') !== false) {
                list($controller, $method) = explode('@', $handler);
                $controllerInstance = new $controller();
                return $controllerInstance->$method(...$params);
            }
        } elseif (is_callable($handler)) {
            return $handler(...$params);
        }
    }
}