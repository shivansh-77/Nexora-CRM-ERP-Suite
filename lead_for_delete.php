<?php
// Include database connection
include('connection.php');

if (!$connection) {
    die("<p style='color:red;'>Database connection failed: " . mysqli_connect_error() . "</p>");
}

// Check if an ID is provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<p style='color:red;'>Error: Invalid or missing ID.</p>");
}

$id = intval($_GET['id']); // Convert to integer for security

// Use a prepared statement to delete the record
$stmt = $connection->prepare("DELETE FROM lead_for WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Redirect to the lead_for_display.php page after successful deletion
    header("Location: lead_for_display.php");
    exit();
} else {
    echo "<p style='color:red;'>Error deleting record: " . $stmt->error . "</p>";
}

// Close the statement and connection
$stmt->close();
$connection->close();
?>
