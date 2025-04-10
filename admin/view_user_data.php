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

// Check if user ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: user_management.php");
    exit;
}

$user_id = intval($_GET["id"]);
$user_data = [];
$farms = [];
$error_msg = "";
$success_msg = "";

// Get user information
$sql = "SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $user_data = mysqli_fetch_assoc($result);
        } else {
            $error_msg = "User not found.";
        }
    } else {
        $error_msg = "Error retrieving user data.";
    }
    
    mysqli_stmt_close($stmt);
}

// Get farms associated with this user
if(empty($error_msg)){
    $sql = "SELECT * FROM farms WHERE user_id = ? ORDER BY created_at DESC";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            while($row = mysqli_fetch_assoc($result)){
                $farm = $row;
                
                // Get animal count
                $animal_sql = "SELECT COUNT(*) as animal_count FROM animals WHERE farm_id = ?";
                if($animal_stmt = mysqli_prepare($conn, $animal_sql)){
                    mysqli_stmt_bind_param($animal_stmt, "i", $row['id']);
                    mysqli_stmt_execute($animal_stmt);
                    $animal_result = mysqli_stmt_get_result($animal_stmt);
                    $animal_count = mysqli_fetch_assoc($animal_result)['animal_count'];
                    $farm['animal_count'] = $animal_count;
                    mysqli_stmt_close($animal_stmt);
                }
                
                // Get employee count
                $employee_sql = "SELECT COUNT(*) as employee_count FROM employees WHERE farm_id = ?";
                if($employee_stmt = mysqli_prepare($conn, $employee_sql)){
                    mysqli_stmt_bind_param($employee_stmt, "i", $row['id']);
                    mysqli_stmt_execute($employee_stmt);
                    $employee_result = mysqli_stmt_get_result($employee_stmt);
                    $employee_count = mysqli_fetch_assoc($employee_result)['employee_count'];
                    $farm['employee_count'] = $employee_count;
                    mysqli_stmt_close($employee_stmt);
                }
                
                $farms[] = $farm;
            }
        } else {
            $error_msg = "Error retrieving farm data.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Log the action
if (function_exists('add_log_entry')) {
    add_log_entry($conn, 'security', $admin_id, "Admin viewed user data for user ID {$user_id}", $_SERVER['REMOTE_ADDR']);
}

// Set page title and include header
$pageTitle = "View User Data - Agro Vision";
include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Admin Header -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <div class="mb-4 md:mb-0">
            <h1 class="text-2xl font-bold">User Data Overview</h1>
            <p class="text-base-content/70">View comprehensive information for this user</p>
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
    
    <?php if (!empty($user_data)): ?>
    <!-- User Profile Information -->
    <div class="bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            User Profile
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
                <div class="flex">
                    <span class="font-semibold w-32">Username:</span>
                    <span><?php echo htmlspecialchars($user_data["username"]); ?></span>
                </div>
                <div class="flex">
                    <span class="font-semibold w-32">Name:</span>
                    <span><?php echo htmlspecialchars($user_data["first_name"] . ' ' . $user_data["last_name"]); ?></span>
                </div>
                <div class="flex">
                    <span class="font-semibold w-32">Email:</span>
                    <span><?php echo htmlspecialchars($user_data["email"]); ?></span>
                </div>
                <div class="flex">
                    <span class="font-semibold w-32">Role:</span>
                    <span>
                        <div class="badge <?php echo $user_data['role_name'] === 'admin' ? 'badge-primary' : 'badge-secondary'; ?>">
                            <?php echo ucfirst(htmlspecialchars($user_data['role_name'])); ?>
                        </div>
                    </span>
                </div>
            </div>
            <div class="space-y-3">
                <div class="flex">
                    <span class="font-semibold w-32">Status:</span>
                    <span>
                        <div class="badge <?php echo $user_data['status'] === 'active' ? 'badge-success' : 'badge-error'; ?>">
                            <?php echo ucfirst(htmlspecialchars($user_data['status'] ?? 'active')); ?>
                        </div>
                    </span>
                </div>
                <div class="flex">
                    <span class="font-semibold w-32">Joined:</span>
                    <span><?php echo date('F j, Y', strtotime($user_data['created_at'])); ?></span>
                </div>
                <div class="flex">
                    <span class="font-semibold w-32">Total Farms:</span>
                    <span><?php echo count($farms); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Farm Information -->
    <div class="bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Farms (<?php echo count($farms); ?>)
        </h2>
        
        <?php if (empty($farms)): ?>
            <div class="alert alert-info">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>This user has not registered any farms yet.</span>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Farm Name</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Animals</th>
                            <th>Employees</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($farms as $farm): ?>
                        <tr>
                            <td class="font-medium"><?php echo htmlspecialchars($farm['farm_name']); ?></td>
                            <td><?php echo htmlspecialchars($farm['location']); ?></td>
                            <td><?php echo htmlspecialchars($farm['farm_type']); ?></td>
                            <td><?php echo htmlspecialchars($farm['size']); ?> acres</td>
                            <td><?php echo htmlspecialchars($farm['animal_count']); ?></td>
                            <td><?php echo htmlspecialchars($farm['employee_count']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($farm['created_at'])); ?></td>
                            <td>
                                <div class="dropdown dropdown-end">
                                    <label tabindex="0" class="btn btn-xs">View Details</label>
                                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                                        <li><a href="view_farm_details.php?id=<?php echo $farm['id']; ?>">View Farm</a></li>
                                        <li><a href="view_farm_animals.php?farm_id=<?php echo $farm['id']; ?>">View Animals</a></li>
                                        <li><a href="view_farm_employees.php?farm_id=<?php echo $farm['id']; ?>">View Employees</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Activity -->
    <div class="bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Recent Activity
        </h2>
        
        <?php
        // Get user's recent activity from logs
        $logs = [];
        $log_sql = "SELECT * FROM system_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
        if($log_stmt = mysqli_prepare($conn, $log_sql)){
            mysqli_stmt_bind_param($log_stmt, "i", $user_id);
            
            if(mysqli_stmt_execute($log_stmt)){
                $log_result = mysqli_stmt_get_result($log_stmt);
                
                while($log_row = mysqli_fetch_assoc($log_result)){
                    $logs[] = $log_row;
                }
            }
            
            mysqli_stmt_close($log_stmt);
        }
        ?>
        
        <?php if (empty($logs)): ?>
            <div class="alert alert-info">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>No recent activity found for this user.</span>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Message</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <div class="badge <?php 
                                    switch($log['log_type']) {
                                        case 'info': echo 'badge-info'; break;
                                        case 'warning': echo 'badge-warning'; break;
                                        case 'error': echo 'badge-error'; break;
                                        case 'security': echo 'badge-secondary'; break;
                                        default: echo 'badge-ghost';
                                    }
                                ?>"><?php echo ucfirst(htmlspecialchars($log['log_type'])); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($log['message']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php endif; ?>
    
    <!-- Admin Navigation -->
    <div class="flex justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg">
        <div>
            <a href="../dashboard.php" class="btn btn-outline mr-2">Back to Dashboard</a>
            <a href="user_management.php" class="btn btn-outline mr-2">User Management</a>
            <a href="system_settings.php" class="btn btn-outline mr-2">System Settings</a>
            <a href="logs.php" class="btn btn-outline mr-2">System Logs</a>
            <a href="user_data_modules.php?id=<?php echo $user_id; ?>" class="btn btn-primary mr-2">Modular View</a>
        </div>
        <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 