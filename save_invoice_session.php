<?php
// Start session
session_start();

// Get the JSON data from the request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Store form data in session
if (isset($data['formData'])) {
    $_SESSION['invoice_form_data'] = $data['formData'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'No form data received']);
}
?>
