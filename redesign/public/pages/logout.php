<?php
// Use authentication system for logout
if (isset($auth)) {
    $auth->logout('User requested logout');
} else {
    // Fallback logout
    session_destroy();
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - Monastery Healthcare System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .logout-container { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); max-width: 400px; width: 100%; text-align: center; }
        .logo { margin-bottom: 2rem; }
        .logo h1 { color: #2563eb; font-size: 1.8rem; margin-bottom: 0.5rem; }
        .logo p { color: #64748b; }
        .success { background: #f0f9ff; color: #0369a1; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #0369a1; }
        .success h2 { margin-bottom: 0.5rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; font-weight: 500; transition: all 0.2s; margin-top: 1rem; }
        .btn:hover { background: #1d4ed8; transform: translateY(-1px); }
        .redirect-info { color: #64748b; font-size: 0.875rem; margin-top: 1rem; }
    </style>
    <script>
        // Auto-redirect after 5 seconds
        setTimeout(function() {
            window.location.href = './';
        }, 5000);
    </script>
</head>
<body>
    <div class="logout-container">
        <div class="logo">
            <h1>🏥 MHS v2.0</h1>
            <p>Monastery Healthcare System</p>
        </div>
        
        <div class="success">
            <h2>✅ Successfully Logged Out</h2>
            <p>Thank you for using the Monastery Healthcare System v2.0. Your session has been securely ended.</p>
        </div>
        
        <a href="./" class="btn">Return to Homepage</a>
        
        <div class="redirect-info">
            You will be automatically redirected to the homepage in 5 seconds...
        </div>
    </div>
</body>
</html>