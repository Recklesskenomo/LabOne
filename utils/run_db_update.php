<?php
// Include database configuration
require_once "config.php";

// SQL to add reset_token column to users table
$sql = "ALTER TABLE users 
        ADD COLUMN reset_token VARCHAR(255) NULL DEFAULT NULL,
        ADD COLUMN reset_token_expires DATETIME NULL DEFAULT NULL,
        ADD COLUMN reset_otp INT(6) NULL DEFAULT NULL";

// Execute query
if (mysqli_query($conn, $sql)) {
    echo "Reset token columns added successfully to users table.<br>";
} else {
    echo "Error adding reset token columns: " . mysqli_error($conn) . "<br>";
}

// Close connection
mysqli_close($conn);

echo "Database update completed.";
?> 