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

// Check if the farm table exists, create if not
$sql = "CREATE TABLE IF NOT EXISTS farms (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    farm_name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    size FLOAT NOT NULL,
    farm_type VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating table: " . mysqli_error($conn));
}

// Define variables and initialize with empty values
$farm_name = $location = $size = $farm_type = $description = "";
$farm_name_err = $location_err = $size_err = $farm_type_err = "";
$success_msg = $error_msg = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
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
    
    // Check input errors before inserting in database
    if(empty($farm_name_err) && empty($location_err) && empty($size_err) && empty($farm_type_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO farms (user_id, farm_name, location, size, farm_type, description) VALUES (?, ?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "issdss", $param_user_id, $param_farm_name, $param_location, $param_size, $param_farm_type, $param_description);
            
            // Set parameters
            $param_user_id = $_SESSION["id"];
            $param_farm_name = $farm_name;
            $param_location = $location;
            $param_size = $size;
            $param_farm_type = $farm_type;
            $param_description = $description;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Farm registered successfully!";
                
                // Clear form data
                $farm_name = $location = $farm_type = $description = "";
                $size = "";
            } else{
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Check for farm deletion success message
if(isset($_SESSION["farm_deleted"]) && $_SESSION["farm_deleted"] === true) {
    $success_msg = "Farm deleted successfully!";
    unset($_SESSION["farm_deleted"]);
}

// Check for farm required message
if(isset($_SESSION["farm_required"]) && $_SESSION["farm_required"] === true) {
    $error_msg = "You need to register a farm first before you can add animals or employees.";
    unset($_SESSION["farm_required"]);
}

// Get user's farms
$user_farms = array();
$sql = "SELECT id, farm_name, location, farm_type FROM farms WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            $user_farms[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Farm Registration - Agro Vision";
include_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold">Farm Registration</h1>
                    <?php if(!empty($user_farms)): ?>
                        <div class="mt-2">
                            <a href="farm_dashboard.php" class="btn btn-sm btn-outline">View Farm Dashboard</a>
                        </div>
                    <?php endif; ?>
                </div>
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
            
            <!-- Registration Form -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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
                
                <div class="form-control mb-6">
                    <button type="submit" class="btn btn-primary">Register Farm</button>
                </div>
            </form>
            
            <!-- User's Farms List -->
            <?php if(!empty($user_farms)): ?>
                <div class="divider">Your Registered Farms</div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach($user_farms as $farm): ?>
                        <div class="card bg-base-200 shadow-md">
                            <div class="card-body">
                                <h2 class="card-title"><?php echo htmlspecialchars($farm["farm_name"]); ?></h2>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($farm["location"]); ?></p>
                                <p><strong>Type:</strong> <?php echo htmlspecialchars($farm["farm_type"]); ?></p>
                                <div class="card-actions justify-end mt-4">
                                    <a href="farm_details.php?id=<?php echo $farm["id"]; ?>" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?> 