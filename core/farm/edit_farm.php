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
$farm_exists = false;

// Define variables and initialize with empty values
$farm_name = $location = $size = $farm_type = $description = "";
$farm_name_err = $location_err = $size_err = $farm_type_err = "";
$success_msg = $error_msg = "";

// Check if the farm exists and belongs to this user
$sql = "SELECT * FROM farms WHERE id = ? AND user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $farm_id, $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $farm_exists = true;
            $farm_data = mysqli_fetch_assoc($result);
            
            // Set form values with existing data
            $farm_name = $farm_data["farm_name"];
            $location = $farm_data["location"];
            $size = $farm_data["size"];
            $farm_type = $farm_data["farm_type"];
            $description = $farm_data["description"];
        } else {
            $error_msg = "Farm not found or you don't have permission to edit it.";
        }
    } else {
        $error_msg = "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
}

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && $farm_exists){
    
    // Validate farm name
    if(empty(trim($_POST["farm_name"]))){
        $farm_name_err = "Please enter farm name.";
    } else{
        $farm_name = trim($_POST["farm_name"]);
    }
    
    // Validate location
    if(empty(trim($_POST["location"]))){
        $location_err = "Please enter farm location.";
    } else{
        $location = trim($_POST["location"]);
    }
    
    // Validate size
    if(empty(trim($_POST["size"]))){
        $size_err = "Please enter farm size.";
    } elseif(!is_numeric(trim($_POST["size"])) || floatval(trim($_POST["size"])) <= 0){
        $size_err = "Please enter a valid farm size.";
    } else{
        $size = floatval(trim($_POST["size"]));
    }
    
    // Validate farm type
    if(empty(trim($_POST["farm_type"]))){
        $farm_type_err = "Please select farm type.";
    } else{
        $farm_type = trim($_POST["farm_type"]);
    }
    
    // Get description
    $description = trim($_POST["description"]);
    
    // Check input errors before updating in database
    if(empty($farm_name_err) && empty($location_err) && empty($size_err) && empty($farm_type_err)){
        
        // Prepare an update statement
        $sql = "UPDATE farms SET farm_name = ?, location = ?, size = ?, farm_type = ?, description = ? WHERE id = ? AND user_id = ?";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssdssis", $param_farm_name, $param_location, $param_size, $param_farm_type, $param_description, $param_farm_id, $param_user_id);
            
            // Set parameters
            $param_farm_name = $farm_name;
            $param_location = $location;
            $param_size = $size;
            $param_farm_type = $farm_type;
            $param_description = $description;
            $param_farm_id = $farm_id;
            $param_user_id = $user_id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Farm updated successfully!";
            } else{
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Edit Farm - Agro Vision";
include_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Edit Farm</h1>
                <a href="../../index.php">
                    <img src="../../assets/images/AVlogo.png" alt="Logo" class="logo-img h-12">
                </a>
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
            
            <?php if($farm_exists): ?>
                <!-- Edit Form -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $farm_id); ?>" method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Farm Name</span>
                            </label>
                            <input type="text" name="farm_name" class="input input-bordered <?php echo (!empty($farm_name_err)) ? 'input-error' : ''; ?>" value="<?php echo $farm_name; ?>">
                            <?php if(!empty($farm_name_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $farm_name_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Location</span>
                            </label>
                            <input type="text" name="location" class="input input-bordered <?php echo (!empty($location_err)) ? 'input-error' : ''; ?>" value="<?php echo $location; ?>">
                            <?php if(!empty($location_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $location_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Size (in acres)</span>
                            </label>
                            <input type="number" step="0.01" name="size" class="input input-bordered <?php echo (!empty($size_err)) ? 'input-error' : ''; ?>" value="<?php echo $size; ?>">
                            <?php if(!empty($size_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $size_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Farm Type</span>
                            </label>
                            <select name="farm_type" class="select select-bordered w-full <?php echo (!empty($farm_type_err)) ? 'select-error' : ''; ?>">
                                <option value="" <?php echo empty($farm_type) ? 'selected' : ''; ?>>Select Farm Type</option>
                                <option value="Dairy" <?php echo ($farm_type == "Dairy") ? 'selected' : ''; ?>>Dairy</option>
                                <option value="Crop" <?php echo ($farm_type == "Crop") ? 'selected' : ''; ?>>Crop</option>
                                <option value="Livestock" <?php echo ($farm_type == "Livestock") ? 'selected' : ''; ?>>Livestock</option>
                                <option value="Mixed" <?php echo ($farm_type == "Mixed") ? 'selected' : ''; ?>>Mixed</option>
                                <option value="Poultry" <?php echo ($farm_type == "Poultry") ? 'selected' : ''; ?>>Poultry</option>
                                <option value="Other" <?php echo ($farm_type == "Other") ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <?php if(!empty($farm_type_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $farm_type_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-control mb-6">
                        <label class="label">
                            <span class="label-text">Description</span>
                        </label>
                        <textarea name="description" class="textarea textarea-bordered h-32"><?php echo $description; ?></textarea>
                    </div>
                    
                    <div class="flex gap-4 mb-6">
                        <button type="submit" class="btn btn-primary">Update Farm</button>
                        <a href="farm_details.php?id=<?php echo $farm_id; ?>" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="farmregistration.php" class="btn btn-outline">Back to Farm List</a>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?> 