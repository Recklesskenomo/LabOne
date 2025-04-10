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

// Check if employee ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: employee_registration.php");
    exit;
}

$employee_id = intval($_GET["id"]);
$user_id = $_SESSION["id"];
$employee_exists = false;

// Define variables and initialize with empty values
$farm_id = $first_name = $last_name = $position = $contact_number = $email = $hire_date = $salary = $notes = "";
$farm_id_err = $first_name_err = $last_name_err = $position_err = $contact_number_err = $email_err = $hire_date_err = $salary_err = "";
$success_msg = $error_msg = "";

// Check if the employee exists and belongs to this user
$sql = "SELECT * FROM employees WHERE id = ? AND user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $employee_id, $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $employee_exists = true;
            $employee_data = mysqli_fetch_assoc($result);
            
            // Set form values with existing data
            $farm_id = $employee_data["farm_id"];
            $first_name = $employee_data["first_name"];
            $last_name = $employee_data["last_name"];
            $position = $employee_data["position"];
            $contact_number = $employee_data["contact_number"];
            $email = $employee_data["email"];
            $hire_date = $employee_data["hire_date"];
            $salary = $employee_data["salary"];
            $notes = $employee_data["notes"];
        } else {
            $error_msg = "Employee not found or you don't have permission to edit it.";
        }
    } else {
        $error_msg = "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
}

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && $employee_exists){
    
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
    
    // Validate first name
    if(empty(trim($_POST["first_name"]))){
        $first_name_err = "Please enter first name.";
    } else{
        $first_name = trim($_POST["first_name"]);
    }
    
    // Validate last name
    if(empty(trim($_POST["last_name"]))){
        $last_name_err = "Please enter last name.";
    } else{
        $last_name = trim($_POST["last_name"]);
    }
    
    // Validate position
    if(empty(trim($_POST["position"]))){
        $position_err = "Please enter position.";
    } else{
        $position = trim($_POST["position"]);
    }
    
    // Validate contact number (optional)
    if(!empty(trim($_POST["contact_number"]))){
        $contact_number = trim($_POST["contact_number"]);
        // Basic phone number validation
        if(!preg_match('/^[0-9+\-\s]{7,20}$/', $contact_number)){
            $contact_number_err = "Please enter a valid contact number.";
        }
    } else {
        $contact_number = "";
    }
    
    // Validate email (optional)
    if(!empty(trim($_POST["email"]))){
        $email = trim($_POST["email"]);
        // Check if email is valid
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $email_err = "Please enter a valid email address.";
        }
    } else {
        $email = "";
    }
    
    // Validate hire date
    if(empty(trim($_POST["hire_date"]))){
        $hire_date_err = "Please enter hire date.";
    } else{
        $hire_date = trim($_POST["hire_date"]);
        // Check if date is valid
        $date_parts = explode('-', $hire_date);
        if(count($date_parts) !== 3 || !checkdate($date_parts[1], $date_parts[2], $date_parts[0])){
            $hire_date_err = "Please enter a valid date in YYYY-MM-DD format.";
        }
    }
    
    // Validate salary (optional)
    if(!empty(trim($_POST["salary"]))){
        if(!is_numeric(trim($_POST["salary"])) || floatval(trim($_POST["salary"])) < 0){
            $salary_err = "Please enter a valid salary.";
        } else{
            $salary = floatval(trim($_POST["salary"]));
        }
    } else {
        $salary = null;
    }
    
    // Get notes
    $notes = trim($_POST["notes"]);
    
    // Check input errors before updating in database
    if(empty($farm_id_err) && empty($first_name_err) && empty($last_name_err) && empty($position_err) && 
       empty($contact_number_err) && empty($email_err) && empty($hire_date_err) && empty($salary_err)){
        
        // Prepare an update statement
        $sql = "UPDATE employees SET farm_id = ?, first_name = ?, last_name = ?, position = ?, 
                contact_number = ?, email = ?, hire_date = ?, salary = ?, notes = ? 
                WHERE id = ? AND user_id = ?";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "issssssdsi", $param_farm_id, $param_first_name, $param_last_name, 
                                  $param_position, $param_contact_number, $param_email, $param_hire_date, 
                                  $param_salary, $param_notes, $param_employee_id, $param_user_id);
            
            // Set parameters
            $param_farm_id = $farm_id;
            $param_first_name = $first_name;
            $param_last_name = $last_name;
            $param_position = $position;
            $param_contact_number = $contact_number;
            $param_email = $email;
            $param_hire_date = $hire_date;
            $param_salary = $salary;
            $param_notes = $notes;
            $param_employee_id = $employee_id;
            $param_user_id = $user_id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Employee updated successfully!";
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
$pageTitle = "Edit Employee - Agro Vision";
include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Edit Employee</h1>
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
            
            <?php if($employee_exists): ?>
                <!-- Edit Form -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $employee_id); ?>" method="post">
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
                                <span class="label-text">Position</span>
                            </label>
                            <input type="text" name="position" class="input input-bordered <?php echo (!empty($position_err)) ? 'input-error' : ''; ?>" value="<?php echo $position; ?>" placeholder="Farm Manager, Worker, etc.">
                            <?php if(!empty($position_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $position_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">First Name</span>
                            </label>
                            <input type="text" name="first_name" class="input input-bordered <?php echo (!empty($first_name_err)) ? 'input-error' : ''; ?>" value="<?php echo $first_name; ?>">
                            <?php if(!empty($first_name_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $first_name_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Last Name</span>
                            </label>
                            <input type="text" name="last_name" class="input input-bordered <?php echo (!empty($last_name_err)) ? 'input-error' : ''; ?>" value="<?php echo $last_name; ?>">
                            <?php if(!empty($last_name_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $last_name_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Contact Number</span>
                                <span class="label-text-alt">Optional</span>
                            </label>
                            <input type="text" name="contact_number" class="input input-bordered <?php echo (!empty($contact_number_err)) ? 'input-error' : ''; ?>" value="<?php echo $contact_number; ?>">
                            <?php if(!empty($contact_number_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $contact_number_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Email</span>
                                <span class="label-text-alt">Optional</span>
                            </label>
                            <input type="email" name="email" class="input input-bordered <?php echo (!empty($email_err)) ? 'input-error' : ''; ?>" value="<?php echo $email; ?>">
                            <?php if(!empty($email_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $email_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Hire Date</span>
                            </label>
                            <input type="date" name="hire_date" class="input input-bordered <?php echo (!empty($hire_date_err)) ? 'input-error' : ''; ?>" value="<?php echo $hire_date; ?>">
                            <?php if(!empty($hire_date_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $hire_date_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Salary</span>
                                <span class="label-text-alt">Optional</span>
                            </label>
                            <input type="number" step="0.01" name="salary" class="input input-bordered <?php echo (!empty($salary_err)) ? 'input-error' : ''; ?>" value="<?php echo $salary; ?>" placeholder="Annual salary">
                            <?php if(!empty($salary_err)): ?>
                                <label class="label">
                                    <span class="label-text-alt text-error"><?php echo $salary_err; ?></span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-control mb-6">
                        <label class="label">
                            <span class="label-text">Notes</span>
                            <span class="label-text-alt">Optional</span>
                        </label>
                        <textarea name="notes" class="textarea textarea-bordered h-32" placeholder="Skills, qualifications, or other important information"><?php echo $notes; ?></textarea>
                    </div>
                    
                    <div class="flex gap-4 mb-6">
                        <button type="submit" class="btn btn-primary">Update Employee</button>
                        <a href="employee_details.php?id=<?php echo $employee_id; ?>" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="employee_registration.php" class="btn btn-outline">Back to Employee List</a>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?> 