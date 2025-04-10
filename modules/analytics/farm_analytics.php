<?php
/**
 * File: farm_analytics.php
 * Description: Enhanced analytics dashboard for farm management with visual charts
 * 
 * Part of Agro Vision Farm Management System
 */

// Enable error reporting in development environment
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // For production, log errors but don't display them
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../auth/login.php");
    exit;
}

// Include database configuration
require_once "../../config.php";

// Set constants for included files
define('INCLUDED', true);

// Initialize variables to prevent undefined variable errors
$farm_stats = [];
$animal_distribution = [];
$health_stats = [];
$employee_distribution = [];
$animal_trends = [];
$error_msg = "";
$success_msg = "";

// Get user information from database
$user_id = $_SESSION["id"] ?? 0;

// Get farm statistics
try {
    $sql = "SELECT f.id, f.farm_name, f.farm_type, f.size as farm_size, 'hectares' as farm_size_unit, f.location as farm_location,
            COUNT(DISTINCT a.id) as animal_types,
            COALESCE(SUM(a.quantity), 0) as total_animals,
            COUNT(DISTINCT e.id) as employee_count
            FROM farms f 
            LEFT JOIN animals a ON f.id = a.farm_id
            LEFT JOIN employees e ON f.id = e.farm_id
            WHERE f.user_id = ?
            GROUP BY f.id, f.farm_name
            ORDER BY f.farm_name";

    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $farm_stats[] = $row;
            }
        } else {
            $error_msg = "Error executing farm statistics query: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $error_msg = "Error preparing farm statistics query: " . mysqli_error($conn);
    }
} catch (Exception $e) {
    $error_msg = "Exception in farm statistics: " . $e->getMessage();
}

// Get animal distribution by type across all farms
try {
    $sql = "SELECT a.animal_type, COALESCE(SUM(a.quantity), 0) as count
            FROM animals a
            JOIN farms f ON a.farm_id = f.id
            WHERE f.user_id = ?
            GROUP BY a.animal_type
            ORDER BY count DESC
            LIMIT 8";

    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $animal_distribution[] = $row;
            }
        } else {
            $error_msg = "Error executing animal distribution query: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $error_msg = "Error preparing animal distribution query: " . mysqli_error($conn);
    }
} catch (Exception $e) {
    $error_msg = "Exception in animal distribution: " . $e->getMessage();
}

// Get animal health statistics
try {
    // Check if animal_health table exists first
    $table_exists = false;
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'animal_health'");
    if ($table_check) {
        $table_exists = mysqli_num_rows($table_check) > 0;
    }

    if ($table_exists) {
        // The health_status values in the database are lowercase
        $sql = "SELECT health_status, COALESCE(SUM(quantity), 0) as count
                FROM animal_health h
                JOIN animals a ON h.animal_id = a.id
                JOIN farms f ON a.farm_id = f.id
                WHERE f.user_id = ?
                GROUP BY health_status";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                while($row = mysqli_fetch_assoc($result)){
                    $health_stats[] = $row;
                }
            } else {
                $error_msg = "Error executing health statistics query: " . mysqli_error($conn);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Error preparing health statistics query: " . mysqli_error($conn);
        }
    } else {
        // Add some dummy data if table doesn't exist
        $health_stats = [
            ['health_status' => 'healthy', 'count' => 0],
            ['health_status' => 'sick', 'count' => 0],
            ['health_status' => 'treatment', 'count' => 0],
            ['health_status' => 'monitoring', 'count' => 0]
        ];
    }
} catch (Exception $e) {
    $error_msg = "Exception in health statistics: " . $e->getMessage();
    // Add some dummy data if there's an error
    $health_stats = [
        ['health_status' => 'healthy', 'count' => 0],
        ['health_status' => 'sick', 'count' => 0],
        ['health_status' => 'treatment', 'count' => 0],
        ['health_status' => 'monitoring', 'count' => 0]
    ];
}

