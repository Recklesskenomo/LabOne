<?php
// Include database configuration
require_once "config.php";

// Clear all reset tokens
$clear_query = "UPDATE users SET reset_token = NULL, reset_token_expires = NULL, reset_otp = NULL";
if(mysqli_query($conn, $clear_query)) {
    echo "All reset tokens have been cleared successfully.";
} else {
    echo "Error clearing reset tokens: " . mysqli_error($conn);
}

echo "<p><a href='login.php'>Return to login page</a></p>";
?> 