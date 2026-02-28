<?php
session_start();
require_once __DIR__ . '/includes/db_config.php';

$error = "";
$success = "";
$con = getDBConnection();

$roles = [];
$result = $con->query("SELECT role_id, role_name FROM roles WHERE role_name IN ('Doctor', 'Donor', 'Monk') ORDER BY role_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role_id = $_POST['role_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($role_id) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!preg_match('/^(071|072|073|074|075|076|077|078|070)\d{7}$/', $phone)) {
        $error = "Please enter a valid Sri Lankan phone number (0712345678).";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $password_confirm) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $con->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            $error = "This email is already registered.";
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $con->prepare("INSERT INTO users (name, email, phone, role_id, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("sssss", $name, $email, $phone, $role_id, $password_hash);
            
            if ($stmt->execute()) {
                $success = "Registration successful! Redirecting to login...";
                header("refresh:2;url=login.php");
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sacred Care</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/modern-design.css">
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; min-height: 100vh; background: var(--bg-primary); }
        .register-layout { min-height: 100vh; display: grid; grid-template-columns: 420px 1fr; }
        .hero-panel {
            background: linear-gradient(160deg, var(--primary-600) 0%, var(--primary-700) 60%, #5C3D2E 100%);
            color: #fff; padding: 48px 36px; display: flex; flex-direction: column; justify-content: center;
            position: relative; overflow: hidden;
        }
        .hero-panel::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 20% 15%, rgba(245,158,11,0.18), transparent 50%);
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25);
            border-radius: 999px; padding: 6px 14px; font-size: 12px; font-weight: 500;
            margin-bottom: 16px; position: relative; z-index: 1; width: fit-content;
        }
        .hero-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 32px; font-weight: 800; margin-bottom: 8px; position: relative; z-index: 1; letter-spacing: -0.5px; }
        .hero-subtitle { font-size: 14px; opacity: 0.9; position: relative; z-index: 1; margin-bottom: 24px; line-height: 1.6; }
        .hero-features { list-style: none; padding: 0; margin: 0; position: relative; z-index: 1; }
        .hero-features li { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; font-size: 13.5px; font-weight: 500; }
        .hero-features li .icon-wrap { width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; }
        .founder-strip { margin-top: 32px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.15); display: flex; align-items: center; gap: 12px; position: relative; z-index: 1; }
        .founder-strip img { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.4); }
        .founder-strip .info { font-size: 13px; line-height: 1.4; }
        .founder-strip .info .name { font-weight: 600; }
        .founder-strip .info .role { opacity: 0.7; font-size: 12px; }

        .form-side { padding: 40px; display: flex; align-items: center; justify-content: center; }
        .register-card {
            width: 100%; max-width: 580px;
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: var(--radius-xl); box-shadow: var(--shadow-lg);
            padding: 36px 40px;
        }
        .form-header { margin-bottom: 24px; }
        .form-header h1 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 28px; font-weight: 800; color: var(--accent); margin-bottom: 6px; }
        .form-header p { font-size: 14px; color: var(--text-secondary); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-grid .full-width { grid-column: 1 / -1; }
        .form-group { margin-bottom: 0; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; letter-spacing: 0.5px; text-transform: uppercase; }
        .form-group input, .form-group select {
            width: 100%; padding: 11px 14px; border: 1.5px solid var(--border-color);
            border-radius: var(--radius-lg); font-size: 14px; font-family: inherit;
            background: var(--bg-primary); transition: all 0.2s; color: var(--text-primary);
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary-500); box-shadow: 0 0 0 3px rgba(110,134,98,0.12); background: var(--bg-card); }
        .form-group select { cursor: pointer; appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 12px center; background-size: 18px; padding-right: 36px; }
        .form-hint { font-size: 11px; color: var(--text-secondary); margin-top: 4px; }

        .alert-modern-reg { padding: 12px 16px; border-radius: var(--radius-lg); margin-bottom: 16px; font-size: 13px; display: flex; align-items: center; gap: 10px; border-left: 3px solid; }
        .alert-modern-reg.error { background: #fef2f2; border-left-color: #ef4444; color: #991b1b; }
        .alert-modern-reg.success { background: #f0fdf4; border-left-color: #22c55e; color: #166534; }

        .btn-register {
            width: 100%; padding: 13px 24px; background: var(--primary-600); color: #fff;
            border: none; border-radius: var(--radius-lg); font-size: 14px; font-weight: 600;
            cursor: pointer; transition: all 0.3s; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .btn-register:hover { background: var(--primary-700); box-shadow: 0 4px 14px rgba(110,134,98,0.3); transform: translateY(-1px); }

        .divider { height: 1px; background: var(--border-color); margin: 24px 0; }
        .login-link { text-align: center; font-size: 13px; color: var(--text-secondary); }
        .login-link a { color: var(--accent); font-weight: 600; text-decoration: none; text-transform: uppercase; letter-spacing: 0.3px; }
        .login-link a:hover { color: var(--primary-600); }

        @media (max-width: 1024px) { .register-layout { grid-template-columns: 1fr; } .hero-panel { padding: 28px; } }
        @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } .register-card { padding: 24px; } .form-header h1 { font-size: 24px; } }
    </style>
</head>
<body>
    <div class="register-layout">
        <div class="hero-panel">
            <div class="hero-badge"><i class="bi bi-person-hearts"></i> Join Our Community</div>
            <h2 class="hero-title">Create Your Account</h2>
            <p class="hero-subtitle">Join the Sacred Care platform to support monastery healthcare and make a meaningful difference.</p>
            <ul class="hero-features">
                <li><span class="icon-wrap"><i class="bi bi-shield-check"></i></span> Secure role-based access control</li>
                <li><span class="icon-wrap"><i class="bi bi-heart-pulse"></i></span> Healthcare management tools</li>
                <li><span class="icon-wrap"><i class="bi bi-graph-up-arrow"></i></span> Transparent donation tracking</li>
                <li><span class="icon-wrap"><i class="bi bi-people"></i></span> Compassionate community platform</li>
            </ul>
            <div class="founder-strip">
                <img src="images/img1.jpeg" alt="Founder">
                <div class="info">
                    <div class="name">Ven. Solewewa Chandrasiri Thero</div>
                    <div class="role">Monastery Healthcare & Donation Platform</div>
                </div>
            </div>
        </div>

        <div class="form-side">
            <div class="register-card">
                <div class="form-header">
                    <h1>Create Account</h1>
                    <p>Fill in your details to join the monastery healthcare platform.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert-modern-reg error">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert-modern-reg success">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" placeholder="John Smith"
                                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="your@email.com"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="0712345678"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                            <div class="form-hint">Sri Lankan format (071-078)</div>
                        </div>

                        <div class="form-group">
                            <label for="role_id">Role</label>
                            <select id="role_id" name="role_id" required>
                                <option value="">Select role...</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="••••••••" required>
                            <div class="form-hint">Minimum 6 characters</div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm">Confirm Password</label>
                            <input type="password" id="password_confirm" name="password_confirm" placeholder="••••••••" required>
                        </div>

                        <div class="form-group full-width" style="margin-top: 8px;">
                            <button type="submit" class="btn-register">
                                <i class="bi bi-person-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </div>
                </form>

                <div class="divider"></div>

                <div class="login-link">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>
            </div>
        </div>
    </div>

    <a href="public_donate.php" class="donation-cta" title="Support Monastery Healthcare" style="position:fixed;bottom:20px;right:20px;background:linear-gradient(135deg,var(--primary-600),var(--primary-700));color:#fff;padding:14px 24px;border-radius:50px;font-size:14px;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:10px;box-shadow:0 8px 32px rgba(110,134,98,0.4);z-index:9999;border:2px solid rgba(255,255,255,0.3);transition:all 0.3s;">
        <i class="bi bi-hearts"></i> Offer Helping Hand
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>