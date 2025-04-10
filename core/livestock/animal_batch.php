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

$user_id = $_SESSION["id"];
$error_msg = "";
$success_msg = "";
$csv_error = "";
$csv_data = [];
$form_data = [];

// Process batch form data
if(isset($_POST["batch_submit"]) && $_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if farm_id is selected
    if(empty($_POST["farm_id"])) {
        $error_msg = "Please select a farm.";
    } else {
        $farm_id = trim($_POST["farm_id"]);
        
        // Verify farm belongs to user
        $sql = "SELECT id FROM farms WHERE id = ? AND user_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $farm_id, $user_id);
            
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 0) {
                    $error_msg = "Invalid farm selection.";
                } else {
                    // Process the batch data
                    $valid_rows = 0;
                    $error_rows = 0;
                    
                    // Get the count of rows
                    $row_count = count($_POST["animal_type"]);
                    
                    for($i = 0; $i < $row_count; $i++) {
                        // Skip empty rows
                        if(empty($_POST["animal_type"][$i]) && empty($_POST["breed"][$i]) && empty($_POST["quantity"][$i])) {
                            continue;
                        }
                        
                        // Validate required fields
                        if(empty($_POST["animal_type"][$i]) || empty($_POST["breed"][$i]) || empty($_POST["quantity"][$i]) || empty($_POST["purpose"][$i])) {
                            $error_rows++;
                            continue;
                        }
                        
                        // Get form data
                        $animal_type = trim($_POST["animal_type"][$i]);
                        $breed = trim($_POST["breed"][$i]);
                        $purpose = trim($_POST["purpose"][$i]);
                        $quantity = intval($_POST["quantity"][$i]);
                        $registration_date = !empty($_POST["registration_date"][$i]) ? trim($_POST["registration_date"][$i]) : date("Y-m-d");
                        $animal_name = !empty($_POST["animal_name"][$i]) ? trim($_POST["animal_name"][$i]) : null;
                        $notes = !empty($_POST["notes"][$i]) ? trim($_POST["notes"][$i]) : null;
                        
                        // Validate quantity
                        if($quantity <= 0 || $quantity > 1000) {
                            $error_rows++;
                            continue;
                        }
                        
                        // Insert into database
                        $sql = "INSERT INTO animals (farm_id, animal_type, breed, purpose, quantity, registration_date, animal_name, notes, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        if($insert_stmt = mysqli_prepare($conn, $sql)) {
                            mysqli_stmt_bind_param($insert_stmt, "isssisisi", $farm_id, $animal_type, $breed, $purpose, $quantity, $registration_date, $animal_name, $notes, $user_id);
                            
                            if(mysqli_stmt_execute($insert_stmt)) {
                                $valid_rows++;
                            } else {
                                $error_rows++;
                            }
                            
                            mysqli_stmt_close($insert_stmt);
                        }
                    }
                    
                    if($valid_rows > 0) {
                        $success_msg = "$valid_rows animals were successfully registered.";
                    }
                    
                    if($error_rows > 0) {
                        $error_msg = "$error_rows records could not be processed due to errors.";
                    }
                }
            } else {
                $error_msg = "Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Process CSV upload
if(isset($_POST["csv_submit"]) && $_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if farm_id is selected
    if(empty($_POST["farm_id"])) {
        $error_msg = "Please select a farm.";
    } else {
        $farm_id = trim($_POST["farm_id"]);
        
        // Verify farm belongs to user
        $sql = "SELECT id FROM farms WHERE id = ? AND user_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $farm_id, $user_id);
            
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 0) {
                    $error_msg = "Invalid farm selection.";
                } else {
                    // Check if a file was uploaded
                    if(isset($_FILES["csv_file"]) && $_FILES["csv_file"]["error"] == 0) {
                        $file_name = $_FILES["csv_file"]["name"];
                        $file_tmp = $_FILES["csv_file"]["tmp_name"];
                        $file_size = $_FILES["csv_file"]["size"];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        // Check file extension
                        if($file_ext != "csv") {
                            $csv_error = "Only CSV files are allowed.";
                        } else {
                            // Read the CSV file
                            if(($handle = fopen($file_tmp, "r")) !== FALSE) {
                                $row = 0;
                                $valid_rows = 0;
                                $error_rows = 0;
                                $headers = [];
                                
                                while(($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                                    if($row == 0) {
                                        // Get headers
                                        $headers = array_map('strtolower', $data);
                                        
                                        // Check required headers
                                        $required_headers = ["animal_type", "breed", "purpose", "quantity"];
                                        $missing_headers = array_diff($required_headers, $headers);
                                        
                                        if(!empty($missing_headers)) {
                                            $csv_error = "Missing required headers: " . implode(", ", $missing_headers);
                                            break;
                                        }
                                    } else {
                                        // Process data row
                                        $animal_data = [];
                                        foreach($headers as $index => $header) {
                                            if(isset($data[$index])) {
                                                $animal_data[$header] = $data[$index];
                                            } else {
                                                $animal_data[$header] = "";
                                            }
                                        }
                                        
                                        // Store for preview
                                        $csv_data[] = $animal_data;
                                        
                                        // Validate required fields
                                        if(empty($animal_data["animal_type"]) || empty($animal_data["breed"]) || empty($animal_data["purpose"]) || empty($animal_data["quantity"])) {
                                            $error_rows++;
                                            continue;
                                        }
                                        
                                        // Get data
                                        $animal_type = trim($animal_data["animal_type"]);
                                        $breed = trim($animal_data["breed"]);
                                        $purpose = trim($animal_data["purpose"]);
                                        $quantity = intval($animal_data["quantity"]);
                                        $registration_date = !empty($animal_data["registration_date"]) ? trim($animal_data["registration_date"]) : date("Y-m-d");
                                        $animal_name = !empty($animal_data["animal_name"]) ? trim($animal_data["animal_name"]) : null;
                                        $notes = !empty($animal_data["notes"]) ? trim($animal_data["notes"]) : null;
                                        
                                        // Validate quantity
                                        if($quantity <= 0 || $quantity > 1000) {
                                            $error_rows++;
                                            continue;
                                        }
                                        
                                        // Insert into database
                                        $sql = "INSERT INTO animals (farm_id, animal_type, breed, purpose, quantity, registration_date, animal_name, notes, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                        
                                        if($insert_stmt = mysqli_prepare($conn, $sql)) {
                                            mysqli_stmt_bind_param($insert_stmt, "isssisisi", $farm_id, $animal_type, $breed, $purpose, $quantity, $registration_date, $animal_name, $notes, $user_id);
                                            
                                            if(mysqli_stmt_execute($insert_stmt)) {
                                                $valid_rows++;
                                            } else {
                                                $error_rows++;
                                            }
                                            
                                            mysqli_stmt_close($insert_stmt);
                                        }
                                    }
                                    
                                    $row++;
                                }
                                
                                fclose($handle);
                                
                                if(empty($csv_error)) {
                                    if($valid_rows > 0) {
                                        $success_msg = "$valid_rows animals were successfully registered from CSV.";
                                    }
                                    
                                    if($error_rows > 0) {
                                        $error_msg = "$error_rows records from CSV could not be processed due to errors.";
                                    }
                                }
                            } else {
                                $csv_error = "Could not read the CSV file.";
                            }
                        }
                    } else {
                        $csv_error = "Please select a CSV file to upload.";
                    }
                }
            } else {
                $error_msg = "Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Get user farms for dropdown
$farms = [];
$sql = "SELECT id, farm_name FROM farms WHERE user_id = ? ORDER BY farm_name";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $farms[$row["id"]] = $row["farm_name"];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// If no farms registered, redirect to farm registration
if(empty($farms)) {
    $_SESSION["farm_required"] = true;
    header("location: farmregistration.php");
    exit;
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Batch Animal Registration - Agro Vision";
include_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Batch Animal Registration</h1>
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
            
            <!-- Batch Options -->
            <div class="mb-8">
                <div class="tabs tabs-boxed">
                    <a class="tab tab-lg batch-tab tab-active" onclick="showTab('form-tab')" id="form-tab-btn">Batch Form Entry</a>
                    <a class="tab tab-lg batch-tab" onclick="showTab('csv-tab')" id="csv-tab-btn">CSV Upload</a>
                    <a class="tab tab-lg batch-tab" onclick="showTab('template-tab')" id="template-tab-btn">CSV Template</a>
                </div>
            </div>
            
            <!-- Batch Form -->
            <div id="form-tab" class="batch-content">
                <div class="card bg-base-200">
                    <div class="card-body">
                        <h2 class="card-title">Register Multiple Animals</h2>
                        <p class="text-sm mb-4">Use this form to register multiple animals at once. Fill in the details for each animal and submit the form.</p>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-control w-full mb-6">
                                <label class="label">
                                    <span class="label-text">Select Farm</span>
                                </label>
                                <select class="select select-bordered w-full" name="farm_id" required>
                                    <option value="" disabled selected>Choose a farm</option>
                                    <?php foreach($farms as $id => $name): ?>
                                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="overflow-x-auto mb-6">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>Animal Type *</th>
                                            <th>Breed *</th>
                                            <th>Purpose *</th>
                                            <th>Quantity *</th>
                                            <th>Registration Date</th>
                                            <th>Animal Name</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="animal-rows">
                                        <?php for($i = 0; $i < 5; $i++): ?>
                                            <tr>
                                                <td><input type="text" name="animal_type[]" class="input input-bordered w-full" placeholder="e.g. Cow"></td>
                                                <td><input type="text" name="breed[]" class="input input-bordered w-full" placeholder="e.g. Holstein"></td>
                                                <td>
                                                    <select name="purpose[]" class="select select-bordered w-full">
                                                        <option value="" disabled selected>Select</option>
                                                        <option value="Dairy">Dairy</option>
                                                        <option value="Meat">Meat</option>
                                                        <option value="Dual Purpose">Dual Purpose</option>
                                                        <option value="Breeding">Breeding</option>
                                                        <option value="Work">Work</option>
                                                        <option value="Pet">Pet</option>
                                                        <option value="Other">Other</option>
                                                    </select>
                                                </td>
                                                <td><input type="number" name="quantity[]" class="input input-bordered w-full" min="1" max="1000" placeholder="1"></td>
                                                <td><input type="date" name="registration_date[]" class="input input-bordered w-full" value="<?php echo date('Y-m-d'); ?>"></td>
                                                <td><input type="text" name="animal_name[]" class="input input-bordered w-full" placeholder="Optional"></td>
                                                <td><input type="text" name="notes[]" class="input input-bordered w-full" placeholder="Optional"></td>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="flex justify-between mb-6">
                                <button type="button" class="btn btn-secondary" onclick="addRow()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Row
                                </button>
                                <div>
                                    <span class="text-sm text-gray-500">* Required fields</span>
                                </div>
                            </div>
                            
                            <div class="form-control mt-6">
                                <button type="submit" name="batch_submit" class="btn btn-primary">Register Animals</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- CSV Upload -->
            <div id="csv-tab" class="batch-content hidden">
                <div class="card bg-base-200">
                    <div class="card-body">
                        <h2 class="card-title">Upload CSV File</h2>
                        <p class="text-sm mb-4">Upload a CSV file with animal details. The CSV should include columns for animal type, breed, purpose, and quantity at minimum.</p>
                        
                        <?php if(!empty($csv_error)): ?>
                            <div class="alert alert-error mb-6">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span><?php echo $csv_error; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="form-control w-full mb-4">
                                <label class="label">
                                    <span class="label-text">Select Farm</span>
                                </label>
                                <select class="select select-bordered w-full" name="farm_id" required>
                                    <option value="" disabled selected>Choose a farm</option>
                                    <?php foreach($farms as $id => $name): ?>
                                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-control w-full mb-6">
                                <label class="label">
                                    <span class="label-text">CSV File</span>
                                </label>
                                <input type="file" name="csv_file" class="file-input file-input-bordered w-full" accept=".csv" required />
                                <label class="label">
                                    <span class="label-text-alt">Upload a CSV file with headers: animal_type, breed, purpose, quantity (required) and registration_date, animal_name, notes (optional)</span>
                                </label>
                            </div>
                            
                            <div class="form-control mt-6">
                                <button type="submit" name="csv_submit" class="btn btn-primary">Upload and Register</button>
                            </div>
                        </form>
                        
                        <?php if(!empty($csv_data)): ?>
                            <div class="divider">CSV Preview</div>
                            
                            <div class="overflow-x-auto">
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <?php foreach(array_keys($csv_data[0]) as $header): ?>
                                                <th><?php echo htmlspecialchars($header); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($csv_data as $row): ?>
                                            <tr>
                                                <?php foreach($row as $value): ?>
                                                    <td><?php echo htmlspecialchars($value); ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- CSV Template -->
            <div id="template-tab" class="batch-content hidden">
                <div class="card bg-base-200">
                    <div class="card-body">
                        <h2 class="card-title">CSV Template</h2>
                        <p class="mb-4">Download and use this template for your CSV upload. The template includes all required and optional fields.</p>
                        
                        <div class="overflow-x-auto mb-6">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>animal_type *</th>
                                        <th>breed *</th>
                                        <th>purpose *</th>
                                        <th>quantity *</th>
                                        <th>registration_date</th>
                                        <th>animal_name</th>
                                        <th>notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Cow</td>
                                        <td>Holstein</td>
                                        <td>Dairy</td>
                                        <td>5</td>
                                        <td><?php echo date('Y-m-d'); ?></td>
                                        <td>Bessie</td>
                                        <td>Healthy dairy cows</td>
                                    </tr>
                                    <tr>
                                        <td>Chicken</td>
                                        <td>Rhode Island Red</td>
                                        <td>Egg Production</td>
                                        <td>20</td>
                                        <td><?php echo date('Y-m-d'); ?></td>
                                        <td></td>
                                        <td>Free range</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="alert alert-info mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Fields marked with * are required. The registration date will default to today if left blank.</span>
                        </div>
                        
                        <div class="mb-6">
                            <h3 class="font-semibold mb-2">Sample Values</h3>
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong>animal_type:</strong> Cow, Chicken, Sheep, Goat, Pig, Horse, etc.</li>
                                <li><strong>breed:</strong> Holstein, Jersey, Rhode Island Red, Leghorn, Suffolk, etc.</li>
                                <li><strong>purpose:</strong> Dairy, Meat, Dual Purpose, Breeding, Egg Production, Work, Pet, etc.</li>
                                <li><strong>quantity:</strong> Number of animals (1-1000)</li>
                                <li><strong>registration_date:</strong> Date in YYYY-MM-DD format</li>
                                <li><strong>animal_name:</strong> Optional name for the animal(s)</li>
                                <li><strong>notes:</strong> Any additional information</li>
                            </ul>
                        </div>
                        
                        <div class="form-control">
                            <a href="download_template.php" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download CSV Template
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="../../core/livestock/animal_dashboard.php" class="btn btn-outline">Back to Animal Dashboard</a>
                <a href="../../core/livestock/animal_registration.php" class="btn btn-primary">Individual Registration</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to show selected tab
    function showTab(tabId) {
        // Hide all content
        document.querySelectorAll('.batch-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        
        // Remove active class from all tabs
        document.querySelectorAll('.batch-tab').forEach(btn => {
            btn.classList.remove('tab-active');
        });
        
        // Show selected content and activate tab
        document.getElementById(tabId).classList.remove('hidden');
        document.getElementById(tabId + '-btn').classList.add('tab-active');
    }
    
    // Function to add a new row to the batch form
    function addRow() {
        const tbody = document.getElementById('animal-rows');
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td><input type="text" name="animal_type[]" class="input input-bordered w-full" placeholder="e.g. Cow"></td>
            <td><input type="text" name="breed[]" class="input input-bordered w-full" placeholder="e.g. Holstein"></td>
            <td>
                <select name="purpose[]" class="select select-bordered w-full">
                    <option value="" disabled selected>Select</option>
                    <option value="Dairy">Dairy</option>
                    <option value="Meat">Meat</option>
                    <option value="Dual Purpose">Dual Purpose</option>
                    <option value="Breeding">Breeding</option>
                    <option value="Work">Work</option>
                    <option value="Pet">Pet</option>
                    <option value="Other">Other</option>
                </select>
            </td>
            <td><input type="number" name="quantity[]" class="input input-bordered w-full" min="1" max="1000" placeholder="1"></td>
            <td><input type="date" name="registration_date[]" class="input input-bordered w-full" value="${new Date().toISOString().split('T')[0]}"></td>
            <td><input type="text" name="animal_name[]" class="input input-bordered w-full" placeholder="Optional"></td>
            <td><input type="text" name="notes[]" class="input input-bordered w-full" placeholder="Optional"></td>
        `;
        
        tbody.appendChild(row);
    }
    
    // Initialize the first tab
    document.addEventListener('DOMContentLoaded', function() {
        showTab('form-tab');
    });
</script>

<?php include_once '../../includes/footer.php'; ?> 