// Get employee distribution by position
try {
    $sql = "SELECT e.position, COUNT(e.id) as count
            FROM employees e
            JOIN farms f ON e.farm_id = f.id
            WHERE f.user_id = ?
            GROUP BY e.position
            ORDER BY count DESC";

    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $employee_distribution[] = $row;
            }
        } else {
            $error_msg = "Error executing employee distribution query: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $error_msg = "Error preparing employee distribution query: " . mysqli_error($conn);
    }
} catch (Exception $e) {
    $error_msg = "Exception in employee distribution: " . $e->getMessage();
}

// Get monthly animal trends (growth over the last 6 months)
try {
    $current_month = date('m');
    $current_year = date('Y');
    
    // Initialize with all months
    for ($i = 5; $i >= 0; $i--) {
        $month = $current_month - $i;
        $year = $current_year;
        
        if ($month <= 0) {
            $month += 12;
            $year--;
        }
        
        $month_name = date('M', mktime(0, 0, 0, $month, 1, $year));
        $animal_trends[$month_name] = 0;
    }

    // Safely check if registration_date column exists in the animals table
    $has_registration_date = false;
    try {
        $column_check = mysqli_query($conn, "SHOW COLUMNS FROM animals LIKE 'registration_date'");
        if ($column_check) {
            $has_registration_date = mysqli_num_rows($column_check) > 0;
        }
    } catch (Exception $e) {
        // If error checking column, assume it doesn't exist
        $has_registration_date = false;
    }

    if ($has_registration_date) {
        $sql = "SELECT MONTH(a.registration_date) as month, YEAR(a.registration_date) as year, 
                COALESCE(SUM(a.quantity), 0) as total
                FROM animals a
                JOIN farms f ON a.farm_id = f.id
                WHERE f.user_id = ? 
                AND a.registration_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY YEAR(a.registration_date), MONTH(a.registration_date)
                ORDER BY year, month";
    } else {
        // Fallback to using created_at if registration_date doesn't exist
        // Also check if created_at exists
        $has_created_at = false;
        try {
            $column_check = mysqli_query($conn, "SHOW COLUMNS FROM animals LIKE 'created_at'");
            if ($column_check) {
                $has_created_at = mysqli_num_rows($column_check) > 0;
            }
        } catch (Exception $e) {
            // If error checking column, assume it doesn't exist
            $has_created_at = false;
        }
        
        if ($has_created_at) {
            $sql = "SELECT MONTH(a.created_at) as month, YEAR(a.created_at) as year, 
                    COALESCE(SUM(a.quantity), 0) as total
                    FROM animals a
                    JOIN farms f ON a.farm_id = f.id
                    WHERE f.user_id = ? 
                    AND a.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    GROUP BY YEAR(a.created_at), MONTH(a.created_at)
                    ORDER BY year, month";
        } else {
            // If neither column exists, we'll just use the initialized animal_trends with zeros
            $sql = "";
        }
    }

    if (!empty($sql)) {
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                while($row = mysqli_fetch_assoc($result)){
                    $month_name = date('M', mktime(0, 0, 0, $row['month'], 1, $row['year']));
                    $animal_trends[$month_name] = intval($row['total']);
                }
            } else {
                $error_msg = "Error executing animal trends query: " . mysqli_error($conn);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Error preparing animal trends query: " . mysqli_error($conn);
        }
    }
} catch (Exception $e) {
    $error_msg = "Exception in animal trends: " . $e->getMessage();
}

