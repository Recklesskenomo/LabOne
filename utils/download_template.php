<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Set the file name and create a file pointer connected to the output stream
$filename = "animal_batch_template_" . date('Y-m-d') . ".csv";

// Set headers to force download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Set column headers
$headers = array(
    'animal_type', 
    'breed', 
    'purpose', 
    'quantity', 
    'registration_date', 
    'animal_name', 
    'notes'
);
fputcsv($output, $headers);

// Example data rows
$row1 = array(
    'Cow',
    'Holstein',
    'Dairy',
    '5',
    date('Y-m-d'),
    'Bessie',
    'Healthy dairy cows'
);
fputcsv($output, $row1);

$row2 = array(
    'Chicken',
    'Rhode Island Red',
    'Egg Production',
    '20',
    date('Y-m-d'),
    '',
    'Free range'
);
fputcsv($output, $row2);

// Close the file pointer
fclose($output);
exit; 