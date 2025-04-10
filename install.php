<?php
// Installation script for Agro Vision Farm Management System
session_start();

// Define page title and include header
define('INCLUDED', true);
$pageTitle = "Installation - Agro Vision";
include_once 'includes/header.php';

// Check if config.php exists
$config_exists = file_exists('config.php');

// Step indicator
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Process steps
$messages = [];
$errors = [];
$warnings = [];

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['check_connection'])) {
        // Database connection check
        $host = $_POST['db_host'];
        $user = $_POST['db_user'];
        $pass = $_POST['db_password'];
        $db = $_POST['db_name'];
        
        // Test connection
        $conn = @mysqli_connect($host, $user, $pass);
        
        if($conn) {
            $messages[] = "Database connection successful!";
            
            // Check if database exists
            $db_exists = mysqli_select_db($conn, $db);
            if(!$db_exists) {
                // Try to create the database
                if(mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $db")) {
                    $messages[] = "Database '$db' created successfully.";
                    $db_exists = true;
                } else {
                    $errors[] = "Unable to create database '$db'. Error: " . mysqli_error($conn);
                }
            } else {
                $messages[] = "Database '$db' already exists.";
            }
            
            if($db_exists) {
                // Create config file
                $config_content = "<?php\n";
                $config_content .= "// Database configuration\n";
                $config_content .= "\$server = \"$host\";\n";
                $config_content .= "\$username = \"$user\";\n";
                $config_content .= "\$password = \"$pass\";\n";
                $config_content .= "\$dbname = \"$db\";\n\n";
                $config_content .= "// Create connection\n";
                $config_content .= "\$conn = mysqli_connect(\$server, \$username, \$password, \$dbname);\n\n";
                $config_content .= "// Check connection\n";
                $config_content .= "if (!\$conn) {\n";
                $config_content .= "    die(\"Connection failed: \" . mysqli_connect_error());\n";
                $config_content .= "}\n";
                
                if(file_put_contents('config.php', $config_content)) {
                    $messages[] = "Configuration file created successfully.";
                    $config_exists = true;
                    
                    // Move to next step
                    $step = 2;
                } else {
                    $errors[] = "Unable to create configuration file. Please check file permissions.";
                }
            }
            
            mysqli_close($conn);
        } else {
            $errors[] = "Database connection failed: " . mysqli_connect_error();
        }
    }
    else if(isset($_POST['install_tables'])) {
        // Install database tables
        require_once 'config.php';
        
        // Check if users table exists
        $table_users_exists = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
        
        if(mysqli_num_rows($table_users_exists) == 0) {
            // Create users table
            $sql_users = "CREATE TABLE users (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                profile_picture VARCHAR(255) DEFAULT NULL,
                reset_token VARCHAR(255) DEFAULT NULL,
                reset_token_expires DATETIME DEFAULT NULL,
                reset_otp INT(6) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            
            if(mysqli_query($conn, $sql_users)) {
                $messages[] = "Users table created successfully.";
            } else {
                $errors[] = "Error creating users table: " . mysqli_error($conn);
            }
        } else {
            $messages[] = "Users table already exists.";
            
            // Check for missing columns in users table
            $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'reset_token'");
            $reset_token_exists = mysqli_num_rows($result) > 0;
            
            $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'reset_token_expires'");
            $reset_token_expires_exists = mysqli_num_rows($result) > 0;
            
            $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'reset_otp'");
            $reset_otp_exists = mysqli_num_rows($result) > 0;
            
            // Add missing columns if needed
            if(!$reset_token_exists || !$reset_token_expires_exists || !$reset_otp_exists) {
                $alter_sql = "ALTER TABLE users ";
                $alter_parts = [];
                
                if(!$reset_token_exists) {
                    $alter_parts[] = "ADD COLUMN reset_token VARCHAR(255) NULL DEFAULT NULL";
                }
                
                if(!$reset_token_expires_exists) {
                    $alter_parts[] = "ADD COLUMN reset_token_expires DATETIME NULL DEFAULT NULL";
                }
                
                if(!$reset_otp_exists) {
                    $alter_parts[] = "ADD COLUMN reset_otp INT(6) NULL DEFAULT NULL";
                }
                
                $alter_sql .= implode(", ", $alter_parts);
                
                if(mysqli_query($conn, $alter_sql)) {
                    $messages[] = "Added missing columns to users table for password reset functionality.";
                } else {
                    $errors[] = "Error adding columns to users table: " . mysqli_error($conn);
                }
            }
        }
        
        // Check if farms table exists
        $table_farms_exists = mysqli_query($conn, "SHOW TABLES LIKE 'farms'");
        
        if(mysqli_num_rows($table_farms_exists) == 0) {
            // Create farms table
            $sql_farms = "CREATE TABLE farms (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL,
                farm_name VARCHAR(100) NOT NULL,
                location VARCHAR(255) NOT NULL,
                size DECIMAL(10,2) NOT NULL,
                farm_type VARCHAR(50) NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            
            if(mysqli_query($conn, $sql_farms)) {
                $messages[] = "Farms table created successfully.";
            } else {
                $errors[] = "Error creating farms table: " . mysqli_error($conn);
            }
        } else {
            $messages[] = "Farms table already exists.";
        }
        
        // Check if animals table exists
        $table_animals_exists = mysqli_query($conn, "SHOW TABLES LIKE 'animals'");
        
        if(mysqli_num_rows($table_animals_exists) == 0) {
            // Create animals table
            $sql_animals = "CREATE TABLE animals (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                farm_id INT(11) NOT NULL,
                animal_name VARCHAR(100) NOT NULL,
                animal_type VARCHAR(50) NOT NULL,
                breed VARCHAR(100),
                date_of_birth DATE,
                gender VARCHAR(10),
                weight DECIMAL(10,2),
                health_status VARCHAR(50),
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
            )";
            
            if(mysqli_query($conn, $sql_animals)) {
                $messages[] = "Animals table created successfully.";
            } else {
                $errors[] = "Error creating animals table: " . mysqli_error($conn);
            }
        } else {
            $messages[] = "Animals table already exists.";
        }
        
        // Check if employees table exists
        $table_employees_exists = mysqli_query($conn, "SHOW TABLES LIKE 'employees'");
        
        if(mysqli_num_rows($table_employees_exists) == 0) {
            // Create employees table
            $sql_employees = "CREATE TABLE employees (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                farm_id INT(11) NOT NULL,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                position VARCHAR(100) NOT NULL,
                contact_number VARCHAR(20),
                email VARCHAR(100),
                address TEXT,
                hire_date DATE NOT NULL,
                salary DECIMAL(10,2),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
            )";
            
            if(mysqli_query($conn, $sql_employees)) {
                $messages[] = "Employees table created successfully.";
            } else {
                $errors[] = "Error creating employees table: " . mysqli_error($conn);
            }
        } else {
            $messages[] = "Employees table already exists.";
        }
        
        // Create default admin user if no users exist
        $user_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
        $row = mysqli_fetch_assoc($user_count);
        
        if($row['count'] == 0) {
            $admin_username = "admin";
            $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
            $admin_email = "admin@agrovision.com";
            
            $sql_admin = "INSERT INTO users (username, password, email, first_name, last_name) 
                        VALUES ('$admin_username', '$admin_password', '$admin_email', 'Admin', 'User')";
            
            if(mysqli_query($conn, $sql_admin)) {
                $messages[] = "Default admin user created. Username: admin, Password: admin123";
                $warnings[] = "Please change the default admin password after logging in.";
            } else {
                $errors[] = "Error creating default admin user: " . mysqli_error($conn);
            }
        }
        
        // Move to final step
        $step = 3;
    }
}
?>

