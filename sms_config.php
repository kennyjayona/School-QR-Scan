<?php
/**
 * SMS Gateway Configuration
 * 
 * SETUP INSTRUCTIONS:
 * 
 * 1. Install an Android SMS Gateway app on your Android device:
 *    - SMS Gateway Ultimate (Recommended)
 *    - SMStoWeb Pro
 *    - Any HTTP API SMS Gateway
 * 
 * 2. Connect your Android device to the same Wi-Fi network as your server
 * 
 * 3. Open the SMS Gateway app and start the HTTP server
 * 
 * 4. Note the IP address and port shown in the app (e.g., 192.168.1.5:8080)
 * 
 * 5. Update the configuration below with your device's IP address
 * 
 * 6. Test the connection using sms_test.php
 */

// ============================================================================
// ANDROID SMS GATEWAY CONFIGURATION
// ============================================================================

/**
 * SMS Gateway Base URL
 * 
 * Format: http://[YOUR_ANDROID_DEVICE_IP]:[PORT]/send
 * 
 * Examples:
 * - http://192.168.1.5:8080/send
 * - http://192.168.0.100:8080/send
 * - http://10.0.0.5:8080/send
 * 
 * IMPORTANT: Update this with your actual Android device IP address!
 */
define('SMS_GATEWAY_URL', 'http://192.168.1.5:8080/send');

/**
 * Enable/Disable SMS Sending
 * 
 * Set to false for testing without sending actual SMS
 * Set to true for production use
 */
define('SMS_ENABLED', true);

/**
 * SMS Gateway Timeout (seconds)
 * 
 * Maximum time to wait for SMS Gateway response
 */
define('SMS_TIMEOUT', 10);

/**
 * Default Country Code
 * 
 * Used for formatting phone numbers
 * Philippines: +63
 */
define('SMS_COUNTRY_CODE', '+63');

/**
 * SMS Sender Name (if supported by gateway)
 * 
 * Some gateways allow custom sender names
 */
define('SMS_SENDER_NAME', 'Smart Classroom');

// ============================================================================
// APPLY CONFIGURATION TO SMS GATEWAY
// ============================================================================

if (file_exists('includes/sms_gateway.php')) {
    require_once 'includes/sms_gateway.php';
    
    // Apply configuration
    SMSGateway::setGatewayURL(SMS_GATEWAY_URL);
    SMSGateway::setEnabled(SMS_ENABLED);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Quick function to send SMS
 * 
 * @param string $phone Phone number
 * @param string $message SMS message
 * @return array Result array
 */
function sendSMS($phone, $message) {
    if (!class_exists('SMSGateway')) {
        require_once 'includes/sms_gateway.php';
    }
    
    return SMSGateway::sendSMS($phone, $message);
}

/**
 * Log SMS to database
 * 
 * @param int $studentId Student ID
 * @param string $phone Phone number
 * @param string $message SMS message
 * @param string $status Status
 * @param string $response Response
 * @return bool Success status
 */
function logSMS($studentId, $phone, $message, $status, $response = null) {
    if (!class_exists('SMSGateway')) {
        require_once 'includes/sms_gateway.php';
    }
    
    return SMSGateway::logSMS($studentId, $phone, $message, $status, $response);
}
?>
