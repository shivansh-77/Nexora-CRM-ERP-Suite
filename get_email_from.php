<?php
// This file provides the sender's email address and name for the frontend.
// In a real application, you might fetch this from a database or a secure configuration.

header('Content-Type: application/json');

// Extracting from your forgot_password.php
$from_email = 'anubhavs777779@gmail.com';
$from_name = 'Shivansh';

echo json_encode([
    'from_email' => $from_email,
    'from_name' => $from_name
]);
?>
