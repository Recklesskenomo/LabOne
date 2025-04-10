<?php
/**
 * File: contactus.php
 * Description: Contact page with form for users to send messages
 * 
 * Part of Agro Vision Farm Management System
 */

// Initialize the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set constants for included files
define('INCLUDED', true);
$pageTitle = "Contact Us - Agro Vision";

// Include database configuration
require_once "config.php";

// Include form validator
require_once "utils/form_validation.php";

// Process form submission
$success_message = "";
$error_message = "";
$name = $email = $subject = $message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_contact'])) {
    // Create validator instance
    $validator = new FormValidator();
    
    // Validate form fields
    $validator->required('name', $_POST['name'] ?? '');
    $validator->maxLength('name', $_POST['name'] ?? '', 100);
    
    $validator->required('email', $_POST['email'] ?? '');
    $validator->email('email', $_POST['email'] ?? '');
    
    $validator->required('subject', $_POST['subject'] ?? '');
    $validator->maxLength('subject', $_POST['subject'] ?? '', 200);
    
    $validator->required('message', $_POST['message'] ?? '');
    
    // Check for validation errors
    if ($validator->hasErrors()) {
        // Get first error message
        $errors = $validator->getErrors();
        $error_message = reset($errors);
        
        // Preserve input values
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $message = $_POST['message'] ?? '';
    } else {
        // Get sanitized data
        $sanitized = $validator->getSanitized();
        $name = $sanitized['name'];
        $email = $sanitized['email'];
        $subject = $sanitized['subject'];
        $message = $sanitized['message'];
        
        // Insert message into database
        $sql = "INSERT INTO contact_messages (name, email, subject, message) 
                VALUES (?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $subject, $message);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Your message has been sent successfully! We will get back to you soon.";
                // Reset form data
                $name = $email = $subject = $message = "";
            } else {
                $error_message = "Oops! Something went wrong. Please try again later.";
                // Log error but don't show to user
                debug_log("Database error in contactus.php: " . mysqli_error($conn));
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database error. Please try again later.";
            // Log error but don't show to user
            debug_log("Prepare failed in contactus.php: " . mysqli_error($conn));
        }
    }
}

// Include header
include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Header -->
            <div class="flex justify-between items-center border-b border-base-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold">Contact Us</h1>
                <a href="index.php">
                    <img src="assets/images/AVlogo.png" alt="Logo" class="logo-img h-12">
                </a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Contact Form -->
                <div>
                    <h2 class="text-xl font-semibold mb-4">Send Us a Message</h2>
                    
                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span><?php echo $success_message; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span><?php echo $error_message; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="contactForm" novalidate>
                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text">Full Name *</span>
                            </label>
                            <input type="text" name="name" class="input input-bordered" value="<?php echo htmlspecialchars($name); ?>" required minlength="2" maxlength="100">
                            <div class="invalid-feedback text-error text-sm mt-1">Please enter your full name</div>
                        </div>
                        
                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text">Email *</span>
                            </label>
                            <input type="email" name="email" class="input input-bordered" value="<?php echo htmlspecialchars($email); ?>" required>
                            <div class="invalid-feedback text-error text-sm mt-1">Please enter a valid email address</div>
                        </div>
                        
                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text">Subject *</span>
                            </label>
                            <input type="text" name="subject" class="input input-bordered" value="<?php echo htmlspecialchars($subject); ?>" required maxlength="200">
                            <div class="invalid-feedback text-error text-sm mt-1">Please enter a subject for your message</div>
                        </div>
                        
                        <div class="form-control mb-4">
                            <label class="label">
                                <span class="label-text">Message *</span>
                            </label>
                            <textarea name="message" class="textarea textarea-bordered h-32" required><?php echo htmlspecialchars($message); ?></textarea>
                            <div class="invalid-feedback text-error text-sm mt-1">Please enter your message</div>
                        </div>
                        
                        <div class="form-control mt-6">
                            <button type="submit" name="submit_contact" class="btn btn-primary">Send Message</button>
                        </div>
                    </form>
                </div>
                
                <!-- Contact Information -->
                <div>
                    <h2 class="text-xl font-semibold mb-4">Contact Information</h2>
                    
                    <div class="card bg-base-200 p-6 mb-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold">Phone</h3>
                                <p>+1 (234) 567-8901</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold">Email</h3>
                                <p>support@agrovision.com</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold">Address</h3>
                                <p>123 Farm Road, Agriculture District, Country</p>
                            </div>
                        </div>
                    </div>
                    
                    <h2 class="text-xl font-semibold mb-4">Office Hours</h2>
                    <div class="card bg-base-200 p-6">
                        <ul class="space-y-2">
                            <li class="flex justify-between">
                                <span class="font-semibold">Monday - Friday:</span>
                                <span>8:00 AM - 5:00 PM</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="font-semibold">Saturday:</span>
                                <span>9:00 AM - 2:00 PM</span>
                            </li>
                            <li class="flex justify-between">
                                <span class="font-semibold">Sunday:</span>
                                <span>Closed</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include client-side validation -->
<script src="assets/js/form-validation.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        FormValidator.init('#contactForm');
    });
</script>

<?php include_once 'includes/footer.php'; ?> 