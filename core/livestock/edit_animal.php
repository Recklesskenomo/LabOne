<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database configuration
require_once "../../config.php";

// Check if animal ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: animal_registration.php");
    exit;
}

$animal_id = intval($_GET["id"]);
$user_id = $_SESSION["id"];
$animal_exists = false;

// Define variables and initialize with empty values
$farm_id = $animal_type = $breed = $purpose = $quantity = $registration_date = $notes = "";
$farm_id_err = $animal_type_err = $breed_err = $purpose_err = $quantity_err = $registration_date_err = "";
$success_msg = $error_msg = "";

// Check if the animal exists and belongs to this user
$sql = "SELECT * FROM animals WHERE id = ? AND user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $animal_id, $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $animal_exists = true;
            $animal_data = mysqli_fetch_assoc($result);
            
            // Set form values with existing data
            $farm_id = $animal_data["farm_id"];
            $animal_type = $animal_data["animal_type"];
            $breed = $animal_data["breed"];
            $purpose = $animal_data["purpose"];
            $quantity = $animal_data["quantity"];
            $registration_date = $animal_data["registration_date"];
            $notes = $animal_data["notes"];
        } else {
            $error_msg = "Animal not found or you don't have permission to edit it.";
        }
    } else {
        $error_msg = "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
}

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && $animal_exists){
    
    // Validate farm ID
    if(empty($_POST["farm_id"])){
        $farm_id_err = "Please select a farm.";
    } else {
        // Check if the farm belongs to the user
        $temp_farm_id = intval($_POST["farm_id"]);
        $sql = "SELECT id FROM farms WHERE id = ? AND user_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ii", $temp_farm_id, $_SESSION["id"]);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) == 1){
                $farm_id = $temp_farm_id;
            } else {
                $farm_id_err = "Invalid farm selection.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate animal type
    if(empty(trim($_POST["animal_type"]))){
        $animal_type_err = "Please enter animal type.";
    } else{
        $animal_type = trim($_POST["animal_type"]);
    }
    
    // Validate breed
    if(empty(trim($_POST["breed"]))){
        $breed_err = "Please enter animal breed.";
    } else{
        $breed = trim($_POST["breed"]);
    }
    
    // Validate purpose
    if(empty(trim($_POST["purpose"]))){
        $purpose_err = "Please enter animal purpose.";
    } else{
        $purpose = trim($_POST["purpose"]);
    }
    
    // Validate quantity
    if(empty(trim($_POST["quantity"]))){
        $quantity_err = "Please enter quantity.";
    } elseif(!is_numeric(trim($_POST["quantity"])) || intval(trim($_POST["quantity"])) <= 0){
        $quantity_err = "Please enter a valid quantity.";
    } else{
        $quantity = intval(trim($_POST["quantity"]));
    }
    
    // Validate registration date
    if(empty(trim($_POST["registration_date"]))){
        $registration_date_err = "Please enter registration date.";
    } else{
        $registration_date = trim($_POST["registration_date"]);
        // Check if date is valid
        $date_parts = explode('-', $registration_date);
        if(count($date_parts) !== 3 || !checkdate($date_parts[1], $date_parts[2], $date_parts[0])){
            $registration_date_err = "Please enter a valid date in YYYY-MM-DD format.";
        }
    }
    
    // Get notes
    $notes = trim($_POST["notes"]);
    
    // Check input errors before updating in database
    if(empty($farm_id_err) && empty($animal_type_err) && empty($breed_err) && empty($purpose_err) && empty($quantity_err) && empty($registration_date_err)){
        
        // Prepare an update statement
        $sql = "UPDATE animals SET farm_id = ?, animal_type = ?, breed = ?, purpose = ?, quantity = ?, registration_date = ?, notes = ? WHERE id = ? AND user_id = ?";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "isssisii", $param_farm_id, $param_animal_type, $param_breed, $param_purpose, $param_quantity, $param_registration_date, $param_notes, $param_animal_id, $param_user_id);
            
            // Set parameters
            $param_farm_id = $farm_id;
            $param_animal_type = $animal_type;
            $param_breed = $breed;
            $param_purpose = $purpose;
            $param_quantity = $quantity;
            $param_registration_date = $registration_date;
            $param_notes = $notes;
            $param_animal_id = $animal_id;
            $param_user_id = $user_id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Animal updated successfully!";
            } else{
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Get all user farms for dropdown
$user_farms = array();
$sql = "SELECT id, farm_name FROM farms WHERE user_id = ?";
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
$pageTitle = "Edit Animal - Agro Vision";
include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Edit Animal</h1>
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
            
            <?php if($animal_exists): ?>
                <!-- Edit Form -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $animal_id); ?>" method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Select Farm</span>
                            </label>
                            <select name="farm_id" class="select select-bordered w-full <?php echo (!empty($farm_id_err)) ? 'select-error' : ''; ?>">
                                <option value="" <?php echo empty($farm_id) ? 'selected' : ''; ?>>Select a Farm</option>
                                <?php foreach($user_farms as $uf): ?>
                                    <option value="<?php echo $uf["id"]; ?>" <?php echo ($farm_id == $uf["id"]) ? 'selected' : ''; ?>><?php echo htmlspecialchars($uf["farm_name"]); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(!empty($farm_id_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $farm_id_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Animal Type</span>
                            </label>
                            <select name="animal_type" class="select select-bordered w-full <?php echo (!empty($animal_type_err)) ? 'select-error' : ''; ?>">
                                <option value="" <?php echo empty($animal_type) ? 'selected' : ''; ?>>Select Animal Type</option>
                                <option value="Cattle" <?php echo ($animal_type == "Cattle") ? 'selected' : ''; ?>>Cattle</option>
                                <option value="Sheep" <?php echo ($animal_type == "Sheep") ? 'selected' : ''; ?>>Sheep</option>
                                <option value="Goat" <?php echo ($animal_type == "Goat") ? 'selected' : ''; ?>>Goat</option>
                                <option value="Pig" <?php echo ($animal_type == "Pig") ? 'selected' : ''; ?>>Pig</option>
                                <option value="Poultry" <?php echo ($animal_type == "Poultry") ? 'selected' : ''; ?>>Poultry</option>
                                <option value="Horse" <?php echo ($animal_type == "Horse") ? 'selected' : ''; ?>>Horse</option>
                                <option value="Other" <?php echo ($animal_type == "Other") ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <?php if(!empty($animal_type_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $animal_type_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Breed</span>
                            </label>
                            <input type="text" name="breed" class="input input-bordered <?php echo (!empty($breed_err)) ? 'input-error' : ''; ?>" value="<?php echo $breed; ?>">
                            <?php if(!empty($breed_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $breed_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Purpose</span>
                            </label>
                            <select name="purpose" class="select select-bordered w-full <?php echo (!empty($purpose_err)) ? 'select-error' : ''; ?>">
                                <option value="" <?php echo empty($purpose) ? 'selected' : ''; ?>>Select Purpose</option>
                                <option value="Dairy" <?php echo ($purpose == "Dairy") ? 'selected' : ''; ?>>Dairy</option>
                                <option value="Meat" <?php echo ($purpose == "Meat") ? 'selected' : ''; ?>>Meat</option>
                                <option value="Dual Purpose" <?php echo ($purpose == "Dual Purpose") ? 'selected' : ''; ?>>Dual Purpose</option>
                                <option value="Breeding" <?php echo ($purpose == "Breeding") ? 'selected' : ''; ?>>Breeding</option>
                                <option value="Draft/Work" <?php echo ($purpose == "Draft/Work") ? 'selected' : ''; ?>>Draft/Work</option>
                                <option value="Eggs" <?php echo ($purpose == "Eggs") ? 'selected' : ''; ?>>Eggs</option>
                                <option value="Wool/Fiber" <?php echo ($purpose == "Wool/Fiber") ? 'selected' : ''; ?>>Wool/Fiber</option>
                                <option value="Pet/Companion" <?php echo ($purpose == "Pet/Companion") ? 'selected' : ''; ?>>Pet/Companion</option>
                                <option value="Other" <?php echo ($purpose == "Other") ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <?php if(!empty($purpose_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $purpose_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Quantity</span>
                            </label>
                            <input type="number" min="1" name="quantity" class="input input-bordered <?php echo (!empty($quantity_err)) ? 'input-error' : ''; ?>" value="<?php echo $quantity; ?>">
                            <?php if(!empty($quantity_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $quantity_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Registration Date</span>
                            </label>
                            <input type="date" name="registration_date" class="input input-bordered <?php echo (!empty($registration_date_err)) ? 'input-error' : ''; ?>" value="<?php echo $registration_date; ?>">
                            <?php if(!empty($registration_date_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $registration_date_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-control mb-6">
                        <label class="label">
                            <span class="label-text">Notes</span>
                        </label>
                        <textarea name="notes" class="textarea textarea-bordered h-32"><?php echo $notes; ?></textarea>
                    </div>
                    
                    <div class="flex gap-4 mb-6">
                        <button type="submit" class="btn btn-primary">Update Animal</button>
                        <a href="animal_details.php?id=<?php echo $animal_id; ?>" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="animal_registration.php" class="btn btn-outline">Back to Animal List</a>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?> 