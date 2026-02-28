<?php
/**
 * Enhanced Logout Script
 * Uses secure logout function from enhanced auth system
 */

require_once __DIR__ . '/includes/auth_enhanced.php';

configureSession();

// Perform secure logout
logoutUser('manual');

// Clear any authentication headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login page with success message
header("Location: login.php?logout=success");
exit();
?>
