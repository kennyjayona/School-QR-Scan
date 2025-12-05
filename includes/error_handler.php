<?php
/**
 * Error Handler Class
 * Provides centralized error handling and logging
 */

class ErrorHandler {
    
    /**
     * Log error to file
     */
    public static function logError($error, $file = null, $line = null) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] ERROR: $error";
        
        if ($file) {
            $log_entry .= " in $file";
        }
        
        if ($line) {
            $log_entry .= " on line $line";
        }
        
        $log_entry .= "\n";
        
        // Create logs directory if it doesn't exist
        if (!file_exists('logs')) {
            mkdir('logs', 0777, true);
        }
        
        file_put_contents('logs/error_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Display error message
     */
    public static function displayError($message, $type = 'danger') {
        $icon = $type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        return "<div class=\"alert alert-$type alert-dismissible fade show\" role=\"alert\">
                    <i class=\"fas $icon\"></i> $message
                    <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
                </div>";
    }
    
    /**
     * Display success message
     */
    public static function displaySuccess($message) {
        return self::displayError($message, 'success');
    }
    
    /**
     * Handle exception
     */
    public static function handleException($exception) {
        self::logError($exception->getMessage(), $exception->getFile(), $exception->getLine());
        
        // In production, show generic error message
        if (defined('PRODUCTION') && PRODUCTION) {
            return self::displayError('An error occurred. Please try again later.');
        } else {
            return self::displayError($exception->getMessage());
        }
    }
    
    /**
     * Display info message
     */
    public static function displayInfo($message) {
        return self::displayError($message, 'info');
    }
    
    /**
     * Display warning message
     */
    public static function displayWarning($message) {
        return self::displayError($message, 'warning');
    }
}
?>