// Set page title and include header
$pageTitle = "Farm Analytics - Agro Vision";
include_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl mb-8">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold">Farm Analytics Dashboard</h1>
                    <p class="text-sm opacity-70">Comprehensive insights into your farm operations</p>
                </div>
                <img src="../../assets/images/AVlogo.png" alt="Logo" class="logo-img">
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
            
            <!-- Stats Overview -->
            <div class="stats shadow mb-8 w-full">
                <?php 
                    $total_animals = 0;
                    $total_employees = 0;
                    $total_farms = count($farm_stats);
                    
                    foreach ($farm_stats as $farm) {
                        $total_animals += $farm['total_animals'];
                        $total_employees += $farm['employee_count'];
                    }
                ?>
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <div class="stat-title">Total Farms</div>
                    <div class="stat-value"><?php echo $total_farms; ?></div>
                    <div class="stat-desc"><?php echo $total_farms > 1 ? 'Farms under management' : 'Farm under management'; ?></div>
                </div>
                
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <div class="stat-title">Total Animals</div>
                    <div class="stat-value"><?php echo number_format($total_animals); ?></div>
                    <div class="stat-desc">Across all farms</div>
                </div>
                
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">Employees</div>
                    <div class="stat-value"><?php echo number_format($total_employees); ?></div>
                    <div class="stat-desc">Workforce size</div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Animal Distribution Chart -->
                <div class="card bg-base-200">
                    <div class="card-body">
                        <h2 class="card-title">Animal Distribution</h2>
                        <div class="h-64">
                            <canvas id="animalDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Health Status Chart -->
                <div class="card bg-base-200">
                    <div class="card-body">
                        <h2 class="card-title">Animal Health Status</h2>
                        <div class="h-64">
                            <canvas id="healthStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Employee Distribution Chart -->
                <div class="card bg-base-200">
                    <div class="card-body">
                        <h2 class="card-title">Employee Distribution by Position</h2>
                        <div class="h-64">
                            <canvas id="employeeDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Animal Trends Chart -->
                <div class="card bg-base-200">
                    <div class="card-body">
                        <h2 class="card-title">Animal Growth Trends (Last 6 Months)</h2>
                        <div class="h-64">
                            <canvas id="animalTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Farm Statistics Table -->
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title mb-4">Detailed Farm Statistics</h2>
                    <?php if(!empty($farm_stats)): ?>
                        <div class="overflow-x-auto">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>Farm Name</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Location</th>
                                        <th>Animal Types</th>
                                        <th>Total Animals</th>
                                        <th>Employees</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($farm_stats as $farm): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($farm["farm_name"]); ?></td>
                                            <td><?php echo htmlspecialchars($farm["farm_type"] ?? 'N/A'); ?></td>
                                            <td><?php echo $farm["farm_size"] ? htmlspecialchars($farm["farm_size"] . ' ' . $farm["farm_size_unit"]) : 'N/A'; ?></td>
                                            <td><?php echo htmlspecialchars($farm["farm_location"] ?? 'N/A'); ?></td>
                                            <td><?php echo number_format($farm["animal_types"]); ?></td>
                                            <td><?php echo number_format($farm["total_animals"]); ?></td>
                                            <td><?php echo number_format($farm["employee_count"]); ?></td>
                                            <td>
                                                <div class="flex gap-2">
                                                    <a href="../../core/farm/farm_details.php?id=<?php echo $farm["id"]; ?>" class="btn btn-xs btn-primary">View Details</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>No farm statistics available. Please register a farm first.</span>
                        </div>
                        <div class="mt-4">
                            <a href="../../core/farm/farm_registration.php" class="btn btn-primary">Register a Farm</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex justify-between">
                <a href="/LabOne - Copy/dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <div class="flex gap-2">
                    <a href="../../core/livestock/animal_dashboard.php" class="btn btn-accent">Animal Dashboard</a>
                    <a href="../../core/employee/employee_dashboard.php" class="btn btn-secondary">Employee Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            // Setup Chart.js theme to match DaisyUI lemonade theme
            const lemonadePalette = [
                '#519903', '#a6e22e', '#ffb86c', '#ff5555', 
                '#bd93f9', '#8be9fd', '#50fa7b', '#f1fa8c'
            ];
            
            // Setup global Chart.js options
            Chart.defaults.color = '#444444';
            Chart.defaults.borderColor = 'rgba(0, 0, 0, 0.1)';
            
            // Function to safely initialize charts
            function initChart(canvasId, initFunction) {
                try {
                    const canvas = document.getElementById(canvasId);
                    if (canvas && canvas.getContext) {
                        const ctx = canvas.getContext('2d');
                        if (ctx) {
                            initFunction(ctx);
                            return true;
                        }
                    }
                    console.warn(`Canvas ${canvasId} not found or context not available`);
                } catch (error) {
                    console.error(`Error initializing chart ${canvasId}:`, error);
                }
                return false;
            }
            
            // Animal Distribution Chart
            try {
                const animalDistribution = <?php echo json_encode($animal_distribution ?? []); ?>;
                
                if (animalDistribution && animalDistribution.length > 0) {
                    initChart('animalDistributionChart', (ctx) => {
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: animalDistribution.map(item => item.animal_type),
                                datasets: [{
                                    data: animalDistribution.map(item => item.count),
                                    backgroundColor: lemonadePalette,
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'right',
                                    }
                                }
                            }
                        });
                    });
                } else {
                    document.getElementById('animalDistributionChart').parentNode.innerHTML = '<div class="flex items-center justify-center h-full"><p class="text-gray-500">No animal data available</p></div>';
                }
            } catch (error) {
                console.error('Error initializing animal distribution chart:', error);
            }
            
            // Health Status Chart
            try {
                const healthStats = <?php echo json_encode($health_stats ?? []); ?>;
                
                if (healthStats && healthStats.length > 0 && healthStats.some(item => parseInt(item.count) > 0)) {
                    initChart('healthStatusChart', (ctx) => {
                        // Capitalize the health status labels for display
                        const healthLabels = healthStats.map(item => {
                            return item.health_status.charAt(0).toUpperCase() + item.health_status.slice(1);
                        });
                        
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: healthLabels,
                                datasets: [{
                                    data: healthStats.map(item => item.count),
                                    backgroundColor: [
                                        '#519903', // healthy - green
                                        '#ff5555', // sick - red
                                        '#f1fa8c', // monitoring - yellow
                                        '#8be9fd'  // other - blue
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'right',
                                    }
                                }
                            }
                        });
                    });
                } else {
                    document.getElementById('healthStatusChart').parentNode.innerHTML = '<div class="flex items-center justify-center h-full"><p class="text-gray-500">No health data available</p></div>';
                }
            } catch (error) {
                console.error('Error initializing health status chart:', error);
            }
            
            // Employee Distribution Chart
            try {
                const employeeDistribution = <?php echo json_encode($employee_distribution ?? []); ?>;
                
                if (employeeDistribution && employeeDistribution.length > 0) {
                    initChart('employeeDistributionChart', (ctx) => {
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: employeeDistribution.map(item => item.position),
                                datasets: [{
                                    label: 'Number of Employees',
                                    data: employeeDistribution.map(item => item.count),
                                    backgroundColor: lemonadePalette[0],
                                    borderColor: lemonadePalette[0],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        }
                                    }
                                }
                            }
                        });
                    });
                } else {
                    document.getElementById('employeeDistributionChart').parentNode.innerHTML = '<div class="flex items-center justify-center h-full"><p class="text-gray-500">No employee data available</p></div>';
                }
            } catch (error) {
                console.error('Error initializing employee distribution chart:', error);
            }
            
            // Animal Trends Chart
            try {
                const animalTrends = <?php echo json_encode(array_values($animal_trends ?? [])); ?>;
                const animalTrendsLabels = <?php echo json_encode(array_keys($animal_trends ?? [])); ?>;
                
                if (animalTrends && animalTrends.length > 0 && animalTrendsLabels && animalTrendsLabels.length > 0) {
                    initChart('animalTrendsChart', (ctx) => {
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: animalTrendsLabels,
                                datasets: [{
                                    label: 'Animals Added',
                                    data: animalTrends,
                                    fill: false,
                                    borderColor: lemonadePalette[0],
                                    tension: 0.1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        }
                                    }
                                }
                            }
                        });
                    });
                } else {
                    document.getElementById('animalTrendsChart').parentNode.innerHTML = '<div class="flex items-center justify-center h-full"><p class="text-gray-500">No animal trend data available</p></div>';
                }
            } catch (error) {
                console.error('Error initializing animal trends chart:', error);
            }
        } catch (error) {
            console.error('Error initializing charts:', error);
        }
    });
</script>

<?php include_once '../../includes/footer.php'; ?> 