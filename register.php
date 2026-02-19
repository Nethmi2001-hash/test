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
            padding: 40px 20px;
        }

        .register-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 50px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 1px solid #e5ddd0;
            padding-bottom: 30px;
        }

        .monastery-icon {
            font-size: 36px;
            margin-bottom: 12px;
        }

        .form-title {
            font-size: 28px;
            font-weight: 300;
            color: #2d5016;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .form-subtitle {
            font-size: 13px;
            color: #666;
            font-weight: 300;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .form-control, select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus, select:focus {
            outline: none;
            border-color: #2d5016;
            background: white;
            box-shadow: 0 0 0 2px rgba(45, 80, 22, 0.05);
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
            font-size: 12px;
            color: #666;
            margin-top: 6px;
            font-weight: 300;
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

        .btn-register {
            width: 100%;
            padding: 13px 24px;
            background: #2d5016;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 12px;
            text-transform: uppercase;
        }

        .btn-register:hover {
            background: #1a3009;
            box-shadow: 0 4px 12px rgba(45, 80, 22, 0.2);
        }

        .divider-line {
            height: 1px;
            background: #e5ddd0;
            margin: 30px 0;
        }

        .login-section {
            text-align: center;
        }

        .login-text {
            font-size: 13px;
            color: #666;
            margin-bottom: 12px;
        }

        .btn-login {
            color: #2d5016;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            color: #D4AF37;
        }

        @media (max-width: 640px) {
            .register-container {
                padding: 30px 20px;
            }

            .form-title {
                font-size: 24px;
            }

            .monastery-icon {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="form-header">
            <div class="monastery-icon">🤝</div>
            <h1 class="form-title">Create Account</h1>
            <p class="form-subtitle">Join our monastic community</p>
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
            <div class="form-group">
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

            <div class="form-group">
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
                <div class="form-hint">Sri Lankan format: 10 digits (071-078)</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="role_id">Role</label>
                <select id="role_id" name="role_id" required>
                    <option value="">Select your role...</option>
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

            <button type="submit" class="btn-register">Create Account</button>
        </form>

        <div class="divider-line"></div>

        <div class="login-section">
            <p class="login-text">Already have an account?</p>
            <a href="login.php" class="btn-login">Sign In</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>