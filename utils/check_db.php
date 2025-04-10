<?php
// Include database configuration
require_once "config.php";

// Check if the users table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if(mysqli_num_rows($table_check) == 0) {
    echo "Users table does not exist!";
    exit;
}

// Check columns in users table
$columns_check = mysqli_query($conn, "SHOW COLUMNS FROM users");
$columns = [];

while($column = mysqli_fetch_assoc($columns_check)) {
    $columns[$column['Field']] = $column;
}

echo "<h2>Users Table Structure</h2>";
echo "<pre>";
print_r($columns);
echo "</pre>";

// Check specifically for reset token columns
echo "<h2>Reset Password Columns</h2>";
echo "<ul>";
if(isset($columns['reset_token'])) {
    echo "<li>reset_token: EXISTS - Type: " . $columns['reset_token']['Type'] . "</li>";
} else {
    echo "<li>reset_token: MISSING</li>";
}

if(isset($columns['reset_token_expires'])) {
    echo "<li>reset_token_expires: EXISTS - Type: " . $columns['reset_token_expires']['Type'] . "</li>";
} else {
    echo "<li>reset_token_expires: MISSING</li>";
}

if(isset($columns['reset_otp'])) {
    echo "<li>reset_otp: EXISTS - Type: " . $columns['reset_otp']['Type'] . "</li>";
} else {
    echo "<li>reset_otp: MISSING</li>";
}
echo "</ul>";

// Check if there are any tokens in the database
$token_check = mysqli_query($conn, "SELECT id, email, reset_token, reset_token_expires, reset_otp FROM users WHERE reset_token IS NOT NULL");
echo "<h2>Active Reset Tokens</h2>";

if(mysqli_num_rows($token_check) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Token (first 10 chars)</th><th>Expiration</th><th>OTP</th><th>Expired?</th></tr>";
    
    while($token = mysqli_fetch_assoc($token_check)) {
        $token_preview = substr($token['reset_token'], 0, 10) . '...';
        $is_expired = strtotime($token['reset_token_expires']) < time() ? "YES" : "NO";
        
        echo "<tr>";
        echo "<td>" . $token['id'] . "</td>";
        echo "<td>" . $token['email'] . "</td>";
        echo "<td>" . $token_preview . "</td>";
        echo "<td>" . $token['reset_token_expires'] . "</td>";
        echo "<td>" . $token['reset_otp'] . "</td>";
        echo "<td>" . $is_expired . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "No active reset tokens found in the database.";
}

// Show current server time
echo "<h2>Current Server Time</h2>";
echo date('Y-m-d H:i:s');
?> 