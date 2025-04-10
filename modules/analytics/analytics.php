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

// Get farm statistics
$farm_stats = [];
$sql = "SELECT f.id, f.farm_name, 
        COUNT(DISTINCT a.id) as animal_count,
        COUNT(DISTINCT e.id) as employee_count,
        SUM(a.quantity) as total_animals
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
    }
    
    mysqli_stmt_close($stmt);
}

// Get animal health statistics
$health_stats = [];
$sql = "SELECT f.farm_name,
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
            $health_stats[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Analytics - Agro Vision";
include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Farm Analytics</h1>
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
            
            <!-- Farm Statistics -->
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title">Farm Statistics</h2>
                    <?php if(!empty($farm_stats)): ?>
                        <div class="overflow-x-auto mt-4">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>Farm Name</th>
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
                                            <td><?php echo number_format($farm["animal_count"]); ?></td>
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
                    <?php else: ?>
                        <p class="text-center py-4">No farm statistics available</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Animal Health Overview -->
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title">Animal Health Overview by Farm</h2>
                    <?php if(!empty($health_stats)): ?>
                        <div class="overflow-x-auto mt-4">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>Farm Name</th>
                                        <th>Healthy</th>
                                        <th>Sick</th>
                                        <th>Under Treatment</th>
                                        <th>Monitoring</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($health_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat["farm_name"]); ?></td>
                                            <td>
                                                <div class="badge badge-success">
                                                    <?php echo number_format($stat["healthy_count"]); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="badge badge-error">
                                                    <?php echo number_format($stat["sick_count"]); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="badge badge-warning">
                                                    <?php echo number_format($stat["treatment_count"]); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="badge badge-info">
                                                    <?php echo number_format($stat["monitoring_count"]); ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center py-4">No health statistics available</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <div class="flex gap-2">
                    <a href="animal_dashboard.php" class="btn btn-primary">Animal Dashboard</a>
                    <a href="employee_dashboard.php" class="btn btn-secondary">Employee Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?> 