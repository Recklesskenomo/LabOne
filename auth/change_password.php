<?php
// Define this constant first to pass security checks in included files
define('INCLUDED', true);

// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database configuration
require_once "../config.php";

$error = "";
$success = "";
$show_otp_form = false;
$show_password_form = true;
$otp = "";

// Generate OTP
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// If requesting OTP
if(isset($_POST['request_otp'])) {
    $current_password = $_POST['current_password'];
    
    // Verify current password first
    $sql = "SELECT password FROM users WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1){
                $row = mysqli_fetch_assoc($result);
                $stored_password = $row['password'];
                
                // Verify the current password
                if(password_verify($current_password, $stored_password)){
                    // Generate OTP
                    $otp = generateOTP();
                    $_SESSION['change_password_otp'] = $otp;
                    $_SESSION['otp_timestamp'] = time();
                    
                    $show_otp_form = true;
                    $show_password_form = false;
                    $success = "OTP has been generated. For testing purposes, your OTP is: " . $otp;
                } else {
                    $error = "Current password is incorrect";
                }
            } else {
                $error = "User not found";
            }
        } else {
            $error = "Oops! Something went wrong. Please try again later.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// If verifying OTP and changing password
if(isset($_POST['change_password'])) {
    $entered_otp = $_POST['otp'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify OTP
    if(!isset($_SESSION['change_password_otp']) || !isset($_SESSION['otp_timestamp'])) {
        $error = "OTP session has expired. Please try again.";
        $show_password_form = true;
        $show_otp_form = false;
    } 
    // Check if OTP is expired (10 minutes validity)
    else if((time() - $_SESSION['otp_timestamp']) > 600) {
        $error = "OTP has expired. Please request a new one.";
        unset($_SESSION['change_password_otp']);
        unset($_SESSION['otp_timestamp']);
        $show_password_form = true;
        $show_otp_form = false;
    }
    else if($entered_otp != $_SESSION['change_password_otp']) {
        $error = "Invalid OTP. Please try again.";
        $show_otp_form = true;
        $show_password_form = false;
    }
    // Validate password
    else if(empty($new_password) || empty($confirm_password)) {
        $error = "Please enter and confirm your new password";
        $show_otp_form = true;
        $show_password_form = false;
    }
    else if($new_password != $confirm_password) {
        $error = "New passwords do not match";
        $show_otp_form = true;
        $show_password_form = false;
    }
    else if(strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long";
        $show_otp_form = true;
        $show_password_form = false;
    }
    else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password = ? WHERE id = ?";
        
        if($update_stmt = mysqli_prepare($conn, $update_sql)){
            mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $_SESSION["id"]);
            
            if(mysqli_stmt_execute($update_stmt)){
                $success = "Password successfully updated";
                // Clear OTP session variables
                unset($_SESSION['change_password_otp']);
                unset($_SESSION['otp_timestamp']);
                $show_password_form = true;
                $show_otp_form = false;
            } else {
                $error = "Error updating password. Please try again later.";
                $show_otp_form = true;
                $show_password_form = false;
            }
            
            mysqli_stmt_close($update_stmt);
        }
    }
}

// Set page title and include header
$pageTitle = "Change Password - Agro Vision";
include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden p-6">
        <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
            <h1 class="text-2xl font-bold">Change Password</h1>
            <img src="../assets/images/AVlogo.png" alt="Logo" class="logo-img">
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($show_password_form): ?>
        <!-- Initial Password Form -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-6">
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="input input-bordered w-full" required>
            </div>
            
            <div class="flex justify-between">
                <a href="../profile.php" class="btn btn-outline">Cancel</a>
                <button type="submit" name="request_otp" class="btn btn-primary">Request OTP</button>
            </div>
        </form>
        <?php endif; ?>
        
        <?php if ($show_otp_form): ?>
        <!-- OTP and New Password Form -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-4">
                <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">Enter OTP</label>
                <input type="text" id="otp" name="otp" class="input input-bordered w-full" required maxlength="6" pattern="[0-9]{6}">
                <p class="text-xs text-gray-500 mt-1">Enter the 6-digit OTP sent to you</p>
            </div>
            
            <div class="mb-4">
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" id="new_password" name="new_password" class="input input-bordered w-full" required>
                <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
            </div>
            
            <div class="mb-6">
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="input input-bordered w-full" required>
            </div>
            
            <div class="flex justify-between">
                <a href="../profile.php" class="btn btn-outline">Cancel</a>
                <button type="submit" name="change_password" class="btn btn-primary">Update Password</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 
 
 