<?php
/**
 * Health Check Endpoint
 * Used for monitoring and load balancer health checks
 */

header('Content-Type: application/json');

$health = [
    'status' => 'unknown',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Check 1: Database Connection
try {
    require_once 'db_connect.php';
    
    if ($conn && $conn->ping()) {
        $health['checks']['database'] = [
            'status' => 'healthy',
            'message' => 'Database connection successful'
        ];
    } else {
        $health['checks']['database'] = [
            'status' => 'unhealthy',
            'message' => 'Database connection failed'
        ];
    }
} catch (Exception $e) {
    $health['checks']['database'] = [
        'status' => 'unhealthy',
        'message' => 'Database error: ' . $e->getMessage()
    ];
}

// Check 2: File System
$upload_dir = 'uploads/students/';
if (is_dir($upload_dir) && is_writable($upload_dir)) {
    $health['checks']['filesystem'] = [
        'status' => 'healthy',
        'message' => 'Upload directory writable'
    ];
} else {
    $health['checks']['filesystem'] = [
        'status' => 'unhealthy',
        'message' => 'Upload directory not writable'
    ];
}

// Check 3: Session
if (session_status() === PHP_SESSION_ACTIVE || session_start()) {
    $health['checks']['session'] = [
        'status' => 'healthy',
        'message' => 'Session system working'
    ];
} else {
    $health['checks']['session'] = [
        'status' => 'unhealthy',
        'message' => 'Session system failed'
    ];
}

// Check 4: Required Extensions
$required_extensions = ['mysqli', 'gd', 'curl', 'json'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (empty($missing_extensions)) {
    $health['checks']['php_extensions'] = [
        'status' => 'healthy',
        'message' => 'All required extensions loaded'
    ];
} else {
    $health['checks']['php_extensions'] = [
        'status' => 'unhealthy',
        'message' => 'Missing extensions: ' . implode(', ', $missing_extensions)
    ];
}

// Overall Status
$all_healthy = true;
foreach ($health['checks'] as $check) {
    if ($check['status'] !== 'healthy') {
        $all_healthy = false;
        break;
    }
}

$health['status'] = $all_healthy ? 'healthy' : 'unhealthy';

// Set HTTP status code
http_response_code($all_healthy ? 200 : 503);

// Output JSON
echo json_encode($health, JSON_PRETTY_PRINT);
?>
