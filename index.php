<?php
// Initialize the session
session_start();
 
// Remove redirect for logged-in users
// No redirection needed - allow all users to see the landing page

// Set constants for included files
define('INCLUDED', true);
$pageTitle = "Agro Vision - Smart Farm Management System";

// Include header
include_once 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero min-h-[650px] relative bg-gradient-to-r from-green-50 to-yellow-50">
    <!-- Background Image -->
    <div class="absolute inset-0 w-full h-full z-0 overflow-hidden">
        <img src="img/landing page.webp" class="w-full h-full object-cover object-center" alt="Modern Farm" />
        <div class="absolute inset-0 bg-gradient-to-r from-green-50/90 to-yellow-50/80"></div>
    </div>
    
    <!-- Content -->
    <div class="hero-content relative z-1 flex-col max-w-7xl mx-auto px-4 py-16 text-center">
        <h1 class="text-5xl font-bold text-green-800 mb-6">Smart Farm Management Made Simple</h1>
        <p class="py-6 text-lg text-gray-700 max-w-3xl mx-auto">Agro Vision brings cutting-edge technology to agriculture, helping farmers optimize operations, increase productivity, and make data-driven decisions.</p>
        <div class="flex flex-wrap gap-4 justify-center mt-4">
            <a href="auth/signup.php" class="btn btn-primary btn-lg">Get Started Free</a>
            <a href="auth/login.php" class="btn btn-outline btn-lg">Sign In</a>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
            <div class="bg-base-100 p-6 rounded-lg shadow-md hover:shadow-xl transition-all duration-300">
                <div class="stat-value text-primary">5000+</div>
                <div class="stat-title">Farms Managed</div>
            </div>
            <div class="bg-base-100 p-6 rounded-lg shadow-md hover:shadow-xl transition-all duration-300">
                <div class="stat-value text-primary">98%</div>
                <div class="stat-title">Customer Satisfaction</div>
            </div>
            <div class="bg-base-100 p-6 rounded-lg shadow-md hover:shadow-xl transition-all duration-300">
                <div class="stat-value text-primary">25K+</div>
                <div class="stat-title">Animals Tracked</div>
            </div>
            <div class="bg-base-100 p-6 rounded-lg shadow-md hover:shadow-xl transition-all duration-300">
                <div class="stat-value text-primary">15+</div>
                <div class="stat-title">Countries Served</div>
            </div>
        </div>
    </div>
</div>

<!-- Main Features Section -->
<div class="py-16 bg-base-200">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-4xl font-bold text-center mb-4">Complete Farm Management Solution</h2>
        <p class="text-center max-w-3xl mx-auto mb-12 text-lg">Everything you need to manage your farm operations efficiently in one integrated platform</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
                <div class="card-body">
                    <div class="text-5xl text-primary mb-4">🌱</div>
                    <h3 class="card-title">Farm Management</h3>
                    <p>Keep track of all your farms, manage land usage, and monitor operational efficiency with our powerful dashboard.</p>
                    <ul class="mt-4 space-y-2">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Detailed analytics
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
                <div class="card-body">
                    <div class="text-5xl text-primary mb-4">🐄</div>
                    <h3 class="card-title">Livestock Management</h3>
                    <p>Efficiently track animal health, manage breeding programs, and maintain detailed records of your livestock.</p>
                    <ul class="mt-4 space-y-2">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Health tracking
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Herd management
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
                <div class="card-body">
                    <div class="text-5xl text-primary mb-4">👨‍🌾</div>
                    <h3 class="card-title">Workforce Management</h3>
                    <p>Efficiently manage employees and optimize workforce productivity with our intuitive tools.</p>
                    <ul class="mt-4 space-y-2">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Employee management
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Role assignments
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Employee profiles
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Preview -->
<div class="py-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col">
            <h2 class="text-4xl font-bold mb-6">Powerful Analytics Dashboard</h2>
            <p class="mb-6 text-lg">Get real-time insights into your farm's performance with our intuitive dashboard. Make data-driven decisions to improve productivity and profitability.</p>
            <div class="space-y-4 mb-8">
                <div class="flex items-start gap-4">
                    <div class="text-green-600 font-bold">✓</div>
                    <p>Real-time monitoring and analytics for informed decision-making</p>
                </div>
                <div class="flex items-start gap-4">
                    <div class="text-green-600 font-bold">✓</div>
                    <p>Automated record-keeping and report generation</p>
                </div>
                <div class="flex items-start gap-4">
                    <div class="text-green-600 font-bold">✓</div>
                    <p>Mobile-friendly interface for access anywhere, anytime</p>
                </div>
                <div class="flex items-start gap-4">
                    <div class="text-green-600 font-bold">✓</div>
                    <p>Secure data storage with regular backups</p>
                </div>
            </div>
            <div>
                <a href="auth/signup.php" class="btn bg-green-600 hover:bg-green-700 text-white border-none">TRY IT FREE</a>
            </div>
            
            <!-- Full-width dashboard image below text -->
            <div class="mt-12">
                <img src="img/screenshots/animal-dashboard.png" alt="Animal Dashboard" class="w-full rounded-lg shadow-xl" />
            </div>
        </div>
    </div>
