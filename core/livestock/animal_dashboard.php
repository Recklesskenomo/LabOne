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

$user_id = $_SESSION["id"];
$error_msg = "";
$success_msg = "";

// Check if the animals table exists, create if not
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'animals'");
if (mysqli_num_rows($table_check) == 0) {
    // Create animals table
    $create_sql = "CREATE TABLE IF NOT EXISTS animals (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        farm_id INT(6) UNSIGNED NOT NULL,
        user_id INT(6) UNSIGNED NOT NULL,
        animal_type VARCHAR(50) NOT NULL,
        breed VARCHAR(100) NOT NULL,
        purpose VARCHAR(100) NOT NULL,
        quantity INT(6) UNSIGNED NOT NULL DEFAULT 1,
        registration_date DATE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (farm_id) REFERENCES farms(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    mysqli_query($conn, $create_sql);
}

// Check if the registration_date column exists in the animals table
$column_check = mysqli_query($conn, "SHOW COLUMNS FROM animals LIKE 'registration_date'");
if (mysqli_num_rows($column_check) == 0) {
    // The column doesn't exist, add it
    $alter_sql = "ALTER TABLE animals ADD COLUMN registration_date DATE AFTER quantity";
    mysqli_query($conn, $alter_sql);
    $success_msg = "Database structure updated. The system may need a page refresh.";
}

// Check if the animal_vaccinations table exists, create if not
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'animal_vaccinations'");
if (mysqli_num_rows($table_check) == 0) {
    // Create animal_vaccinations table
    $create_sql = "CREATE TABLE IF NOT EXISTS animal_vaccinations (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        animal_id INT(6) UNSIGNED NOT NULL,
        user_id INT(6) UNSIGNED NOT NULL,
        vaccination_name VARCHAR(100) NOT NULL,
        scheduled_date DATE NOT NULL,
        status ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
        quantity INT(6) UNSIGNED NOT NULL DEFAULT 1,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (animal_id) REFERENCES animals(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    mysqli_query($conn, $create_sql);
    $success_msg = "Database structure updated. The vaccinations tracking system is now available.";
}

// Check if the animal_health table exists, create if not
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'animal_health'");
if (mysqli_num_rows($table_check) == 0) {
    // Create animal_health table
    $create_sql = "CREATE TABLE IF NOT EXISTS animal_health (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        animal_id INT(6) UNSIGNED NOT NULL,
        user_id INT(6) UNSIGNED NOT NULL,
        health_status ENUM('Healthy', 'Sick', 'Treatment', 'Monitoring') DEFAULT 'Healthy',
        condition_details TEXT,
        treatment TEXT,
        quantity INT(6) UNSIGNED NOT NULL DEFAULT 1,
        reported_date DATE NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (animal_id) REFERENCES animals(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    mysqli_query($conn, $create_sql);
    $success_msg = "Database structure updated. The animal health tracking system is now available.";
}

// Get statistics about animals
$total_animals = 0;
$animal_stats = [
    'types' => [],
    'purposes' => [],
    'by_farm' => []
];

// Get total animals count
$sql = "SELECT SUM(quantity) as total FROM animals WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)){
            $total_animals = $row["total"] ?: 0;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get animals by type
$sql = "SELECT animal_type, SUM(quantity) as count FROM animals WHERE user_id = ? GROUP BY animal_type ORDER BY count DESC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $animal_stats['types'][$row["animal_type"]] = $row["count"];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get animals by purpose
$sql = "SELECT purpose, SUM(quantity) as count FROM animals WHERE user_id = ? GROUP BY purpose ORDER BY count DESC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $animal_stats['purposes'][$row["purpose"]] = $row["count"];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get animals by farm
$sql = "SELECT f.id, f.farm_name, SUM(a.quantity) as count 
        FROM animals a
        JOIN farms f ON a.farm_id = f.id
        WHERE a.user_id = ?
        GROUP BY f.id, f.farm_name
        ORDER BY count DESC";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $animal_stats['by_farm'][$row["id"]] = [
                'name' => $row["farm_name"],
                'count' => $row["count"]
            ];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get recent animal registrations
$recent_registrations = [];
$sql = "SELECT a.id, a.animal_type, a.breed, a.purpose, a.quantity, a.created_at, f.farm_name 
        FROM animals a
        JOIN farms f ON a.farm_id = f.id
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC LIMIT 5";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $recent_registrations[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get health stats
$health_stats = [
    'healthy' => 0,
    'sick' => 0,
    'treatment' => 0,
    'monitoring' => 0
];

// Get animal health stats
$sql = "SELECT health_status, SUM(quantity) as count FROM animal_health 
        WHERE user_id = ? 
        GROUP BY health_status";
// Check if the table exists before querying it
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'animal_health'");
if (mysqli_num_rows($table_check) > 0) {
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $status = strtolower($row["health_status"]);
                if(isset($health_stats[$status])){
                    $health_stats[$status] = $row["count"];
                }
            }
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Get upcoming vaccinations
$upcoming_vaccinations = [];
// Check if the table exists before querying it
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'animal_vaccinations'");
if (mysqli_num_rows($table_check) > 0) {
    $sql = "SELECT av.id, a.animal_type, av.vaccination_name, av.scheduled_date, f.farm_name, av.quantity 
            FROM animal_vaccinations av
            JOIN animals a ON av.animal_id = a.id
            JOIN farms f ON a.farm_id = f.id
            WHERE av.user_id = ? AND av.status = 'Scheduled' AND av.scheduled_date >= CURDATE()
            ORDER BY av.scheduled_date ASC LIMIT 5";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $upcoming_vaccinations[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Set page title and include header
define('INCLUDED', true);
$pageTitle = "Animal Dashboard - Agro Vision";
include_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Animal Dashboard</h1>
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
            
            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap gap-4 mb-8">
                <a href="animal_registration.php" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Register New Animals
                </a>
                <a href="animal_health.php" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    Health Records
                </a>
                <a href="animal_batch.php" class="btn btn-accent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Batch Operations
                </a>
                <a href="animal_report.php" class="btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Generate Reports
                </a>
            </div>
            
            <!-- Overview Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="stats shadow">
                    <div class="stat">
                        <div class="stat-figure text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </div>
                        <div class="stat-title">Total Animals</div>
                        <div class="stat-value text-primary"><?php echo number_format($total_animals); ?></div>
                        <div class="stat-desc">Across all your farms</div>
                    </div>
                </div>
                
                <div class="stats shadow">
                    <div class="stat">
                        <div class="stat-figure text-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                        </div>
                        <div class="stat-title">Animal Types</div>
                        <div class="stat-value text-secondary"><?php echo count($animal_stats['types']); ?></div>
                        <div class="stat-desc">Different types of animals</div>
                    </div>
                </div>
                
                <div class="stats shadow">
                    <div class="stat">
                        <div class="stat-figure text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                        </div>
                        <div class="stat-title">Farms with Animals</div>
                        <div class="stat-value text-accent"><?php echo count($animal_stats['by_farm']); ?></div>
                        <div class="stat-desc">Where animals are registered</div>
                    </div>
                </div>
            </div>
            
            <!-- Health Overview -->
            <?php 
            // Check if animal_health table exists
            $health_table_exists = mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'animal_health'")) > 0;
            if ($health_table_exists):
            ?>
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title">Animal Health Overview</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                        <div class="stat bg-success bg-opacity-20 rounded-box">
                            <div class="stat-title text-success">Healthy</div>
                            <div class="stat-value text-success text-2xl"><?php echo number_format($health_stats['healthy']); ?></div>
                            <div class="stat-desc"><?php echo ($total_animals > 0) ? round($health_stats['healthy'] / $total_animals * 100, 1) . '%' : '0%'; ?> of animals</div>
                        </div>
                        
                        <div class="stat bg-error bg-opacity-20 rounded-box">
                            <div class="stat-title text-error">Sick</div>
                            <div class="stat-value text-error text-2xl"><?php echo number_format($health_stats['sick']); ?></div>
                            <div class="stat-desc"><?php echo ($total_animals > 0) ? round($health_stats['sick'] / $total_animals * 100, 1) . '%' : '0%'; ?> of animals</div>
                        </div>
                        
                        <div class="stat bg-warning bg-opacity-20 rounded-box">
                            <div class="stat-title text-warning">Under Treatment</div>
                            <div class="stat-value text-warning text-2xl"><?php echo number_format($health_stats['treatment']); ?></div>
                            <div class="stat-desc"><?php echo ($total_animals > 0) ? round($health_stats['treatment'] / $total_animals * 100, 1) . '%' : '0%'; ?> of animals</div>
                        </div>
                        
                        <div class="stat bg-info bg-opacity-20 rounded-box">
                            <div class="stat-title text-info">Monitoring</div>
                            <div class="stat-value text-info text-2xl"><?php echo number_format($health_stats['monitoring']); ?></div>
                            <div class="stat-desc"><?php echo ($total_animals > 0) ? round($health_stats['monitoring'] / $total_animals * 100, 1) . '%' : '0%'; ?> of animals</div>
                        </div>
                    </div>
                    <div class="card-actions justify-end mt-4">
                        <a href="animal_health.php" class="btn btn-sm btn-primary">Manage Health Records</a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title">Animal Health Tracking</h2>
                    <p class="py-4">Health tracking is now available! Start monitoring your animals' health status.</p>
                    <div class="card-actions justify-end mt-4">
                        <a href="animal_health.php" class="btn btn-sm btn-primary">Set Up Health Records</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Distribution Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="card bg-base-200">
                    <div class="card-body">
                        <h2 class="card-title">Animals by Type</h2>
                        <?php if(!empty($animal_stats['types'])): ?>
                            <div class="space-y-4 mt-4">
                                <?php foreach($animal_stats['types'] as $type => $count): ?>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span><?php echo htmlspecialchars($type); ?></span>
                                            <span class="font-semibold"><?php echo number_format($count); ?></span>
                                        </div>
                                        <progress class="progress progress-primary w-full" value="<?php echo $count; ?>" max="<?php echo $total_animals; ?>"></progress>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center py-4">No animal data available</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card bg-base-200">
                    <div class="card-body">
                        <h2 class="card-title">Animals by Purpose</h2>
                        <?php if(!empty($animal_stats['purposes'])): ?>
                            <div class="space-y-4 mt-4">
                                <?php foreach($animal_stats['purposes'] as $purpose => $count): ?>
                                    <div>
                                        <div class="flex justify-between mb-1">
                                            <span><?php echo htmlspecialchars($purpose); ?></span>
                                            <span class="font-semibold"><?php echo number_format($count); ?></span>
                                        </div>
                                        <progress class="progress progress-secondary w-full" value="<?php echo $count; ?>" max="<?php echo $total_animals; ?>"></progress>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center py-4">No animal data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Animals by Farm -->
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title">Animals by Farm</h2>
                    <?php if(!empty($animal_stats['by_farm'])): ?>
                        <div class="overflow-x-auto mt-4">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>Farm Name</th>
                                        <th>Animal Count</th>
                                        <th>Percentage of Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($animal_stats['by_farm'] as $farm_id => $farm): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($farm['name']); ?></td>
                                            <td><?php echo number_format($farm['count']); ?></td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <progress class="progress progress-accent w-24" value="<?php echo $farm['count']; ?>" max="<?php echo $total_animals; ?>"></progress>
                                                    <span><?php echo round($farm['count'] / $total_animals * 100, 1); ?>%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex gap-2">
                                                    <a href="farm_details.php?id=<?php echo $farm_id; ?>" class="btn btn-xs btn-primary">View Farm</a>
                                                    <a href="animal_registration.php?farm_id=<?php echo $farm_id; ?>" class="btn btn-xs btn-secondary">Add Animals</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center py-4">No animal data available</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Registrations -->
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title">Recent Animal Registrations</h2>
                    <?php if(!empty($recent_registrations)): ?>
                        <div class="overflow-x-auto mt-4">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>Farm</th>
                                        <th>Type</th>
                                        <th>Breed</th>
                                        <th>Purpose</th>
                                        <th>Quantity</th>
                                        <th>Registration Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_registrations as $animal): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($animal["farm_name"]); ?></td>
                                            <td><?php echo htmlspecialchars($animal["animal_type"]); ?></td>
                                            <td><?php echo htmlspecialchars($animal["breed"]); ?></td>
                                            <td><?php echo htmlspecialchars($animal["purpose"]); ?></td>
                                            <td><?php echo htmlspecialchars($animal["quantity"]); ?></td>
                                            <td><?php echo date("M j, Y", strtotime($animal["created_at"])); ?></td>
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
                    <?php else: ?>
                        <p class="text-center py-4">No recent animal registrations</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Upcoming Vaccinations -->
            <?php 
            // Check if animal_vaccinations table exists
            $vaccinations_table_exists = mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'animal_vaccinations'")) > 0;
            if ($vaccinations_table_exists):
            ?>
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title">Upcoming Vaccinations</h2>
                    <?php if(!empty($upcoming_vaccinations)): ?>
                        <div class="overflow-x-auto mt-4">
                            <table class="table table-zebra w-full">
                                <thead>
                                    <tr>
                                        <th>Farm</th>
                                        <th>Animal Type</th>
                                        <th>Vaccination</th>
                                        <th>Scheduled Date</th>
                                        <th>Quantity</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($upcoming_vaccinations as $vaccination): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($vaccination["farm_name"]); ?></td>
                                            <td><?php echo htmlspecialchars($vaccination["animal_type"]); ?></td>
                                            <td><?php echo htmlspecialchars($vaccination["vaccination_name"]); ?></td>
                                            <td>
                                                <?php 
                                                    $date = new DateTime($vaccination["scheduled_date"]);
                                                    $now = new DateTime();
                                                    $interval = $now->diff($date);
                                                    $days_away = $interval->days;
                                                    echo date("M j, Y", strtotime($vaccination["scheduled_date"]));
                                                    
                                                    if($days_away <= 7) {
                                                        echo ' <span class="badge badge-warning">Soon</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($vaccination["quantity"]); ?></td>
                                            <td>
                                                <div class="flex gap-2">
                                                    <a href="vaccination_details.php?id=<?php echo $vaccination["id"]; ?>" class="btn btn-xs btn-primary">View</a>
                                                    <a href="mark_vaccination.php?id=<?php echo $vaccination["id"]; ?>" class="btn btn-xs btn-success">Mark Complete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center py-4">No upcoming vaccinations scheduled</p>
                    <?php endif; ?>
                    <div class="card-actions justify-end mt-4">
                        <a href="schedule_vaccination.php" class="btn btn-sm btn-primary">Schedule Vaccination</a>
                        <a href="vaccination_calendar.php" class="btn btn-sm btn-outline">View Calendar</a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card bg-base-200 mb-8">
                <div class="card-body">
                    <h2 class="card-title">Vaccination Tracking</h2>
                    <p class="py-4">Vaccination tracking is now available! Start scheduling vaccinations for your animals.</p>
                    <div class="card-actions justify-end mt-4">
                        <a href="schedule_vaccination.php" class="btn btn-sm btn-primary">Schedule Vaccinations</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="flex justify-between mt-8">
                <a href="../../dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                <div class="flex gap-2">
                    <a href="animal_registration.php" class="btn btn-primary">Register Animal</a>
                    <a href="animal_health.php" class="btn btn-accent">Add Health Record</a>
                    <a href="health_records_view.php" class="btn btn-secondary">View Health Records</a>
                    <a href="../../modules/analytics/farm_analytics.php" class="btn btn-primary">View Analytics</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?> 