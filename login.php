<?php
session_start();

require_once __DIR__ . '/includes/db_config.php';

$con = getDBConnection();

$error = "";
$success = "";

//check logout success message 
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success = "You have been logged out successfully.";
}

//check session timeout message 
if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $error = "Your session has expired. Please login again.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Both email and password are required.";
    } else {
        // prepare and execute query to prevent sql  injection 
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
            // set session variables
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['last_activity'] = time();
            
            // redirect to dashboard
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
<html>
<head>
    <title>Login - Seela Suwa Herath Bikshu Gilan Arana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --monastery-saffron: #f57c00;
            --monastery-orange: #ff9800;
            --monastery-light: #ffa726;
            --monastery-dark: #e65100;
            --monastery-pale: #fff3e0;
        }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, rgba(245, 124, 0, 0.9) 0%, rgba(255, 152, 0, 0.9) 100%), 
                        url('https://images.unsplash.com/photo-1582510003544-4d00b7f74220?w=1920') center/cover;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="50" font-size="50" opacity="0.03">🪷</text></svg>') repeat;
            animation: float 60s linear infinite;
        }
        @keyframes float {
            0% { transform: translateY(0); }
            100% { transform: translateY(-100px); }
        }
        .login-container {
            display: flex;
            max-width: 1100px;
            width: 95%;
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        .login-image {
            flex: 1;
            background: linear-gradient(135deg, rgba(245, 124, 0, 0.8) 0%, rgba(255, 152, 0, 0.8) 100%), 
                        url('https://images.unsplash.com/photo-1604002260721-2e61e5e2ffc8?w=800') center/cover;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px;
            color: white;
            text-align: center;
        }
        .login-image .monastery-logo {
            font-size: 8rem;
            margin-bottom: 20px;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: pulse 3s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .login-image h1 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 15px;
            text-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
        }
        .login-image p {
            font-size: 1.1rem;
            opacity: 0.95;
            line-height: 1.6;
        }
        .login-card {
            flex: 1;
            padding: 60px 50px;
            position: relative;
            background: white;
        }
        .login-card::before {
            content: "☸️";
            position: absolute;
            font-size: 150px;
            opacity: 0.03;
            right: -30px;
            top: -30px;
            transform: rotate(20deg);
        }
        .login-header {
            margin-bottom: 40px;
        }
        .login-header h2 {
            color: var(--monastery-dark);
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 2rem;
        }
        .login-header .subtitle {
            color: #666;
            font-size: 1rem;
        }
        .form-floating label {
            color: #666;
        }
        .form-floating input:focus ~ label {
            color: var(--monastery-saffron);
        }
        .form-control {
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            height: 55px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: var(--monastery-saffron);
            box-shadow: 0 0 0 0.2rem rgba(245, 124, 0, 0.15);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(245, 124, 0, 0.4);
        }
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }
        .info-box {
            background: var(--monastery-pale);
            border-left: 4px solid var(--monastery-saffron);
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .info-box small {
            font-size: 0.875rem;
        }
        .features-list {
            margin-top: 30px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: white;
        }
        .feature-item i {
            font-size: 1.5rem;
            margin-right: 15px;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 10px;
        }
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            .login-image {
                padding: 30px;
            }
            .login-image .monastery-logo {
                font-size: 5rem;
            }
            .login-card {
                padding: 40px 30px;
            }
        }
    </style>
    <script>
        function validateForm() {
            var email = document.getElementById("email").value.trim();
            var password = document.getElementById("password").value;

            if (email === "" || password === "") {
                alert("Both email and password are required.");
                return false;
            }
            
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert("Please enter a valid email address.");
                return false;
            }
            
            return true;
        }
    </script>
</head>
<body>
    <div class="login-container">
        <!--left side - image & info-->
        <div class="login-image">
            <div class="monastery-logo">🪷</div>
            <h1>Seela Suwa Herath<br>Bikshu Gilan Arana</h1>
            <p>Healthcare & Donation Management System</p>
            
            <div class="features-list">
                <div class="feature-item">
                    <i class="bi bi-hospital"></i>
                    <div>
                        <strong>Healthcare Services</strong><br>
                        <small>Complete medical management for monks</small>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="bi bi-cash-coin"></i>
                    <div>
                        <strong>Donation Management</strong><br>
                        <small>Secure online & offline donations</small>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="bi bi-robot"></i>
                    <div>
                        <strong>AI Assistant</strong><br>
                        <small>Bilingual chatbot support (සිංහල)</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!--right side - login form -->
        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p class="subtitle">Login to access the monastery system</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" onsubmit="return validateForm();">
                <div class="form-floating mb-4">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="admin@monastery.lk" required autofocus>
                    <label for="email"><i class="bi bi-envelope"></i> Email Address</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password"><i class="bi bi-lock"></i> Password</label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                    <i class="bi bi-box-arrow-in-right"></i> <strong>Sign In</strong>
                </button>
            </form>
            
            <div class="info-box">
                <div class="text-center">
                    <small><i class="bi bi-info-circle"></i> <strong>Demo Credentials</strong></small><br>
                    <small class="text-muted">
                        Email: <code>admin@monastery.lk</code><br>
                        Password: <code>admin123</code>
                    </small>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="bi bi-shield-check"></i> Secured with bcrypt encryption
                </small>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $con->close(); ?>
