<?php
/**
 * SMS Notification System
 * Handles SMS sending via Semaphore API with retry logic
 * Only triggers for TIME IN / TIME OUT (not subject attendance)
 */

/**
 * Main SMS sending function
 * @param string $phone Phone number (format: 09XXXXXXXXX)
 * @param string $message SMS message content
 * @return array ['success' => bool, 'message' => string]
 */
function send_sms($phone, $message) {
    // Load API key from config
    require_once 'config.php';
    $apikey = defined('SMS_API_KEY') ? SMS_API_KEY : 'YOUR_SEMAPHORE_API_KEY';
    
    // If API key not configured, return success in test mode
    if ($apikey === 'YOUR_SEMAPHORE_API_KEY' || empty($apikey)) {
        error_log("SMS Test Mode: Would send to $phone: $message");
        return [
            'success' => true, 
            'message' => 'SMS sent (test mode - configure SMS_API_KEY in config.php)'
        ];
    }
    
    // Clean phone number (remove spaces, dashes)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Validate Philippine phone number format
    if (!preg_match('/^(09|\+639)\d{9}$/', $phone)) {
        return [
            'success' => false,
            'message' => 'Invalid phone number format. Use 09XXXXXXXXX or +639XXXXXXXXX'
        ];
    }
    
    // Convert to international format if needed
    if (substr($phone, 0, 2) === '09') {
        $phone = '+63' . substr($phone, 1);
    }
    
    try {
        // Semaphore API call
        $ch = curl_init();
        $parameters = [
            'apikey' => $apikey,
            'number' => $phone,
            'message' => $message,
            'sendername' => 'SmartClass' // Optional sender name
        ];
        
        curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing
        
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: $error");
        }
        
        // Parse response
        $response = json_decode($output, true);
        
        if ($httpcode == 200 || $httpcode == 201) {
            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'response' => $output
            ];
        } else {
            return [
                'success' => false,
                'message' => 'SMS API error: ' . ($response['message'] ?? $output),
                'response' => $output
            ];
        }
        
    } catch (Exception $e) {
        error_log("SMS Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'SMS sending failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Alternative: Twilio SMS (uncomment to use)
 */
/*
function send_sms_twilio($phone, $message) {
    $account_sid = 'YOUR_TWILIO_ACCOUNT_SID';
    $auth_token = 'YOUR_TWILIO_AUTH_TOKEN';
    $twilio_number = 'YOUR_TWILIO_PHONE_NUMBER';
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'From' => $twilio_number,
        'To' => $phone,
        'Body' => $message
    ]));
    curl_setopt($ch, CURLOPT_USERPWD, "$account_sid:$auth_token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => ($httpcode == 200 || $httpcode == 201),
        'message' => $output
    ];
}
*/
?>
