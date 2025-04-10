<?php
/**
 * File: form_validation.php
 * Description: Contains functions for form validation and sanitization
 * 
 * Part of Agro Vision Farm Management System
 */

// Prevent direct access to this file
if (!defined('INCLUDED')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Form Validation Class
 * Provides methods for form validation and sanitization
 */
class FormValidator {
    // Store validation errors
    private $errors = [];
    
    // Store sanitized data
    private $sanitized = [];
    
    /**
     * Validate if a field is not empty
     * 
     * @param string $field The field name
     * @param string $value The field value
     * @param string $errorMsg Custom error message (optional)
     * @return bool True if validation passes, false otherwise
     */
    public function required($field, $value, $errorMsg = null) {
        $value = trim($value);
        if (empty($value)) {
            $this->errors[$field] = $errorMsg ?? "The $field field is required.";
            return false;
        }
        
        $this->sanitized[$field] = $value;
        return true;
    }
    
    /**
     * Validate email format
     * 
     * @param string $field The field name
     * @param string $value The field value
     * @param string $errorMsg Custom error message (optional)
     * @return bool True if validation passes, false otherwise
     */
    public function email($field, $value, $errorMsg = null) {
        if (empty($value)) {
            return true; // Skip if empty and not required
        }
        
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $errorMsg ?? "The $field field must be a valid email address.";
            return false;
        }
        
        $this->sanitized[$field] = filter_var($value, FILTER_SANITIZE_EMAIL);
        return true;
    }
    
    /**
     * Validate minimum length
     * 
     * @param string $field The field name
     * @param string $value The field value
     * @param int $min Minimum length
     * @param string $errorMsg Custom error message (optional)
     * @return bool True if validation passes, false otherwise
     */
    public function minLength($field, $value, $min, $errorMsg = null) {
        if (empty($value)) {
            return true; // Skip if empty and not required
        }
        
        if (mb_strlen($value) < $min) {
            $this->errors[$field] = $errorMsg ?? "The $field field must be at least $min characters.";
            return false;
        }
        
        $this->sanitized[$field] = $value;
        return true;
    }
    
    /**
     * Validate maximum length
     * 
     * @param string $field The field name
     * @param string $value The field value
     * @param int $max Maximum length
     * @param string $errorMsg Custom error message (optional)
     * @return bool True if validation passes, false otherwise
     */
    public function maxLength($field, $value, $max, $errorMsg = null) {
        if (empty($value)) {
            return true; // Skip if empty and not required
        }
        
        if (mb_strlen($value) > $max) {
            $this->errors[$field] = $errorMsg ?? "The $field field must not exceed $max characters.";
            return false;
        }
        
        $this->sanitized[$field] = $value;
        return true;
    }
    
    /**
     * Validate numeric value
     * 
     * @param string $field The field name
     * @param string $value The field value
     * @param string $errorMsg Custom error message (optional)
     * @return bool True if validation passes, false otherwise
     */
    public function numeric($field, $value, $errorMsg = null) {
        if (empty($value)) {
            return true; // Skip if empty and not required
        }
        
        if (!is_numeric($value)) {
            $this->errors[$field] = $errorMsg ?? "The $field field must be a number.";
            return false;
        }
        
        $this->sanitized[$field] = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        return true;
    }
    
    /**
     * Validate integer value
     * 
     * @param string $field The field name
     * @param string $value The field value
     * @param string $errorMsg Custom error message (optional)
     * @return bool True if validation passes, false otherwise
     */
    public function integer($field, $value, $errorMsg = null) {
        if (empty($value)) {
            return true; // Skip if empty and not required
        }
        
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field] = $errorMsg ?? "The $field field must be an integer.";
            return false;
        }
        
        $this->sanitized[$field] = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        return true;
    }
    
    /**
     * Validate date format
     * 
     * @param string $field The field name
     * @param string $value The field value
     * @param string $format Date format (default: Y-m-d)
     * @param string $errorMsg Custom error message (optional)
     * @return bool True if validation passes, false otherwise
     */
    public function date($field, $value, $format = 'Y-m-d', $errorMsg = null) {
        if (empty($value)) {
            return true; // Skip if empty and not required
        }
        
        $date = DateTime::createFromFormat($format, $value);
        if (!$date || $date->format($format) !== $value) {
            $this->errors[$field] = $errorMsg ?? "The $field field must be a valid date in the format $format.";
            return false;
        }
        
        $this->sanitized[$field] = $value;
        return true;
    }
    
    /**
     * Validate password strength
     * 
     * @param string $field The field name
     * @param string $value The field value
     * @param string $errorMsg Custom error message (optional)
     * @return bool True if validation passes, false otherwise
     */
    public function passwordStrength($field, $value, $errorMsg = null) {
        if (empty($value)) {
            return true; // Skip if empty and not required
        }
        
        // Check password strength
        $uppercase = preg_match('/[A-Z]/', $value);
        $lowercase = preg_match('/[a-z]/', $value);
        $number = preg_match('/[0-9]/', $value);
        $specialChar = preg_match('/[^A-Za-z0-9]/', $value);
        
        if (!$uppercase || !$lowercase || !$number || !$specialChar || strlen($value) < 8) {
            $this->errors[$field] = $errorMsg ?? "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
            return false;
        }
        
        $this->sanitized[$field] = $value;
        return true;
    }
    
    /**
     * Validate if two fields match
     * 
     * @param string $field1 The first field name
     * @param string $value1 The first field value
     * @param string $field2 The second field name
     * @param string $value2 The second field value
     * @param string $errorMsg Custom error message (optional)
     * @return bool True if validation passes, false otherwise
     */
    public function match($field1, $value1, $field2, $value2, $errorMsg = null) {
        if ($value1 !== $value2) {
            $this->errors[$field2] = $errorMsg ?? "The $field1 and $field2 fields must match.";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate file upload
     * 
     * @param string $field The field name
     * @param array $file The $_FILES array element
     * @param array $allowedTypes Allowed mime types
     * @param int $maxSize Maximum file size in bytes
     * @param string $errorMsg Custom error message (optional)
     * @return bool True if validation passes, false otherwise
     */
    public function file($field, $file, $allowedTypes, $maxSize, $errorMsg = null) {
        // Check if file was uploaded
        if (!isset($file) || $file['error'] === 4) {
            return true; // Skip if no file was uploaded and it's not required
        }
        
        // Check for upload errors
        if ($file['error'] !== 0) {
            $this->errors[$field] = "File upload failed with error code: " . $file['error'];
            return false;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $this->errors[$field] = $errorMsg ?? "File size must not exceed " . number_format($maxSize / 1048576, 2) . " MB.";
            return false;
        }
        
        // Check file type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileType = $finfo->file($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $this->errors[$field] = $errorMsg ?? "Invalid file type. Allowed types: " . implode(', ', $allowedTypes);
            return false;
        }
        
        $this->sanitized[$field] = $file;
        return true;
    }
    
    /**
     * Check if string contains only alphanumeric characters
     * 
     * @param string $field The field name
     * @param string $value The field value
     * @param string $errorMsg Custom error message (optional)
     * @return bool True if validation passes, false otherwise
     */
    public function alphaNumeric($field, $value, $errorMsg = null) {
        if (empty($value)) {
            return true; // Skip if empty and not required
        }
        
        if (!ctype_alnum($value)) {
            $this->errors[$field] = $errorMsg ?? "The $field field may only contain letters and numbers.";
            return false;
        }
        
        $this->sanitized[$field] = $value;
        return true;
    }
    
    /**
     * Validate URL format
     * 
     * @param string $field The field name
     * @param string $value The field value
     * @param string $errorMsg Custom error message (optional)
     * @return bool True if validation passes, false otherwise
     */
    public function url($field, $value, $errorMsg = null) {
        if (empty($value)) {
            return true; // Skip if empty and not required
        }
        
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = $errorMsg ?? "The $field field must be a valid URL.";
            return false;
        }
        
        $this->sanitized[$field] = filter_var($value, FILTER_SANITIZE_URL);
        return true;
    }
    
    /**
     * General sanitize function for strings
     * 
     * @param string $field The field name
     * @param string $value The field value
     * @return string The sanitized value
     */
    public function sanitize($field, $value) {
        $sanitized = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        $this->sanitized[$field] = $sanitized;
        return $sanitized;
    }
    
    /**
     * Get validation errors
     * 
     * @return array Array of validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if validation has errors
     * 
     * @return bool True if there are errors, false otherwise
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Get sanitized data
     * 
     * @return array Array of sanitized data
     */
    public function getSanitized() {
        return $this->sanitized;
    }
    
    /**
     * Get a specific sanitized value
     * 
     * @param string $field The field name
     * @return mixed The sanitized value or null if not found
     */
    public function getValue($field) {
        return $this->sanitized[$field] ?? null;
    }
    
    /**
     * Generate a CSRF token
     * 
     * @return string CSRF token
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token The token to validate
     * @return bool True if token is valid, false otherwise
     */
    public static function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }
}
?> 