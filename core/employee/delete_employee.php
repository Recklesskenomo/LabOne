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

$error_msg = "";
$employee_id = 0;
$employee_info = "";
$confirmed = false;
$farm_id = 0;

// Check if employee ID is provided
if(isset($_GET["id"]) && !empty($_GET["id"])){
    $employee_id = intval($_GET["id"]);
    $user_id = $_SESSION["id"];
    
    // First, check if the employee exists and belongs to this user
    $sql = "SELECT e.*, f.farm_name FROM employees e 
            JOIN farms f ON e.farm_id = f.id
            WHERE e.id = ? AND e.user_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $employee_id, $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1){
                $row = mysqli_fetch_assoc($result);
                $employee_info = $row["first_name"] . " " . $row["last_name"] . " (" . $row["position"] . ") from " . $row["farm_name"];
                $farm_id = $row["farm_id"];
            } else {
                $error_msg = "Employee not found or you don't have permission to delete it.";
            }
        } else {
            $error_msg = "Oops! Something went wrong. Please try again later.";
        }
        
        mysqli_stmt_close($stmt);
    }
} else {
    // No ID provided, redirect to employee list
    header("location: employee_registration.php");
    exit;
}

// Process deletion on confirmation
if(isset($_POST["confirm_delete"]) && $_POST["confirm_delete"] === "yes" && !empty($employee_id)){
    $confirmed = true;
    
    // Delete the employee
    $sql = "DELETE FROM employees WHERE id = ? AND user_id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $employee_id, $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            // Redirect to employee list with success message
            $_SESSION["employee_deleted"] = true;
            header("location: employee_registration.php");
            exit;
        } else {
            $error_msg = "Oops! Something went wrong. Please try again later.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Delete Employee - Agro Vision";
include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Delete Employee</h1>
                <img src="AVlogo.png" alt="Logo" class="logo-img">
            </div>
            
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-error mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if(!$confirmed && !empty($employee_info)): ?>
                <div class="alert alert-warning mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <div>
                        <h3 class="font-bold">Warning!</h3>
                        <p>Are you sure you want to delete the employee record for <?php echo htmlspecialchars($employee_info); ?>? This action cannot be undone.</p>
                    </div>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $employee_id); ?>" method="post" class="mb-6">
                    <input type="hidden" name="confirm_delete" value="yes">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button type="submit" class="btn btn-error">Yes, Delete Employee</button>
                        <a href="employee_details.php?id=<?php echo $employee_id; ?>" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="employee_registration.php" class="btn btn-outline">Back to Employee List</a>
                <a href="farm_details.php?id=<?php echo $farm_id; ?>" class="btn btn-secondary">Back to Farm</a>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?> 