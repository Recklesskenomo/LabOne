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

// Check if reset_token column exists in the users table, add it if not
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'reset_token'");
$column_exists = mysqli_num_rows($result) > 0;

if (!$column_exists) {
    // SQL to add reset_token column to users table
    $sql = "ALTER TABLE users 
            ADD COLUMN reset_token VARCHAR(255) NULL DEFAULT NULL,
            ADD COLUMN reset_token_expires DATETIME NULL DEFAULT NULL,
            ADD COLUMN reset_otp INT(6) NULL DEFAULT NULL";
    
    // Execute query
    mysqli_query($conn, $sql);
}
 
// Define variables and initialize with empty values
$email = "";
$email_err = "";
$success_msg = "";
$otp = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if email is empty
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email address.";
    } else{
        $email = trim($_POST["email"]);
        // Check if email format is valid
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $email_err = "Please enter a valid email address.";
        }
    }
    
    // Validate email exists in database
    if(empty($email_err)){
        // Prepare a select statement
        $sql = "SELECT id, username, email FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if email exists
                if(mysqli_stmt_num_rows($stmt) == 1){
                    // Generate a random 6-digit OTP
                    $otp = rand(100000, 999999);
                    
                    // Generate a unique token
                    $token = bin2hex(random_bytes(32));
                    
                    // Set token expiration (24 hours from now instead of 1 hour)
                    $expires = date('Y-m-d H:i:s', time() + 86400); // 86400 seconds = 24 hours
                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $db_email);
                    if(mysqli_stmt_fetch($stmt)){
                        // Store OTP in session
                        $_SESSION["reset_email"] = $email;
                        
                        // Update the user record with the reset token and expiration
                        $update_sql = "UPDATE users SET reset_token = ?, reset_token_expires = ?, reset_otp = ? WHERE id = ?";
                        
                        if($update_stmt = mysqli_prepare($conn, $update_sql)){
                            // Bind variables to the prepared statement as parameters
                            mysqli_stmt_bind_param($update_stmt, "ssii", $token, $expires, $otp, $id);
                            
                            // Attempt to execute the prepared statement
                            if(mysqli_stmt_execute($update_stmt)){
                                // Success! Token stored in database
                                $success_msg = "A verification code has been sent to your email address. Please check your inbox.";
                                
                                // In a real application, send the OTP via email
                                // For now, we'll display it and store the token in the session
                                $_SESSION["reset_token"] = $token;
                                
                                // For debugging - display token information
                                $debug_info = "<div class='mt-2 text-xs'><strong>Debug:</strong> Token stored in database and session.</div>";
                            } else {
                                $email_err = "Error updating account. Please try again later.";
                            }
                            
                            // Close statement
                            mysqli_stmt_close($update_stmt);
                        } else {
                            $email_err = "Error preparing update statement. Please try again later.";
                        }
                    }
                } else{
                    $email_err = "No account found with that email address.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Set page title and include header
$pageTitle = "Forgot Password - Agro Vision";
include_once '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="card w-full max-w-md bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="text-center mb-6">
                <img src="../assets/images/AVlogo.png" alt="Agro Vision" class="logo-img mx-auto mb-4">
                <h2 class="text-2xl font-bold">Forgot Password</h2>
                <p class="text-base-content/70 mt-2">Enter your email address and we'll send you a verification code to reset your password.</p>
            </div>
            
            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success mb-6">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span><?php echo $success_msg; ?></span>
                    </div>
                </div>
                
                <!-- For testing purposes only - show the OTP -->
                <div class="alert alert-info mb-6">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current flex-shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <span class="font-bold">Testing Only:</span> Your OTP is <span class="font-mono font-bold"><?php echo $otp; ?></span>
                            <p class="text-xs">In a real application, this would be sent via email</p>
                            <?php if(isset($debug_info)) echo $debug_info; ?>
                            <p class="text-xs">Token: <span class="font-mono"><?php echo substr($token, 0, 16) . '...'; ?></span></p>
                        </div>
                    </div>
                </div>
                
                <div class="form-control mt-4">
                    <a href="reset_password.php?token=<?php echo $token; ?>" class="btn btn-primary">Continue to Reset Password</a>
                </div>
            <?php else: ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-control mb-6">
                        <label class="label">
                            <span class="label-text">Email</span>
                        </label>
                        <input type="email" name="email" class="input input-bordered <?php echo (!empty($email_err)) ? 'input-error' : ''; ?>" value="<?php echo $email; ?>" placeholder="Enter your registered email">
                        <?php if(!empty($email_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $email_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-control mt-6">
                        <button type="submit" class="btn btn-primary">Request Reset Link</button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="text-center mt-6">
                <p>Remember your password? <a href="login.php" class="link link-primary">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 