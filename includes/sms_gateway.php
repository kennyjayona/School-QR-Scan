<?php
/**
 * SMS Gateway Helper Class
 * Sends SMS notifications via Android SMS Gateway device
 * 
 * CONFIGURATION:
 * - Update $gatewayURL with your Android device's IP address and port
 * - Make sure the Android SMS Gateway app is running
 * - Ensure both devices are on the same Wi-Fi network
 * 
 * Compatible with:
 * - SMS Gateway Ultimate
 * - SMStoWeb Pro
 * - Any Android SMS Gateway with HTTP API
 */

class SMSGateway {
    
    /**
     * Android SMS Gateway Configuration
     * 
     * IMPORTANT: Update this URL with your Android device's IP address
     * Format: http://[DEVICE_IP]:[PORT]/send
     * 
     * Example: http://192.168.1.5:8080/send
     */
    private static $gatewayURL = "http://192.168.1.5:8080/send";
    
    /**
     * Enable/Disable SMS sending
     * Set to false for testing without sending actual SMS
     */
    private static $enabled = true;
    
    /**
     * Timeout for HTTP request (in seconds)
     */
    private static $timeout = 10;
    
    /**
     * Send SMS via Android Gateway
     * 
     * @param string $phone Phone number (e.g., +639388043855)
     * @param string $message SMS message content
     * @return array ['success' => bool, 'message' => string, 'response' => string]
     */
    public static function sendSMS($phone, $message) {
        global $conn;
        
        // Validate phone number
        $phone = self::formatPhoneNumber($phone);
        if (empty($phone)) {
            return [
                'success' => false,
                'message' => 'Invalid phone number',
                'response' => null
            ];
        }
        
        // Validate message
        if (empty($message)) {
            return [
                'success' => false,
                'message' => 'Message cannot be empty',
                'response' => null
            ];
        }
        
        // Check if SMS is enabled
        if (!self::$enabled) {
            error_log("SMS Gateway: Disabled - Would send to $phone: $message");
            return [
                'success' => true,
                'message' => 'SMS disabled (test mode)',
                'response' => 'Test mode - SMS not sent'
            ];
        }
        
        try {
            // Build the URL with encoded parameters
            $url = self::$gatewayURL . '?' . http_build_query([
                'phone' => $phone,
                'msg' => $message
            ]);
            
            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            // Check for cURL errors
            if ($error) {
                error_log("SMS Gateway Error: $error");
                return [
                    'success' => false,
                    'message' => 'Connection error: ' . $error,
                    'response' => $error
                ];
            }
            
            // Check HTTP response code
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'response' => $response
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "HTTP Error: $httpCode",
                    'response' => $response
                ];
            }
            
        } catch (Exception $e) {
            error_log("SMS Gateway Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'response' => null
            ];
        }
    }
    
    /**
     * Format phone number to international format
     * 
     * @param string $phone Phone number
     * @return string Formatted phone number or empty string if invalid
     */
    private static function formatPhoneNumber($phone) {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // If empty, return empty
        if (empty($phone)) {
            return '';
        }
        
        // If already has +, return as is
        if (substr($phone, 0, 1) === '+') {
            return $phone;
        }
        
        // If starts with 0, replace with +63 (Philippines)
        if (substr($phone, 0, 1) === '0') {
            return '+63' . substr($phone, 1);
        }
        
        // If starts with 63, add +
        if (substr($phone, 0, 2) === '63') {
            return '+' . $phone;
        }
        
        // If 10 digits, assume Philippines mobile
        if (strlen($phone) === 10) {
            return '+63' . $phone;
        }
        
        // Return as is
        return $phone;
    }
    
    /**
     * Log SMS to database
     * 
     * @param int $studentId Student ID
     * @param string $phone Phone number
     * @param string $message SMS message
     * @param string $status Status (Success/Failed)
     * @param string $response Gateway response
     * @return bool Success status
     */
    public static function logSMS($studentId, $phone, $message, $status, $response = null) {
        global $conn;
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO sms_logs (student_id, phone_number, message, status, response, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param("issss", $studentId, $phone, $message, $status, $response);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("SMS Log Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send TIME IN notification
     * 
     * @param int $studentId Student ID
     * @param string $studentName Student name
     * @param string $timeIn Time in timestamp
     * @param string $parentPhone Parent phone number
     * @return array Result array
     */
    public static function sendTimeInNotification($studentId, $studentName, $timeIn, $parentPhone) {
        $formattedTime = date('h:i A', strtotime($timeIn));
        $formattedDate = date('F j, Y', strtotime($timeIn));
        
        $message = "Hi! Your child $studentName has TIMED IN at $formattedTime on $formattedDate. - Smart Classroom";
        
        $result = self::sendSMS($parentPhone, $message);
        
        // Log to database
        $status = $result['success'] ? 'Success' : 'Failed';
        self::logSMS($studentId, $parentPhone, $message, $status, $result['response']);
        
        return $result;
    }
    
    /**
     * Send TIME OUT notification
     * 
     * @param int $studentId Student ID
     * @param string $studentName Student name
     * @param string $timeOut Time out timestamp
     * @param string $parentPhone Parent phone number
     * @return array Result array
     */
    public static function sendTimeOutNotification($studentId, $studentName, $timeOut, $parentPhone) {
        $formattedTime = date('h:i A', strtotime($timeOut));
        $formattedDate = date('F j, Y', strtotime($timeOut));
        
        $message = "Hi! Your child $studentName has TIMED OUT at $formattedTime on $formattedDate. - Smart Classroom";
        
        $result = self::sendSMS($parentPhone, $message);
        
        // Log to database
        $status = $result['success'] ? 'Success' : 'Failed';
        self::logSMS($studentId, $parentPhone, $message, $status, $result['response']);
        
        return $result;
    }
    
    /**
     * Configure Gateway URL
     * 
     * @param string $url Gateway URL
     */
    public static function setGatewayURL($url) {
        self::$gatewayURL = $url;
    }
    
    /**
     * Enable or disable SMS sending
     * 
     * @param bool $enabled Enable status
     */
    public static function setEnabled($enabled) {
        self::$enabled = $enabled;
    }
    
    /**
     * Get Gateway URL
     * 
     * @return string Gateway URL
     */
    public static function getGatewayURL() {
        return self::$gatewayURL;
    }
}
?>
