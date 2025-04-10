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
if(!isset($_GET["farm_id"]) || empty($_GET["farm_id"])){
    header("location: user_management.php");
    exit;
}

$farm_id = intval($_GET["farm_id"]);
$farm_data = [];
$employees = [];
$error_msg = "";
$success_msg = "";

// Get farm information
$sql = "SELECT f.*, u.username, u.first_name, u.last_name, u.id as user_id 
        FROM farms f
        JOIN users u ON f.user_id = u.id
        WHERE f.id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $farm_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $farm_data = mysqli_fetch_assoc($result);
        } else {
            $error_msg = "Farm not found.";
        }
    } else {
        $error_msg = "Error retrieving farm data.";
    }
    
    mysqli_stmt_close($stmt);
}

// Get employee information
if(empty($error_msg)){
    $sql = "SELECT * FROM employees WHERE farm_id = ? ORDER BY position, last_name, first_name";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $farm_id);
        
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
    add_log_entry($conn, 'info', $admin_id, "Admin viewed employees for farm ID {$farm_id}", $_SERVER['REMOTE_ADDR']);
}

// Set page title and include header
$pageTitle = "Farm Employees - Agro Vision";
include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Admin Header -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <div class="mb-4 md:mb-0">
            <h1 class="text-2xl font-bold">Farm Employees</h1>
            <p class="text-base-content/70">
                <?php if (!empty($farm_data)): ?>
                    View all employees for <?php echo htmlspecialchars($farm_data['farm_name']); ?>
                <?php else: ?>
                    Farm Details Not Available
                <?php endif; ?>
            </p>
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
    <!-- Farm Info Banner -->
    <div class="bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div>
                <h2 class="text-xl font-bold"><?php echo htmlspecialchars($farm_data['farm_name']); ?></h2>
                <p class="text-base-content/70"><?php echo htmlspecialchars($farm_data['location']); ?> - 
                    <?php echo htmlspecialchars($farm_data['farm_type']); ?> Farm, 
                    <?php echo htmlspecialchars($farm_data['size']); ?> acres</p>
                <p class="text-sm mt-1">Owned by: <?php echo htmlspecialchars($farm_data['first_name'] . ' ' . $farm_data['last_name'] . ' (' . $farm_data['username'] . ')'); ?></p>
            </div>
            <div class="flex gap-2 mt-4 md:mt-0">
                <a href="view_farm_details.php?farm_id=<?php echo $farm_id; ?>" class="btn btn-outline">Back to Farm Details</a>
                <a href="user_data_modules.php?id=<?php echo $farm_data['user_id']; ?>" class="btn btn-outline">Owner Data</a>
            </div>
        </div>
    </div>
    
    <!-- Employees List -->
    <div class="bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Employees (<?php echo count($employees); ?>)
        </h2>
        
        <?php if (empty($employees)): ?>
            <div class="alert alert-info">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>No employees have been registered for this farm yet.</span>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Hire Date</th>
                            <th>Salary</th>
                            <th>Time with Farm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee['id']); ?></td>
                            <td class="font-medium"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['position']); ?></td>
                            <td><?php echo htmlspecialchars($employee['contact_number']); ?></td>
                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                            <td><?php echo htmlspecialchars($employee['address']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></td>
                            <td><?php echo number_format($employee['salary'], 2); ?></td>
                            <td><?php echo calculate_time_with_farm($employee['hire_date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Position Distribution -->
    <?php if (!empty($employees)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-base-100 p-6 rounded-lg shadow-lg">
            <h3 class="text-lg font-bold mb-4">Position Distribution</h3>
            <?php
            // Prepare position counts
            $positionCounts = [];
            
            foreach ($employees as $employee) {
                $position = $employee['position'];
                if (!isset($positionCounts[$position])) {
                    $positionCounts[$position] = 0;
                }
                $positionCounts[$position]++;
            }
            
            // Calculate total employees
            $totalEmployees = count($employees);
            ?>
            
            <div class="grid grid-cols-1 gap-2">
                <?php foreach ($positionCounts as $position => $count): ?>
                <div class="bg-base-200 p-3 rounded-lg">
                    <div class="flex justify-between items-center">
                        <div class="font-medium"><?php echo htmlspecialchars($position); ?></div>
                        <div class="badge badge-primary"><?php echo $count; ?> employee<?php echo $count > 1 ? 's' : ''; ?></div>
                    </div>
                    <div class="w-full bg-base-300 rounded-full h-2.5 mt-2">
                        <div class="bg-primary h-2.5 rounded-full" style="width: <?php echo ($count / $totalEmployees) * 100; ?>%"></div>
                    </div>
                    <div class="text-xs mt-1 text-right">
                        <?php echo round(($count / $totalEmployees) * 100, 1); ?>% of total
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="bg-base-100 p-6 rounded-lg shadow-lg">
            <h3 class="text-lg font-bold mb-4">Salary Statistics</h3>
            <?php
            // Calculate salary statistics
            $totalSalary = 0;
            $minSalary = PHP_FLOAT_MAX;
            $maxSalary = 0;
            
            foreach ($employees as $employee) {
                $salary = floatval($employee['salary']);
                $totalSalary += $salary;
                if ($salary < $minSalary) $minSalary = $salary;
                if ($salary > $maxSalary) $maxSalary = $salary;
            }
            
            $avgSalary = $totalEmployees > 0 ? $totalSalary / $totalEmployees : 0;
            ?>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="stat bg-base-200 p-3 rounded-lg">
                    <div class="stat-title">Average Salary</div>
                    <div class="stat-value text-lg"><?php echo number_format($avgSalary, 2); ?></div>
                </div>
                <div class="stat bg-base-200 p-3 rounded-lg">
                    <div class="stat-title">Total Monthly Payroll</div>
                    <div class="stat-value text-lg"><?php echo number_format($totalSalary, 2); ?></div>
                </div>
                <div class="stat bg-base-200 p-3 rounded-lg">
                    <div class="stat-title">Lowest Salary</div>
                    <div class="stat-value text-lg"><?php echo number_format($minSalary, 2); ?></div>
                </div>
                <div class="stat bg-base-200 p-3 rounded-lg">
                    <div class="stat-title">Highest Salary</div>
                    <div class="stat-value text-lg"><?php echo number_format($maxSalary, 2); ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
    
    <!-- Admin Navigation -->
    <div class="flex justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg">
        <div>
            <a href="../dashboard.php" class="btn btn-outline mr-2">Back to Dashboard</a>
            <a href="view_farm_details.php?farm_id=<?php echo $farm_id; ?>" class="btn btn-outline mr-2">Farm Details</a>
            <a href="view_farm_animals.php?farm_id=<?php echo $farm_id; ?>" class="btn btn-outline mr-2">Farm Animals</a>
            <a href="user_management.php" class="btn btn-outline mr-2">User Management</a>
        </div>
        <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
    </div>
</div>

<?php
// Helper function to calculate time with farm
function calculate_time_with_farm($hire_date) {
    $hire = new DateTime($hire_date);
    $today = new DateTime('today');
    $diff = $hire->diff($today);
    
    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . 
               ($diff->m > 0 ? ', ' . $diff->m . ' month' . ($diff->m > 1 ? 's' : '') : '');
    } elseif ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . 
               ($diff->d > 0 ? ', ' . $diff->d . ' day' . ($diff->d > 1 ? 's' : '') : '');
    } else {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
    }
}

include_once '../includes/footer.php'; 
?> 