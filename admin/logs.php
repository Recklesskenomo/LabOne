<?php
// Initialize the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

// Include database configuration
require_once "../config.php";

// Set constants for included files
if (!defined('INCLUDED')) {
    define('INCLUDED', true);
}

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

// Create system_logs table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS system_logs (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    log_type ENUM('info', 'warning', 'error', 'security') NOT NULL,
    user_id INT(6) UNSIGNED DEFAULT NULL,
    message TEXT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

if (!mysqli_query($conn, $sql)) {
    $error_msg = "Error creating system_logs table: " . mysqli_error($conn);
}

// Function to add a log entry
function add_log_entry($conn, $type, $user_id, $message, $ip_address = null) {
    $sql = "INSERT INTO system_logs (log_type, user_id, message, ip_address) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "siss", $type, $user_id, $message, $ip_address);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// Add example log entries if the table is empty
$check_sql = "SELECT COUNT(*) as count FROM system_logs";
$result = mysqli_query($conn, $check_sql);
$row = mysqli_fetch_assoc($result);

if ($row['count'] == 0) {
    // Add some sample log entries
    add_log_entry($conn, 'info', null, 'System initialization complete', '127.0.0.1');
    add_log_entry($conn, 'info', $user_id, 'Admin user logged in', $_SERVER['REMOTE_ADDR']);
    add_log_entry($conn, 'warning', null, 'Low disk space warning - 80% used', '127.0.0.1');
    add_log_entry($conn, 'error', null, 'Database connection error at 2023-10-15 03:45:12', '127.0.0.1');
    add_log_entry($conn, 'security', null, 'Failed login attempt for username: admin', '192.168.1.100');
    add_log_entry($conn, 'info', $user_id, 'System settings updated', $_SERVER['REMOTE_ADDR']);
}

// Pagination variables
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filtering
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$valid_types = ['info', 'warning', 'error', 'security', ''];

if (!in_array($filter_type, $valid_types)) {
    $filter_type = '';
}

// Get logs with pagination and filtering
$logs = [];
$where_clause = $filter_type ? "WHERE log_type = '$filter_type'" : "";
$count_sql = "SELECT COUNT(*) as total FROM system_logs $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_logs = $count_row['total'];
$total_pages = ceil($total_logs / $per_page);

// Ensure valid page number
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

$sql = "SELECT l.*, u.username 
        FROM system_logs l
        LEFT JOIN users u ON l.user_id = u.id
        $where_clause
        ORDER BY l.created_at DESC
        LIMIT $offset, $per_page";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
    mysqli_free_result($result);
}

// Log a new admin action
add_log_entry($conn, 'info', $user_id, 'Admin viewed system logs', $_SERVER['REMOTE_ADDR']);

// Set page title and include header
$pageTitle = "System Logs - Agro Vision";
include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Admin Header -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <div class="mb-4 md:mb-0">
            <h1 class="text-2xl font-bold">System Logs</h1>
            <p class="text-base-content/70">Monitor system activities and events</p>
            <div class="badge badge-primary mt-2">Administrator Access</div>
        </div>
        <img src="../assets/images/AVlogo.png" alt="Logo" class="logo-img">
    </div>
    
    <!-- Filter Controls -->
    <div class="bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0">
                <h2 class="text-xl font-bold">Log Entries</h2>
                <p class="text-sm text-base-content/70">Showing <?php echo count($logs); ?> of <?php echo $total_logs; ?> entries</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="?type=" class="btn btn-sm <?php echo $filter_type === '' ? 'btn-primary' : 'btn-outline'; ?>">All</a>
                <a href="?type=info" class="btn btn-sm <?php echo $filter_type === 'info' ? 'btn-primary' : 'btn-outline'; ?>">Info</a>
                <a href="?type=warning" class="btn btn-sm <?php echo $filter_type === 'warning' ? 'btn-primary' : 'btn-outline'; ?>">Warning</a>
                <a href="?type=error" class="btn btn-sm <?php echo $filter_type === 'error' ? 'btn-primary' : 'btn-outline'; ?>">Error</a>
                <a href="?type=security" class="btn btn-sm <?php echo $filter_type === 'security' ? 'btn-primary' : 'btn-outline'; ?>">Security</a>
            </div>
        </div>
    </div>
    
    <!-- Log Table -->
    <div class="bg-base-100 p-6 rounded-lg shadow-lg overflow-x-auto mb-8">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>User</th>
                    <th>Message</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4">No log entries found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['id']); ?></td>
                    <td>
                        <div class="badge <?php 
                            switch($log['log_type']) {
                                case 'info': echo 'badge-info'; break;
                                case 'warning': echo 'badge-warning'; break;
                                case 'error': echo 'badge-error'; break;
                                case 'security': echo 'badge-secondary'; break;
                                default: echo 'badge-ghost';
                            }
                        ?>"><?php echo ucfirst(htmlspecialchars($log['log_type'])); ?></div>
                    </td>
                    <td><?php echo $log['username'] ? htmlspecialchars($log['username']) : '<span class="text-gray-400">System</span>'; ?></td>
                    <td><?php echo htmlspecialchars($log['message']); ?></td>
                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                    <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="flex justify-center mb-8">
        <div class="btn-group">
            <?php if ($page > 1): ?>
                <a href="?page=1<?php echo $filter_type ? '&type=' . $filter_type : ''; ?>" class="btn btn-sm">«</a>
                <a href="?page=<?php echo $page - 1; ?><?php echo $filter_type ? '&type=' . $filter_type : ''; ?>" class="btn btn-sm">‹</a>
            <?php else: ?>
                <button class="btn btn-sm" disabled>«</button>
                <button class="btn btn-sm" disabled>‹</button>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $filter_type ? '&type=' . $filter_type : ''; ?>" class="btn btn-sm <?php echo $i == $page ? 'btn-active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $filter_type ? '&type=' . $filter_type : ''; ?>" class="btn btn-sm">›</a>
                <a href="?page=<?php echo $total_pages; ?><?php echo $filter_type ? '&type=' . $filter_type : ''; ?>" class="btn btn-sm">»</a>
            <?php else: ?>
                <button class="btn btn-sm" disabled>›</button>
                <button class="btn btn-sm" disabled>»</button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Admin Navigation -->
    <div class="flex justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg">
        <div>
            <a href="../dashboard.php" class="btn btn-outline mr-2">Back to Dashboard</a>
            <a href="user_management.php" class="btn btn-outline mr-2">User Management</a>
            <a href="system_settings.php" class="btn btn-outline mr-2">System Settings</a>
            <a href="contact_messages.php" class="btn btn-outline mr-2">Contact Messages</a>
            <a href="user_data_modules.php" class="btn btn-outline mr-2">View User Data</a>
        </div>
        <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 