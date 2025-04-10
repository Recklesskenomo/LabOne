<?php
// Initialize the session
session_start();

// Set constants for included files
define('INCLUDED', true);
$pageTitle = "About Us - Agro Vision";

// Include header
include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-12">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-green-50 to-yellow-50 rounded-xl p-8 mb-12">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-green-800 mb-4">About Agro Vision</h1>
            <p class="text-xl text-gray-700 mb-6">Empowering farmers with cutting-edge technology for sustainable agriculture</p>
            <div class="w-24 h-1 bg-yellow-400 mx-auto mb-8"></div>
            <p class="text-lg text-gray-600">
                Founded in 2022, Agro Vision is dedicated to revolutionizing farm management through innovative digital solutions.
                Our platform combines intuitive design with powerful features to help farmers increase productivity, reduce waste,
                and make data-driven decisions.
            </p>
        </div>
    </div>

    <!-- Our Mission -->
    <div class="max-w-5xl mx-auto mb-16">
        <h2 class="text-3xl font-bold text-center text-green-800 mb-12">Our Mission</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-3">Enhance Efficiency</h3>
                <p class="text-gray-600">Streamline farm operations through digitization, automation, and smart workflow management.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-3">Promote Sustainability</h3>
                <p class="text-gray-600">Help farmers optimize resource usage, reduce environmental impact, and implement sustainable practices.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-3">Secure Future</h3>
                <p class="text-gray-600">Empower farmers with data security, reliable technology, and future-proof solutions for generations to come.</p>
            </div>
        </div>
    </div>

    <!-- Our Story -->
    <div class="bg-gradient-to-r from-yellow-50 to-green-50 rounded-xl p-8 mb-16">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-3xl font-bold text-center text-green-800 mb-8">Our Story</h2>
            <p class="text-lg text-gray-700 mb-6">
                Agro Vision was born from a simple observation: while technology was transforming industries worldwide, 
                many farms were still relying on outdated methods and manual record-keeping. Our founders, with backgrounds 
                in agricultural science and software development, saw an opportunity to bridge this gap.
            </p>
            <p class="text-lg text-gray-700 mb-6">
                Starting with a small team of passionate individuals, we developed our first farm management prototype in 2022. 
                Working closely with local farmers, we refined our platform to address real-world challenges in livestock management, 
                crop planning, and workforce coordination.
            </p>
            <p class="text-lg text-gray-700">
                Today, Agro Vision serves farming operations of all sizes across the country, continually evolving to meet 
                the changing needs of modern agriculture while staying true to our core mission: making powerful technology 
                accessible to every farmer.
            </p>
        </div>
    </div>

    <!-- Our Team -->
    <div class="max-w-5xl mx-auto mb-16">
        <h2 class="text-3xl font-bold text-center text-green-800 mb-12">Leadership Team</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center group">
                <div class="w-32 h-32 bg-base-200 rounded-full mx-auto mb-4 overflow-hidden transition-transform duration-300 group-hover:scale-105 shadow-lg">
                    <img src="assets/images/1cc6fce5-3d93-4073-a797-ece4083ac417.jpeg" alt="CEO" class="w-full h-full object-cover">
                </div>
                <h3 class="text-xl font-semibold">John Meadows</h3>
                <p class="text-gray-600">CEO & Co-founder</p>
            </div>
            <div class="text-center group">
                <div class="w-32 h-32 bg-base-200 rounded-full mx-auto mb-4 overflow-hidden transition-transform duration-300 group-hover:scale-105 shadow-lg">
                    <img src="assets/images/0ab5818b-972c-4e73-9c6c-de3d25a3f5d7.jpeg" alt="CTO" class="w-full h-full object-cover">
                </div>
                <h3 class="text-xl font-semibold">Sarah Chen</h3>
                <p class="text-gray-600">CTO & Co-founder</p>
            </div>
            <div class="text-center group">
                <div class="w-32 h-32 bg-base-200 rounded-full mx-auto mb-4 overflow-hidden transition-transform duration-300 group-hover:scale-105 shadow-lg">
                    <img src="assets/images/cbb4cc54-9e4d-414c-b82f-5756cebca6d7.jpeg" alt="Chief Agronomist" class="w-full h-full object-cover">
                </div>
                <h3 class="text-xl font-semibold">Miguel Rodriguez</h3>
                <p class="text-gray-600">Chief Agronomist</p>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="bg-primary text-primary-content rounded-xl p-10 text-center max-w-4xl mx-auto shadow-xl">
        <h2 class="text-3xl font-bold mb-4">Ready to transform your farm?</h2>
        <p class="text-xl mb-6 opacity-90">Join thousands of farmers already using Agro Vision to revolutionize their operations.</p>
        <div class="flex justify-center space-x-4 flex-wrap">
            <a href="auth/signup.php" class="btn btn-accent btn-lg">Get Started</a>
            <a href="contactus.php" class="btn btn-outline btn-lg border-2">Contact Us</a>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?> 
 
 