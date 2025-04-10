<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
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
$admin_id = $_SESSION["id"];

// Initialize role manager
$roleManager = new RoleManager($conn, $admin_id);

// Check if user is admin
if (!$roleManager->isAdmin()) {
    // Not an admin, redirect to dashboard
    header("location: ../dashboard.php");
    exit;
}

// Process message response
$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["respond_to_message"])) {
    $message_id = $_POST["message_id"];
    $admin_response = mysqli_real_escape_string($conn, trim($_POST["admin_response"]));
    
    // Update message status and response
    $sql = "UPDATE contact_messages 
            SET status = 'answered', 
                admin_response = ?, 
                responded_by = ?, 
                updated_at = NOW() 
            WHERE id = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sii", $admin_response, $admin_id, $message_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Message has been marked as answered and your response has been saved.";
            
            // Log the action
            if (function_exists('add_log_entry')) {
                add_log_entry($conn, 'info', $admin_id, "Admin responded to contact message ID: {$message_id}", $_SERVER['REMOTE_ADDR']);
            }
        } else {
            $error_msg = "Error updating message status.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Get messages with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filter by status if provided
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where_clause = '';

if ($status_filter === 'pending' || $status_filter === 'answered') {
    $where_clause = "WHERE status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

// Count total messages
$count_sql = "SELECT COUNT(*) as total FROM contact_messages " . $where_clause;
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_messages = $count_row['total'];
$total_pages = ceil($total_messages / $per_page);

// Get message list
$messages = [];
$sql = "SELECT cm.*, u.username 
        FROM contact_messages cm
        LEFT JOIN users u ON cm.responded_by = u.id
        $where_clause
        ORDER BY cm.created_at DESC
        LIMIT $offset, $per_page";

$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }
    mysqli_free_result($result);
}

// Set page title and include header
$pageTitle = "Contact Messages - Agro Vision";
include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Admin Header -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <div class="mb-4 md:mb-0">
            <h1 class="text-2xl font-bold">Contact Messages</h1>
            <p class="text-base-content/70">Manage and respond to contact messages from users</p>
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
    
    <!-- Filter Controls -->
    <div class="bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0">
                <h2 class="text-xl font-bold">Message Status</h2>
                <p class="text-sm text-base-content/70">Filter messages by their current status</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="?status=" class="btn btn-sm <?php echo $status_filter === '' ? 'btn-primary' : 'btn-outline'; ?>">All Messages</a>
                <a href="?status=pending" class="btn btn-sm <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline'; ?>">Pending</a>
                <a href="?status=answered" class="btn btn-sm <?php echo $status_filter === 'answered' ? 'btn-primary' : 'btn-outline'; ?>">Answered</a>
            </div>
        </div>
    </div>
    
    <!-- Message List -->
    <div class="bg-base-100 p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4">
            <?php 
            if ($status_filter === 'pending') echo "Pending Messages";
            elseif ($status_filter === 'answered') echo "Answered Messages";
            else echo "All Messages";
            ?>
            <span class="text-base font-normal text-base-content/70">(<?php echo $total_messages; ?>)</span>
        </h2>
        
        <?php if (empty($messages)): ?>
        <div class="alert alert-info">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>No messages found. <?php echo $status_filter ? "There are no " . $status_filter . " messages." : ""; ?></span>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                    <tr class="<?php echo $msg['status'] === 'pending' ? 'bg-base-200' : ''; ?>">
                        <td><?php echo htmlspecialchars($msg['id']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($msg['name']); ?></td>
                        <td><?php echo htmlspecialchars($msg['email']); ?></td>
                        <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                        <td>
                            <div class="badge <?php echo $msg['status'] === 'pending' ? 'badge-warning' : 'badge-success'; ?>">
                                <?php echo ucfirst(htmlspecialchars($msg['status'])); ?>
                            </div>
                        </td>
                        <td>
                            <a href="#message-<?php echo $msg['id']; ?>" class="btn btn-sm btn-outline" onclick="document.getElementById('message-<?php echo $msg['id']; ?>').showModal()">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-6">
            <div class="btn-group">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" class="btn btn-sm">«</a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" class="btn btn-sm">‹</a>
                <?php else: ?>
                    <button class="btn btn-sm" disabled>«</button>
                    <button class="btn btn-sm" disabled>‹</button>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" class="btn btn-sm <?php echo $i == $page ? 'btn-active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" class="btn btn-sm">›</a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" class="btn btn-sm">»</a>
                <?php else: ?>
                    <button class="btn btn-sm" disabled>›</button>
                    <button class="btn btn-sm" disabled>»</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Message Dialogs -->
    <?php foreach ($messages as $msg): ?>
    <dialog id="message-<?php echo $msg['id']; ?>" class="modal">
        <div class="modal-box max-w-3xl">
            <h3 class="font-bold text-lg"><?php echo htmlspecialchars($msg['subject']); ?></h3>
            <p class="py-1">From: <?php echo htmlspecialchars($msg['name']); ?> (<?php echo htmlspecialchars($msg['email']); ?>)</p>
            <p class="py-1 text-sm text-base-content/70">Received: <?php echo date('F j, Y, g:i a', strtotime($msg['created_at'])); ?></p>
            
            <div class="mt-4 p-4 bg-base-200 rounded-lg">
                <p class="whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
            </div>
            
            <?php if ($msg['status'] === 'answered'): ?>
            <div class="divider">Admin Response</div>
            <div class="bg-blue-50 p-4 rounded-lg">
                <p class="whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($msg['admin_response'])); ?></p>
                <p class="text-sm text-base-content/70 mt-2">
                    Answered by: <?php echo htmlspecialchars($msg['username']); ?> 
                    on <?php echo date('F j, Y, g:i a', strtotime($msg['updated_at'])); ?>
                </p>
            </div>
            <?php else: ?>
            <div class="divider">Respond to Message</div>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Your Response</span>
                    </label>
                    <textarea name="admin_response" class="textarea textarea-bordered h-32" required></textarea>
                </div>
                <div class="form-control mt-4">
                    <button type="submit" name="respond_to_message" class="btn btn-primary">Send Response & Mark as Answered</button>
                </div>
            </form>
            <?php endif; ?>
            
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn">Close</button>
                </form>
            </div>
        </div>
    </dialog>
    <?php endforeach; ?>
    
    <!-- Admin Navigation -->
    <div class="flex justify-between items-center bg-base-100 p-6 rounded-lg shadow-lg">
        <div>
            <a href="../dashboard.php" class="btn btn-outline mr-2">Back to Dashboard</a>
            <a href="user_management.php" class="btn btn-outline mr-2">User Management</a>
            <a href="system_settings.php" class="btn btn-outline mr-2">System Settings</a>
            <a href="logs.php" class="btn btn-outline mr-2">System Logs</a>
            <a href="user_data_modules.php" class="btn btn-outline mr-2">View User Data</a>
        </div>
        <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 