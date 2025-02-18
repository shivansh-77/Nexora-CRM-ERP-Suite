<?php
// Include database connection
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the financial year ID and status from the AJAX request
    $id = $_GET['id']; // Use GET because we are using fetch API with query parameters
    $status = $_GET['status'];

    // Prepare the SQL statement to update the is_current status
    $stmt = $connection->prepare("UPDATE financial_years SET is_current = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $id); // Bind the parameters

    if ($stmt->execute()) {
        // If the update was successful, send a success response
        echo json_encode(['message' => 'Status updated successfully']);
    } else {
        // If there was an error, send an error response
        echo json_encode(['message' => 'Error: ' . $stmt->error]);
    }

    // Close the prepared statement
    $stmt->close();
}

// Close the database connection
$connection->close();
?>
