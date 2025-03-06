<?php
// Database connection
include('connection.php');

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if the ID is passed via GET and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Invalid request!'); window.location.href='item_category_display.php';</script>";
    exit();
}

$id = intval($_GET['id']);

// Delete query using prepared statements
$deleteQuery = "DELETE FROM item_category WHERE id = ?";
$stmt = $connection->prepare($deleteQuery);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "<script>alert('Record deleted successfully!'); window.location.href='item_category_display.php';</script>";
} else {
    echo "<script>alert('Error deleting record: " . mysqli_error($connection) . "'); window.location.href='item_category_display.php';</script>";
}

$stmt->close();
mysqli_close($connection);
?>