</div>

<!-- Testimonials Section -->
<div class="py-16 bg-base-200">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-4xl font-bold text-center mb-4">Trusted by Farmers Worldwide</h2>
        <p class="text-center max-w-3xl mx-auto mb-12 text-lg">See what our users have to say about Agro Vision</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
                <div class="card-body">
                    <div class="flex items-center mb-4">
                        <div class="avatar">
                            <div class="w-12 h-12 rounded-full overflow-hidden">
                                <img src="assets/images/d19dd16c-4bcf-4e05-acc4-9987fd4a6cc5.jpeg" alt="John Doe" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="font-bold">John Doe</h3>
                            <p class="text-sm">Dairy Farmer</p>
                        </div>
                    </div>
                    <div class="flex mb-2">
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    </div>
                    <p>"Agro Vision has transformed how we manage our dairy farm. The livestock tracking features are invaluable. I can now monitor my entire herd with just a few clicks."</p>
                </div>
            </div>
            <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
                <div class="card-body">
                    <div class="flex items-center mb-4">
                        <div class="avatar">
                            <div class="w-12 h-12 rounded-full overflow-hidden">
                                <img src="assets/images/e3f7e8e9-3ccc-4abc-89bd-9eadff98231b.jpeg" alt="Maria Smith" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="font-bold">Maria Smith</h3>
                            <p class="text-sm">Ranch Owner</p>
                        </div>
                    </div>
                    <div class="flex mb-2">
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    </div>
                    <p>"The employee management system has made scheduling and payroll so much easier. My team is more productive, and I can focus on growing my business instead of paperwork."</p>
                </div>
            </div>
            <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
                <div class="card-body">
                    <div class="flex items-center mb-4">
                        <div class="avatar">
                            <div class="w-12 h-12 rounded-full overflow-hidden">
                                <img src="assets/images/4608f4b7-d1de-4814-b4ed-3c084db5f396.jpeg" alt="Robert Johnson" />
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="font-bold">Robert Johnson</h3>
                            <p class="text-sm">Farm Manager</p>
                        </div>
                    </div>
                    <div class="flex mb-2">
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    </div>
                    <p>"The analytics and reporting features help us make better decisions and improve our farm's efficiency. I can quickly identify trends and take action before problems arise."</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="py-16 bg-primary text-primary-content">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-4xl font-bold mb-6">Ready to Transform Your Farm Management?</h2>
        <p class="text-xl mb-8">Join thousands of farmers who are already using Agro Vision to modernize their operations.</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="auth/signup.php" class="btn btn-lg bg-white text-primary hover:bg-gray-100">Start Free Trial</a>
            <a href="auth/login.php" class="btn btn-lg btn-outline border-white text-white hover:bg-white hover:text-primary">Sign In</a>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?> 