<?php
require_once __DIR__ . '/includes/auth_enhanced.php';
require_once __DIR__ . '/includes/db_config.php';

configureSession();

$error = "";
$success = "";

// Check for logout or timeout messages
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success = "You have been logged out successfully.";
}

if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $error = "Your session has expired. Please login again.";
}

// Redirect if already logged in
if (isAuthenticated()) {
    header("Location: dashboard.php");
    exit();
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Security token mismatch. Please try again.";
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Rate limiting (simple implementation)
        $max_attempts = 5;
        $lockout_time = 300; // 5 minutes
        
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        // Clean old attempts
        $_SESSION['login_attempts'] = array_filter(
            $_SESSION['login_attempts'], 
            fn($time) => (time() - $time) < $lockout_time
        );
        
        if (count($_SESSION['login_attempts']) >= $max_attempts) {
            $error = "Too many failed attempts. Please try again in 5 minutes.";
        } else {
            $result = authenticateUser($email, $password);
            
            if ($result['success']) {
                // Clear failed attempts on success
                $_SESSION['login_attempts'] = [];
                header("Location: " . $result['redirect']);
                exit();
            } else {
                // Record failed attempt
                $_SESSION['login_attempts'][] = time();
                $error = $result['message'];
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
    <title>Login - Seela Suwa Herath Bikshu Gilan Arana</title>
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
            --sc-card: #FFFFFF;
            --sc-border: #D9D1C4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
            background: radial-gradient(circle at top right, #FFEFD9 0%, var(--sc-bg) 40%);
            color: #1f2937;
            line-height: 1.6;
        }

        .page-wrapper {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(360px, 520px) minmax(460px, 1fr);
        }

        .hero-section {
            background: linear-gradient(160deg, var(--sc-primary) 0%, var(--sc-primary-dark) 65%, #7c2d12 100%);
            color: white;
            padding: 48px 34px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 20%, rgba(245, 158, 11, 0.26), transparent 45%);
        }

        .hero-image {
            width: 100%;
            height: 270px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.25);
            margin-bottom: 20px;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.25);
            z-index: 2;
            position: relative;
        }

        .hero-content {
            z-index: 2;
            position: relative;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 14px;
        }

        .monastery-name {
            font-size: 29px;
            font-weight: 600;
            letter-spacing: 0.2px;
            margin-bottom: 6px;
        }

        .monastery-subtitle {
            font-size: 14px;
            opacity: 0.92;
            letter-spacing: 0.1px;
            margin-bottom: 16px;
        }

        .hero-points {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .hero-points li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            margin-bottom: 8px;
            opacity: 0.95;
        }

        .form-section {
            padding: 46px 34px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: transparent;
        }

        .auth-wrap {
            width: 100%;
            max-width: 620px;
            display: grid;
            gap: 16px;
        }

        .auth-card {
            width: 100%;
            background: var(--sc-card);
            border: 1px solid var(--sc-border);
            border-radius: 16px;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.12);
            padding: 20px 24px;
        }

        .support-card {
            width: 100%;
            background: #fff;
            border: 1px solid var(--sc-border);
            border-radius: 14px;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
            padding: 16px 18px;
        }

        .support-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--sc-accent);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .support-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .support-item {
            background: #fff7ed;
            border: 1px solid #f3dfc5;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
        }

        .support-item i {
            color: var(--sc-primary);
            font-size: 17px;
            margin-bottom: 4px;
            display: block;
        }

        .support-item span {
            font-size: 11px;
            color: #6b7280;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .form-title {
            font-size: 31px;
            font-weight: 700;
            color: var(--sc-accent);
            margin-bottom: 4px;
            letter-spacing: -0.4px;
        }

        .form-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
            letter-spacing: 0.45px;
            text-transform: uppercase;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d7d2c8;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s ease;
            background: #fffdfa;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--sc-primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(194, 65, 12, 0.12);
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

        .demo-box {
            background: #f5efe6;
            border: 1px dashed #a67c52;
            border-radius: 8px;
            padding: 8px 10px;
            margin-bottom: 12px;
            font-size: 12px;
        }

        .demo-label {
            font-weight: 600;
            color: var(--sc-accent);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .demo-item {
            margin-bottom: 6px;
            color: #333;
        }

        .btn-login {
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

        .btn-login:hover {
            background: var(--sc-primary-dark);
            box-shadow: 0 4px 12px rgba(194, 65, 12, 0.25);
        }

        .divider {
            text-align: center;
            margin: 18px 0;
        }

        .divider-line {
            height: 1px;
            background: #ece7de;
            margin: 16px 0;
        }

        .signup-section {
            text-align: center;
        }

        .signup-text {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }

        .btn-signup {
            width: 100%;
            padding: 11px 24px;
            background: var(--sc-primary);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-transform: uppercase;
        }

        .btn-signup:hover {
            background: var(--sc-primary-dark);
            box-shadow: 0 4px 12px rgba(166, 124, 82, 0.3);
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

        @media (max-width: 1024px) {
            .page-wrapper {
                grid-template-columns: 1fr;
            }

            .hero-section {
                padding: 28px 24px;
            }

            .hero-image {
                height: 220px;
            }

            .form-section {
                padding: 24px;
            }

            .auth-wrap {
                max-width: 680px;
            }

            .support-grid {
                grid-template-columns: 1fr 1fr 1fr;
            }

            .donation-cta {
                bottom: 16px;
                right: 16px;
                padding: 10px 14px;
                font-size: 12px;
            }
        }

        @media (max-width: 640px) {
            .form-title {
                font-size: 26px;
            }

            .auth-card {
                padding: 20px;
            }

            .support-grid {
                grid-template-columns: 1fr;
            }

            .support-item {
                text-align: left;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .support-item i {
                margin-bottom: 0;
            }

            .hero-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Hero Section -->
        <div class="hero-section">
            <img src="images/img2.jpeg" alt="Seela Suwa Herath Monastery" class="hero-image">
            <div class="hero-content">
                <div class="hero-badge"><i class="bi bi-shield-check"></i> Sacred Care Portal</div>
                <h1 class="monastery-name">Seela Suwa Herath</h1>
                <p class="monastery-subtitle">Bikshu Gilan Arana Healthcare</p>
                <ul class="hero-points">
                    <li><i class="bi bi-check-circle"></i> Healthcare coordination for monks</li>
                    <li><i class="bi bi-check-circle"></i> Trusted donation & support management</li>
                    <li><i class="bi bi-check-circle"></i> Secure staff access and reporting</li>
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
        </div>

        <!-- Login Form Section -->
        <div class="form-section">
            <div class="auth-wrap">
                <div class="auth-card">
                    <h2 class="form-title">Sign In</h2>
                    <p class="form-subtitle">Access your account to manage healthcare and donations.</p>

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

                    <div class="demo-box">
                        <div class="demo-label">Demo Credentials</div>
                        <div class="demo-item"><strong>Email:</strong> admin@monastery.com</div>
                        <div class="demo-item"><strong>Password:</strong> password123</div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="your@email.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                required
                            >
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
                        </div>

                        <button type="submit" class="btn-login">Sign In</button>
                    </form>

                    <div class="divider-line"></div>

                    <div class="signup-section">
                        <p class="signup-text">Don't have an account?</p>
                        <a href="register.php" class="btn-signup">Create Account</a>
                    </div>
                </div>

                <div class="support-card">
                    <div class="support-title"><i class="bi bi-stars"></i> Why this portal is trusted</div>
                    <div class="support-grid">
                        <div class="support-item">
                            <i class="bi bi-shield-lock"></i>
                            <span>Secure access</span>
                        </div>
                        <div class="support-item">
                            <i class="bi bi-clipboard2-pulse"></i>
                            <span>Healthcare focused</span>
                        </div>
                        <div class="support-item">
                            <i class="bi bi-person-hearts"></i>
                            <span>Compassion-led</span>
                        </div>
                    </div>
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