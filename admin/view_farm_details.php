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

// Get admin user information from database
$admin_id = $_SESSION["id"];

// Initialize role manager
$roleManager = new RoleManager($conn, $admin_id);

// Check if user is admin
if (!$roleManager->isAdmin()) {
    // Not an admin, redirect to dashboard
    header("location: ../dashboard.php");
    exit;
}

// Check if farm ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: user_management.php");
    exit;
}

$farm_id = intval($_GET["id"]);
$farm_data = [];
$owner_data = [];
$error_msg = "";
$success_msg = "";

// Get farm information
$sql = "SELECT f.*, u.username, u.first_name, u.last_name, u.email 
        FROM farms f
        JOIN users u ON f.user_id = u.id
        WHERE f.id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $farm_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $farm_data = mysqli_fetch_assoc($result);
            $owner_data = [
                'id' => $farm_data['user_id'],
                'username' => $farm_data['username'],
                'first_name' => $farm_data['first_name'],
                'last_name' => $farm_data['last_name'],
                'email' => $farm_data['email']
            ];
            
            // Remove user data from farm array
            unset($farm_data['username']);
            unset($farm_data['first_name']);
            unset($farm_data['last_name']);
            unset($farm_data['email']);
        } else {
            $error_msg = "Farm not found.";
        }
    } else {
        $error_msg = "Error retrieving farm data.";
    }
    
    mysqli_stmt_close($stmt);
}

// Get statistics
$animal_count = 0;
$employee_count = 0;

if(!empty($farm_data)) {
    // Get animal count
    $animal_sql = "SELECT COUNT(*) as count FROM animals WHERE farm_id = ?";
    if($stmt = mysqli_prepare($conn, $animal_sql)){
        mysqli_stmt_bind_param($stmt, "i", $farm_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            $animal_count = mysqli_fetch_assoc($result)['count'];
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Get employee count
    $employee_sql = "SELECT COUNT(*) as count FROM employees WHERE farm_id = ?";
    if($stmt = mysqli_prepare($conn, $employee_sql)){
        mysqli_stmt_bind_param($stmt, "i", $farm_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            $employee_count = mysqli_fetch_assoc($result)['count'];
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Log the action
if (function_exists('add_log_entry')) {
    add_log_entry($conn, 'security', $admin_id, "Admin viewed farm details for farm ID {$farm_id}", $_SERVER['REMOTE_ADDR']);
}

// Set page title and include header
$pageTitle = "Farm Details - Agro Vision";
include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Admin Header -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <div class="mb-4 md:mb-0">
            <h1 class="text-2xl font-bold">Farm Details</h1>
            <p class="text-base-content/70">View detailed information about this farm</p>
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
    
    <?php if (!empty($farm_data)): ?>
    <!-- Farm Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Farm Details -->
        <div class="lg:col-span-2 bg-base-100 p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Farm Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="mb-4">
                        <div class="font-bold text-lg"><?php echo htmlspecialchars($farm_data['farm_name']); ?></div>
                        <div class="text-base-content/70"><?php echo htmlspecialchars($farm_data['location']); ?></div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex">
                            <span class="font-semibold w-32">Farm Type:</span>
                            <span><?php echo htmlspecialchars($farm_data['farm_type']); ?></span>
                        </div>
                        <div class="flex">
                            <span class="font-semibold w-32">Size:</span>
                            <span><?php echo htmlspecialchars($farm_data['size']); ?> acres</span>
                        </div>
                        <div class="flex">
                            <span class="font-semibold w-32">Registered:</span>
                            <span><?php echo date('F j, Y', strtotime($farm_data['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="mb-4 font-semibold">Description:</div>
                    <div class="bg-base-200 p-3 rounded-lg">
                        <?php echo !empty($farm_data['description']) ? htmlspecialchars($farm_data['description']) : 'No description provided.'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Overview -->
        <div class="bg-base-100 p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Farm Statistics
            </h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-primary/10 p-4 rounded-lg text-center">
                    <div class="font-bold text-3xl text-primary"><?php echo $animal_count; ?></div>
                    <div class="text-sm">Animals</div>
                </div>
                <div class="bg-secondary/10 p-4 rounded-lg text-center">
                    <div class="font-bold text-3xl text-secondary"><?php echo $employee_count; ?></div>
                    <div class="text-sm">Employees</div>
                </div>
            </div>
            
            <!-- Owner Information -->
            <div class="mt-6">
                <h3 class="font-semibold mb-2">Farm Owner:</h3>
                <div class="bg-base-200 p-4 rounded-lg">
                    <div class="font-bold"><?php echo htmlspecialchars($owner_data['first_name'] . ' ' . $owner_data['last_name']); ?></div>
                    <div class="text-sm text-base-content/70">Username: <?php echo htmlspecialchars($owner_data['username']); ?></div>
                    <div class="text-sm text-base-content/70">Email: <?php echo htmlspecialchars($owner_data['email']); ?></div>
                    <div class="mt-2">
                        <a href="user_data_modules.php?id=<?php echo $owner_data['id']; ?>" class="btn btn-xs btn-outline">View Owner Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-base-100 p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Quick Actions
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="view_farm_animals.php?farm_id=<?php echo $farm_id; ?>" class="btn btn-primary">View Animals</a>
                <a href="view_farm_employees.php?farm_id=<?php echo $farm_id; ?>" class="btn btn-secondary">View Employees</a>
            </div>
        </div>
        
        <!-- Activity Logs -->
        <div class="bg-base-100 p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Recent Activity
            </h2>
            <div class="text-base-content/70">
                <p>View recent activity related to this farm in the system logs.</p>
                <div class="mt-2">
                    <a href="logs.php?farm_id=<?php echo $farm_id; ?>" class="btn btn-outline">View Logs</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Admin Navigation -->
    <div class="flex justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg">
        <div>
            <a href="../dashboard.php" class="btn btn-outline mr-2">Back to Dashboard</a>
            <?php if (!empty($owner_data)): ?>
            <a href="user_data_modules.php?id=<?php echo $owner_data['id']; ?>" class="btn btn-outline mr-2">View User Data</a>
            <?php else: ?>
            <a href="user_data_modules.php" class="btn btn-outline mr-2">View User Data</a>
            <?php endif; ?>
            <a href="user_management.php" class="btn btn-outline mr-2">User Management</a>
            <a href="system_settings.php" class="btn btn-outline mr-2">System Settings</a>
            <a href="logs.php" class="btn btn-outline mr-2">System Logs</a>
        </div>
        <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 