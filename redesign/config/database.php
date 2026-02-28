<?php

/**
 * Database & Application Configuration
 * Monastery Healthcare System v2.0
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'monastery_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'Monastery Healthcare System');
define('APP_VERSION', '2.0');
define('APP_URL', '/test/redesign/public/');

// Session Settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_NAME', 'monastery_session');

// Security
define('BCRYPT_COST', 10);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes

// Upload Settings
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', __DIR__ . '/../uploads/');