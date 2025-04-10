<?php
/**
 * File: health_records_view.php
 * Description: Dedicated view for animal health records with filtering and detailed information
 * 
 * Part of Agro Vision Farm Management System
 */

// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../auth/login.php");
    exit;
}

// Include database configuration
require_once "../../config.php";

// Set constants for included files
define('INCLUDED', true);

// Get user information from database
$user_id = $_SESSION["id"];
$error_msg = "";
$success_msg = "";

// Get filter parameters
$filter_animal = isset($_GET['animal_id']) ? intval($_GET['animal_id']) : 0;
$filter_farm = isset($_GET['farm_id']) ? intval($_GET['farm_id']) : 0;
$filter_type = isset($_GET['health_type']) ? $_GET['health_type'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query conditions based on filters
$conditions = ["h.user_id = ?"]; // Base condition
$params = [$user_id]; // Base params
$types = "i"; // Base types (i for user_id)

if ($filter_animal > 0) {
    $conditions[] = "h.animal_id = ?";
    $params[] = $filter_animal;
    $types .= "i";
}

if ($filter_farm > 0) {
    $conditions[] = "a.farm_id = ?";
    $params[] = $filter_farm;
    $types .= "i";
}

if (!empty($filter_type)) {
    $conditions[] = "h.health_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if (!empty($filter_date_from)) {
    $conditions[] = "h.health_date >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

if (!empty($filter_date_to)) {
    $conditions[] = "h.health_date <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

// Combine conditions
$where_clause = implode(" AND ", $conditions);

// Get health records
$health_records = [];
$sql = "SELECT h.*, a.animal_type, a.breed, a.animal_name, a.tag_number, f.farm_name 
        FROM animals_health h
        JOIN animals a ON h.animal_id = a.id
        JOIN farms f ON a.farm_id = f.id
        WHERE {$where_clause}
        ORDER BY h.health_date DESC";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $health_records[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get health record summary
$health_summary = [
    'total' => 0,
    'checkup' => 0,
    'vaccination' => 0,
    'treatment' => 0,
    'medication' => 0,
    'other' => 0
];

$sql = "SELECT health_type, COUNT(*) as count
        FROM animals_health
        WHERE user_id = ?
        GROUP BY health_type";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $health_summary[$row['health_type']] = $row['count'];
            $health_summary['total'] += $row['count'];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get farms for dropdown
$farms = [];
$sql = "SELECT id, farm_name FROM farms WHERE user_id = ? ORDER BY farm_name";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $farms[$row['id']] = $row['farm_name'];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get animals for dropdown
$animals = [];
$sql = "SELECT a.id, a.animal_name, a.animal_type, a.breed, a.tag_number, f.farm_name
        FROM animals a
        JOIN farms f ON a.farm_id = f.id
        WHERE a.user_id = ?
        ORDER BY f.farm_name, a.animal_type, a.animal_name";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)) {
            $display_name = $row["animal_name"] ?: ($row["animal_type"] . " - " . $row["breed"]);
            if ($row["tag_number"]) {
                $display_name .= " (Tag: " . $row["tag_number"] . ")";
            }
            $animals[$row['id']] = [
                'name' => $display_name,
                'farm' => $row['farm_name']
            ];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Set page title and include header
$pageTitle = "Health Records - Agro Vision";
include_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl mb-8">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold">Animal Health Records</h1>
                    <p class="text-sm opacity-70">View and manage all health-related records for your livestock</p>
                </div>
                <img src="../../assets/images/AVlogo.png" alt="Logo" class="logo-img">
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
            
            <!-- Stats Overview -->
            <div class="stats shadow mb-8 w-full">
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="stat-title">Total Records</div>
                    <div class="stat-value"><?php echo number_format($health_summary['total']); ?></div>
                    <div class="stat-desc">All health records</div>
                </div>
                
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="stat-title">Vaccinations</div>
                    <div class="stat-value"><?php echo number_format($health_summary['vaccination']); ?></div>
                    <div class="stat-desc">Preventive care</div>
                </div>
                
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="stat-title">Treatments</div>
                    <div class="stat-value"><?php echo number_format($health_summary['treatment']); ?></div>
                    <div class="stat-desc">Medical interventions</div>
                </div>
                
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div class="stat-title">Checkups</div>
                    <div class="stat-value"><?php echo number_format($health_summary['checkup']); ?></div>
                    <div class="stat-desc">Routine examinations</div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title">Filter Records</h2>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="mt-4">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Farm</span>
                                </label>
                                <select class="select select-bordered w-full" name="farm_id">
                                    <option value="0">All Farms</option>
                                    <?php foreach($farms as $id => $name): ?>
                                        <option value="<?php echo $id; ?>" <?php echo $filter_farm == $id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Animal</span>
                                </label>
                                <select class="select select-bordered w-full" name="animal_id">
                                    <option value="0">All Animals</option>
                                    <?php foreach($animals as $id => $animal): ?>
                                        <option value="<?php echo $id; ?>" <?php echo $filter_animal == $id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($animal['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Record Type</span>
                                </label>
                                <select class="select select-bordered w-full" name="health_type">
                                    <option value="">All Types</option>
                                    <option value="checkup" <?php echo $filter_type == 'checkup' ? 'selected' : ''; ?>>Checkup</option>
                                    <option value="vaccination" <?php echo $filter_type == 'vaccination' ? 'selected' : ''; ?>>Vaccination</option>
                                    <option value="treatment" <?php echo $filter_type == 'treatment' ? 'selected' : ''; ?>>Treatment</option>
                                    <option value="medication" <?php echo $filter_type == 'medication' ? 'selected' : ''; ?>>Medication</option>
                                    <option value="other" <?php echo $filter_type == 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Date From</span>
                                </label>
                                <input type="date" name="date_from" class="input input-bordered w-full" value="<?php echo $filter_date_from; ?>">
                            </div>
                            
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Date To</span>
                                </label>
                                <input type="date" name="date_to" class="input input-bordered w-full" value="<?php echo $filter_date_to; ?>">
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end space-x-2">
                            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-ghost">Clear Filters</a>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Health Records Table -->
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title mb-4">Health Records</h2>
                    
                    <?php if(!empty($health_records)): ?>
                        <div class="overflow-x-auto">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Animal</th>
                                        <th>Farm</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Performed By</th>
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
                                                    
                                                    if (!empty($record["tag_number"])) {
                                                        echo '<div class="text-xs opacity-70">Tag: ' . htmlspecialchars($record["tag_number"]) . '</div>';
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($record["farm_name"]); ?></td>
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
                                            </td>
                                            <td><?php echo htmlspecialchars($record["performed_by"] ?: 'N/A'); ?></td>
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
                        
                        <?php if(count($health_records) === 0 && ($filter_animal || $filter_farm || $filter_type || $filter_date_from || $filter_date_to)): ?>
                            <div class="alert alert-warning mt-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                <span>No records match your filter criteria. Try adjusting your filters.</span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>No health records found. Add health records using the Animal Health page.</span>
                        </div>
                        <div class="mt-4">
                            <a href="animal_health.php" class="btn btn-primary">Add Health Records</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex justify-between">
                <div class="flex gap-2">
                    <a href="animal_dashboard.php" class="btn btn-outline">Animal Dashboard</a>
                    <a href="animal_health.php" class="btn btn-accent">Add Health Record</a>
                </div>
                <a href="../../modules/analytics/farm_analytics.php" class="btn btn-primary">View Analytics</a>
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
                    <h4 class="font-semibold text-accent">Animal</h4>
                    <p>${animalName} ${record.tag_number ? `(Tag: ${record.tag_number})` : ''}</p>
                    <div class="text-sm opacity-70">Farm: ${record.farm_name}</div>
                </div>
                <div>
                    <h4 class="font-semibold text-accent">Date</h4>
                    <p>${new Date(record.health_date).toLocaleDateString()}</p>
                </div>
                <div>
                    <h4 class="font-semibold text-accent">Type</h4>
                    <p>${record.health_type.charAt(0).toUpperCase() + record.health_type.slice(1)}</p>
                </div>
                <div>
                    <h4 class="font-semibold text-accent">Description</h4>
                    <p>${record.description}</p>
                </div>
        `;
        
        if (record.performed_by) {
            content += `
                <div>
                    <h4 class="font-semibold text-accent">Performed By</h4>
                    <p>${record.performed_by}</p>
                </div>
            `;
        }
        
        if (record.notes) {
            content += `
                <div>
                    <h4 class="font-semibold text-accent">Additional Notes</h4>
                    <p>${record.notes}</p>
                </div>
            `;
        }
        
        content += `
                <div>
                    <h4 class="font-semibold text-accent">Recorded On</h4>
                    <p>${new Date(record.created_at).toLocaleString()}</p>
                </div>
        `;
        
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