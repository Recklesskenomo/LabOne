<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

// Include database configuration
require_once "../config.php";

// Set constants for included files
define('INCLUDED', true);

// Include role manager
require_once "../utils/role_manager.php";

// Get user information from database
$user_id = $_SESSION["id"];

// Initialize role manager
$roleManager = new RoleManager($conn, $user_id);

// Check if user is admin
if (!$roleManager->isAdmin()) {
    // Not an admin, redirect to dashboard
    header("location: ../dashboard.php");
    exit;
}

// Process system settings form submission
$success_msg = "";
$error_msg = "";

// Create settings table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_description TEXT,
    is_protected TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $sql)) {
    $error_msg = "Error creating settings table: " . mysqli_error($conn);
}

// Initialize default settings if they don't exist
$default_settings = [
    ['site_name', 'Agro Vision', 'The name of the website', 0],
    ['site_description', 'Smart Farm Management System', 'Short description of the site', 0],
    ['maintenance_mode', '0', 'Put the site in maintenance mode (1=enabled, 0=disabled)', 0],
    ['max_upload_size', '5', 'Maximum file upload size in MB', 0],
    ['allow_user_registration', '1', 'Allow new user registrations (1=enabled, 0=disabled)', 0],
    ['system_version', '1.0.0', 'Current system version', 1],
    ['install_date', date('Y-m-d H:i:s'), 'System installation date', 1]
];

foreach ($default_settings as $setting) {
    $name = $setting[0];
    $value = $setting[1];
    $description = $setting[2];
    $protected = $setting[3];
    
    // Check if setting exists
    $check_sql = "SELECT id FROM system_settings WHERE setting_name = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $name);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) == 0) {
        // Setting doesn't exist, create it
        $insert_sql = "INSERT INTO system_settings (setting_name, setting_value, setting_description, is_protected) 
                      VALUES (?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "sssi", $name, $value, $description, $protected);
        mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);
    }
    
    mysqli_stmt_close($check_stmt);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_settings"])) {
    $updated = 0;
    $failed = 0;
    
    // Loop through all submitted settings
    foreach ($_POST as $key => $value) {
        // Skip non-setting fields
        if ($key == "update_settings") continue;
        
        // Validate setting name format
        if (preg_match('/^setting_(.+)$/', $key, $matches)) {
            $setting_name = $matches[1];
            $setting_value = trim($value);
            
            // Update setting
            $update_sql = "UPDATE system_settings SET setting_value = ? 
                          WHERE setting_name = ? AND is_protected = 0";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ss", $setting_value, $setting_name);
            
            if (mysqli_stmt_execute($update_stmt)) {
                if (mysqli_stmt_affected_rows($update_stmt) > 0) {
                    $updated++;
                }
            } else {
                $failed++;
            }
            
            mysqli_stmt_close($update_stmt);
        }
    }
    
    if ($updated > 0) {
        $success_msg = "Settings updated successfully ($updated settings).";
    }
    
    if ($failed > 0) {
        $error_msg = "Failed to update some settings ($failed settings).";
    }
}

// Get all system settings
$settings = [];
$sql = "SELECT * FROM system_settings ORDER BY is_protected, setting_name";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $settings[] = $row;
    }
    mysqli_free_result($result);
}

// Set page title and include header
$pageTitle = "System Settings - Agro Vision";
include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Admin Header -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <div class="mb-4 md:mb-0">
            <h1 class="text-2xl font-bold">System Settings</h1>
            <p class="text-base-content/70">Manage system-wide configuration settings</p>
            <div class="badge badge-primary mt-2">Administrator Access</div>
        </div>
        <img src="../assets/images/AVlogo.png" alt="Logo" class="logo-img">
    </div>
    
    <!-- Messages -->
    <?php if (!empty($success_msg)): ?>
    <div class="alert alert-success mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span><?php echo $success_msg; ?></span>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
    <div class="alert alert-error mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span><?php echo $error_msg; ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Settings Form -->
    <div class="bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4">General Settings</h2>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($settings as $setting): ?>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">
                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $setting['setting_name']))); ?>
                                <?php if ($setting['is_protected']): ?>
                                    <span class="text-error">(Protected)</span>
                                <?php endif; ?>
                            </span>
                        </label>
                        <?php if ($setting['is_protected']): ?>
                            <input type="text" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" class="input input-bordered bg-base-200" disabled>
                        <?php else: ?>
                            <input type="text" name="setting_<?php echo htmlspecialchars($setting['setting_name']); ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" class="input input-bordered">
                        <?php endif; ?>
                        <label class="label">
                            <span class="label-text-alt"><?php echo htmlspecialchars($setting['setting_description']); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="form-control mt-6">
                <button type="submit" name="update_settings" class="btn btn-primary">Update Settings</button>
            </div>
        </form>
    </div>
    
    <!-- Admin Navigation -->
    <div class="flex justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg">
        <div>
            <a href="../dashboard.php" class="btn btn-outline mr-2">Back to Dashboard</a>
            <a href="user_management.php" class="btn btn-outline mr-2">User Management</a>
            <a href="logs.php" class="btn btn-outline mr-2">System Logs</a>
            <a href="contact_messages.php" class="btn btn-outline mr-2">Contact Messages</a>
            <a href="user_data_modules.php" class="btn btn-outline mr-2">View User Data</a>
        </div>
        <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 