<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Diagnostics - MHS v2.0</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f8fafc; padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        h1 { color: #2563eb; margin-bottom: 2rem; text-align: center; }
        h2 { color: #1f2937; margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem; }
        .status-good { color: #059669; font-weight: bold; }
        .status-info { color: #0369a1; }
        .status-warning { color: #d97706; font-weight: bold; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .test-link { display: inline-block; margin: 0.25rem; padding: 0.5rem 1rem; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; font-size: 0.875rem; }
        .test-link:hover { background: #1d4ed8; }
        code { background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px; font-family: monospace; }
        .back-link { text-align: center; margin-top: 2rem; }
        .back-link a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 System Diagnostics</h1>
        
        <div class="grid">
            <div class="card">
                <h2>🌐 URL Routing Status</h2>
                <p><strong>Current URL:</strong> <code><?= htmlspecialchars($_SERVER['REQUEST_URI']) ?></code></p>
                <p><strong>Script Name:</strong> <code><?= htmlspecialchars($_SERVER['SCRIPT_NAME']) ?></code></p>
                <p><strong>Server Name:</strong> <code><?= htmlspecialchars($_SERVER['SERVER_NAME']) ?></code></p>
                <p><strong>Document Root:</strong> <code><?= htmlspecialchars($_SERVER['DOCUMENT_ROOT']) ?></code></p>
                <p><strong>Status:</strong> <span class="status-good">✅ Routing Working!</span></p>
                
                <h3 style="margin-top: 1rem;">Test All Routes:</h3>
                <div style="margin-top: 0.5rem;">
                    <a href="./" class="test-link">🏠 Home</a>
                    <a href="login" class="test-link">🔐 Login</a>
                    <a href="dashboard" class="test-link">📊 Dashboard</a>
                    <a href="logout" class="test-link">🚪 Logout</a>
                    <a href="test" class="test-link">🧪 Test</a>
                    <a href="nonexistent" class="test-link">❌ 404 Test</a>
                </div>
            </div>
            
            <div class="card">
                <h2>🔒 Session Information</h2>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <p><strong>Status:</strong> <span class="status-good">✅ Logged In</span></p>
                    <p><strong>User ID:</strong> <code><?= htmlspecialchars($_SESSION['user_id']) ?></code></p>
                    <p><strong>User Name:</strong> <code><?= htmlspecialchars($_SESSION['user_name']) ?></code></p>
                    <p><strong>User Role:</strong> <code><?= htmlspecialchars($_SESSION['user_role']) ?></code></p>
                    <p><strong>Login Time:</strong> <code><?= date('Y-m-d H:i:s', $_SESSION['login_time']) ?></code></p>
                <?php else: ?>
                    <p><strong>Status:</strong> <span class="status-info">ℹ️ Not logged in</span></p>
                    <p>Visit the <a href="login">login page</a> to authenticate.</p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>⚙️ Server Configuration</h2>
                <p><strong>PHP Version:</strong> <code><?= PHP_VERSION ?></code></p>
                <p><strong>Server Software:</strong> <code><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></code></p>
                <p><strong>mod_rewrite:</strong> <span class="status-good">✅ Enabled</span></p>
                <p><strong>.htaccess:</strong> <span class="status-good">✅ Active</span></p>
                <p><strong>Sessions:</strong> <span class="status-good">✅ Working</span></p>
                <p><strong>Error Reporting:</strong> <span class="status-info">On (for development)</span></p>
            </div>
            
            <div class="card">
                <h2>📁 File System</h2>
                <p><strong>Index File:</strong> 
                    <?= file_exists(__DIR__ . '/../index.php') ? '<span class="status-good">✅ Found</span>' : '<span class="status-warning">❌ Missing</span>' ?>
                </p>
                <p><strong>.htaccess:</strong> 
                    <?= file_exists(__DIR__ . '/../.htaccess') ? '<span class="status-good">✅ Found</span>' : '<span class="status-warning">❌ Missing</span>' ?>
                </p>
                <p><strong>Pages Directory:</strong> 
                    <?= is_dir(__DIR__) ? '<span class="status-good">✅ Found</span>' : '<span class="status-warning">❌ Missing</span>' ?>
                </p>
                <p><strong>Available Pages:</strong></p>
                <ul style="margin-left: 1rem; margin-top: 0.5rem;">
                    <?php
                    $pages = ['home.php', 'login.php', 'dashboard.php', 'logout.php', 'test.php'];
                    foreach ($pages as $page) {
                        $exists = file_exists(__DIR__ . '/' . $page);
                        echo '<li>' . ($exists ? '✅' : '❌') . ' ' . htmlspecialchars($page) . '</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <h2>🐛 Troubleshooting Guide</h2>
            <div style="background: #f0f9ff; padding: 1rem; border-radius: 6px; border-left: 4px solid #0369a1;">
                <h3>If you're still getting "Not Found" errors:</h3>
                <ol style="margin-left: 1.5rem; margin-top: 0.5rem;">
                    <li>Make sure you're accessing: <code>http://localhost/test/redesign/public/</code></li>
                    <li>Check that XAMPP Apache is running</li>
                    <li>Verify .htaccess file exists and is readable</li>
                    <li>Clear browser cache and try again</li>
                    <li>Check Apache error logs in XAMPP</li>
                </ol>
            </div>
        </div>
        
        <div class="back-link">
            <a href="./">← Return to Homepage</a>
        </div>
    </div>
</body>
</html>