<?php
/**
 * Input Validation Class
 * Provides standardized validation methods for form inputs
 */

class InputValidator {
    
    /**
     * Validate string length
     */
    public static function validateLength($input, $min = 0, $max = 255, $field_name = "Field") {
        $length = strlen(trim($input));
        
        if ($length < $min) {
            return ["valid" => false, "error" => "$field_name must be at least $min characters long."];
        }
        
        if ($length > $max) {
            return ["valid" => false, "error" => "$field_name cannot exceed $max characters."];
        }
        
        return ["valid" => true, "error" => null];
    }
    
    /**
     * Validate email address
     */
    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["valid" => false, "error" => "Please enter a valid email address."];
        }
        return ["valid" => true, "error" => null];
    }
    
    /**
     * Validate required field
     */
    public static function validateRequired($input, $field_name = "Field") {
        if (empty(trim($input))) {
            return ["valid" => false, "error" => "$field_name is required."];
        }
        return ["valid" => true, "error" => null];
    }
    
    /**
     * Sanitize input for safe output
     */
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate phone number (Philippine format)
     */
    public static function validatePhone($phone) {
        $pattern = '/^(09|\+639)\d{9}$/';
        if (!preg_match($pattern, $phone)) {
            return ["valid" => false, "error" => "Please enter a valid Philippine phone number."];
        }
        return ["valid" => true, "error" => null];
    }
    
    /**
     * Validate numeric value
     */
    public static function validateNumeric($value, $min = null, $max = null, $field_name = "Field") {
        if (!is_numeric($value)) {
            return ["valid" => false, "error" => "$field_name must be a number."];
        }
        
        if ($min !== null && $value < $min) {
            return ["valid" => false, "error" => "$field_name must be at least $min."];
        }
        
        if ($max !== null && $value > $max) {
            return ["valid" => false, "error" => "$field_name cannot exceed $max."];
        }
        
        return ["valid" => true, "error" => null];
    }
}
?>
