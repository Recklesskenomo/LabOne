<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: auth/login.php");
    exit;
}

// Include database configuration
require_once "config.php";

// Set constants for included files
define('INCLUDED', true);

// Include role manager
require_once "utils/role_manager.php";

// Get user information from database
$user_id = $_SESSION["id"];
$username = $_SESSION["username"];
$first_name = $last_name = $profile_picture = "";

// Initialize role manager
$roleManager = new RoleManager($conn, $user_id);
$user_role = $roleManager->getUserRole();
$is_admin = $roleManager->isAdmin();

// Get user data
$sql = "SELECT first_name, last_name, profile_picture FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        mysqli_stmt_store_result($stmt);
        
        if(mysqli_stmt_num_rows($stmt) == 1){
            mysqli_stmt_bind_result($stmt, $first_name, $last_name, $profile_picture);
            mysqli_stmt_fetch($stmt);
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Count user's farms
$farm_count = 0;
$sql = "SELECT COUNT(*) as count FROM farms WHERE user_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)){
            $farm_count = $row["count"];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Count total users (admin only)
$user_count = 0;
if ($is_admin) {
    $sql = "SELECT COUNT(*) as count FROM users";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $user_count = $row["count"];
    }
}

// Set page title and include header
$pageTitle = "Dashboard - Agro Vision";
include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Dashboard Header -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <div class="mb-4 md:mb-0">
            <h1 class="text-2xl font-bold">Welcome to Your Dashboard, <?php echo !empty($first_name) ? htmlspecialchars($first_name) : htmlspecialchars($username); ?>!</h1>
            <p class="text-base-content/70">
                <?php if ($is_admin): ?>
                    Administrator Access - Manage your system with full privileges
                <?php else: ?>
                    Manage your account and farm operations with Agro Vision's intelligent tools
                <?php endif; ?>
            </p>
            <?php if ($user_role): ?>
                <div class="badge badge-primary mt-2">Role: <?php echo ucfirst(htmlspecialchars($user_role)); ?></div>
            <?php endif; ?>
        </div>
        <img src="assets/images/AVlogo.png" alt="Logo" class="logo-img">
    </div>
    
    <!-- Stats Overview -->
    <div class="stats shadow mb-8 w-full">
        <div class="stat">
            <div class="stat-figure text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
            </div>
            <div class="stat-title">Total Farms</div>
            <div class="stat-value text-primary"><?php echo $farm_count; ?></div>
            <div class="stat-desc"><?php echo $farm_count > 0 ? 'Active farms in your account' : 'Register your first farm today!'; ?></div>
        </div>
        
        <div class="stat">
            <div class="stat-figure text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <div class="stat-title">Last Login</div>
            <div class="stat-value text-secondary"><?php echo date("d M"); ?></div>
            <div class="stat-desc"><?php echo date("h:i A"); ?></div>
        </div>
        
        <?php if ($is_admin): ?>
        <div class="stat">
            <div class="stat-figure text-info">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </div>
            <div class="stat-title">Total Users</div>
            <div class="stat-value text-info"><?php echo $user_count; ?></div>
            <div class="stat-desc">Active users in the system</div>
        </div>
        <?php else: ?>
        <div class="stat">
            <div class="stat-figure text-primary">
                <div class="avatar">
                    <div class="w-16 h-16 rounded-full">
                        <?php if (!empty($profile_picture)): ?>
                            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" />
                        <?php else: ?>
                            <div class="avatar-placeholder w-full h-full flex items-center justify-center text-xl">
                                <?php echo strtoupper(substr($username, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="stat-title">Account Status</div>
            <div class="stat-value">Active</div>
            <div class="stat-desc text-success">Premium Member</div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Dashboard Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-<?php echo $is_admin ? '1' : '3'; ?> gap-6 mb-12">
        <?php if (!$is_admin): ?>
        <!-- Farm Management -->
        <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
            <div class="card-body items-center text-center">
                <div class="text-5xl text-primary mb-4">🌱</div>
                <h2 class="card-title">Farm Management</h2>
                <p class="mb-4">Register a new farm or manage your existing farms.</p>
                <div class="card-actions flex-col gap-2">
                    <?php if($farm_count > 0): ?>
                        <a href="core/farm/farm_dashboard.php" class="btn btn-primary">Farm Dashboard</a>
                        <div class="flex gap-2 flex-wrap justify-center">
                            <a href="core/farm/farmregistration.php" class="btn btn-outline btn-sm">Register Farm</a>
                            <a href="modules/analytics/farm_analytics.php" class="btn btn-outline btn-sm">Statistics</a>
                        </div>
                    <?php else: ?>
                        <a href="core/farm/farmregistration.php" class="btn btn-primary">Register Farm</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Livestock Management -->
        <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
            <div class="card-body items-center text-center">
                <div class="text-5xl text-primary mb-4">🐄</div>
                <h2 class="card-title">Livestock Management</h2>
                <p class="mb-4">Register new animals or manage your existing livestock.</p>
                <div class="card-actions flex-col gap-2">
                    <?php if($farm_count > 0): ?>
                        <a href="core/livestock/animal_dashboard.php" class="btn btn-primary">Animal Dashboard</a>
                        <div class="flex gap-2 flex-wrap justify-center">
                            <a href="core/livestock/animal_registration.php" class="btn btn-outline btn-sm">Register</a>
                            <a href="core/livestock/animal_health.php" class="btn btn-outline btn-sm">Health Records</a>
                            <a href="modules/analytics/farm_analytics.php" class="btn btn-accent btn-sm">Analytics</a>
                        </div>
                    <?php else: ?>
                        <a href="core/farm/farmregistration.php" class="btn btn-primary">Register Farm First</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Workforce Management -->
        <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
            <div class="card-body items-center text-center">
                <div class="text-5xl text-primary mb-4">👨‍🌾</div>
                <h2 class="card-title">Workforce Management</h2>
                <p class="mb-4">Manage farm employees, assign roles, and track work hours.</p>
                <div class="card-actions flex-col gap-2">
                    <?php if($farm_count > 0): ?>
                        <a href="core/employee/employee_dashboard.php" class="btn btn-primary">Employee Dashboard</a>
                        <div class="flex gap-2 flex-wrap justify-center">
                            <a href="core/employee/employee_registration.php" class="btn btn-outline btn-sm">Register</a>
                        </div>
                    <?php else: ?>
                        <a href="core/farm/farmregistration.php" class="btn btn-primary">Register Farm First</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($is_admin): ?>
        <!-- Admin Management -->
        <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
            <div class="card-body items-center text-center">
                <div class="text-5xl text-primary mb-4">⚙️</div>
                <h2 class="card-title">System Administration</h2>
                <p class="mb-4">Manage users, roles, and system settings.</p>
                <div class="card-actions flex-col gap-2">
                    <a href="admin/user_management.php" class="btn btn-primary">User Management</a>
                    <div class="flex gap-2 flex-wrap justify-center">
                        <a href="admin/system_settings.php" class="btn btn-outline btn-sm">System Settings</a>
                        <a href="admin/logs.php" class="btn btn-outline btn-sm">System Logs</a>
                        <a href="admin/contact_messages.php" class="btn btn-outline btn-sm">Contact Messages</a>
                        <a href="admin/user_data_modules.php" class="btn btn-outline btn-sm">View User Data</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Dashboard Footer -->
    <div class="flex justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg">
        <div>
            <a href="index.php" class="btn btn-outline mr-2">Home</a>
            <a href="about.php" class="btn btn-outline mr-2">About Us</a>
            <a href="modules/analytics/farm_analytics.php" class="btn btn-accent mr-2">Analytics</a>
        </div>
        <a href="auth/logout.php" class="btn btn-secondary">Logout</a>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?> 