<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database configuration
require_once "config.php";

// Define variables
$user_id = $_SESSION["id"];
$username = $_SESSION["username"];
$email = $first_name = $last_name = $profile_picture = "";
$email_err = "";
$success_msg = $error_msg = "";

// Get user data
$sql = "SELECT email, first_name, last_name, profile_picture FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        mysqli_stmt_store_result($stmt);
        
        if(mysqli_stmt_num_rows($stmt) == 1){
            mysqli_stmt_bind_result($stmt, $email, $first_name, $last_name, $profile_picture);
            mysqli_stmt_fetch($stmt);
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Check if profile update form was submitted
    if(isset($_POST["update_profile"])){
        
        // Validate email
        if(empty(trim($_POST["email"]))){
            $email_err = "Please enter an email.";
        } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
            $email_err = "Please enter a valid email address.";
        } else{
            $email = trim($_POST["email"]);
        }
        
        // Get first and last name
        $first_name = trim($_POST["first_name"]);
        $last_name = trim($_POST["last_name"]);
        
        // Check if there are no errors
        if(empty($email_err)){
            // Update user data
            $sql = "UPDATE users SET email = ?, first_name = ?, last_name = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "sssi", $email, $first_name, $last_name, $user_id);
                
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "Profile updated successfully.";
                } else{
                    $error_msg = "Something went wrong. Please try again later.";
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Check if profile picture upload form was submitted
    if(isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0){
        $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png"];
        $filename = $_FILES["profile_picture"]["name"];
        $filetype = $_FILES["profile_picture"]["type"];
        $filesize = $_FILES["profile_picture"]["size"];
        
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) {
            $error_msg = "Error: Please select a valid file format.";
        }
        
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize) {
            $error_msg = "Error: File size is larger than the allowed limit.";
        }
        
        // Verify MIME type of the file
        if(in_array($filetype, $allowed)){
            // Check whether file exists before uploading it
            $upload_dir = "uploads/";
            
            // Create directory if it doesn't exist
            if(!is_dir($upload_dir)){
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate a unique filename
            $new_filename = uniqid() . "." . $ext;
            $target_file = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)){
                // Update profile picture in database
                $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "si", $target_file, $user_id);
                    
                    if(mysqli_stmt_execute($stmt)){
                        $profile_picture = $target_file;
                        $success_msg = "Profile picture uploaded successfully.";
                    } else{
                        $error_msg = "Something went wrong updating the database.";
                    }
                    
                    mysqli_stmt_close($stmt);
                }
            } else{
                $error_msg = "There was an error uploading your file.";
            }
        } else{
            $error_msg = "Error: There was a problem with your upload. Please try again.";
        }
    }
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Profile - Agro Vision";
include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Profile Header -->
            <div class="flex flex-col md:flex-row items-center pb-6 border-b border-base-300 mb-6">
                <div class="mr-6 mb-4 md:mb-0">
                    <?php if (!empty($profile_picture)): ?>
                        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="w-32 h-32 rounded-full object-cover border-4 border-primary">
                    <?php else: ?>
                        <div class="w-32 h-32 rounded-full bg-base-300 flex items-center justify-center text-3xl font-bold text-base-content">
                            <?php echo strtoupper(substr($username, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
                    <p class="text-base-content/70"><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                    <p class="text-base-content/70"><strong>Name:</strong> <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></p>
                </div>
            </div>
            
            <!-- Alerts -->
            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><?php echo $success_msg; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-error mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="tabs tabs-bordered mb-6">
                <a class="tab tab-active" data-tab="edit-profile">Edit Profile</a>
                <a class="tab" data-tab="upload-picture">Upload Picture</a>
                <a class="tab" data-tab="change-password">Change Password</a>
                <a class="tab" data-tab="farm-details">Farm Details</a>
            </div>
            
            <!-- Tab Content: Edit Profile -->
            <div class="tab-content active" id="edit-profile">
                <h2 class="text-xl font-bold mb-4">Edit Profile</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Username</span>
                            </label>
                            <input type="text" value="<?php echo htmlspecialchars($username); ?>" class="input input-bordered" disabled>
                            <label class="label">
                                <span class="label-text-alt text-error">Username cannot be changed</span>
                            </label>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Email</span>
                            </label>
                            <input type="email" name="email" class="input input-bordered <?php echo (!empty($email_err)) ? 'input-error' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>">
                            <?php if(!empty($email_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $email_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">First Name</span>
                            </label>
                            <input type="text" name="first_name" class="input input-bordered" value="<?php echo htmlspecialchars($first_name); ?>">
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Last Name</span>
                            </label>
                            <input type="text" name="last_name" class="input input-bordered" value="<?php echo htmlspecialchars($last_name); ?>">
                        </div>
                    </div>
                    
                    <div class="form-control mt-6">
                        <input type="submit" name="update_profile" class="btn btn-primary" value="Update Profile">
                    </div>
                </form>
            </div>
            
            <!-- Tab Content: Upload Picture -->
            <div class="tab-content hidden" id="upload-picture">
                <h2 class="text-xl font-bold mb-4">Upload Profile Picture</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Select Image</span>
                        </label>
                        <input type="file" name="profile_picture" accept="image/*" class="file-input file-input-bordered w-full">
                    </div>
                    <div class="form-control mt-6">
                        <input type="submit" class="btn btn-primary" value="Upload Picture">
                    </div>
                </form>
            </div>
            
            <!-- Tab Content: Change Password -->
            <div class="tab-content hidden" id="change-password">
                <h2 class="text-xl font-bold mb-4">Change Password</h2>
                <p class="mb-4">Update your password to keep your account secure.</p>
                <a href="change_password.php" class="btn btn-primary">Change Password</a>
            </div>
            
            <!-- Tab Content: Farm Details -->
            <div class="tab-content hidden" id="farm-details">
                <h2 class="text-xl font-bold mb-4">Farm Details</h2>
                <p class="mb-4">This is where you can view and manage your farm details.</p>
                <a href="farmregistration.php" class="btn btn-primary">Manage Farm</a>
            </div>
            
            <!-- Actions -->
            <div class="flex justify-between items-center mt-8 pt-6 border-t border-base-300">
                <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
                <a href="logout.php" class="btn btn-outline btn-secondary">Logout</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('tab-active'));
                
                // Hide all tab contents
                tabContents.forEach(c => {
                    c.classList.add('hidden');
                    c.classList.remove('active');
                });
                
                // Add active class to current tab
                this.classList.add('tab-active');
                
                // Show corresponding content
                const tabId = this.getAttribute('data-tab');
                const content = document.getElementById(tabId);
                content.classList.remove('hidden');
                content.classList.add('active');
            });
        });
    });
</script>

<?php include_once 'includes/footer.php'; ?> 