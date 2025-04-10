<?php
// Include database configuration
require_once "config.php";

// Get token from URL parameter
$token = isset($_GET['token']) ? $_GET['token'] : '';
$now = date('Y-m-d H:i:s');

echo "<h1>Token Test Script</h1>";

if(empty($token)) {
    echo "<p>No token provided. Please add ?token=YOUR_TOKEN to the URL.</p>";
    exit;
}

echo "<p>Testing token: " . htmlspecialchars($token) . "</p>";
echo "<p>Current server time: " . $now . "</p>";

// Simple query to get token info
$query = "SELECT id, email, reset_token, reset_token_expires, reset_otp FROM users WHERE reset_token = ?";

if($stmt = mysqli_prepare($conn, $query)) {
    mysqli_stmt_bind_param($stmt, "s", $token);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            
            echo "<h2>Token Found</h2>";
            echo "<p>User ID: " . $row['id'] . "</p>";
            echo "<p>Email: " . $row['email'] . "</p>";
            echo "<p>Token: " . substr($row['reset_token'], 0, 16) . "...</p>";
            echo "<p>Expiration: " . $row['reset_token_expires'] . "</p>";
            echo "<p>OTP: " . $row['reset_otp'] . "</p>";
            
            // Check if token is expired
            if(strtotime($row['reset_token_expires']) < time()) {
                echo "<p style='color: red;'>Status: EXPIRED</p>";
                echo "<p>Token expired " . round((time() - strtotime($row['reset_token_expires'])) / 60) . " minutes ago</p>";
            } else {
                echo "<p style='color: green;'>Status: VALID</p>";
                echo "<p>Token expires in " . round((strtotime($row['reset_token_expires']) - time()) / 60) . " minutes</p>";
            }
            
            // Create a link to reset password
            echo "<p><a href='reset_password.php?token=" . $token . "'>Click here to go to reset password page</a></p>";
        } else {
            echo "<h2>Token Not Found</h2>";
            echo "<p>No user found with this token.</p>";
        }
    } else {
        echo "<p>Error executing query: " . mysqli_error($conn) . "</p>";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "<p>Error preparing query: " . mysqli_error($conn) . "</p>";
}

// Show all tokens in database for debugging
echo "<h2>All Active Tokens</h2>";
$all_tokens = mysqli_query($conn, "SELECT id, email, reset_token, reset_token_expires, reset_otp FROM users WHERE reset_token IS NOT NULL");

if(mysqli_num_rows($all_tokens) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Token (first 10 chars)</th><th>Expiration</th><th>OTP</th><th>Status</th></tr>";
    
    while($t = mysqli_fetch_assoc($all_tokens)) {
        $token_preview = substr($t['reset_token'], 0, 10) . '...';
        $is_expired = strtotime($t['reset_token_expires']) < time() ? "EXPIRED" : "VALID";
        $status_color = $is_expired == "EXPIRED" ? "red" : "green";
        
        echo "<tr>";
        echo "<td>" . $t['id'] . "</td>";
        echo "<td>" . $t['email'] . "</td>";
        echo "<td>" . $token_preview . "</td>";
        echo "<td>" . $t['reset_token_expires'] . "</td>";
        echo "<td>" . $t['reset_otp'] . "</td>";
        echo "<td style='color: " . $status_color . ";'>" . $is_expired . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No tokens found in the database.</p>";
}

mysqli_close($conn);
?> 