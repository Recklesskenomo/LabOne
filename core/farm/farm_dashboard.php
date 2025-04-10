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

// Get farm statistics
$farm_stats = [];
$debug_info = ''; // For debugging

// Modified query with improved LEFT JOINs and GROUP BY
$sql = "SELECT f.id, f.farm_name, f.location, f.farm_type, f.size,
        COUNT(DISTINCT a.id) as animal_types_count,
        COALESCE(SUM(a.quantity), 0) as total_animals,
        COUNT(DISTINCT e.id) as employee_count
        FROM farms f 
        LEFT JOIN animals a ON f.id = a.farm_id
        LEFT JOIN employees e ON f.id = e.farm_id
        WHERE f.user_id = ?
        GROUP BY f.id, f.farm_name, f.location, f.farm_type, f.size
        ORDER BY f.farm_name";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $farm_stats[] = $row;
        }
        
        // Debug info - count farms returned from query
        $debug_info .= "Farms found in query: " . count($farm_stats);
    } else {
        $error_msg = "Error executing farm query: " . mysqli_error($conn);
        $debug_info .= "SQL error: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
} else {
    $error_msg = "Error preparing farm query: " . mysqli_error($conn);
    $debug_info .= "Prepare error: " . mysqli_error($conn);
}

// Fall back to a simpler query if no farms were found
if(empty($farm_stats)) {
    $sql = "SELECT id, farm_name, location, farm_type, size 
            FROM farms 
            WHERE user_id = ?
            ORDER BY farm_name";
            
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                // Add default values for the missing columns
                $row['animal_types_count'] = 0;
                $row['total_animals'] = 0;
                $row['employee_count'] = 0;
                $farm_stats[] = $row;
            }
            
            // Debug info - count farms from backup query
            $debug_info .= " | Farms found in backup query: " . count($farm_stats);
        } else {
            $error_msg = "Error executing backup farm query: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Get animal health statistics
$health_stats = [];
$sql = "SELECT f.farm_name, f.id,
        SUM(CASE WHEN h.health_status = 'healthy' THEN h.quantity ELSE 0 END) as healthy_count,
        SUM(CASE WHEN h.health_status = 'sick' THEN h.quantity ELSE 0 END) as sick_count,
        SUM(CASE WHEN h.health_status = 'treatment' THEN h.quantity ELSE 0 END) as treatment_count,
        SUM(CASE WHEN h.health_status = 'monitoring' THEN h.quantity ELSE 0 END) as monitoring_count
        FROM farms f
        LEFT JOIN animals a ON f.id = a.farm_id
        LEFT JOIN animal_health h ON a.id = h.animal_id
        WHERE f.user_id = ?
        GROUP BY f.id, f.farm_name";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $health_stats[$row['id']] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get animal distribution by type for each farm
$animal_distribution = [];
$sql = "SELECT f.id as farm_id, f.farm_name, a.animal_type, SUM(a.quantity) as count
        FROM farms f
        JOIN animals a ON f.id = a.farm_id
        WHERE f.user_id = ?
        GROUP BY f.id, f.farm_name, a.animal_type
        ORDER BY f.farm_name, count DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            if(!isset($animal_distribution[$row['farm_id']])) {
                $animal_distribution[$row['farm_id']] = [
                    'farm_name' => $row['farm_name'],
                    'types' => []
                ];
            }
            $animal_distribution[$row['farm_id']]['types'][$row['animal_type']] = $row['count'];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get employee distribution by position for each farm
$employee_distribution = [];
$sql = "SELECT f.id as farm_id, f.farm_name, e.position, COUNT(e.id) as count
        FROM farms f
        JOIN employees e ON f.id = e.farm_id
        WHERE f.user_id = ?
        GROUP BY f.id, f.farm_name, e.position
        ORDER BY f.farm_name, count DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            if(!isset($employee_distribution[$row['farm_id']])) {
                $employee_distribution[$row['farm_id']] = [
                    'farm_name' => $row['farm_name'],
                    'positions' => []
                ];
            }
            $employee_distribution[$row['farm_id']]['positions'][$row['position']] = $row['count'];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get recent activities - combined recent animals and employees
$recent_activities = [];

// Recent animal registrations
$sql = "SELECT 'animal' as type, a.id, a.animal_type as name, a.quantity, a.created_at, f.farm_name, f.id as farm_id
        FROM animals a
        JOIN farms f ON a.farm_id = f.id
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC LIMIT 5";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $recent_activities[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Recent employee registrations
$sql = "SELECT 'employee' as type, e.id, CONCAT(e.first_name, ' ', e.last_name) as name, 1 as quantity, e.created_at, f.farm_name, f.id as farm_id
        FROM employees e
        JOIN farms f ON e.farm_id = f.id
        WHERE e.user_id = ?
        ORDER BY e.created_at DESC LIMIT 5";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $recent_activities[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Sort recent activities by date
usort($recent_activities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_activities = array_slice($recent_activities, 0, 5);

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Farm Dashboard - Agro Vision";
include_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Farm Dashboard</h1>
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

            <!-- Debug Information -->
            <div class="alert alert-info mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>
                    <strong>Debug Info:</strong> <?php echo $debug_info; ?><br>
                    <strong>Farm IDs in farm_stats:</strong> 
                    <?php 
                    if(!empty($farm_stats)) {
                        $farm_ids = array_column($farm_stats, 'id');
                        echo implode(', ', $farm_ids);
                    } else {
                        echo "None";
                    }
                    ?>
                </span>
            </div>
            
            <!-- Direct Farm List -->
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title">Direct Farm List (Debug)</h2>
                    <?php
                    // Direct query to list all farms
                    $direct_farms = [];
                    $direct_sql = "SELECT id, farm_name, location, farm_type, size FROM farms WHERE user_id = ?";
                    if($stmt = mysqli_prepare($conn, $direct_sql)){
                        mysqli_stmt_bind_param($stmt, "i", $user_id);
                        
                        if(mysqli_stmt_execute($stmt)){
                            $result = mysqli_stmt_get_result($stmt);
                            while($row = mysqli_fetch_assoc($result)){
                                $direct_farms[] = $row;
                            }
                        }
                        
                        mysqli_stmt_close($stmt);
                    }
                    ?>
                    
                    <div class="overflow-x-auto mt-4">
                        <table class="table table-zebra w-full">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Farm Name</th>
                                    <th>Location</th>
                                    <th>Farm Type</th>
                                    <th>Size (acres)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($direct_farms)): ?>
                                    <?php foreach($direct_farms as $farm): ?>
                                        <tr>
                                            <td><?php echo $farm["id"]; ?></td>
                                            <td><?php echo htmlspecialchars($farm["farm_name"]); ?></td>
                                            <td><?php echo htmlspecialchars($farm["location"]); ?></td>
                                            <td><?php echo htmlspecialchars($farm["farm_type"]); ?></td>
                                            <td><?php echo number_format($farm["size"], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No farms found in direct query</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if(empty($farm_stats)): ?>
                <div class="alert alert-info mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>You haven't registered any farms yet. <a href="farmregistration.php" class="underline">Register your first farm</a> to get started.</span>
                </div>
            <?php else: ?>
                <!-- Farm Overview -->
                <div class="card bg-base-200 mb-8">
                    <div class="card-body">
                        <h2 class="card-title">Farm Overview</h2>
                        <div class="overflow-x-auto mt-4">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>Farm Name</th>
                                        <th>Location</th>
                                        <th>Farm Type</th>
                                        <th>Size (acres)</th>
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
                                            <td><?php echo htmlspecialchars($farm["location"]); ?></td>
                                            <td><?php echo htmlspecialchars($farm["farm_type"]); ?></td>
                                            <td><?php echo number_format($farm["size"], 2); ?></td>
                                            <td><?php echo number_format($farm["animal_types_count"]); ?></td>
                                            <td><?php echo number_format($farm["total_animals"]); ?></td>
                                            <td><?php echo number_format($farm["employee_count"]); ?></td>
                                            <td>
                                                <div class="flex gap-2">
                                                    <a href="farm_details.php?id=<?php echo $farm["id"]; ?>" class="btn btn-xs btn-primary">View Details</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Animal Health Status -->
                <div class="card bg-base-200 mb-8">
                    <div class="card-body">
                        <h2 class="card-title">Animal Health Status</h2>
                        <div class="overflow-x-auto mt-4">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>Farm Name</th>
                                        <th>Healthy</th>
                                        <th>Sick</th>
                                        <th>Under Treatment</th>
                                        <th>Monitoring</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($farm_stats as $farm): 
                                        $farm_id = $farm["id"];
                                        $health_data = isset($health_stats[$farm_id]) ? $health_stats[$farm_id] : [
                                            'healthy_count' => 0,
                                            'sick_count' => 0,
                                            'treatment_count' => 0,
                                            'monitoring_count' => 0
                                        ];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($farm["farm_name"]); ?></td>
                                            <td class="text-success"><?php echo number_format($health_data["healthy_count"]); ?></td>
                                            <td class="text-error"><?php echo number_format($health_data["sick_count"]); ?></td>
                                            <td class="text-warning"><?php echo number_format($health_data["treatment_count"]); ?></td>
                                            <td class="text-info"><?php echo number_format($health_data["monitoring_count"]); ?></td>
                                            <td>
                                                <a href="animal_dashboard.php?farm_id=<?php echo $farm["id"]; ?>" class="btn btn-xs btn-primary">Animal Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Distribution Charts -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Animal Distribution -->
                    <div class="card bg-base-200">
                        <div class="card-body">
                            <h2 class="card-title">Animal Distribution by Type</h2>
                            <?php foreach($animal_distribution as $farm_id => $farm_data): ?>
                                <div class="my-4">
                                    <h3 class="font-semibold"><?php echo htmlspecialchars($farm_data["farm_name"]); ?></h3>
                                    <?php if(!empty($farm_data["types"])): ?>
                                        <div class="space-y-3 mt-2">
                                            <?php 
                                            $total = array_sum($farm_data["types"]);
                                            foreach($farm_data["types"] as $type => $count): 
                                            ?>
                                                <div>
                                                    <div class="flex justify-between mb-1">
                                                        <span><?php echo htmlspecialchars($type); ?></span>
                                                        <span class="font-semibold"><?php echo number_format($count); ?> (<?php echo round(($count/$total)*100); ?>%)</span>
                                                    </div>
                                                    <progress class="progress progress-primary w-full" value="<?php echo $count; ?>" max="<?php echo $total; ?>"></progress>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-center py-2">No animal data available</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Employee Distribution -->
                    <div class="card bg-base-200">
                        <div class="card-body">
                            <h2 class="card-title">Employee Distribution by Position</h2>
                            <?php foreach($employee_distribution as $farm_id => $farm_data): ?>
                                <div class="my-4">
                                    <h3 class="font-semibold"><?php echo htmlspecialchars($farm_data["farm_name"]); ?></h3>
                                    <?php if(!empty($farm_data["positions"])): ?>
                                        <div class="space-y-3 mt-2">
                                            <?php 
                                            $total = array_sum($farm_data["positions"]);
                                            foreach($farm_data["positions"] as $position => $count): 
                                            ?>
                                                <div>
                                                    <div class="flex justify-between mb-1">
                                                        <span><?php echo htmlspecialchars($position); ?></span>
                                                        <span class="font-semibold"><?php echo number_format($count); ?> (<?php echo round(($count/$total)*100); ?>%)</span>
                                                    </div>
                                                    <progress class="progress progress-secondary w-full" value="<?php echo $count; ?>" max="<?php echo $total; ?>"></progress>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-center py-2">No employee data available</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="card bg-base-200 mb-8">
                    <div class="card-body">
                        <h2 class="card-title">Recent Activities</h2>
                        <?php if(!empty($recent_activities)): ?>
                            <div class="overflow-x-auto mt-4">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Farm</th>
                                            <th>Activity Type</th>
                                            <th>Details</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recent_activities as $activity): ?>
                                            <tr>
                                                <td><?php echo date("M j, Y g:i A", strtotime($activity["created_at"])); ?></td>
                                                <td><?php echo htmlspecialchars($activity["farm_name"]); ?></td>
                                                <td>
                                                    <?php if($activity["type"] == "animal"): ?>
                                                        <span class="badge badge-accent">Animal Registration</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Employee Registration</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($activity["type"] == "animal"): ?>
                                                        Added <?php echo number_format($activity["quantity"]); ?> <?php echo htmlspecialchars($activity["name"]); ?>
                                                    <?php else: ?>
                                                        Hired <?php echo htmlspecialchars($activity["name"]); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="farm_details.php?id=<?php echo $activity["farm_id"]; ?>" class="btn btn-xs btn-primary">View Farm</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center py-4">No recent activities</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card bg-base-200 mb-8">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Quick Actions</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <a href="farmregistration.php" class="btn btn-primary">Register New Farm</a>
                            <a href="animal_registration.php" class="btn btn-secondary">Register Animals</a>
                            <a href="employee_registration.php" class="btn btn-accent">Manage Employees</a>
                            <a href="analytics.php" class="btn btn-info">View Analytics</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex flex-wrap justify-between gap-4 mt-6">
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <a href="farmregistration.php" class="btn btn-primary">Manage Farms</a>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?> 