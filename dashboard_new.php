<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Role-based dashboard routing
$role_name = $_SESSION['role_name'] ?? '';

switch(strtolower($role_name)) {
    case 'admin':
        include 'dashboards/admin_dashboard.php';
        break;
    case 'doctor':
        include 'dashboards/doctor_dashboard.php';  
        break;
    case 'monk':
        include 'dashboards/monk_dashboard.php';
        break;
    case 'donor':
        include 'dashboards/donor_dashboard.php';
        break;
    case 'helper':
        include 'dashboards/helper_dashboard.php';
        break;
    default:
        include 'dashboards/admin_dashboard.php'; // Fallback
        break;
}
?>