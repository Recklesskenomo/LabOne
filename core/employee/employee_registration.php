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

// Check if the employees table exists, create if not
$sql = "CREATE TABLE IF NOT EXISTS employees (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farm_id INT(6) UNSIGNED NOT NULL,
    user_id INT(6) UNSIGNED NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    position VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20),
    email VARCHAR(100),
    hire_date DATE NOT NULL,
    salary DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating table: " . mysqli_error($conn));
}

// Check if notes column exists in the employees table, add it if it doesn't
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM employees LIKE 'notes'");
if (mysqli_num_rows($check_column) == 0) {
    // The notes column doesn't exist, add it
    $alter_sql = "ALTER TABLE employees ADD COLUMN notes TEXT AFTER salary";
    if (!mysqli_query($conn, $alter_sql)) {
        die("Error adding notes column: " . mysqli_error($conn));
    }
}

// Define variables and initialize with empty values
$farm_id = $first_name = $last_name = $position = $contact_number = $email = $hire_date = $salary = $notes = "";
$farm_id_err = $first_name_err = $last_name_err = $position_err = $contact_number_err = $email_err = $hire_date_err = $salary_err = $notes_err = "";
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
    
    // Validate first name
    if(empty(trim($_POST["first_name"]))){
        $first_name_err = "Please enter first name.";
    } else{
        $first_name = trim($_POST["first_name"]);
        // Check if first name contains only letters and whitespace
        if (!preg_match("/^[a-zA-Z\s]+$/", $first_name)) {
            $first_name_err = "First name should only contain letters and spaces.";
        }
    }
    
    // Validate last name
    if(empty(trim($_POST["last_name"]))){
        $last_name_err = "Please enter last name.";
    } else{
        $last_name = trim($_POST["last_name"]);
        // Check if last name contains only letters and whitespace
        if (!preg_match("/^[a-zA-Z\s]+$/", $last_name)) {
            $last_name_err = "Last name should only contain letters and spaces.";
        }
    }
    
    // Validate position
    if(empty(trim($_POST["position"]))){
        $position_err = "Please enter position.";
    } else{
        $position = trim($_POST["position"]);
        // Check if position contains only letters and whitespace
        if (!preg_match("/^[a-zA-Z\s]+$/", $position)) {
            $position_err = "Position should only contain letters and spaces.";
        }
    }
    
    // Validate contact number (now required)
    if(empty(trim($_POST["contact_number"]))){
        $contact_number_err = "Please enter contact number.";
    } else {
        $contact_number = trim($_POST["contact_number"]);
        // Advanced phone number validation
        if(!preg_match('/^[0-9+\-\s]{7,20}$/', $contact_number)){
            $contact_number_err = "Please enter a valid contact number (7-20 digits, may include +, -, and spaces).";
        }
    }
    
    // Validate email (now required)
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter email address.";
    } else{
        $email = trim($_POST["email"]);
        // Check if email is valid
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $email_err = "Please enter a valid email address.";
        }
    }
    
    // Validate hire date
    if(empty(trim($_POST["hire_date"]))){
        $hire_date_err = "Please enter hire date.";
    } else{
        $hire_date = trim($_POST["hire_date"]);
        
        // First check if the date format matches YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hire_date)) {
            $hire_date_err = "Please enter a valid date in YYYY-MM-DD format.";
        } else {
            // If format is correct, check if the date is valid
            $date_parts = explode('-', $hire_date);
            
            // Convert parts to integers to ensure they're valid numbers
            $year = (int)$date_parts[0];
            $month = (int)$date_parts[1];
            $day = (int)$date_parts[2];
            
            // Check if it's a valid date
            if (!checkdate($month, $day, $year)) {
                $hire_date_err = "Please enter a valid date. The date you entered does not exist.";
            }
            
            // Check if hire date is not in the future
            $current_date = new DateTime();
            $hire_datetime = new DateTime($hire_date);
            if ($hire_datetime > $current_date) {
                $hire_date_err = "Hire date cannot be in the future.";
            }
        }
    }
    
    // Validate salary (now required)
    if(empty(trim($_POST["salary"]))){
        $salary_err = "Please enter salary.";
    } else{
        if(!is_numeric(trim($_POST["salary"]))){
            $salary_err = "Salary must be a numeric value.";
        } elseif(floatval(trim($_POST["salary"])) < 0){
            $salary_err = "Salary cannot be negative.";
        } else{
            $salary = floatval(trim($_POST["salary"]));
        }
    }
    
    // Validate notes (now required)
    if(empty(trim($_POST["notes"]))){
        $notes_err = "Please enter notes or qualifications.";
    } else{
        $notes = trim($_POST["notes"]);
        if (strlen($notes) < 10) {
            $notes_err = "Notes should be at least 10 characters long.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($farm_id_err) && empty($first_name_err) && empty($last_name_err) && empty($position_err) && 
       empty($contact_number_err) && empty($email_err) && empty($hire_date_err) && empty($salary_err) && empty($notes_err)){
        
        // Check if the notes column exists in the employees table
        $notes_column_exists = false;
        $columns_check = mysqli_query($conn, "SHOW COLUMNS FROM employees LIKE 'notes'");
        if(mysqli_num_rows($columns_check) > 0) {
            $notes_column_exists = true;
        } else {
            // Add the notes column if it doesn't exist
            $add_column = mysqli_query($conn, "ALTER TABLE employees ADD COLUMN notes TEXT AFTER salary");
            if($add_column) {
                $notes_column_exists = true;
            } else {
                $error_msg = "Failed to update database structure. Please contact the administrator.";
            }
        }
        
        if($notes_column_exists) {
            // Prepare an insert statement
            $sql = "INSERT INTO employees (farm_id, user_id, first_name, last_name, position, contact_number, email, hire_date, salary, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                // i = integer, s = string, d = double
                mysqli_stmt_bind_param($stmt, "iissssssds", $param_farm_id, $param_user_id, $param_first_name, $param_last_name, 
                                    $param_position, $param_contact_number, $param_email, $param_hire_date, $param_salary, $param_notes);
                
                // Set parameters
                $param_farm_id = $farm_id;
                $param_user_id = $_SESSION["id"];
                $param_first_name = $first_name;
                $param_last_name = $last_name;
                $param_position = $position;
                $param_contact_number = $contact_number;
                $param_email = $email;
                $param_hire_date = $hire_date;
                $param_salary = $salary;
                $param_notes = $notes;
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    // Record created successfully
                    $success_msg = "Employee registered successfully!";
                    
                    // Clear form data
                    $farm_id = $first_name = $last_name = $position = $contact_number = $email = $hire_date = $salary = $notes = "";
                } else{
                    $error_msg = "Error executing query: " . mysqli_error($conn);
                }

                // Close statement
                mysqli_stmt_close($stmt);
            } else {
                $error_msg = "Error preparing statement: " . mysqli_error($conn);
            }
        }
    }
}

