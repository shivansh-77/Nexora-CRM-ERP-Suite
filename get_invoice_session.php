<?php
// Start session
session_start();

// Return the stored form data as JSON
if (isset($_SESSION['invoice_form_data'])) {
    echo json_encode(['success' => true, 'formData' => $_SESSION['invoice_form_data']]);
} else {
    echo json_encode(['success' => false, 'error' => 'No form data in session']);
}
?>
