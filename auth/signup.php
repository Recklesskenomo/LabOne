<?php
// Define this constant first to pass security checks in included files
define('INCLUDED', true);

// Include database configuration
require_once '../config.php';

// Initialize variables
$username = $password = $confirm_password = $email = $first_name = $last_name = "";
$username_err = $password_err = $confirm_password_err = $email_err = "";
$signup_success = false;

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Get other form fields
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    
    // Check input errors before inserting in database
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, email, first_name, last_name) VALUES (?, ?, ?, ?, ?)";
         
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssss", $param_username, $param_password, $param_email, $param_first_name, $param_last_name);
            
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_email = $email;
            $param_first_name = $first_name;
            $param_last_name = $last_name;
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Set success flag
                $signup_success = true;
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Set page title and include header
$pageTitle = "Sign Up - Agro Vision";
include_once '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="card w-full max-w-lg bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="text-center mb-6">
                <img src="../assets/images/AVlogo.png" alt="Welcome Image" class="logo-img mx-auto mb-4">
                <h2 class="text-2xl font-bold">Sign Up</h2>
            </div>
            
            <?php if ($signup_success): ?>
                <div class="alert alert-success mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>You have successfully signed up! <a href="login.php" class="font-bold">Login here</a>.</span>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
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
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Email</span>
                        </label>
                        <input type="email" name="email" class="input input-bordered <?php echo (!empty($email_err)) ? 'input-error' : ''; ?>" value="<?php echo $email; ?>">
                        <?php if(!empty($email_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $email_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Password</span>
                        </label>
                        <input type="password" name="password" class="input input-bordered <?php echo (!empty($password_err)) ? 'input-error' : ''; ?>">
                        <?php if(!empty($password_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $password_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-control">
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
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">First Name</span>
                        </label>
                        <input type="text" name="first_name" class="input input-bordered" value="<?php echo $first_name; ?>">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Last Name</span>
                        </label>
                        <input type="text" name="last_name" class="input input-bordered" value="<?php echo $last_name; ?>">
                    </div>
                </div>
                
                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary">Sign Up</button>
                </div>
                
                <div class="text-center mt-4">
                    <p>Already have an account? <a href="login.php" class="link link-primary">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 