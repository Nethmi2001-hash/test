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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
            background-color: #f8f7f4;
            color: #333;
            line-height: 1.6;
        }

        .page-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
        }

        .hero-section {
            background: linear-gradient(135deg, #2d5016 0%, #1a3009 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.08) 0%, transparent 70%);
            animation: drift 20s linear infinite;
        }

        @keyframes drift {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, 20px); }
        }

        .hero-image {
            width: 100%;
            height: 350px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 2;
            position: relative;
        }

        .hero-content {
            text-align: center;
            z-index: 2;
            position: relative;
        }

        .monastery-name {
            font-size: 28px;
            font-weight: 300;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .monastery-subtitle {
            font-size: 14px;
            opacity: 0.85;
            letter-spacing: 0.5px;
            font-weight: 300;
        }

        .form-section {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-title {
            font-size: 32px;
            font-weight: 300;
            color: #2d5016;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .form-subtitle {
            font-size: 14px;
            color: #666;
            font-weight: 300;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .form-control {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus {
            outline: none;
            border-color: #2d5016;
            background: white;
            box-shadow: 0 0 0 2px rgba(45, 80, 22, 0.05);
        }

        .alert-box {
            padding: 14px 16px;
            border-radius: 4px;
            margin-bottom: 24px;
            font-size: 14px;
            border-left: 3px solid;
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
            background: #f9f6f0;
            border: 1px solid #e5ddd0;
            border-radius: 4px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 13px;
        }

        .demo-label {
            font-weight: 600;
            color: #2d5016;
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
            padding: 13px 24px;
            background: #2d5016;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 12px;
            text-transform: uppercase;
        }

        .btn-login:hover {
            background: #1a3009;
            box-shadow: 0 4px 12px rgba(45, 80, 22, 0.2);
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        .divider-line {
            height: 1px;
            background: #ddd;
            margin: 30px 0;
        }

        .signup-section {
            text-align: center;
        }

        .signup-text {
            font-size: 13px;
            color: #666;
            margin-bottom: 12px;
        }

        .btn-signup {
            width: 100%;
            padding: 13px 24px;
            background: #D4AF37;
            color: #2d5016;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-transform: uppercase;
        }

        .btn-signup:hover {
            background: #C9A027;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.2);
        }

        .donation-cta {
            position: fixed;
            bottom: 40px;
            right: 40px;
            background: #dc2626;
            color: white;
            padding: 14px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 16px rgba(220, 38, 38, 0.3);
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            z-index: 100;
        }

        .donation-cta:hover {
            background: #b91c1c;
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
            transform: translateY(-2px);
        }

        @media (max-width: 1024px) {
            .page-wrapper {
                grid-template-columns: 1fr;
            }

            .hero-section {
                padding: 40px 30px;
                min-height: 400px;
            }

            .hero-image {
                height: 250px;
            }

            .form-section {
                padding: 40px 30px;
            }

            .donation-cta {
                bottom: 20px;
                right: 20px;
                padding: 12px 18px;
                font-size: 12px;
            }
        }

        @media (max-width: 640px) {
            .form-title {
                font-size: 24px;
            }

            .form-section {
                padding: 30px 20px;
            }

            .hero-section {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Hero Section -->
        <div class="hero-section">
            <img src="images/img5.jpeg" alt="Seela Suwa Herath Monastery" class="hero-image">
            <div class="hero-content">
                <h1 class="monastery-name">Seela Suwa Herath</h1>
                <p class="monastery-subtitle">Bikshu Gilan Arana Healthcare</p>
            </div>
        </div>

        <!-- Login Form Section -->
        <div class="form-section">
            <div class="form-header">
                <h2 class="form-title">Sign In</h2>
                <p class="form-subtitle">Access your account to continue</p>
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

            <div class="demo-box">
                <div class="demo-label">Demo Credentials</div>
                <div class="demo-item"><strong>Email:</strong> admin@monastery.com</div>
                <div class="demo-item"><strong>Password:</strong> password123</div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="your@email.com"
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
    </div>

    <a href="public_donate.php" class="donation-cta">
        <i class="bi bi-heart-fill"></i> Support Us
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>