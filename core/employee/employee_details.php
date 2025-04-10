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

// Make sure the employees table has a user_id column
$sql = "SHOW COLUMNS FROM employees LIKE 'user_id'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    // The user_id column doesn't exist
    // First add the column without the foreign key constraint
    $sql = "ALTER TABLE employees ADD COLUMN user_id INT(6) UNSIGNED NOT NULL AFTER farm_id";
    if (!$conn->query($sql)) {
        die("Error adding user_id column: " . $conn->error);
    }
    
    // Update existing records to set user_id based on the farm's owner
    $sql = "UPDATE employees e JOIN farms f ON e.farm_id = f.id SET e.user_id = f.user_id";
    if (!$conn->query($sql)) {
        die("Error updating user_id values: " . $conn->error);
    }
    
    // Now add the foreign key constraint after data has been properly set
    $sql = "ALTER TABLE employees ADD FOREIGN KEY (user_id) REFERENCES users(id)";
    if (!$conn->query($sql)) {
        // If this fails, it's not critical - log the error but don't crash
        error_log("Warning: Could not add foreign key constraint: " . $conn->error);
    }
}

// Check if employee ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: employee_registration.php");
    exit;
}

$employee_id = intval($_GET["id"]);
$user_id = $_SESSION["id"];
$employee_details = [];
$error_msg = "";

// Get employee details
$sql = "SELECT e.*, f.farm_name FROM employees e 
        JOIN farms f ON e.farm_id = f.id
        WHERE e.id = ? AND e.user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $employee_id, $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $employee_details = mysqli_fetch_assoc($result);
        } else {
            $error_msg = "Employee not found or you don't have permission to view it.";
        }
    } else {
        $error_msg = "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Employee Details - Agro Vision";
include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Employee Details</h1>
                <img src="AVlogo.png" alt="Logo" class="logo-img">
            </div>
            
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-error mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($employee_details)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Employee Information -->
                    <div class="bg-base-200 p-6 rounded-lg h-full">
                        <h2 class="text-xl font-bold mb-4">Employee Information</h2>
                        <div class="space-y-3">
                            <div>
                                <span class="font-bold">Farm:</span> 
                                <span><?php echo htmlspecialchars($employee_details["farm_name"]); ?></span>
                            </div>
                            <div>
                                <span class="font-bold">Name:</span> 
                                <span><?php echo htmlspecialchars($employee_details["first_name"] . " " . $employee_details["last_name"]); ?></span>
                            </div>
                            <div>
                                <span class="font-bold">Position:</span> 
                                <span><?php echo htmlspecialchars($employee_details["position"]); ?></span>
                            </div>
                            <div>
                                <span class="font-bold">Hire Date:</span> 
                                <span><?php echo date("F j, Y", strtotime($employee_details["hire_date"])); ?></span>
                            </div>
                            <?php if(!empty($employee_details["salary"])): ?>
                                <div>
                                    <span class="font-bold">Salary:</span> 
                                    <span>$<?php echo number_format($employee_details["salary"], 2); ?></span>
                                </div>
                            <?php endif; ?>
                            <div>
                                <span class="font-bold">Added to System:</span> 
                                <span><?php echo date("F j, Y", strtotime($employee_details["created_at"])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="bg-base-200 p-6 rounded-lg h-full">
                        <h2 class="text-xl font-bold mb-4">Contact Information</h2>
                        <div class="space-y-3">
                            <?php if(!empty($employee_details["contact_number"])): ?>
                                <div>
                                    <span class="font-bold">Contact Number:</span> 
                                    <span><?php echo htmlspecialchars($employee_details["contact_number"]); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if(!empty($employee_details["email"])): ?>
                                <div>
                                    <span class="font-bold">Email:</span> 
                                    <span><?php echo htmlspecialchars($employee_details["email"]); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(empty($employee_details["contact_number"]) && empty($employee_details["email"])): ?>
                                <p>No contact information available.</p>
                            <?php endif; ?>
                            
                            <h3 class="font-bold mt-6 mb-2">Notes</h3>
                            <p class="whitespace-pre-line"><?php echo !empty($employee_details["notes"]) ? htmlspecialchars($employee_details["notes"]) : "No notes available."; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-between mt-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="employee_registration.php" class="btn btn-outline">Back to Employee List</a>
                        <a href="edit_employee.php?id=<?php echo $employee_id; ?>" class="btn btn-primary">Edit Employee</a>
                        <a href="delete_employee.php?id=<?php echo $employee_id; ?>" class="btn btn-error">Delete Employee</a>
                    </div>
                    <a href="farm_details.php?id=<?php echo $employee_details["farm_id"]; ?>" class="btn btn-secondary">View Farm</a>
                </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <a href="employee_registration.php?farm_id=<?php echo $employee_details["farm_id"] ?? ''; ?>" class="btn btn-primary">Register Another Employee</a>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?> 