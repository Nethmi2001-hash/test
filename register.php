<?php
session_start();
require_once __DIR__ . '/includes/db_config.php';

$error = "";
$success = "";
$con = getDBConnection();

$roles = [];
$result = $con->query("SELECT role_id, role_name FROM roles");
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
    <title>Register - Seela Suwa Herath Bikshu Gilan Arana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/sacred-care-theme.css">
    <link rel="stylesheet" href="assets/css/monastery-theme.css">
    <style>
        :root {
            --sc-primary: #6E8662;
            --sc-primary-dark: #4F6645;
            --sc-secondary: #ECE5D8;
            --sc-accent: #8A5A3B;
            --sc-bg: #F7F4EE;
            --sc-border: #DCD4C7;
            --sc-card: #FFFFFF;
            --sc-muted: #6B7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
            background: radial-gradient(circle at top left, #FFEED9 0%, var(--sc-bg) 40%);
            color: #1f2937;
            line-height: 1.6;
            min-height: 100vh;
        }

        .register-layout {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(320px, 460px) minmax(420px, 1fr);
        }

        .brand-panel {
            background: linear-gradient(160deg, var(--sc-primary) 0%, var(--sc-primary-dark) 70%, #7C2D12 100%);
            color: #fff;
            padding: 42px 34px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 15%, rgba(245, 158, 11, 0.22), transparent 45%);
        }

        .brand-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 14px;
            border: 2px solid rgba(255, 255, 255, 0.22);
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.24);
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.30);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
            width: fit-content;
        }

        .brand-title {
            font-size: 28px;
            font-weight: 650;
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
            letter-spacing: 0.2px;
        }

        .brand-subtitle {
            font-size: 14px;
            opacity: 0.92;
            position: relative;
            z-index: 1;
            margin-bottom: 14px;
        }

        .brand-list {
            list-style: none;
            padding: 0;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .brand-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 13px;
            opacity: 0.95;
        }
        .donation-cta {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%);
            color: #FFFFFF;
            padding: 16px 28px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 800;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 32px rgba(46, 125, 50, 0.5),
                        0 4px 16px rgba(0, 0, 0, 0.2);
            transition: all 0.4s ease;
            letter-spacing: 0.5px;
            z-index: 9999;
            border: 3px solid rgba(255, 255, 255, 0.4);
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            animation: gentle-pulse 3s ease-in-out infinite;
        }

        @keyframes gentle-pulse {
            0%, 100% { box-shadow: 0 8px 32px rgba(46, 125, 50, 0.5), 0 4px 16px rgba(0, 0, 0, 0.2); }
            50% { box-shadow: 0 8px 40px rgba(46, 125, 50, 0.65), 0 4px 20px rgba(0, 0, 0, 0.25); }
        }

        .donation-cta i {
            font-size: 18px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        .donation-cta:hover {
            background: linear-gradient(135deg, #1B5E20 0%, #0D4715 100%);
            box-shadow: 0 12px 48px rgba(46, 125, 50, 0.65),
                        0 6px 24px rgba(0, 0, 0, 0.3);
            transform: translateY(-4px) scale(1.08);
            color: #FFFFFF;
            border-color: rgba(255, 255, 255, 0.6);
        }
        .form-side {
            padding: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-card {
            width: 100%;
            max-width: 560px;
            background: var(--sc-card);
            border: 1px solid var(--sc-border);
            border-radius: 18px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.12);
            padding: 20px 24px;
        }

        .form-header {
            margin-bottom: 14px;
        }

        .form-title {
            font-size: 30px;
            font-weight: 700;
            color: var(--sc-accent);
            margin-bottom: 4px;
            letter-spacing: -0.4px;
        }

        .form-subtitle {
            font-size: 14px;
            color: var(--sc-muted);
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            letter-spacing: 0.45px;
            text-transform: uppercase;
        }

        .form-control, select {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #D8D0C2;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s ease;
            background: #FFFDFA;
        }

        .form-control:focus, select:focus {
            outline: none;
            border-color: var(--sc-primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(194, 65, 12, 0.12);
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 36px;
        }

        .form-hint {
            font-size: 11px;
            color: var(--sc-muted);
            margin-top: 5px;
        }

        .alert-box {
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 13px;
            border-left: 3px solid;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error {
            background: #fef2f2;
            border-left-color: #dc2626;
            color: #991b1b;
        }

        .alert-success {
            background: #f0fdf4;
            border-left-color: #16a34a;
            color: #166534;
        }

        .btn-register {
            width: 100%;
            padding: 12px 24px;
            background: var(--sc-primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 6px;
            text-transform: uppercase;
        }

        .btn-register:hover {
            background: var(--sc-primary-dark);
            box-shadow: 0 4px 12px rgba(194, 65, 12, 0.25);
        }

        .divider-line {
            height: 1px;
            background: #ECE5D9;
            margin: 16px 0;
        }

        .login-section {
            text-align: center;
        }

        .login-text {
            font-size: 13px;
            color: var(--sc-muted);
            margin-bottom: 12px;
        }

        .btn-login {
            color: var(--sc-accent);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            color: var(--sc-secondary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-grid .full-width {
            grid-column: 1 / -1;
        }

        @media (max-width: 1024px) {
            .register-layout {
                grid-template-columns: 1fr;
            }

            .brand-panel {
                padding: 22px;
            }

            .brand-image {
                height: 200px;
            }

            .form-side {
                padding: 18px;
            }

            .register-card {
                padding: 22px;
            }
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-title {
                font-size: 26px;
            }

            .register-card {
                padding: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="register-layout">
        <div class="brand-panel">
            <img src="images/img3.jpeg" alt="Seela Suwa Herath" class="brand-image">
            <div class="brand-badge"><i class="bi bi-person-hearts"></i> Helping-Hand Community</div>
            <h2 class="brand-title">Join Sacred Care</h2>
            <p class="brand-subtitle">Create your account to support monastery healthcare and donations.</p>
            <ul class="brand-list">
                <li><i class="bi bi-check-circle"></i> Secure account with role-based access</li>
                <li><i class="bi bi-check-circle"></i> Transparent donation and healthcare workflows</li>
                <li><i class="bi bi-check-circle"></i> Trusted and compassionate service platform</li>
            </ul>
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.2);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <img src="images/img1.jpeg" alt="Founder" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.5);">
                    <div style="font-size: 0.85rem; line-height: 1.4; opacity: 0.95;">
                        <div style="font-weight: 600;">Founded by Ven. Solewewa Chandrasiri Thero</div>
                        <div style="opacity: 0.8; font-size: 0.8rem;">Monastery healthcare & donation platform</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-side">
            <div class="register-card">
                <div class="form-header">
                    <h1 class="form-title">Create Account</h1>
                    <p class="form-subtitle">Use your details to join the monastery healthcare platform.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert-box alert-error">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert-box alert-success">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label" for="name">Full Name</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="form-control" 
                                placeholder="John Smith"
                                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                required
                            >
                        </div>

                        <div class="form-group full-width">
                            <label class="form-label" for="email">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="your@email.com"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                class="form-control" 
                                placeholder="0712345678"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                required
                            >
                            <div class="form-hint">Sri Lankan format (071-078)</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="role_id">Role</label>
                            <select id="role_id" name="role_id" class="form-control" required>
                                <option value="">Select role...</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="••••••••"
                                required
                            >
                            <div class="form-hint">Minimum 6 characters</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password_confirm">Confirm Password</label>
                            <input 
                                type="password" 
                                id="password_confirm" 
                                name="password_confirm" 
                                class="form-control" 
                                placeholder="••••••••"
                                required
                            >
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" class="btn-register">Create Account</button>
                        </div>
                    </div>
                </form>

                <div class="divider-line"></div>

                <div class="login-section">
                    <p class="login-text">Already have an account?</p>
                    <a href="login.php" class="btn-login">Sign In</a>
                </div>
            </div>
        </div>
    </div>

    <a href="public_donate.php" class="donation-cta" title="Support Monastery Healthcare">
        <i class="bi bi-hearts"></i> Offer Helping Hand
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/ui-interactions.js"></script>
</body>
</html>