<?php
// Include database connection
include('connection.php');

if (!$connection) {
    die("<p style='color:red;'>Database connection failed: " . mysqli_connect_error() . "</p>");
}

// Check if ID is set and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<p style='color:red;'>Error: Invalid or missing ID.</p>");
}

$id = intval($_GET['id']); // Convert to integer for security

// Use a prepared statement to delete the record
$stmt = $connection->prepare("DELETE FROM lead_sourc WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Redirect to lead_source_display.php after successful deletion
    header("Location: lead_source_display.php");
    exit();
} else {
    echo "<p style='color:red;'>Error deleting record: " . $stmt->error . "</p>";
}

// Close the statement and database connection
$stmt->close();
$connection->close();
?>
