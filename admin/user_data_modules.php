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
$animals = [];
$employees = [];
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
                $farms[] = $row;
            }
        } else {
            $error_msg = "Error retrieving farm data.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Get all animals associated with this user (via their farms)
if(empty($error_msg) && !empty($farms)){
    $farm_ids = array_column($farms, 'id');
    $farm_ids_str = implode(',', $farm_ids);
    
    $sql = "SELECT a.*, f.farm_name 
            FROM animals a 
            JOIN farms f ON a.farm_id = f.id 
            WHERE a.user_id = ? 
            ORDER BY a.farm_id, a.animal_name";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            while($row = mysqli_fetch_assoc($result)){
                $animals[] = $row;
            }
        } else {
            $error_msg = "Error retrieving animal data.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Get all employees associated with this user (via their farms)
if(empty($error_msg) && !empty($farms)){
    $farm_ids = array_column($farms, 'id');
    $farm_ids_str = implode(',', $farm_ids);
    
    $sql = "SELECT e.*, f.farm_name 
            FROM employees e 
            JOIN farms f ON e.farm_id = f.id 
            WHERE e.user_id = ? 
            ORDER BY e.farm_id, e.last_name, e.first_name";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            while($row = mysqli_fetch_assoc($result)){
                $employees[] = $row;
            }
        } else {
            $error_msg = "Error retrieving employee data.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Log the action
if (function_exists('add_log_entry')) {
    add_log_entry($conn, 'security', $admin_id, "Admin viewed comprehensive user data for user ID {$user_id}", $_SERVER['REMOTE_ADDR']);
}

// Set page title and include header
$pageTitle = "User Data Modules - Agro Vision";
include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Admin Header -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <div class="mb-4 md:mb-0">
            <h1 class="text-2xl font-bold">User Data Modules</h1>
            <p class="text-base-content/70">Comprehensive view of user data across all modules</p>
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
                    <span class="font-semibold w-32">Profile:</span>
                    <span><?php echo !empty($user_data['profile_picture']) ? 'Has profile picture' : 'No profile picture'; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabbed Content for Modules -->
    <div class="bg-base-100 rounded-lg shadow-lg mb-8">
        <div class="tabs tabs-bordered">
            <input type="radio" name="module_tabs" id="tab_farms" class="tab-toggle" checked />
            <label for="tab_farms" class="tab tab-lg tab-lifted">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Farms (<?php echo count($farms); ?>)
            </label>
            
            <input type="radio" name="module_tabs" id="tab_animals" class="tab-toggle" />
            <label for="tab_animals" class="tab tab-lg tab-lifted">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Animals (<?php echo count($animals); ?>)
            </label>
            
            <input type="radio" name="module_tabs" id="tab_employees" class="tab-toggle" />
            <label for="tab_employees" class="tab tab-lg tab-lifted">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Employees (<?php echo count($employees); ?>)
            </label>
            
            <div class="tab-content p-6">
                <!-- Farms Tab Content -->
                <div id="content_farms">
                    <h3 class="text-xl font-bold mb-4">Farm Management</h3>
                    
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
                                        <th>ID</th>
                                        <th>Farm Name</th>
                                        <th>Location</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($farms as $farm): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($farm['id']); ?></td>
                                        <td class="font-medium"><?php echo htmlspecialchars($farm['farm_name']); ?></td>
                                        <td><?php echo htmlspecialchars($farm['location']); ?></td>
                                        <td><?php echo htmlspecialchars($farm['farm_type']); ?></td>
                                        <td><?php echo htmlspecialchars($farm['size']); ?> acres</td>
                                        <td><?php echo date('M d, Y', strtotime($farm['created_at'])); ?></td>
                                        <td>
                                            <a href="view_farm_details.php?id=<?php echo $farm['id']; ?>" class="btn btn-xs btn-primary">View Details</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Animals Tab Content -->
                <div id="content_animals" class="hidden">
                    <h3 class="text-xl font-bold mb-4">Livestock Management</h3>
                    
                    <?php if (empty($animals)): ?>
                        <div class="alert alert-info">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>This user has not registered any animals yet.</span>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="table w-full">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Farm</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Breed</th>
                                        <th>Gender</th>
                                        <th>Quantity</th>
                                        <th>Purpose</th>
                                        <th>Health</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($animals as $animal): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($animal['id']); ?></td>
                                        <td><?php echo htmlspecialchars($animal['farm_name']); ?></td>
                                        <td class="font-medium"><?php echo htmlspecialchars($animal['animal_name']); ?></td>
                                        <td><?php echo htmlspecialchars($animal['animal_type']); ?></td>
                                        <td><?php echo htmlspecialchars($animal['breed']); ?></td>
                                        <td><?php echo htmlspecialchars($animal['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($animal['quantity']); ?></td>
                                        <td><?php echo htmlspecialchars($animal['purpose']); ?></td>
                                        <td>
                                            <div class="badge <?php echo $animal['health_status'] === 'Healthy' ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo htmlspecialchars($animal['health_status']); ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Employees Tab Content -->
                <div id="content_employees" class="hidden">
                    <h3 class="text-xl font-bold mb-4">Workforce Management</h3>
                    
                    <?php if (empty($employees)): ?>
                        <div class="alert alert-info">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>This user has not registered any employees yet.</span>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="table w-full">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Farm</th>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Contact</th>
                                        <th>Email</th>
                                        <th>Hire Date</th>
                                        <th>Salary</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($employee['id']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['farm_name']); ?></td>
                                        <td class="font-medium"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['contact_number']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></td>
                                        <td><?php echo htmlspecialchars(number_format($employee['salary'], 2)); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
    
    <!-- Admin Navigation -->
    <div class="flex justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg">
        <div>
            <a href="../dashboard.php" class="btn btn-outline mr-2">Back to Dashboard</a>
            <a href="user_management.php" class="btn btn-outline mr-2">User Management</a>
            <a href="system_settings.php" class="btn btn-outline mr-2">System Settings</a>
            <a href="logs.php" class="btn btn-outline mr-2">System Logs</a>
            <a href="view_user_data.php?id=<?php echo $user_id; ?>" class="btn btn-outline mr-2">Standard View</a>
        </div>
        <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
    </div>
</div>

<!-- Tab Control JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all tab toggles
    const tabToggles = document.querySelectorAll('.tab-toggle');
    
    // Get all content divs
    const contentFarms = document.getElementById('content_farms');
    const contentAnimals = document.getElementById('content_animals');
    const contentEmployees = document.getElementById('content_employees');
    
    // Add event listeners to each tab toggle
    tabToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            // Hide all content first
            contentFarms.classList.add('hidden');
            contentAnimals.classList.add('hidden');
            contentEmployees.classList.add('hidden');
            
            // Show the appropriate content based on which tab is selected
            if (document.getElementById('tab_farms').checked) {
                contentFarms.classList.remove('hidden');
            } else if (document.getElementById('tab_animals').checked) {
                contentAnimals.classList.remove('hidden');
            } else if (document.getElementById('tab_employees').checked) {
                contentEmployees.classList.remove('hidden');
            }
        });
    });
});
</script>

<?php include_once '../includes/footer.php'; ?> 