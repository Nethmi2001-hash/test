<?php
/**
 * Main Entry Point - Monastery Healthcare System v2.0
 * Complete system with database integration
 */

session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include configuration and classes
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';

// Initialize authentication
$auth = new Auth();

// Get the request URI and clean it
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Remove the script name from the URI to get the path
$base_path = dirname($script_name);
if ($base_path !== '/') {
    $request_uri = substr($request_uri, strlen($base_path));
}

// Remove query string and get clean path
$path = strtok($request_uri, '?');
$route = trim($path, '/');

// Handle empty route (homepage)
if (empty($route) || $route === 'index.php') {
    $route = 'home';
}

// Simple routing system
switch ($route) {
    case 'home':
    case '':
        // Homepage
        include __DIR__ . '/pages/home.php';
        break;
        
    case 'setup':
        // Database setup
        include __DIR__ . '/../setup/install.php';
        break;
        
    case 'login':
        // Login page
        include __DIR__ . '/pages/login.php';
        break;
    
    case 'register':
        // Donator registration
        include __DIR__ . '/pages/register.php';
        break;
        
    case 'dashboard':
        // Check if logged in
        if (!$auth->isLoggedIn()) {
            header('Location: ' . $base_path . '/login');
            exit;
        }
        include __DIR__ . '/pages/dashboard.php';
        break;
        
    case 'logout':
        // Logout and redirect
        include __DIR__ . '/pages/logout.php';
        break;
        
    case 'test':
        // Test page
        include __DIR__ . '/pages/test.php';
        break;
        
    case 'debug':
        // Debug/diagnostics page
        include __DIR__ . '/pages/debug.php';
        break;
        
    default:
        // 404 page
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 - Page Not Found</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
                .error-container { background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); max-width: 500px; width: 100%; text-align: center; }
                h1 { color: #dc2626; font-size: 4rem; margin-bottom: 1rem; }
                h2 { color: #1f2937; margin-bottom: 1rem; }
                p { color: #6b7280; margin-bottom: 2rem; }
                .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; font-weight: 500; transition: all 0.2s; }
                .btn:hover { background: #1d4ed8; transform: translateY(-1px); }
                .debug { background: #f3f4f6; padding: 1rem; border-radius: 6px; margin-top: 2rem; font-size: 0.875rem; color: #374151; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>404</h1>
                <h2>Oops! Page Not Found</h2>
                <p>The page you're looking for doesn't exist in our monastery system.</p>
                <div class="debug">
                    <strong>Requested:</strong> <?= htmlspecialchars($route) ?><br>
                    <strong>Available:</strong> home, login, dashboard, logout, test, debug
                </div>
                <div style="margin-top: 2rem;">
                    <a href="<?= $base_path ?>/" class="btn">🏠 Return Home</a>
                </div>
            </div>
        </body>
        </html>
        <?php
        break;
}
?>