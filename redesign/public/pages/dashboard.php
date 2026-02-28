<?php
/**
 * Dashboard Router - Routes to role-specific dashboards
 * Monastery Healthcare System v2.0
 */

// Get user information from authentication system
$user = $auth->getUser();
if (!$user) {
    header('Location: login');
    exit;
}

// Store user info in session for dashboard pages
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['full_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];

// Include role-specific dashboard
switch ($user['role']) {
    case 'admin':
        include __DIR__ . '/dashboards/admin.php';
        break;
    case 'monk':
        include __DIR__ . '/dashboards/monk.php';
        break;
    case 'doctor':
        include __DIR__ . '/dashboards/doctor.php';
        break;
    case 'donator':
        include __DIR__ . '/dashboards/donator.php';
        break;
    default:
        include __DIR__ . '/dashboards/default.php';
        break;
}