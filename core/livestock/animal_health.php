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

// Check if the animals_health table exists, if not, create it
$sql = "CREATE TABLE IF NOT EXISTS animals_health (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    animal_id INT(6) UNSIGNED NOT NULL,
    health_date DATE NOT NULL,
    health_type ENUM('checkup', 'vaccination', 'treatment', 'medication', 'other') NOT NULL,
    description TEXT NOT NULL,
    performed_by VARCHAR(255),
    notes TEXT,
    user_id INT(6) UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if($conn->query($sql) !== TRUE) {
    $error_msg = "Error creating animals_health table: " . $conn->error;
}

// Process form data
$animal_id = $health_date = $health_type = $description = $performed_by = $notes = "";
$animal_id_err = $health_date_err = $health_type_err = $description_err = "";

// Process record deletion
if(isset($_GET["delete"]) && !empty($_GET["delete"])) {
    $record_id = trim($_GET["delete"]);
    
    $sql = "SELECT * FROM animals_health WHERE id = ? AND user_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $record_id, $user_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1) {
                $delete_sql = "DELETE FROM animals_health WHERE id = ?";
                if($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                    mysqli_stmt_bind_param($delete_stmt, "i", $record_id);
                    
                    if(mysqli_stmt_execute($delete_stmt)) {
                        $success_msg = "Health record deleted successfully!";
                    } else {
                        $error_msg = "Something went wrong. Please try again later.";
                    }
                    
                    mysqli_stmt_close($delete_stmt);
                }
            } else {
                $error_msg = "No record found or you don't have permission to delete this record.";
            }
        } else {
            $error_msg = "Something went wrong. Please try again later.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate animal ID
    if(empty(trim($_POST["animal_id"]))) {
        $animal_id_err = "Please select an animal.";
    } else {
        $animal_id = trim($_POST["animal_id"]);
        
        // Check if animal exists and belongs to user
        $sql = "SELECT id FROM animals WHERE id = ? AND user_id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $animal_id, $user_id);
            
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 0) {
                    $animal_id_err = "Invalid animal selection.";
                }
            } else {
                $error_msg = "Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate health date
    if(empty(trim($_POST["health_date"]))) {
        $health_date_err = "Please enter the date.";
    } else {
        $health_date = trim($_POST["health_date"]);
        
        // Check if date is valid and not in the future
        $date = new DateTime($health_date);
        $today = new DateTime();
        if($date > $today) {
            $health_date_err = "Health date cannot be in the future.";
        }
    }
    
    // Validate health type
    if(empty(trim($_POST["health_type"]))) {
        $health_type_err = "Please select a health record type.";
    } else {
        $health_type = trim($_POST["health_type"]);
        
        // Check if health type is valid
        $valid_types = ['checkup', 'vaccination', 'treatment', 'medication', 'other'];
        if(!in_array($health_type, $valid_types)) {
            $health_type_err = "Invalid health record type.";
        }
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))) {
        $description_err = "Please enter a description.";
    } else {
        $description = trim($_POST["description"]);
    }
    
    // Get optional fields
    $performed_by = trim($_POST["performed_by"]);
    $notes = trim($_POST["notes"]);
    
    // Check input errors before inserting into database
    if(empty($animal_id_err) && empty($health_date_err) && empty($health_type_err) && empty($description_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO animals_health (animal_id, health_date, health_type, description, performed_by, notes, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "isssssi", $animal_id, $health_date, $health_type, $description, $performed_by, $notes, $user_id);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                $success_msg = "Health record added successfully!";
                
                // Clear input fields
                $animal_id = $health_date = $health_type = $description = $performed_by = $notes = "";
            } else {
                $error_msg = "Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Get all animals for dropdown
$animals = [];
$sql = "SELECT a.id, a.animal_type, a.breed, f.farm_name, a.quantity, a.animal_name 
        FROM animals a
        JOIN farms f ON a.farm_id = f.id
        WHERE a.user_id = ?
        ORDER BY f.farm_name, a.animal_type";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $display_name = $row["animal_name"] ?? ($row["animal_type"] . " - " . $row["breed"]);
            $animals[$row["id"]] = [
                "name" => $display_name,
                "farm" => $row["farm_name"],
                "quantity" => $row["quantity"]
            ];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get recent health records
$health_records = [];
$sql = "SELECT h.*, a.animal_type, a.breed, a.animal_name, f.farm_name 
        FROM animals_health h
        JOIN animals a ON h.animal_id = a.id
        JOIN farms f ON a.farm_id = f.id
        WHERE h.user_id = ?
        ORDER BY h.health_date DESC
        LIMIT 25";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $health_records[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Animal Health Records - Agro Vision";
include_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Animal Health Records</h1>
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Health Record Form -->
                <div class="lg:col-span-1">
                    <div class="card bg-base-200">
                        <div class="card-body">
                            <h2 class="card-title">Add Health Record</h2>
                            <p class="text-sm mb-4">Add health records for checkups, vaccinations, treatments, and more.</p>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <div class="form-control w-full mb-4">
                                    <label class="label">
                                        <span class="label-text">Animal</span>
                                    </label>
                                    <select class="select select-bordered <?php echo (!empty($animal_id_err)) ? 'select-error' : ''; ?>" name="animal_id" required>
                                        <option value="" disabled <?php echo empty($animal_id) ? "selected" : ""; ?>>Select an animal</option>
                                        <?php foreach($animals as $id => $animal): ?>
                                            <option value="<?php echo $id; ?>" <?php echo ($animal_id == $id) ? "selected" : ""; ?>>
                                                <?php echo htmlspecialchars($animal["name"] . " (" . $animal["farm"] . ")"); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(!empty($animal_id_err)): ?>
                                        <label class="label">
                                            <span class="label-text-alt text-error"><?php echo $animal_id_err; ?></span>
                                        </label>
                                    <?php endif; ?>
                                </div>

                                <div class="form-control w-full mb-4">
                                    <label class="label">
                                        <span class="label-text">Date</span>
                                    </label>
                                    <input type="date" name="health_date" class="input input-bordered <?php echo (!empty($health_date_err)) ? 'input-error' : ''; ?>" value="<?php echo htmlspecialchars($health_date ?: date('Y-m-d')); ?>" required>
                                    <?php if(!empty($health_date_err)): ?>
                                        <label class="label">
                                            <span class="label-text-alt text-error"><?php echo $health_date_err; ?></span>
                                        </label>
                                    <?php endif; ?>
                                </div>

                                <div class="form-control w-full mb-4">
                                    <label class="label">
                                        <span class="label-text">Record Type</span>
                                    </label>
                                    <select class="select select-bordered <?php echo (!empty($health_type_err)) ? 'select-error' : ''; ?>" name="health_type" required>
                                        <option value="" disabled <?php echo empty($health_type) ? "selected" : ""; ?>>Select record type</option>
                                        <option value="checkup" <?php echo ($health_type == "checkup") ? "selected" : ""; ?>>Checkup</option>
                                        <option value="vaccination" <?php echo ($health_type == "vaccination") ? "selected" : ""; ?>>Vaccination</option>
                                        <option value="treatment" <?php echo ($health_type == "treatment") ? "selected" : ""; ?>>Treatment</option>
                                        <option value="medication" <?php echo ($health_type == "medication") ? "selected" : ""; ?>>Medication</option>
                                        <option value="other" <?php echo ($health_type == "other") ? "selected" : ""; ?>>Other</option>
                                    </select>
                                    <?php if(!empty($health_type_err)): ?>
                                        <label class="label">
                                            <span class="label-text-alt text-error"><?php echo $health_type_err; ?></span>
                                        </label>
                                    <?php endif; ?>
                                </div>

                                <div class="form-control w-full mb-4">
                                    <label class="label">
                                        <span class="label-text">Description</span>
                                    </label>
                                    <textarea name="description" class="textarea textarea-bordered h-24 <?php echo (!empty($description_err)) ? 'textarea-error' : ''; ?>" placeholder="Describe the health record in detail" required><?php echo htmlspecialchars($description); ?></textarea>
                                    <?php if(!empty($description_err)): ?>
                                        <label class="label">
                                            <span class="label-text-alt text-error"><?php echo $description_err; ?></span>
                                        </label>
                                    <?php endif; ?>
                                </div>

                                <div class="form-control w-full mb-4">
                                    <label class="label">
                                        <span class="label-text">Performed By</span>
                                    </label>
                                    <input type="text" name="performed_by" class="input input-bordered" placeholder="Veterinarian, staff member, etc." value="<?php echo htmlspecialchars($performed_by); ?>">
                                </div>

                                <div class="form-control w-full mb-4">
                                    <label class="label">
                                        <span class="label-text">Additional Notes</span>
                                    </label>
                                    <textarea name="notes" class="textarea textarea-bordered h-24" placeholder="Any additional notes or observations"><?php echo htmlspecialchars($notes); ?></textarea>
                                </div>

                                <div class="form-control mt-6">
                                    <button type="submit" class="btn btn-primary">Add Health Record</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Health Records List -->
                <div class="lg:col-span-2">
                    <div class="card bg-base-200">
                        <div class="card-body">
                            <h2 class="card-title">Recent Health Records</h2>
                            
                            <?php if(!empty($health_records)): ?>
                                <div class="overflow-x-auto mt-4">
                                    <table class="table table-zebra w-full">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Animal</th>
                                                <th>Type</th>
                                                <th>Description</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($health_records as $record): ?>
                                                <tr>
                                                    <td><?php echo date("M j, Y", strtotime($record["health_date"])); ?></td>
                                                    <td>
                                                        <?php 
                                                            $animal_display = !empty($record["animal_name"]) ? $record["animal_name"] : ($record["animal_type"] . " - " . $record["breed"]);
                                                            echo htmlspecialchars($animal_display); 
                                                        ?>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($record["farm_name"]); ?></div>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $badge_class = "";
                                                            switch($record["health_type"]) {
                                                                case "checkup":
                                                                    $badge_class = "badge-info";
                                                                    break;
                                                                case "vaccination":
                                                                    $badge_class = "badge-success";
                                                                    break;
                                                                case "treatment":
                                                                    $badge_class = "badge-warning";
                                                                    break;
                                                                case "medication":
                                                                    $badge_class = "badge-primary";
                                                                    break;
                                                                default:
                                                                    $badge_class = "badge-secondary";
                                                            }
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst(htmlspecialchars($record["health_type"])); ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="truncate max-w-xs" title="<?php echo htmlspecialchars($record["description"]); ?>">
                                                            <?php echo htmlspecialchars($record["description"]); ?>
                                                        </div>
                                                        <?php if(!empty($record["performed_by"])): ?>
                                                            <div class="text-xs text-gray-500">By: <?php echo htmlspecialchars($record["performed_by"]); ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="flex flex-col gap-2">
                                                            <button class="btn btn-xs btn-primary" onclick="showDetails(<?php echo $record['id']; ?>)">View Details</button>
                                                            <a href="animal_health.php?delete=<?php echo $record['id']; ?>" class="btn btn-xs btn-error" onclick="return confirm('Are you sure you want to delete this health record? This action cannot be undone.')">Delete</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>No health records found. Add your first animal health record using the form.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="../../core/livestock/animal_dashboard.php" class="btn btn-outline">Back to Animal Dashboard</a>
                <div class="flex gap-2">
                    <a href="health_records_view.php" class="btn btn-secondary">View All Health Records</a>
                    <a href="../../modules/analytics/farm_analytics.php" class="btn btn-accent">View Analytics</a>
                    <a href="../../dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for displaying health record details -->
<div class="modal" id="healthRecordModal">
    <div class="modal-box">
        <h3 class="font-bold text-lg" id="modalTitle">Health Record Details</h3>
        <div class="py-4" id="modalContent">
            <!-- Content will be filled via JavaScript -->
        </div>
        <div class="modal-action">
            <button class="btn" id="closeModal">Close</button>
        </div>
    </div>
</div>

<script>
    // Health records data for modal display
    const healthRecords = <?php echo json_encode($health_records); ?>;
    
    // Function to show health record details in modal
    function showDetails(recordId) {
        const record = healthRecords.find(r => r.id == recordId);
        if (!record) return;
        
        const animalName = record.animal_name || `${record.animal_type} - ${record.breed}`;
        
        let content = `
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <h4 class="font-semibold text-primary">Animal</h4>
                    <p>${animalName} (${record.farm_name})</p>
                </div>
                <div>
                    <h4 class="font-semibold text-primary">Date</h4>
                    <p>${new Date(record.health_date).toLocaleDateString()}</p>
                </div>
                <div>
                    <h4 class="font-semibold text-primary">Type</h4>
                    <p>${record.health_type.charAt(0).toUpperCase() + record.health_type.slice(1)}</p>
                </div>
                <div>
                    <h4 class="font-semibold text-primary">Description</h4>
                    <p>${record.description}</p>
                </div>
        `;
        
        if (record.performed_by) {
            content += `
                <div>
                    <h4 class="font-semibold text-primary">Performed By</h4>
                    <p>${record.performed_by}</p>
                </div>
            `;
        }
        
        if (record.notes) {
            content += `
                <div>
                    <h4 class="font-semibold text-primary">Additional Notes</h4>
                    <p>${record.notes}</p>
                </div>
            `;
        }
        
        content += '</div>';
        
        document.getElementById('modalContent').innerHTML = content;
        document.getElementById('modalTitle').textContent = `Health Record: ${animalName}`;
        
        // Show modal (using DaisyUI)
        document.getElementById('healthRecordModal').classList.add('modal-open');
    }
    
    // Close modal
    document.getElementById('closeModal').addEventListener('click', function() {
        document.getElementById('healthRecordModal').classList.remove('modal-open');
    });
</script>

<?php include_once '../../includes/footer.php'; ?> 