<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

// Include database configuration
require_once "../config.php";

// Set constants for included files
define('INCLUDED', true);

// Include role manager
require_once "../utils/role_manager.php";

// Get user information from database
$user_id = $_SESSION["id"];

// Initialize role manager
$roleManager = new RoleManager($conn, $user_id);

// Check if user is admin
if (!$roleManager->isAdmin()) {
    // Not an admin, redirect to dashboard
    header("location: ../dashboard.php");
    exit;
}

// Add status column to users table if it doesn't exist
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'status'");
$column_exists = mysqli_num_rows($result) > 0;

if (!$column_exists) {
    // SQL to add status column to users table
    $sql = "ALTER TABLE users ADD COLUMN status ENUM('active', 'blocked') NOT NULL DEFAULT 'active'";
    mysqli_query($conn, $sql);
}

// Process role change requests
$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_role"])) {
    $target_user_id = $_POST["user_id"];
    $new_role_id = $_POST["role_id"];
    
    // Make sure admin is not changing their own role
    if ($target_user_id == $user_id) {
        $error_msg = "You cannot change your own role.";
    } else {
        if ($roleManager->changeUserRole($target_user_id, $new_role_id)) {
            $success_msg = "User role updated successfully.";
            
            // Log the action
            if (function_exists('add_log_entry')) {
                add_log_entry($conn, 'security', $user_id, "Admin changed user ID {$target_user_id} role to ID {$new_role_id}", $_SERVER['REMOTE_ADDR']);
            }
        } else {
            $error_msg = "Failed to update user role.";
        }
    }
}

// Process block/unblock requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["toggle_block"])) {
    $target_user_id = $_POST["user_id"];
    $new_status = $_POST["new_status"];
    
    // Make sure admin is not blocking themselves
    if ($target_user_id == $user_id) {
        $error_msg = "You cannot block yourself.";
    } else {
        $sql = "UPDATE users SET status = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $new_status, $target_user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $action = $new_status === 'blocked' ? 'blocked' : 'unblocked';
                $success_msg = "User {$action} successfully.";
                
                // Log the action
                if (function_exists('add_log_entry')) {
                    add_log_entry($conn, 'security', $user_id, "Admin {$action} user ID {$target_user_id}", $_SERVER['REMOTE_ADDR']);
                }
            } else {
                $error_msg = "Failed to update user status.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Get list of all users
$users = [];
$sql = "SELECT u.id, u.username, u.email, u.first_name, u.last_name, 
              u.created_at, r.id as role_id, r.role_name, u.status 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        ORDER BY u.id";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    mysqli_free_result($result);
}

// Get all available roles
$roles = $roleManager->getAllRoles();

// Set page title and include header
$pageTitle = "User Management - Agro Vision";
include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Admin Header -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <div class="mb-4 md:mb-0">
            <h1 class="text-2xl font-bold">User Management</h1>
            <p class="text-base-content/70">Manage users and their roles in the system</p>
            <div class="badge badge-primary mt-2">Administrator Access</div>
        </div>
        <img src="../assets/images/AVlogo.png" alt="Logo" class="logo-img">
    </div>
    
    <!-- Messages -->
    <?php if (!empty($success_msg)): ?>
    <div class="alert alert-success mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span><?php echo $success_msg; ?></span>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
    <div class="alert alert-error mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span><?php echo $error_msg; ?></span>
    </div>
    <?php endif; ?>
    
    <!-- User List -->
    <div class="bg-base-100 p-6 rounded-lg shadow-lg overflow-x-auto mb-8">
        <h2 class="text-xl font-bold mb-4">System Users</h2>
        <table class="table w-full">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Current Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="<?php echo $u['id'] == $user_id ? 'bg-base-200' : ''; ?>">
                    <td><?php echo htmlspecialchars($u['id']); ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <div class="badge <?php echo $u['role_name'] === 'admin' ? 'badge-primary' : 'badge-secondary'; ?>">
                            <?php echo ucfirst(htmlspecialchars($u['role_name'])); ?>
                        </div>
                    </td>
                    <td>
                        <div class="badge <?php echo $u['status'] === 'active' ? 'badge-success' : 'badge-error'; ?>">
                            <?php echo ucfirst(htmlspecialchars($u['status'] ?? 'active')); ?>
                        </div>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                    <td>
                        <?php if ($u['id'] != $user_id): ?>
                        <div class="flex flex-col gap-2">
                            <div class="dropdown dropdown-end">
                                <label tabindex="0" class="btn btn-xs">Change Role</label>
                                <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                                    <?php foreach ($roles as $role): ?>
                                    <li>
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                            <button type="submit" name="change_role" class="btn btn-ghost btn-xs w-full text-left <?php echo $u['role_id'] == $role['id'] ? 'text-primary font-bold' : ''; ?>">
                                                Set as <?php echo ucfirst(htmlspecialchars($role['role_name'])); ?>
                                            </button>
                                        </form>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <!-- Block/Unblock Button -->
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <?php if (($u['status'] ?? 'active') === 'active'): ?>
                                    <input type="hidden" name="new_status" value="blocked">
                                    <button type="submit" name="toggle_block" class="btn btn-xs btn-error btn-outline">Block User</button>
                                <?php else: ?>
                                    <input type="hidden" name="new_status" value="active">
                                    <button type="submit" name="toggle_block" class="btn btn-xs btn-success btn-outline">Unblock User</button>
                                <?php endif; ?>
                            </form>
                            
                            <!-- View User Data Button -->
                            <a href="user_data_modules.php?id=<?php echo $u['id']; ?>" class="btn btn-xs btn-primary btn-outline mt-2">View User Data</a>
                        </div>
                        <?php else: ?>
                        <span class="text-gray-500">Current User</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Admin Navigation -->
    <div class="flex justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg">
        <div>
            <a href="../dashboard.php" class="btn btn-outline mr-2">Back to Dashboard</a>
            <a href="system_settings.php" class="btn btn-outline mr-2">System Settings</a>
            <a href="logs.php" class="btn btn-outline mr-2">System Logs</a>
            <a href="contact_messages.php" class="btn btn-outline mr-2">Contact Messages</a>
            <a href="user_data_modules.php" class="btn btn-outline mr-2">View User Data</a>
        </div>
        <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 