<?php
// No direct access
if(!defined('INCLUDED')) {
    header("HTTP/1.0 403 Forbidden");
    exit;
}

// Include database configuration if not already included
if (!isset($conn)) {
    require_once __DIR__ . '/../config.php';
}

// Initialize role manager if user is logged in
$user_role = '';
$is_admin = false;

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["id"])) {
    // Set constants for included files
    if (!defined('INCLUDED')) {
        define('INCLUDED', true);
    }
    
    // Include role manager
    require_once __DIR__ . '/../utils/role_manager.php';
    
    // Initialize role manager
    $roleManager = new RoleManager($conn, $_SESSION["id"]);
    $user_role = $roleManager->getUserRole();
    $is_admin = $roleManager->isAdmin();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="lemonade">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Agro Vision'; ?></title>
    
    <!-- Tailwind CSS and DaisyUI CDN -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Configure Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Lemonade theme colors
                        'custom-green': '#519903',
                        'custom-dark': '#1f2937',
                    }
                }
            },
            daisyui: {
                themes: ["lemonade", "light", "dark"],
                darkTheme: "dark"
            }
        }
    </script>
    
    <!-- Custom styles -->
    <style>
        body {
            background-image: url('<?php echo get_relative_path("img/texture-light.png"); ?>');
            background-repeat: repeat;
            background-attachment: fixed;
        }
        .logo-img {
            max-height: 60px;
        }
        .hero-img {
            max-height: 120px;
        }
        .avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #519903;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            color: #ffffff;
        }
        .navbar {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        .btn-accent {
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        .card {
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Navbar -->
    <div class="navbar bg-base-100 shadow-sm">
        <div class="navbar-start">
            <div class="dropdown">
                <label tabindex="0" class="btn btn-ghost lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                </label>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[50] p-2 shadow bg-base-100 rounded-box w-52">
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <li><a href="<?php echo get_relative_path('dashboard.php'); ?>">Dashboard</a></li>
                        <li><a href="<?php echo get_relative_path('index.php'); ?>">Home</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo get_relative_path('index.php'); ?>">Home</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo get_relative_path('about.php'); ?>">About</a></li>
                    <li><a href="<?php echo get_relative_path('contactus.php'); ?>">Contact Us</a></li>
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <li><a href="<?php echo get_relative_path('profile.php'); ?>">Profile</a></li>
                        <li><a href="<?php echo get_relative_path('auth/logout.php'); ?>">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo get_relative_path('auth/login.php'); ?>">Login</a></li>
                        <li><a href="<?php echo get_relative_path('auth/signup.php'); ?>">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <a href="<?php echo get_relative_path('index.php'); ?>" class="btn btn-ghost normal-case text-xl">
                <img src="<?php echo get_relative_path('assets/images/AVlogo.png'); ?>" alt="Agro Vision" class="h-8 mr-2">
                Agro Vision
            </a>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1">
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <li><a href="<?php echo get_relative_path('dashboard.php'); ?>">Dashboard</a></li>
                    <li><a href="<?php echo get_relative_path('index.php'); ?>">Home</a></li>
                <?php else: ?>
                    <li><a href="<?php echo get_relative_path('index.php'); ?>">Home</a></li>
                <?php endif; ?>
                <li><a href="<?php echo get_relative_path('about.php'); ?>">About</a></li>
                <li><a href="<?php echo get_relative_path('contactus.php'); ?>">Contact Us</a></li>
            </ul>
        </div>
        <div class="navbar-end">
            <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                        <?php 
                        // Get user profile picture
                        $profile_picture = "";
                        
                        if(isset($_SESSION["id"]) && isset($conn)) {
                            $user_id = $_SESSION["id"];
                            
                            $sql = "SELECT profile_picture FROM users WHERE id = ?";
                            if($stmt = mysqli_prepare($conn, $sql)){
                                mysqli_stmt_bind_param($stmt, "i", $user_id);
                                
                                if(mysqli_stmt_execute($stmt)){
                                    mysqli_stmt_store_result($stmt);
                                    
                                    if(mysqli_stmt_num_rows($stmt) == 1){
                                        mysqli_stmt_bind_result($stmt, $profile_picture);
                                        mysqli_stmt_fetch($stmt);
                                    }
                                }
                                
                                mysqli_stmt_close($stmt);
                            }
                        }
                        
                        if (!empty($profile_picture)): 
                        ?>
                            <div class="w-10 rounded-full">
                                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" />
                            </div>
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </label>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[50] p-2 shadow bg-base-100 rounded-box w-52">
                        <li>
                            <a href="<?php echo get_relative_path('profile.php'); ?>" class="justify-between">
                                Profile
                                <span class="badge badge-accent">New</span>
                            </a>
                        </li>
                        <li><a href="<?php echo get_relative_path('dashboard.php'); ?>">Dashboard</a></li>
                        <li><a href="<?php echo get_relative_path('auth/logout.php'); ?>">Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?php echo get_relative_path('auth/login.php'); ?>" class="btn btn-ghost mr-2">Login</a>
                <a href="<?php echo get_relative_path('auth/signup.php'); ?>" class="btn btn-primary">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="pt-4">

<?php
/**
 * Helper function to get a relative path from the current file to the target file
 * This helps with navigating between different directory levels
 */
function get_relative_path($target_file) {
    // Use the BASE_URL constant from config.php
    if (defined('BASE_URL')) {
        return BASE_URL . '/' . ltrim($target_file, '/');
    }
    
    // Fallback for when BASE_URL is not defined
    return '/LabOne - Copy/' . ltrim($target_file, '/');
} 