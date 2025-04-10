<?php
/**
 * File: config.php
 * Description: Database configuration and application settings
 * 
 * Part of Agro Vision Farm Management System
 */

// Debug mode configuration
// Set to false in production, true only in development environment
define('DEBUG_MODE', false);

// Configure error handling based on environment
if (DEBUG_MODE) {
    // Development environment - show errors but log them
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
} else {
    // Production environment - hide errors but log them
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmtech";

// Create connection
$conn = mysqli_connect($servername, $username, $password);

// Check connection
if (!$conn) {
    if (DEBUG_MODE) {
        die("Connection failed: " . mysqli_connect_error());
    } else {
        error_log("Database connection failed: " . mysqli_connect_error());
        die("Database connection error. Please contact the administrator.");
    }
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (mysqli_query($conn, $sql)) {
    // Select the database
    mysqli_select_db($conn, $dbname);
    
    // Create users table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(50),
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        profile_picture VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        if (DEBUG_MODE) {
            die("Error creating table: " . mysqli_error($conn));
        } else {
            error_log("Error creating users table: " . mysqli_error($conn));
            die("Database setup error. Please contact the administrator.");
        }
    }
} else {
    if (DEBUG_MODE) {
        die("Error creating database: " . mysqli_error($conn));
    } else {
        error_log("Error creating database: " . mysqli_error($conn));
        die("Database setup error. Please contact the administrator.");
    }
}

// Set the base URL for the application
define('BASE_URL', '/LabOne - Copy');

/**
 * Helper function for debugging
 * Only outputs information when DEBUG_MODE is true
 * 
 * @param mixed $data The data to be logged
 * @param string $label Optional label for the debug message
 * @return void
 */
function debug_log($data, $label = 'Debug') {
    if (DEBUG_MODE) {
        if (is_array($data) || is_object($data)) {
            error_log($label . ': ' . print_r($data, true));
        } else {
            error_log($label . ': ' . $data);
        }
    }
}
?> 