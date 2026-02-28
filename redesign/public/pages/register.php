<?php
/**
 * Donator Registration Page - Monastery Healthcare System
 */

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Name, email, and password are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            $db = Database::getInstance();
            
            // Check if email already exists
            $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existing) {
                $error = 'This email is already registered.';
            } else {
                // Create user
                $userId = $db->insert('users', [
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
                    'role' => 'donator',
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'status' => 'active'
                ]);
                
                // Create donator profile
                $db->insert('donators', [
                    'user_id' => $userId,
                    'donator_id' => 'DON-' . str_pad($userId, 3, '0', STR_PAD_LEFT),
                    'address' => $address,
                    'city' => $city ?? '',
                ]);
                
                $success = 'Registration successful! You can now login with your email and password.';
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Monastery Healthcare System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0f9ff 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .register-card { background: white; padding: 2.5rem; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); max-width: 600px; width: 100%; }
        .logo { text-align: center; margin-bottom: 1.5rem; }
        .logo h1 { font-size: 1.6rem; color: #1e293b; margin-top: 0.5rem; }
        .logo p { color: #64748b; font-size: 0.9rem; }
        .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.3rem; font-weight: 600; color: #374151; font-size: 0.875rem; }
        .form-control { width: 100%; padding: 0.65rem 0.9rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; transition: border-color 0.2s; }
        .form-control:focus { outline: none; border-color: #10b981; }
        select.form-control { cursor: pointer; }
        .btn-register { width: 100%; padding: 0.8rem; background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; margin-top: 0.5rem; }
        .btn-register:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); }
        .login-link { text-align: center; margin-top: 1.5rem; color: #64748b; }
        .login-link a { color: #2563eb; text-decoration: none; font-weight: 600; }
        @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo">
            <div style="font-size: 3rem;">🏛️</div>
            <h1>Donator Registration</h1>
            <p>Register to make donations to the monastery</p>
        </div>
        
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        
        <form method="POST">
            <div class="grid-2">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($full_name ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($city ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($address ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn-register">📝 Create Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login">Login here</a>
        </div>
    </div>
</body>
</html>