// Check for employee deletion success message
if(isset($_SESSION["employee_deleted"]) && $_SESSION["employee_deleted"] === true) {
    $success_msg = "Employee deleted successfully!";
    unset($_SESSION["employee_deleted"]);
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

// If no farms are registered yet, redirect to farm registration
if(empty($user_farms)){
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

// If hire date is empty, set to today
if(empty($hire_date)){
    $hire_date = date("Y-m-d");
}

// Get user's recently registered employees
$recent_employees = array();
$sql = "SELECT e.id, e.first_name, e.last_name, e.position, f.farm_name 
        FROM employees e
        JOIN farms f ON e.farm_id = f.id
        WHERE e.user_id = ?
        ORDER BY e.created_at DESC LIMIT 10";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            $recent_employees[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Employee Registration - Agro Vision";
include_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Employee Registration</h1>
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
                            <span class="label-text">Select Farm<span class="text-error">*</span></span>
                        </label>
                        <select name="farm_id" class="select select-bordered w-full <?php echo (!empty($farm_id_err)) ? 'select-error' : ''; ?>" required>
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
                            <span class="label-text">Position<span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="position" class="input input-bordered <?php echo (!empty($position_err)) ? 'input-error' : ''; ?>" value="<?php echo $position; ?>" placeholder="Farm Manager, Worker, etc." required>
                        <?php if(!empty($position_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $position_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">First Name<span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="first_name" class="input input-bordered <?php echo (!empty($first_name_err)) ? 'input-error' : ''; ?>" value="<?php echo $first_name; ?>" placeholder="Enter first name" required>
                        <?php if(!empty($first_name_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $first_name_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Last Name<span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="last_name" class="input input-bordered <?php echo (!empty($last_name_err)) ? 'input-error' : ''; ?>" value="<?php echo $last_name; ?>" placeholder="Enter last name" required>
                        <?php if(!empty($last_name_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $last_name_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Contact Number<span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="contact_number" class="input input-bordered <?php echo (!empty($contact_number_err)) ? 'input-error' : ''; ?>" value="<?php echo $contact_number; ?>" placeholder="e.g., +1234567890" required>
                        <?php if(!empty($contact_number_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $contact_number_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Email<span class="text-error">*</span></span>
                        </label>
                        <input type="email" name="email" class="input input-bordered <?php echo (!empty($email_err)) ? 'input-error' : ''; ?>" value="<?php echo $email; ?>" placeholder="email@example.com" required>
                        <?php if(!empty($email_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $email_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Hire Date<span class="text-error">*</span></span>
                        </label>
                        <input type="date" name="hire_date" class="input input-bordered <?php echo (!empty($hire_date_err)) ? 'input-error' : ''; ?>" value="<?php echo $hire_date; ?>" required>
                        <?php if(!empty($hire_date_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $hire_date_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Salary<span class="text-error">*</span></span>
                        </label>
                        <input type="number" step="0.01" name="salary" class="input input-bordered <?php echo (!empty($salary_err)) ? 'input-error' : ''; ?>" value="<?php echo $salary; ?>" placeholder="Annual salary" required>
                        <?php if(!empty($salary_err)): ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo $salary_err; ?></span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-control mb-6">
                    <label class="label">
                        <span class="label-text">Notes<span class="text-error">*</span></span>
                    </label>
                    <textarea name="notes" class="textarea textarea-bordered h-32 <?php echo (!empty($notes_err)) ? 'textarea-error' : ''; ?>" placeholder="Skills, qualifications, or other important information (at least 10 characters)" required><?php echo $notes; ?></textarea>
                    <?php if(!empty($notes_err)): ?>
                        <label class="label">
                            <span class="label-text-alt text-error"><?php echo $notes_err; ?></span>
                        </label>
                    <?php endif; ?>
                </div>
                
                <div class="form-control mb-6">
                    <div class="text-error text-sm mb-2">* Required fields</div>
                    <button type="submit" class="btn btn-primary">Register Employee</button>
                </div>
            </form>
            
            <!-- Recently Registered Employees -->
            <?php if(!empty($recent_employees)): ?>
                <div class="divider">Recently Registered Employees</div>
                
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead>
                            <tr>
                                <th>Farm</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_employees as $employee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee["farm_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($employee["first_name"] . " " . $employee["last_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($employee["position"]); ?></td>
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
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <a href="../../farmregistration.php" class="btn btn-secondary">Manage Farms</a>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?> 
 
 