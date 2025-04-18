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

$user_id = $_SESSION["id"];
$error_msg = "";
$success_msg = "";

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
    }
    
    mysqli_stmt_close($stmt);
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
    }
    
    mysqli_stmt_close($stmt);
}

// Get employees by farm
$sql = "SELECT f.id, f.farm_name, COUNT(e.id) as count 
        FROM employees e
        JOIN farms f ON e.farm_id = f.id
        WHERE e.user_id = ?
        GROUP BY f.id, f.farm_name
        ORDER BY count DESC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $employee_stats['by_farm'][$row["id"]] = [
                'name' => $row["farm_name"],
                'count' => $row["count"]
            ];
        }
    }
    
    mysqli_stmt_close($stmt);
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
    }
    
    mysqli_stmt_close($stmt);
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
    }
    
    mysqli_stmt_close($stmt);
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
    }
    
    mysqli_stmt_close($stmt);
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Employee Dashboard - Agro Vision";
include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Employee Dashboard</h1>
                <img src="AVlogo.png" alt="Logo" class="logo-img">
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
            
            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap gap-4 mb-8">
                <a href="employee_registration.php" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Register New Employee
                </a>
                <a href="employee_attendance.php" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Attendance Tracking
                </a>
                <a href="employee_schedule.php" class="btn btn-accent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Work Schedules
                </a>
                <a href="employee_report.php" class="btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Generate Reports
                </a>
            </div>
            
            <!-- Overview Statistics -->
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
            
            <!-- Distribution Charts -->
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
                                            <span><?php echo htmlspecialchars($farm['farm_name']); ?></span>
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
            
            <!-- Employees by Farm -->
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
                                    <?php foreach($employee_stats['by_farm'] as $farm_id => $farm): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($farm['farm_name']); ?></td>
                                            <td><?php echo number_format($farm['count']); ?></td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <progress class="progress progress-accent w-24" value="<?php echo $farm['count']; ?>" max="<?php echo $total_employees; ?>"></progress>
                                                    <span><?php echo round($farm['count'] / $total_employees * 100, 1); ?>%</span>
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
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <a href="employee_registration.php" class="btn btn-primary">Register New Employee</a>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?> 
