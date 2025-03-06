<?php
// Database connection
include('connection.php');

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if the ID is set and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Invalid item ID!'); window.location.href='item_unit_display.php';</script>";
    exit();
}

$id = intval($_GET['id']); // Convert to integer for security

// Prepare the DELETE query
$stmt = $connection->prepare("DELETE FROM item_unit WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Check if any row was actually deleted
    if ($stmt->affected_rows > 0) {
        echo "<script>alert('Record deleted successfully!'); window.location.href='item_unit_display.php';</script>";
    } else {
        echo "<script>alert('No record found with the specified ID!'); window.location.href='item_unit_display.php';</script>";
    }
} else {
    echo "<script>alert('Error deleting record: " . $stmt->error . "'); window.location.href='item_unit_display.php';</script>";
}

// Close the statement and connection
$stmt->close();
$connection->close();
?>
