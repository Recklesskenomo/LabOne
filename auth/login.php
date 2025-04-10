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
 
// Include role manager
require_once "../utils/role_manager.php";
 
// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT id, username, password, status FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $status);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, now check if account is blocked
                            if($status === 'blocked'){
                                // Account is blocked
                                $login_err = "Your account has been blocked. Please contact the administrator.";
                                $account_blocked = true; // Add a flag to indicate account is blocked
                                
                                // Add log entry for blocked account login attempt
                                $log_message = "Blocked user '{$username}' attempted to log in";
                                if (file_exists("../admin/logs.php")) {
                                    require_once "../admin/logs.php";
                                    if (function_exists('add_log_entry')) {
                                        add_log_entry($conn, 'security', $id, $log_message, $_SERVER['REMOTE_ADDR']);
                                    }
                                }
                            } else {
                                // Account is active, proceed with login
                                
                                // Password is correct - session is already started at the beginning of the file
                                
                                // Store data in session variables
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["username"] = $username;

                                // Get user role
                                $roleManager = new RoleManager($conn, $id);
                                $_SESSION["role"] = $roleManager->getUserRole();
                                $_SESSION["role_id"] = $roleManager->getUserRoleId();
                                
                                // Add log entry for successful login
                                $log_message = "User '{$username}' logged in successfully";
                                if (file_exists("../admin/logs.php")) {
                                    // Use the add_log_entry function without requiring the whole file
                                    require_once "../admin/logs.php";
                                    if (function_exists('add_log_entry')) {
                                        add_log_entry($conn, 'security', $id, $log_message, $_SERVER['REMOTE_ADDR']);
                                    }
                                }
                                
                                // Redirect user to profile page
                                header("location: ../profile.php");
                            }
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid username or password.";
                            
                            // Add log entry for failed login
                            $log_message = "Failed login attempt for username '{$username}'";
                            if (file_exists("../admin/logs.php")) {
                                require_once "../admin/logs.php";
                                if (function_exists('add_log_entry')) {
                                    add_log_entry($conn, 'security', null, $log_message, $_SERVER['REMOTE_ADDR']);
                                }
                            }
                        }
                    }
                } else{
                    // Username doesn't exist, display a generic error message
                    $login_err = "Invalid username or password.";
                    
                    // Add log entry for non-existent username
                    $log_message = "Login attempt with non-existent username '{$username}'";
                    if (file_exists("../admin/logs.php")) {
                        require_once "../admin/logs.php";
                        if (function_exists('add_log_entry')) {
                            add_log_entry($conn, 'security', null, $log_message, $_SERVER['REMOTE_ADDR']);
                        }
                    }
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
$pageTitle = "Login - Agro Vision";
include_once '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="card w-full max-w-md bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="text-center mb-6">
                <img src="../assets/images/AVlogo.png" alt="Welcome Image" class="logo-img mx-auto mb-4">
                <h2 class="text-2xl font-bold">Login</h2>
            </div>
            
            <?php if(!empty($login_err) && isset($account_blocked)): ?>
                <div class="alert alert-error mb-4 flex flex-col items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <span class="text-lg font-bold">Account Blocked</span>
                    <span><?php echo $login_err; ?></span>
                    <span class="text-sm mt-2">If you believe this is an error, please contact our support team.</span>
                </div>
            <?php elseif(!empty($login_err)): ?>
                <div class="alert alert-error mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><?php echo $login_err; ?></span>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-control mb-4">
                    <label class="label">
                        <span class="label-text">Username</span>
                    </label>
                    <input type="text" name="username" class="input input-bordered <?php echo (!empty($username_err)) ? 'input-error' : ''; ?>" value="<?php echo $username; ?>">
                    <?php if(!empty($username_err)): ?>
                        <label class="label">
                            <span class="label-text-alt text-error"><?php echo $username_err; ?></span>
                        </label>
                    <?php endif; ?>
                </div>
                
                <div class="form-control mb-6">
                    <label class="label">
                        <span class="label-text">Password</span>
                    </label>
                    <input type="password" name="password" class="input input-bordered <?php echo (!empty($password_err)) ? 'input-error' : ''; ?>">
                    <label class="label">
                        <span class="label-text-alt"></span>
                        <a href="forgot_password.php" class="label-text-alt link link-primary">Forgot Password?</a>
                    </label>
                    <?php if(!empty($password_err)): ?>
                        <label class="label">
                            <span class="label-text-alt text-error"><?php echo $password_err; ?></span>
                        </label>
                    <?php endif; ?>
                </div>
                
                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
                
                <div class="text-center mt-4">
                    <p>Don't have an account? <a href="signup.php" class="link link-primary">Sign up now</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 