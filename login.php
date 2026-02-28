<?php
session_start();

require_once __DIR__ . '/includes/db_config.php';

$con = getDBConnection();

$error = "";
$success = "";

if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success = "You have been logged out successfully.";
}

if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $error = "Your session has expired. Please login again.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Both email and password are required.";
    } else {
        $stmt = $con->prepare("SELECT u.user_id, u.name, u.email, u.password_hash, u.role_id, r.role_name 
                               FROM users u 
                               JOIN roles r ON u.role_id = r.role_id 
                               WHERE u.email = ? AND u.status = 'active'
                               LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $user = null;
        if (method_exists($stmt, 'get_result')) {
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $stmt->bind_result($user_id, $name, $email_db, $password_hash, $role_id, $role_name);
            if ($stmt->fetch()) {
                $user = [
                    'user_id' => $user_id,
                    'name' => $name,
                    'email' => $email_db,
                    'password_hash' => $password_hash,
                    'role_id' => $role_id,
                    'role_name' => $role_name
                ];
            }
        }

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['last_activity'] = time();
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Seela Suwa Herath Bikshu Gilan Arana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/modern-design.css">
</head>
<body>
    <div class="auth-page">
        <!-- Hero Section -->
        <div class="auth-hero">
            <div class="auth-hero-content">
                <div class="auth-hero-badge"><i class="bi bi-heart-pulse"></i> Sacred Care Portal</div>
                <h1>Seela Suwa Herath<br>Bikshu Gilan Arana</h1>
                <p>Comprehensive healthcare coordination and donation management system for monastic communities.</p>
                
                <ul class="auth-features">
                    <li>
                        <i class="bi bi-shield-check"></i>
                        <span>Secure healthcare coordination for monks</span>
                    </li>
                    <li>
                        <i class="bi bi-cash-coin"></i>
                        <span>Transparent donation & financial management</span>
                    </li>
                    <li>
                        <i class="bi bi-calendar2-check"></i>
                        <span>Smart appointment scheduling system</span>
                    </li>
                    <li>
                        <i class="bi bi-robot"></i>
                        <span>AI-powered bilingual assistant</span>
                    </li>
                    <li>
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Real-time reports & analytics</span>
                    </li>
                </ul>

                <div style="margin-top:36px;padding-top:24px;border-top:1px solid rgba(255,255,255,0.15);">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <img src="images/img1.jpeg" alt="Founder" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.3);">
                        <div>
                            <div style="font-weight:600;font-size:14px;">Ven. Solewewa Chandrasiri Thero</div>
                            <div style="opacity:0.6;font-size:12px;">Founder & Spiritual Guide</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login Form Section -->
        <div class="auth-form-section">
            <div class="auth-form-wrapper">
                <div class="auth-form-card">
                    <h2 class="auth-form-title">Welcome back</h2>
                    <p class="auth-form-subtitle">Sign in to your account to continue</p>

                    <?php if ($error): ?>
                        <div class="alert-modern alert-danger-modern">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert-modern alert-success-modern">
                            <i class="bi bi-check-circle-fill"></i>
                            <span><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="auth-demo-box">
                        <div class="auth-demo-label"><i class="bi bi-key me-1"></i> Demo Credentials</div>
                        <div class="auth-demo-item">admin@monastery.lk</div>
                        <div class="auth-demo-item">admin123</div>
                    </div>

                    <form method="POST">
                        <div class="form-group-modern">
                            <label class="form-label-modern" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control-modern" placeholder="you@example.com" required>
                        </div>

                        <div class="form-group-modern">
                            <label class="form-label-modern" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control-modern" placeholder="Enter your password" required>
                        </div>

                        <button type="submit" class="btn-modern btn-primary-modern btn-lg-modern" style="width:100%;margin-top:8px;" data-loading="Signing in...">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In
                        </button>
                    </form>

                    <div class="divider" style="margin:24px 0;"></div>

                    <div class="auth-footer-text">
                        Don't have an account? <a href="register.php">Create one</a>
                    </div>
                </div>

                <!-- Trust Badges -->
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:16px;">
                    <div style="text-align:center;padding:16px 8px;background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--border-radius);box-shadow:var(--shadow-xs);">
                        <i class="bi bi-shield-lock" style="font-size:20px;color:var(--primary-600);display:block;margin-bottom:6px;"></i>
                        <span style="font-size:11px;font-weight:600;color:var(--text-secondary);">Secure Access</span>
                    </div>
                    <div style="text-align:center;padding:16px 8px;background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--border-radius);box-shadow:var(--shadow-xs);">
                        <i class="bi bi-clipboard2-pulse" style="font-size:20px;color:var(--primary-600);display:block;margin-bottom:6px;"></i>
                        <span style="font-size:11px;font-weight:600;color:var(--text-secondary);">Healthcare</span>
                    </div>
                    <div style="text-align:center;padding:16px 8px;background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--border-radius);box-shadow:var(--shadow-xs);">
                        <i class="bi bi-person-hearts" style="font-size:20px;color:var(--primary-600);display:block;margin-bottom:6px;"></i>
                        <span style="font-size:11px;font-weight:600;color:var(--text-secondary);">Compassion</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Donate Button -->
    <a href="public_donate.php" class="btn-modern btn-primary-modern" style="position:fixed;bottom:24px;right:24px;border-radius:var(--border-radius-full);padding:14px 24px;box-shadow:var(--shadow-lg);z-index:9999;font-size:14px;" title="Support Monastery Healthcare">
        <i class="bi bi-hearts"></i> Donate
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/modern-app.js"></script>
</body>
</html>