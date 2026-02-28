<?php
// Handle login form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            // Check if database is available
            $db = Database::getInstance();
            
            // Attempt login
            $user = $auth->login($email, $password);
            
            // Redirect to dashboard (role-based routing happens there)
            header('Location: dashboard');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
            
            // If database doesn't exist, show setup message
            if (strpos($error, 'database') !== false || strpos($error, 'Connection') !== false) {
                $error = 'Database not found. Please run the setup first.';
                $showSetupButton = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Monastery Healthcare System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); max-width: 400px; width: 100%; }
        .logo { text-align: center; margin-bottom: 2rem; }
        .logo h1 { color: #2563eb; font-size: 1.8rem; margin-bottom: 0.5rem; }
        .logo p { color: #64748b; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 500; }
        .form-group input { width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem; transition: border-color 0.2s; }
        .form-group input:focus { outline: none; border-color: #2563eb; }
        .btn { width: 100%; padding: 0.75rem; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .btn:hover { background: #1d4ed8; transform: translateY(-1px); }
        .error { background: #fef2f2; color: #dc2626; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; border-left: 4px solid #dc2626; }
        .info { background: #f0f9ff; color: #0369a1; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; border-left: 4px solid #0369a1; }
        .back-link { text-align: center; margin-top: 1.5rem; }
        .back-link a { color: #2563eb; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
        .credentials { background: #f8fafc; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.875rem; }
        .credentials strong { color: #1f2937; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🏥 MHS v2.0</h1>
            <p>Monastery Healthcare System</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class=\"error\"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class=\"info\"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (isset($showSetupButton)): ?>
            <div class=\"info\">
                <strong>⚠️ Database Setup Required</strong><br>
                The system database needs to be installed first.
            </div>
            <div style=\"text-align: center; margin: 1.5rem 0;\">
                <a href=\"setup\" style=\"display: inline-block; padding: 0.75rem 1.5rem; background: #059669; color: white; text-decoration: none; border-radius: 8px; font-weight: 500;\">
                    🛠️ Run Database Setup
                </a>
            </div>
        <?php else: ?>
            <div class=\"info\">
                <strong>🏥 Welcome to the Complete System!</strong><br>
                Full database integration with all features enabled.
            </div>
        <?php endif; ?>
        
        <div class=\"credentials\">
            <strong>Default Login Credentials:</strong><br>
            Email: <strong>admin@monastery.lk</strong><br>
            Password: <strong>admin123</strong><br><br>
            
            <strong>Other Test Users:</strong><br>
            👨‍⚕️ Doctor: <strong>doctor@monastery.lk</strong> / <strong>doctor123</strong><br>
            🧘 Monk: <strong>monk@monastery.lk</strong> / <strong>monk123</strong><br>
            💝 Donator: <strong>donor@monastery.lk</strong> / <strong>donor123</strong>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email ?? 'admin@monastery.lk') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            
            <button type="submit" class="btn">Sign In</button>
        </form>
        
        <div class="back-link">
            <a href="./">&larr; Back to Homepage</a>
        </div>
    </div>
</body>
</html>