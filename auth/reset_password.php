<?php
// Define this constant first to pass security checks in included files
define('INCLUDED', true);

// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect to profile page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: ../profile.php");
    exit;
}

// Include database configuration
require_once "../config.php";
 
// Define variables and initialize with empty values
$otp = $new_password = $confirm_password = "";
$otp_err = $new_password_err = $confirm_password_err = $token_err = "";
$success = false;
$verification_step = true;
$token = $user_id = $user_email = "";

// Check if token is set in the URL or POST data
if ((!isset($_GET["token"]) || empty(trim($_GET["token"]))) && 
    (!isset($_POST["token"]) || empty(trim($_POST["token"])))) {
    $token_err = "Invalid password reset request. No token provided.";
} else {
    // Get token from either GET or POST
    $token = isset($_POST["token"]) ? trim($_POST["token"]) : trim($_GET["token"]);
    
    // For debugging
    $debug_info = "";
    $current_time = date('Y-m-d H:i:s');
    
    // Check for token in database with a simpler query
    $debug_query = "SELECT id, email, reset_otp, reset_token, reset_token_expires FROM users WHERE reset_token = ?";
    
    if($debug_stmt = mysqli_prepare($conn, $debug_query)){
        mysqli_stmt_bind_param($debug_stmt, "s", $token);
        if(mysqli_stmt_execute($debug_stmt)){
            $debug_result = mysqli_stmt_get_result($debug_stmt);
            if(mysqli_num_rows($debug_result) == 1){
                $debug_row = mysqli_fetch_assoc($debug_result);
                $debug_info = "Token exists in database. Expires: " . $debug_row['reset_token_expires'] . 
                              ", Current time: " . $current_time;
                
                // Check if token has expired
                if(strtotime($debug_row['reset_token_expires']) < time()){
                    $debug_info .= " (EXPIRED)";
                }
            } else {
                $debug_info = "Token not found in database.";
            }
        } else {
            $debug_info = "Error executing debug query: " . mysqli_error($conn);
        }
        mysqli_stmt_close($debug_stmt);
    }
    
    // Check if token exists in the database and hasn't expired
    $sql = "SELECT id, email, reset_otp FROM users WHERE reset_token = ? AND reset_token_expires > ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "ss", $token, $current_time);
        
        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt)){
            // Store result
            mysqli_stmt_store_result($stmt);
            
            // Check if token exists and is valid
            if(mysqli_stmt_num_rows($stmt) == 1){
                // Bind result variables
                mysqli_stmt_bind_result($stmt, $user_id, $user_email, $db_otp);
                if(mysqli_stmt_fetch($stmt)){
                    // Token is valid, proceed with password reset
                } else {
                    $token_err = "Error retrieving user information.";
                }
            } else {
                $token_err = "Invalid or expired reset token.";
            }
        } else {
            $token_err = "Oops! Something went wrong. Please try again later.";
        }
        
        // Close statement
        mysqli_stmt_close($stmt);
    }
}

// Process verification form
if($_SERVER["REQUEST_METHOD"] == "POST" && empty($token_err)){
    
    // Make sure token is available
    if(isset($_POST["token"])) {
        $token = trim($_POST["token"]);
    }
    
    // Check if we're processing OTP verification
    if(isset($_POST["verify_otp"])){
        // Validate OTP
        if(empty(trim($_POST["otp"]))){
            $otp_err = "Please enter the verification code.";
        } else{
            $otp = trim($_POST["otp"]);
            // Check if OTP matches
            if($otp != $db_otp){
                $otp_err = "The verification code is invalid.";
            } else {
                // OTP is correct, move to password reset step
                $verification_step = false;
            }
        }
    } 
    // Check if we're processing password update
    else if(isset($_POST["reset_password"])){
        
        // Validate new password
        if(empty(trim($_POST["new_password"]))){
            $new_password_err = "Please enter a new password.";     
        } elseif(strlen(trim($_POST["new_password"])) < 6){
            $new_password_err = "Password must have at least 6 characters.";
        } else{
            $new_password = trim($_POST["new_password"]);
        }
        
        // Validate confirm password
        if(empty(trim($_POST["confirm_password"]))){
            $confirm_password_err = "Please confirm the password.";
        } else{
            $confirm_password = trim($_POST["confirm_password"]);
            if(empty($new_password_err) && ($new_password != $confirm_password)){
                $confirm_password_err = "Password did not match.";
            }
        }
        
        // Check input errors before updating the database
        if(empty($new_password_err) && empty($confirm_password_err)){
            
            // Prepare an update statement
            $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL, reset_otp = NULL WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "si", $param_password, $param_id);
                
                // Set parameters
                $param_password = password_hash($new_password, PASSWORD_DEFAULT);
                $param_id = $user_id;
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    // Password updated successfully
                    $success = true;
                    
                    // Clear the session variables
                    unset($_SESSION["reset_email"]);
                    unset($_SESSION["reset_token"]);
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }

                // Close statement
                mysqli_stmt_close($stmt);
            }
        }
        
        // Skip verification step
        $verification_step = false;
    }
}

