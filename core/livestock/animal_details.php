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

// Make sure the animals table has necessary columns
$sql = "SHOW COLUMNS FROM animals LIKE 'user_id'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    // The user_id column doesn't exist
    // First add the column without the foreign key constraint
    $sql = "ALTER TABLE animals ADD COLUMN user_id INT(6) UNSIGNED NOT NULL AFTER farm_id";
    if (!$conn->query($sql)) {
        die("Error adding user_id column: " . $conn->error);
    }
    
    // Update existing records to set user_id based on the farm's owner
    $sql = "UPDATE animals a JOIN farms f ON a.farm_id = f.id SET a.user_id = f.user_id";
    if (!$conn->query($sql)) {
        die("Error updating user_id values: " . $conn->error);
    }
    
    // Now add the foreign key constraint after data has been properly set
    $sql = "ALTER TABLE animals ADD FOREIGN KEY (user_id) REFERENCES users(id)";
    if (!$conn->query($sql)) {
        // If this fails, it's not critical - log the error but don't crash
        error_log("Warning: Could not add foreign key constraint: " . $conn->error);
    }
}

// Check if quantity column exists
$sql = "SHOW COLUMNS FROM animals LIKE 'quantity'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    // The quantity column doesn't exist, add it
    $sql = "ALTER TABLE animals ADD COLUMN quantity INT(6) NOT NULL DEFAULT 1";
    if (!$conn->query($sql)) {
        die("Error adding quantity column: " . $conn->error);
    }
    
    // Update existing records to set quantity to 1 by default
    $sql = "UPDATE animals SET quantity = 1 WHERE quantity IS NULL";
    if (!$conn->query($sql)) {
        die("Error updating quantity values: " . $conn->error);
    }
}

// Check if animal ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: animal_registration.php");
    exit;
}

$animal_id = intval($_GET["id"]);
$user_id = $_SESSION["id"];
$animal_details = [];
$error_msg = "";

// Get animal details
$sql = "SELECT a.*, f.farm_name FROM animals a 
        JOIN farms f ON a.farm_id = f.id
        WHERE a.id = ? AND a.user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $animal_id, $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $animal_details = mysqli_fetch_assoc($result);
        } else {
            $error_msg = "Animal not found or you don't have permission to view it.";
        }
    } else {
        $error_msg = "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Animal Details - Agro Vision";
include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Animal Details</h1>
                <img src="AVlogo.png" alt="Logo" class="logo-img">
            </div>
            
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-error mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($animal_details)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Animal Information -->
                    <div class="bg-base-200 p-6 rounded-lg h-full">
                        <h2 class="text-xl font-bold mb-4">Animal Information</h2>
                        <div class="space-y-3">
                            <div>
                                <span class="font-bold">Farm:</span> 
                                <span><?php echo htmlspecialchars($animal_details["farm_name"]); ?></span>
                            </div>
                            <div>
                                <span class="font-bold">Animal Type:</span> 
                                <span><?php echo htmlspecialchars($animal_details["animal_type"]); ?></span>
                            </div>
                            <div>
                                <span class="font-bold">Breed:</span> 
                                <span><?php echo htmlspecialchars($animal_details["breed"]); ?></span>
                            </div>
                            <div>
                                <span class="font-bold">Purpose:</span> 
                                <span><?php echo htmlspecialchars($animal_details["purpose"]); ?></span>
                            </div>
                            <div>
                                <span class="font-bold">Quantity:</span> 
                                <span><?php echo htmlspecialchars($animal_details["quantity"]); ?></span>
                            </div>
                            <div>
                                <span class="font-bold">Registration Date:</span> 
                                <span><?php echo date("F j, Y", strtotime($animal_details["registration_date"])); ?></span>
                            </div>
                            <div>
                                <span class="font-bold">Added to System:</span> 
                                <span><?php echo date("F j, Y", strtotime($animal_details["created_at"])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="bg-base-200 p-6 rounded-lg h-full">
                        <h2 class="text-xl font-bold mb-4">Notes</h2>
                        <p class="whitespace-pre-line"><?php echo !empty($animal_details["notes"]) ? htmlspecialchars($animal_details["notes"]) : "No notes available."; ?></p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-between mt-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="animal_registration.php" class="btn btn-outline">Back to Animal List</a>
                        <a href="edit_animal.php?id=<?php echo $animal_id; ?>" class="btn btn-primary">Edit Animal</a>
                        <a href="delete_animal.php?id=<?php echo $animal_id; ?>" class="btn btn-error">Delete Animal</a>
                    </div>
                    <a href="farm_details.php?id=<?php echo $animal_details["farm_id"]; ?>" class="btn btn-secondary">View Farm</a>
                </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <a href="animal_registration.php?farm_id=<?php echo $animal_details["farm_id"] ?? ''; ?>" class="btn btn-primary">Register Another Animal</a>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?> 