<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="card w-full max-w-2xl bg-base-100 shadow-xl">
        <div class="card-body">
            <div class="text-center mb-6">
                <img src="AVlogo.png" alt="Agro Vision" class="logo-img mx-auto mb-4">
                <h2 class="text-2xl font-bold">Agro Vision Installation</h2>
                <p class="text-base-content/70 mt-2">Set up your farm management system</p>
            </div>
            
            <!-- Progress indicator -->
            <ul class="steps steps-horizontal w-full mb-8">
                <li class="step <?php echo $step >= 1 ? 'step-primary' : ''; ?>">Database Configuration</li>
                <li class="step <?php echo $step >= 2 ? 'step-primary' : ''; ?>">Database Installation</li>
                <li class="step <?php echo $step >= 3 ? 'step-primary' : ''; ?>">Completion</li>
            </ul>
            
            <!-- Messages -->
            <?php if(!empty($messages)): ?>
                <div class="alert alert-success mb-4">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div>
                            <?php foreach($messages as $message): ?>
                                <p><?php echo $message; ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Errors -->
            <?php if(!empty($errors)): ?>
                <div class="alert alert-error mb-4">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div>
                            <?php foreach($errors as $error): ?>
                                <p><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Warnings -->
            <?php if(!empty($warnings)): ?>
                <div class="alert alert-warning mb-4">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <div>
                            <?php foreach($warnings as $warning): ?>
                                <p><?php echo $warning; ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if($step == 1): ?>
                <!-- Step 1: Database Configuration -->
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">Database Host</span>
                        </label>
                        <input type="text" name="db_host" class="input input-bordered" value="localhost" required>
                    </div>
                    
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">Database Username</span>
                        </label>
                        <input type="text" name="db_user" class="input input-bordered" value="root" required>
                    </div>
                    
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">Database Password</span>
                        </label>
                        <input type="password" name="db_password" class="input input-bordered">
                    </div>
                    
                    <div class="form-control mb-6">
                        <label class="label">
                            <span class="label-text">Database Name</span>
                        </label>
                        <input type="text" name="db_name" class="input input-bordered" value="FarmTech" required>
                    </div>
                    
                    <div class="form-control mt-6">
                        <button type="submit" name="check_connection" class="btn btn-primary">Check Connection</button>
                    </div>
                </form>
            <?php elseif($step == 2): ?>
                <!-- Step 2: Database Installation -->
                <div class="mb-6">
                    <p>The database connection has been successfully configured. Click the button below to install the database tables and initialize the system.</p>
                </div>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?step=2">
                    <div class="form-control mt-6">
                        <button type="submit" name="install_tables" class="btn btn-primary">Install Database Tables</button>
                    </div>
                </form>
            <?php elseif($step == 3): ?>
                <!-- Step 3: Completion -->
                <div class="alert alert-success mb-6">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Installation completed successfully!</span>
                    </div>
                </div>
                
                <div class="mb-6">
                    <p>The Agro Vision Farm Management System has been successfully installed. You can now start using the system.</p>
                </div>
                
                <div class="flex flex-col gap-4 sm:flex-row sm:justify-between">
                    <a href="index.php" class="btn btn-primary">Go to Home</a>
                    <a href="login.php" class="btn">Log In to System</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?> 
 
 