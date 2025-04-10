<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../auth/login.php");
    exit;
}

// Include database configuration
require_once "../../config.php";

$user_id = $_SESSION["id"];
$error_msg = "";
$success_msg = "";
$debug_info = "";

// Check for session messages
if(isset($_SESSION["success_msg"])) {
    $success_msg = $_SESSION["success_msg"];
    unset($_SESSION["success_msg"]);
}
if(isset($_SESSION["error_msg"])) {
    $error_msg = $_SESSION["error_msg"];
    unset($_SESSION["error_msg"]);
}

// Verify database connection
if (!isset($conn) || $conn->connect_error) {
    $error_msg = "Database connection failed. Please try again later.";
    $debug_info = "Connection error: " . (isset($conn) ? mysqli_connect_error() : "Connection variable not set");
} else {
    // Check if employees table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'employees'");
    if (!$table_check || mysqli_num_rows($table_check) == 0) {
        // Create employees table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS employees (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            farm_id INT(6) UNSIGNED NOT NULL,
            user_id INT(6) UNSIGNED NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            position VARCHAR(100) NOT NULL,
            contact_number VARCHAR(20),
            email VARCHAR(100),
            hire_date DATE NOT NULL,
            salary DECIMAL(10,2),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (farm_id) REFERENCES farms(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        
        if (!mysqli_query($conn, $sql)) {
            $error_msg = "Error setting up employee database. Please contact support.";
            $debug_info = "Error creating table: " . mysqli_error($conn);
        } else {
            $success_msg = "Employee database initialized successfully. Add your first employee to get started.";
        }
    }
}

// Get statistics about employees
$total_employees = 0;
$employee_stats = [
    'positions' => [],
    'by_farm' => [],
    'salary_ranges' => [
        '0-1000' => 0,
        '1001-2000' => 0,
        '2001-3000' => 0,
        '3001-4000' => 0,
        '4001+' => 0
    ],
    'tenure' => [
        'less_than_1_year' => 0,
        '1_to_3_years' => 0,
        '3_to_5_years' => 0,
        'more_than_5_years' => 0
    ]
];

// Get total employees count
$sql = "SELECT COUNT(*) as total FROM employees WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)){
            $total_employees = $row["total"] ?: 0;
        }
    } else {
        $debug_info .= "Error executing total employees query: " . mysqli_stmt_error($stmt) . "; ";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $debug_info .= "Error preparing total employees query: " . mysqli_error($conn) . "; ";
}

// Get employees by position
$sql = "SELECT position, COUNT(*) as count FROM employees WHERE user_id = ? GROUP BY position ORDER BY count DESC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $employee_stats['positions'][$row["position"]] = $row["count"];
        }
    } else {
        $debug_info .= "Error executing position query: " . mysqli_stmt_error($stmt) . "; ";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $debug_info .= "Error preparing position query: " . mysqli_error($conn) . "; ";
}

// Get employees by farm - using LEFT JOIN to show all farms
$sql = "SELECT f.id, f.farm_name, COUNT(e.id) as count 
        FROM farms f
        LEFT JOIN employees e ON f.id = e.farm_id AND e.user_id = ?
        WHERE f.user_id = ?
        GROUP BY f.id, f.farm_name
        ORDER BY count DESC, f.farm_name ASC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $employee_stats['by_farm'][$row["id"]] = [
                'name' => $row["farm_name"],
                'count' => $row["count"]
            ];
        }
    } else {
        $debug_info .= "Error executing farm query: " . mysqli_stmt_error($stmt) . "; ";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $debug_info .= "Error preparing farm query: " . mysqli_error($conn) . "; ";
}

// Get salary distribution
$sql = "SELECT salary FROM employees WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            if($row["salary"] === null) continue;
            
            $salary = (float)$row["salary"];
            if($salary <= 1000) {
                $employee_stats['salary_ranges']['0-1000']++;
            } elseif($salary <= 2000) {
                $employee_stats['salary_ranges']['1001-2000']++;
            } elseif($salary <= 3000) {
                $employee_stats['salary_ranges']['2001-3000']++;
            } elseif($salary <= 4000) {
                $employee_stats['salary_ranges']['3001-4000']++;
            } else {
                $employee_stats['salary_ranges']['4001+']++;
            }
        }
    } else {
        $debug_info .= "Error executing salary query: " . mysqli_stmt_error($stmt) . "; ";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $debug_info .= "Error preparing salary query: " . mysqli_error($conn) . "; ";
}

