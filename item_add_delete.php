<?php
session_start();
include('connection.php'); // Include the database connection

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if a valid ID is passed via GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request.");
}

$id = intval($_GET['id']);

// Delete query using prepared statements
$delete_query = "DELETE FROM item_add WHERE id = ?";
$stmt = $connection->prepare($delete_query);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: " . $_SERVER['HTTP_REFERER']); // Redirect to the previous page
    exit();
} else {
    die("Error deleting record: " . $stmt->error);
}

$stmt->close();
mysqli_close($connection);
?>
