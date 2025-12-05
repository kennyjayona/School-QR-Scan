<?php
/**
 * Smart Classroom Configuration
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smart_classroom');

// Application Configuration
define('APP_NAME', 'Smart Classroom');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/smart_classroom/');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour

// File Upload Configuration
define('UPLOAD_PATH', 'uploads/');
define('QR_CODE_PATH', 'qrcodes/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Error Logging
define('ERROR_LOG_PATH', 'logs/error_log.txt');
define('ENABLE_ERROR_LOG', true);

// SMS Configuration
define('SMS_API_KEY', 'YOUR_SEMAPHORE_API_KEY');
define('SMS_API_URL', 'https://api.semaphore.co/api/v4/messages');

// Timezone
date_default_timezone_set('Asia/Manila');

// Production flag (set to true in production)
define('PRODUCTION', false);

// Enhanced session security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
// Note: Enable session.cookie_secure in production with HTTPS

// Performance optimizations
if (function_exists('opcache_reset')) {
    ini_set('opcache.enable', 1);
    ini_set('opcache.memory_consumption', 128);
    ini_set('opcache.max_accelerated_files', 4000);
    ini_set('opcache.revalidate_freq', 60);
}

// Output compression
if (!ob_get_level()) {
    ob_start('ob_gzhandler');
}

// Set memory limit
ini_set('memory_limit', '256M');

// Set execution time limit
ini_set('max_execution_time', 30);

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', PRODUCTION ? 0 : 1);
ini_set('log_errors', 1);
ini_set('error_log', ERROR_LOG_PATH);

// Start session with secure parameters (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => '/',
        'domain' => '',
        'secure' => PRODUCTION, // Enable in production with HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    session_start();
}

// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?>