// Set page title and include header
$pageTitle = "Reset Password - Agro Vision";
include_once '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="card w-full max-w-md bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="text-center mb-6">
                <img src="../assets/images/AVlogo.png" alt="Agro Vision" class="logo-img mx-auto mb-4">
                <h2 class="text-2xl font-bold"><?php echo $verification_step ? "Verify Your Email" : "Reset Password"; ?></h2>
                
                <?php if(!empty($token_err)): ?>
                    <p class="text-base-content/70 mt-2 text-error"><?php echo $token_err; ?></p>
                    <?php if(isset($debug_info)): ?>
                        <div class="alert alert-warning mt-4 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            <span>Debug Info: <?php echo $debug_info; ?></span>
                        </div>
                    <?php endif; ?>
                <?php elseif($verification_step): ?>
                    <p class="text-base-content/70 mt-2">We've sent a verification code to <span class="font-bold"><?php echo htmlspecialchars($user_email); ?></span>. Please enter it below to continue.</p>
                <?php else: ?>
                    <p class="text-base-content/70 mt-2">Please create a new password for your account.</p>
                <?php endif; ?>
            </div>
            
            <?php if($success): ?>
                <div class="alert alert-success mb-6">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Your password has been reset successfully!</span>
                    </div>
                </div>
                
                <div class="form-control mt-6">
                    <a href="login.php" class="btn btn-primary">Log In with New Password</a>
                </div>
            <?php elseif(!empty($token_err)): ?>
                <div class="form-control mt-6">
                    <a href="forgot_password.php" class="btn btn-primary">Return to Forgot Password</a>
                </div>
            <?php elseif($verification_step): ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?token=<?php echo $token; ?>" method="post">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-control mb-6">
                        <label class="label">
                            <span class="label-text">Verification Code</span>
                        </label>
                        <input type="text" name="otp" class="input input-bordered font-mono text-center tracking-widest text-lg <?php echo (!empty($otp_err)) ? 'input-error' : ''; ?>" placeholder="Enter 6-digit code">
                        <?php if(!empty($otp_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $otp_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-control mt-6">
                        <button type="submit" name="verify_otp" class="btn btn-primary">Verify Code</button>
                    </div>
                </form>
                
                <div class="divider my-6">OR</div>
                
                <div class="text-center">
                    <p>Didn't receive the code? <a href="forgot_password.php" class="link link-primary">Request a new one</a></p>
                </div>
            <?php else: ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?token=<?php echo $token; ?>" method="post">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">New Password</span>
                        </label>
                        <input type="password" name="new_password" class="input input-bordered <?php echo (!empty($new_password_err)) ? 'input-error' : ''; ?>">
                        <?php if(!empty($new_password_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $new_password_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-control mb-6">
                        <label class="label">
                            <span class="label-text">Confirm Password</span>
                        </label>
                        <input type="password" name="confirm_password" class="input input-bordered <?php echo (!empty($confirm_password_err)) ? 'input-error' : ''; ?>">
                        <?php if(!empty($confirm_password_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $confirm_password_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-control mt-6">
                        <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 