// Get tenure distribution
$today = new DateTime();
$sql = "SELECT hire_date FROM employees WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            if($row["hire_date"] === null) continue;
            
            $hireDate = new DateTime($row["hire_date"]);
            $tenure = $hireDate->diff($today);
            $years = $tenure->y;
            
            if($years < 1) {
                $employee_stats['tenure']['less_than_1_year']++;
            } elseif($years < 3) {
                $employee_stats['tenure']['1_to_3_years']++;
            } elseif($years < 5) {
                $employee_stats['tenure']['3_to_5_years']++;
            } else {
                $employee_stats['tenure']['more_than_5_years']++;
            }
        }
    } else {
        $debug_info .= "Error executing tenure query: " . mysqli_stmt_error($stmt) . "; ";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $debug_info .= "Error preparing tenure query: " . mysqli_error($conn) . "; ";
}

// Get recent employee registrations
$recent_hires = [];
$sql = "SELECT e.id, e.first_name, e.last_name, e.position, e.hire_date, e.salary, f.farm_name 
        FROM employees e
        JOIN farms f ON e.farm_id = f.id
        WHERE e.user_id = ?
        ORDER BY e.created_at DESC LIMIT 5";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $recent_hires[] = $row;
        }
    } else {
        $debug_info .= "Error executing recent hires query: " . mysqli_stmt_error($stmt) . "; ";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $debug_info .= "Error preparing recent hires query: " . mysqli_error($conn) . "; ";
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Employee Dashboard - Agro Vision";
include_once '../../includes/header.php';

// Check if user has any farms registered
$has_farms = false;
$sql = "SELECT COUNT(*) as count FROM farms WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)){
            $has_farms = ($row["count"] > 0);
        }
    }
    
    mysqli_stmt_close($stmt);
}

