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

// Get user ID and set default redirect
$user_id = $_SESSION["id"];
$redirect = isset($_GET["redirect"]) ? $_GET["redirect"] : "dashboard.php";
$dataType = isset($_GET["type"]) ? $_GET["type"] : "";
$success_msg = "";
$error_msg = "";

// Only run this in development/debug mode
if (!isset($_GET["debug"])) {
    $_SESSION["error_msg"] = "This tool is only available in debug mode.";
    header("location: " . $redirect);
    exit;
}

// Check which type of data to generate
if ($dataType === "employees") {
    generateEmployeeData();
} else {
    $_SESSION["error_msg"] = "Invalid data type specified.";
    header("location: " . $redirect);
    exit;
}

// Redirect back with message
if (!empty($success_msg)) {
    $_SESSION["success_msg"] = $success_msg;
}
if (!empty($error_msg)) {
    $_SESSION["error_msg"] = $error_msg;
}
header("location: " . $redirect);
exit;

// Function to generate sample employee data
function generateEmployeeData() {
    global $conn, $user_id, $success_msg, $error_msg;
    
    // Check if there are farms first
    $sql = "SELECT id, farm_name FROM farms WHERE user_id = ?";
    $farms = [];
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            while($row = mysqli_fetch_assoc($result)){
                $farms[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    if (empty($farms)) {
        $error_msg = "You need to register at least one farm before generating employee data.";
        return;
    }
    
    // Sample data
    $sampleEmployees = [
        [
            "first_name" => "John",
            "last_name" => "Smith",
            "position" => "Farm Manager",
            "contact_number" => "555-123-4567",
            "email" => "john.smith@example.com",
            "hire_date" => date('Y-m-d', strtotime('-2 years')),
            "salary" => 3500.00,
            "notes" => "Manages day-to-day operations of the farm."
        ],
        [
            "first_name" => "Maria",
            "last_name" => "Garcia",
            "position" => "Assistant Manager",
            "contact_number" => "555-987-6543",
            "email" => "maria.garcia@example.com",
            "hire_date" => date('Y-m-d', strtotime('-1 year')),
            "salary" => 2800.00,
            "notes" => "Assists the farm manager and oversees farm workers."
        ],
        [
            "first_name" => "Robert",
            "last_name" => "Johnson",
            "position" => "Livestock Specialist",
            "contact_number" => "555-456-7890",
            "email" => "robert.johnson@example.com",
            "hire_date" => date('Y-m-d', strtotime('-6 months')),
            "salary" => 2400.00,
            "notes" => "Specializes in animal health and breeding programs."
        ],
        [
            "first_name" => "Sarah",
            "last_name" => "Williams",
            "position" => "Crop Specialist",
            "contact_number" => "555-789-0123",
            "email" => "sarah.williams@example.com",
            "hire_date" => date('Y-m-d', strtotime('-3 years')),
            "salary" => 2300.00,
            "notes" => "Expert in crop rotation, fertilization, and pest management."
        ],
        [
            "first_name" => "David",
            "last_name" => "Brown",
            "position" => "Equipment Operator",
            "contact_number" => "555-321-6547",
            "email" => "david.brown@example.com",
            "hire_date" => date('Y-m-d', strtotime('-8 months')),
            "salary" => 1950.00,
            "notes" => "Operates and maintains farm machinery and equipment."
        ],
        [
            "first_name" => "Jennifer",
            "last_name" => "Lee",
            "position" => "Administrative Assistant",
            "contact_number" => "555-654-3210",
            "email" => "jennifer.lee@example.com",
            "hire_date" => date('Y-m-d', strtotime('-4 months')),
            "salary" => 1800.00,
            "notes" => "Handles office tasks, recordkeeping, and scheduling."
        ],
        [
            "first_name" => "Michael",
            "last_name" => "Taylor",
            "position" => "Farm Worker",
            "contact_number" => "555-147-2583",
            "email" => "michael.taylor@example.com",
            "hire_date" => date('Y-m-d', strtotime('-1 month')),
            "salary" => 1600.00,
            "notes" => "General farm worker with multiple responsibilities."
        ],
        [
            "first_name" => "Emily",
            "last_name" => "Anderson",
            "position" => "Farm Worker",
            "contact_number" => "555-369-8520",
            "email" => "emily.anderson@example.com",
            "hire_date" => date('Y-m-d', strtotime('-5 years')),
            "salary" => 1750.00,
            "notes" => "Experienced farm worker focused on livestock care."
        ],
        [
            "first_name" => "Carlos",
            "last_name" => "Rodriguez",
            "position" => "Seasonal Worker",
            "contact_number" => "555-852-7413",
            "email" => "carlos.rodriguez@example.com",
            "hire_date" => date('Y-m-d', strtotime('-2 weeks')),
            "salary" => 1200.00,
            "notes" => "Hired for harvest season assistance."
        ],
        [
            "first_name" => "Lisa",
            "last_name" => "Martinez",
            "position" => "Accountant",
            "contact_number" => "555-741-8523",
            "email" => "lisa.martinez@example.com",
            "hire_date" => date('Y-m-d', strtotime('-1.5 years')),
            "salary" => 2500.00,
            "notes" => "Handles farm finances, taxes, and financial planning."
        ]
    ];
    
    // Prepare SQL statement for insertion
    $sql = "INSERT INTO employees (farm_id, user_id, first_name, last_name, position, contact_number, email, hire_date, salary, notes, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "iissssssdsS", $farm_id, $param_user_id, $param_first_name, $param_last_name, 
                              $param_position, $param_contact_number, $param_email, $param_hire_date, $param_salary, $param_notes, $param_created_at);
        
        // Set common parameters
        $param_user_id = $user_id;
        
        $inserted = 0;
        $total_employees = count($sampleEmployees);
        
        // Distribute employees across farms evenly
        foreach($sampleEmployees as $index => $employee){
            // Determine which farm to assign this employee to
            $farm_index = $index % count($farms);
            $farm_id = $farms[$farm_index]['id'];
            
            // Set employee parameters
            $param_first_name = $employee['first_name'];
            $param_last_name = $employee['last_name'];
            $param_position = $employee['position'];
            $param_contact_number = $employee['contact_number'];
            $param_email = $employee['email'];
            $param_hire_date = $employee['hire_date'];
            $param_salary = $employee['salary'];
            $param_notes = $employee['notes'];
            
            // Create a random created_at timestamp in the past week
            $days_ago = rand(0, 7);
            $hours_ago = rand(0, 23);
            $minutes_ago = rand(0, 59);
            $param_created_at = date('Y-m-d H:i:s', strtotime("-{$days_ago} days -{$hours_ago} hours -{$minutes_ago} minutes"));
            
            // Execute insertion
            if(mysqli_stmt_execute($stmt)){
                $inserted++;
            }
        }
        
        // Close statement
        mysqli_stmt_close($stmt);
        
        if($inserted > 0){
            $success_msg = "Successfully generated {$inserted} sample employees across your farms.";
        } else {
            $error_msg = "Failed to generate sample employee data.";
        }
    } else {
        $error_msg = "Database error: " . mysqli_error($conn);
    }
} 