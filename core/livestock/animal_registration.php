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

// Check if the animals table exists, create if not
$sql = "CREATE TABLE IF NOT EXISTS animals (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farm_id INT(6) UNSIGNED NOT NULL,
    user_id INT(6) UNSIGNED NOT NULL,
    animal_type VARCHAR(50) NOT NULL,
    breed VARCHAR(100) NOT NULL,
    purpose VARCHAR(100) NOT NULL,
    quantity INT(6) UNSIGNED NOT NULL DEFAULT 1,
    registration_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating table: " . mysqli_error($conn));
}

// Define variables and initialize with empty values
$farm_id = $animal_type = $breed = $purpose = $quantity = $registration_date = $notes = "";
$farm_id_err = $animal_type_err = $breed_err = $purpose_err = $quantity_err = $registration_date_err = "";
$success_msg = $error_msg = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
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
    
    // Check input errors before inserting in database
    if(empty($farm_id_err) && empty($animal_type_err) && empty($breed_err) && empty($purpose_err) && empty($quantity_err) && empty($registration_date_err)){
        
        // Check if the registration_date column exists
        $column_check = mysqli_query($conn, "SHOW COLUMNS FROM animals LIKE 'registration_date'");
        $has_registration_date = mysqli_num_rows($column_check) > 0;
        
        // Prepare an insert statement
        if ($has_registration_date) {
            $sql = "INSERT INTO animals (farm_id, user_id, animal_type, breed, purpose, quantity, registration_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        } else {
            $sql = "INSERT INTO animals (farm_id, user_id, animal_type, breed, purpose, quantity, notes) VALUES (?, ?, ?, ?, ?, ?, ?)";
        }
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            if ($has_registration_date) {
                mysqli_stmt_bind_param($stmt, "iisssiss", $param_farm_id, $param_user_id, $param_animal_type, $param_breed, $param_purpose, $param_quantity, $param_registration_date, $param_notes);
            } else {
                mysqli_stmt_bind_param($stmt, "iisssss", $param_farm_id, $param_user_id, $param_animal_type, $param_breed, $param_purpose, $param_quantity, $param_notes);
            }
            
            // Set parameters
            $param_farm_id = $farm_id;
            $param_user_id = $_SESSION["id"];
            $param_animal_type = $animal_type;
            $param_breed = $breed;
            $param_purpose = $purpose;
            $param_quantity = $quantity;
            $param_registration_date = $registration_date;
            $param_notes = $notes;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Animal registered successfully!";
                
                // Clear form data
                $animal_type = $breed = $purpose = $notes = "";
                $quantity = "";
                $registration_date = date("Y-m-d"); // Set to current date
            } else{
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Check for animal deletion success message
if(isset($_SESSION["animal_deleted"]) && $_SESSION["animal_deleted"] === true) {
    $success_msg = "Animal deleted successfully!";
    unset($_SESSION["animal_deleted"]);
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

// Check if user has any farms
if(count($user_farms) == 0) {
    $_SESSION["farm_required"] = true;
    header("location: ../../farmregistration.php");
    exit;
}

// Get farm ID from URL parameter if provided
if(isset($_GET["farm_id"]) && !empty($_GET["farm_id"])){
    $farm_id = intval($_GET["farm_id"]);
    
    // Verify if farm belongs to user
    $farm_exists = false;
    foreach($user_farms as $uf){
        if($uf["id"] == $farm_id){
            $farm_exists = true;
            break;
        }
    }
    
    if(!$farm_exists){
        $farm_id = "";
    }
}

// If registration date is empty, set to today
if(empty($registration_date)){
    $registration_date = date("Y-m-d");
}

// Get user's recently registered animals
$recent_animals = array();
$sql = "SELECT a.id, a.animal_type, a.breed, a.purpose, a.quantity, f.farm_name 
        FROM animals a
        JOIN farms f ON a.farm_id = f.id
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC LIMIT 10";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            $recent_animals[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Animal Registration - Agro Vision";
include_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Animal Registration</h1>
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
                        <input type="number" min="1" name="quantity" class="input input-bordered <?php echo (!empty($quantity_err)) ? 'input-error' : ''; ?>" value="<?php echo $quantity ?: 1; ?>">
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
                
                <div class="form-control mb-6">
                    <button type="submit" class="btn btn-primary">Register Animal</button>
                </div>
            </form>
            
            <!-- Recently Registered Animals -->
            <?php if(!empty($recent_animals)): ?>
                <div class="divider">Recently Registered Animals</div>
                
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>Farm</th>
                                <th>Type</th>
                                <th>Breed</th>
                                <th>Purpose</th>
                                <th>Quantity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_animals as $animal): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($animal["farm_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($animal["animal_type"]); ?></td>
                                    <td><?php echo htmlspecialchars($animal["breed"]); ?></td>
                                    <td><?php echo htmlspecialchars($animal["purpose"]); ?></td>
                                    <td><?php echo htmlspecialchars($animal["quantity"]); ?></td>
                                    <td>
                                        <div class="flex gap-2">
                                            <a href="animal_details.php?id=<?php echo $animal["id"]; ?>" class="btn btn-xs btn-primary">View</a>
                                            <a href="edit_animal.php?id=<?php echo $animal["id"]; ?>" class="btn btn-xs btn-secondary">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <a href="farmregistration.php" class="btn btn-secondary">Manage Farms</a>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?> 