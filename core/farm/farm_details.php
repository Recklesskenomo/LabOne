<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../auth/login.php");
    exit;
}

// Check if farm ID is specified
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))){
    // If no id provided, redirect to farm listing page
    header("location: ../../farmregistration.php");
    exit;
}

// Include database configuration
require_once "../../config.php";

$farm_id = intval($_GET["id"]);
$user_id = $_SESSION["id"];
$farm_details = [];
$error_msg = "";

// Get farm details
$sql = "SELECT * FROM farms WHERE id = ? AND user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $farm_id, $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $farm_details = mysqli_fetch_assoc($result);
        } else {
            $error_msg = "Farm not found or you don't have permission to view it.";
        }
    } else {
        $error_msg = "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
}

// Get animal count for this farm
$animal_count = 0;
$sql = "SELECT COUNT(*) as count FROM animals WHERE farm_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $farm_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)){
            $animal_count = $row["count"];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get employee count for this farm
$employee_count = 0;
$sql = "SELECT COUNT(*) as count FROM employees WHERE farm_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $farm_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)){
            $employee_count = $row["count"];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Farm Details - Agro Vision";
include_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Farm Details</h1>
                <a href="../../index.php">
                    <img src="../../assets/images/AVlogo.png" alt="Logo" class="logo-img h-12">
                </a>
            </div>
            
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-error mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php elseif(!empty($farm_details)): ?>
                <!-- Farm Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <div class="bg-base-200 p-6 rounded-lg h-full">
                            <h2 class="text-xl font-bold mb-4">Basic Information</h2>
                            <div class="space-y-3">
                                <div>
                                    <span class="font-bold">Farm Name:</span> 
                                    <span><?php echo htmlspecialchars($farm_details["farm_name"]); ?></span>
                                </div>
                                <div>
                                    <span class="font-bold">Location:</span> 
                                    <span><?php echo htmlspecialchars($farm_details["location"]); ?></span>
                                </div>
                                <div>
                                    <span class="font-bold">Farm Type:</span> 
                                    <span><?php echo htmlspecialchars($farm_details["farm_type"]); ?></span>
                                </div>
                                <div>
                                    <span class="font-bold">Size:</span> 
                                    <span><?php echo htmlspecialchars($farm_details["size"]); ?> acres</span>
                                </div>
                                <div>
                                    <span class="font-bold">Registration Date:</span> 
                                    <span><?php echo date("F j, Y", strtotime($farm_details["created_at"])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="bg-base-200 p-6 rounded-lg h-full">
                            <h2 class="text-xl font-bold mb-4">Description</h2>
                            <p class="whitespace-pre-line"><?php echo !empty($farm_details["description"]) ? htmlspecialchars($farm_details["description"]) : "No description available."; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="stats shadow w-full mb-8">
                    <div class="stat">
                        <div class="stat-figure text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                        </div>
                        <div class="stat-title">Total Animals</div>
                        <div class="stat-value text-primary"><?php echo $animal_count; ?></div>
                        <div class="stat-desc"><?php echo $animal_count > 0 ? 'Click to view details' : 'No animals registered yet'; ?></div>
                    </div>
                    
                    <div class="stat">
                        <div class="stat-figure text-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div class="stat-title">Total Employees</div>
                        <div class="stat-value text-secondary"><?php echo $employee_count; ?></div>
                        <div class="stat-desc"><?php echo $employee_count > 0 ? 'Click to view details' : 'No employees registered yet'; ?></div>
                    </div>
                    
                    <div class="stat">
                        <div class="stat-figure text-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                        </div>
                        <div class="stat-title">Farm Status</div>
                        <div class="stat-value">Active</div>
                        <div class="stat-desc text-success">operational</div>
                    </div>
                </div>
                
                <!-- Action Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div class="card bg-base-200">
                        <div class="card-body items-center text-center">
                            <h3 class="card-title">Register Animals</h3>
                            <p>Add new animals to your farm inventory</p>
                            <div class="card-actions mt-4">
                                <a href="animalregistration.php?farm_id=<?php echo $farm_id; ?>" class="btn btn-primary">Register Animals</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card bg-base-200">
                        <div class="card-body items-center text-center">
                            <h3 class="card-title">Manage Employees</h3>
                            <p>Add or edit employees for this farm</p>
                            <div class="card-actions mt-4">
                                <a href="employeeregistration.php?farm_id=<?php echo $farm_id; ?>" class="btn btn-primary">Manage Employees</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card bg-base-200">
                        <div class="card-body items-center text-center">
                            <h3 class="card-title">Farm Statistics</h3>
                            <p>View detailed stats and reports</p>
                            <div class="card-actions mt-4">
                                <a href="analytics.php?farm_id=<?php echo $farm_id; ?>" class="btn btn-primary">View Analytics</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-between mt-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="farmregistration.php" class="btn btn-outline">Back to Farm List</a>
                    <a href="farm_dashboard.php" class="btn btn-outline">Farm Dashboard</a>
                    <?php if(!empty($farm_details)): ?>
                        <a href="edit_farm.php?id=<?php echo $farm_id; ?>" class="btn btn-primary">Edit Farm</a>
                        <a href="delete_farm.php?id=<?php echo $farm_id; ?>" class="btn btn-error">Delete Farm</a>
                    <?php endif; ?>
                </div>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?> 