// If no farms, show a message and link to farm registration
if(!$has_farms) {
    $error_msg = "You need to register a farm before you can add employees.";
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Employee Dashboard</h1>
                <img src="AVlogo.png" alt="Logo" class="logo-img">
            </div>
            
            <!-- Admin Debug Information (only visible in development) -->
            <?php if(!empty($debug_info) && isset($_GET['debug'])): ?>
                <div class="alert alert-warning mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <div>
                        <h3 class="font-bold">Debug Information</h3>
                        <div class="text-xs whitespace-pre-wrap"><?php echo $debug_info; ?></div>
                    </div>
                </div>
            <?php endif; ?>
            
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
                    <?php if(!$has_farms): ?>
                        <a href="farmregistration.php" class="btn btn-sm btn-primary ml-4">Register Farm</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Debug Information -->
            <div class="alert alert-info mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>
                    <strong>Farm Information:</strong>
                    <ul>
                        <li>Total Farms: <?php echo ($has_farms) ? count($employee_stats['by_farm']) : 0; ?></li>
                        <li>
                            <strong>Farm IDs in employee_stats['by_farm']:</strong> 
                            <?php 
                            if(!empty($employee_stats['by_farm'])) {
                                echo implode(', ', array_keys($employee_stats['by_farm']));
                            } else {
                                echo "None";
                            }
                            ?>
                        </li>
                    </ul>
                </span>
            </div>
            
            <!-- No Employees Message -->
            <?php if($total_employees == 0 && empty($error_msg) && $has_farms): ?>
                <div class="alert alert-info mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <h3 class="font-bold">No Employees Found</h3>
                        <div class="text-sm">You haven't registered any employees yet. Use the button below to add your first employee.</div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Debug Tools (only visible in development mode) -->
            <?php if(isset($_GET['debug']) && $has_farms && $total_employees == 0): ?>
                <div class="card bg-warning text-warning-content mb-8">
                    <div class="card-body">
                        <h2 class="card-title">Developer Tools</h2>
                        <p>These tools are only visible in debug mode and are meant for development and testing purposes.</p>
                        <div class="mt-4">
                            <!-- Sample data generation button removed -->
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title mb-4">Quick Actions</h2>
                    <div class="flex flex-wrap gap-4">
                        <a href="employee_registration.php" class="btn btn-primary <?php echo ($has_farms) ? '' : 'btn-disabled'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Register Employee
                        </a>
                        <!-- Sample data generation button removed -->
                    </div>
                </div>
            </div>
            
            <!-- Overview Statistics -->
            <?php if($total_employees > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="stats shadow">
                        <div class="stat">
                            <div class="stat-figure text-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="stat-title">Total Employees</div>
                            <div class="stat-value text-primary"><?php echo number_format($total_employees); ?></div>
                            <div class="stat-desc">Across all your farms</div>
                        </div>
                    </div>
                    
                    <div class="stats shadow">
                        <div class="stat">
                            <div class="stat-figure text-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="stat-title">Positions</div>
                            <div class="stat-value text-secondary"><?php echo count($employee_stats['positions']); ?></div>
                            <div class="stat-desc">Different job roles</div>
                        </div>
                    </div>
                    
                    <div class="stats shadow">
                        <div class="stat">
                            <div class="stat-figure text-accent">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                                </svg>
                            </div>
                            <div class="stat-title">Farms with Employees</div>
                            <div class="stat-value text-accent"><?php echo count($employee_stats['by_farm']); ?></div>
                            <div class="stat-desc">Where employees are assigned</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Distribution Charts -->
            <?php if($total_employees > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="card bg-base-200">
                        <div class="card-body">
                            <h2 class="card-title">Employees by Position</h2>
                            <?php if(!empty($employee_stats['positions'])): ?>
                                <div class="space-y-4 mt-4">
                                    <?php foreach($employee_stats['positions'] as $position => $count): ?>
                                        <div>
                                            <div class="flex justify-between mb-1">
                                                <span><?php echo htmlspecialchars($position); ?></span>
                                                <span class="font-semibold"><?php echo number_format($count); ?></span>
                                            </div>
                                            <progress class="progress progress-primary w-full" value="<?php echo $count; ?>" max="<?php echo $total_employees; ?>"></progress>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center py-4">No employee data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card bg-base-200">
                        <div class="card-body">
                            <h2 class="card-title">Employee Tenure</h2>
                            <?php if(!empty($employee_stats['tenure']) && array_sum($employee_stats['tenure']) > 0): ?>
                                <div class="space-y-4 mt-4">
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span>Less than 1 year</span>
                                            <span class="font-semibold"><?php echo number_format($employee_stats['tenure']['less_than_1_year']); ?></span>
                                        </div>
                                        <progress class="progress progress-secondary w-full" value="<?php echo $employee_stats['tenure']['less_than_1_year']; ?>" max="<?php echo $total_employees; ?>"></progress>
                                    </div>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span>1-3 years</span>
                                            <span class="font-semibold"><?php echo number_format($employee_stats['tenure']['1_to_3_years']); ?></span>
                                        </div>
                                        <progress class="progress progress-secondary w-full" value="<?php echo $employee_stats['tenure']['1_to_3_years']; ?>" max="<?php echo $total_employees; ?>"></progress>
                                    </div>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span>3-5 years</span>
                                            <span class="font-semibold"><?php echo number_format($employee_stats['tenure']['3_to_5_years']); ?></span>
                                        </div>
                                        <progress class="progress progress-secondary w-full" value="<?php echo $employee_stats['tenure']['3_to_5_years']; ?>" max="<?php echo $total_employees; ?>"></progress>
                                    </div>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span>More than 5 years</span>
                                            <span class="font-semibold"><?php echo number_format($employee_stats['tenure']['more_than_5_years']); ?></span>
                                        </div>
                                        <progress class="progress progress-secondary w-full" value="<?php echo $employee_stats['tenure']['more_than_5_years']; ?>" max="<?php echo $total_employees; ?>"></progress>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-center py-4">No tenure data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- More Charts -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="card bg-base-200">
                        <div class="card-body">
                            <h2 class="card-title">Salary Distribution</h2>
                            <?php if(!empty($employee_stats['salary_ranges']) && array_sum($employee_stats['salary_ranges']) > 0): ?>
                                <div class="space-y-4 mt-4">
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span>$0 - $1,000</span>
                                            <span class="font-semibold"><?php echo number_format($employee_stats['salary_ranges']['0-1000']); ?></span>
                                        </div>
                                        <progress class="progress progress-accent w-full" value="<?php echo $employee_stats['salary_ranges']['0-1000']; ?>" max="<?php echo $total_employees; ?>"></progress>
                                    </div>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span>$1,001 - $2,000</span>
                                            <span class="font-semibold"><?php echo number_format($employee_stats['salary_ranges']['1001-2000']); ?></span>
                                        </div>
                                        <progress class="progress progress-accent w-full" value="<?php echo $employee_stats['salary_ranges']['1001-2000']; ?>" max="<?php echo $total_employees; ?>"></progress>
                                    </div>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span>$2,001 - $3,000</span>
                                            <span class="font-semibold"><?php echo number_format($employee_stats['salary_ranges']['2001-3000']); ?></span>
                                        </div>
                                        <progress class="progress progress-accent w-full" value="<?php echo $employee_stats['salary_ranges']['2001-3000']; ?>" max="<?php echo $total_employees; ?>"></progress>
                                    </div>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span>$3,001 - $4,000</span>
                                            <span class="font-semibold"><?php echo number_format($employee_stats['salary_ranges']['3001-4000']); ?></span>
                                        </div>
                                        <progress class="progress progress-accent w-full" value="<?php echo $employee_stats['salary_ranges']['3001-4000']; ?>" max="<?php echo $total_employees; ?>"></progress>
                                    </div>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span>$4,001+</span>
                                            <span class="font-semibold"><?php echo number_format($employee_stats['salary_ranges']['4001+']); ?></span>
                                        </div>
                                        <progress class="progress progress-accent w-full" value="<?php echo $employee_stats['salary_ranges']['4001+']; ?>" max="<?php echo $total_employees; ?>"></progress>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-center py-4">No salary data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card bg-base-200">
                        <div class="card-body">
                            <h2 class="card-title">Employees by Farm</h2>
                            <?php if(!empty($employee_stats['by_farm'])): ?>
                                <div class="space-y-4 mt-4">
                                    <?php foreach($employee_stats['by_farm'] as $farm_id => $farm): ?>
                                        <div>
                                            <div class="flex justify-between mb-1">
                                                <span><?php echo htmlspecialchars($farm['name']); ?></span>
                                                <span class="font-semibold"><?php echo number_format($farm['count']); ?></span>
                                            </div>
                                            <progress class="progress progress-info w-full" value="<?php echo $farm['count']; ?>" max="<?php echo $total_employees; ?>"></progress>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center py-4">No farm assignment data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Employees by Farm Table -->
                <div class="card bg-base-200 mb-8">
                    <div class="card-body">
                        <h2 class="card-title">Employees by Farm</h2>
                        <?php if(!empty($employee_stats['by_farm'])): ?>
                            <div class="overflow-x-auto mt-4">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>Farm Name</th>
                                            <th>Employee Count</th>
                                            <th>Percentage of Total</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($employee_stats['by_farm'] as $farm_id => $farm): 
                                            $percentage = $total_employees > 0 ? round($farm['count'] / $total_employees * 100, 1) : 0;
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($farm['name']); ?></td>
                                                <td><?php echo number_format($farm['count']); ?></td>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        <progress class="progress progress-accent w-24" value="<?php echo $farm['count']; ?>" max="<?php echo $total_employees > 0 ? $total_employees : 1; ?>"></progress>
                                                        <span><?php echo $percentage; ?>%</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="flex gap-2">
                                                        <a href="farm_details.php?id=<?php echo $farm_id; ?>" class="btn btn-xs btn-primary">View Farm</a>
                                                        <a href="employee_registration.php?farm_id=<?php echo $farm_id; ?>" class="btn btn-xs btn-secondary">Add Employee</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center py-4">No employee data available</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Hires -->
                <div class="card bg-base-200 mb-8">
                    <div class="card-body">
                        <h2 class="card-title">Recent Employee Registrations</h2>
                        <?php if(!empty($recent_hires)): ?>
                            <div class="overflow-x-auto mt-4">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>Farm</th>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Hire Date</th>
                                            <th>Salary</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recent_hires as $employee): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($employee["farm_name"]); ?></td>
                                                <td><?php echo htmlspecialchars($employee["first_name"] . ' ' . $employee["last_name"]); ?></td>
                                                <td><?php echo htmlspecialchars($employee["position"]); ?></td>
                                                <td><?php echo $employee["hire_date"] ? date("M j, Y", strtotime($employee["hire_date"])) : "N/A"; ?></td>
                                                <td><?php echo $employee["salary"] ? '$' . number_format($employee["salary"], 2) : "N/A"; ?></td>
                                                <td>
                                                    <div class="flex gap-2">
                                                        <a href="employee_details.php?id=<?php echo $employee["id"]; ?>" class="btn btn-xs btn-primary">View</a>
                                                        <a href="edit_employee.php?id=<?php echo $employee["id"]; ?>" class="btn btn-xs btn-secondary">Edit</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center py-4">No recent employee registrations</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <?php if($has_farms): ?>
                    <a href="employee_registration.php" class="btn btn-primary">Register New Employee</a>
                <?php else: ?>
                    <a href="farmregistration.php" class="btn btn-primary">Register Farm</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?> 