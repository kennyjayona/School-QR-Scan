<?php
/**
 * Configuration Example File
 * Copy this to config.php and update with your settings
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smart_classroom');

// SMS API Configuration (Semaphore)
define('SMS_API_KEY', 'YOUR_SEMAPHORE_API_KEY');
define('SMS_API_URL', 'https://api.semaphore.co/api/v4/messages');

// Alternative: Twilio Configuration
// define('TWILIO_SID', 'YOUR_TWILIO_SID');
// define('TWILIO_TOKEN', 'YOUR_TWILIO_TOKEN');
// define('TWILIO_FROM', '+1234567890');

// System Configuration
define('SITE_NAME', 'Smart Classroom System');
define('LATE_TIME', '08:00:00'); // Students arriving after this time are marked as late

// Error Logging
define('ERROR_LOG_PATH', 'logs/error_log.txt');
define('ENABLE_ERROR_LOG', true);

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// File Upload Configuration
define('QR_CODE_PATH', 'qrcodes/');
define('MAX_LOGIN_ATTEMPTS', 5);
?>
