<?php
include('connection.php');

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if the ID is set and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Invalid item ID!'); window.location.href='item_display.php';</script>";
    exit();
}

$itemId = intval($_GET['id']); // Ensure it's an integer

// Prepare the DELETE statement
$stmt = $connection->prepare("DELETE FROM item WHERE id = ?");
$stmt->bind_param("i", $itemId);

if ($stmt->execute()) {
    // Check if any row was actually deleted
    if ($stmt->affected_rows > 0) {
        echo "<script>alert('Item deleted successfully!'); window.location.href='item_display.php';</script>";
    } else {
        echo "<script>alert('No item found with the specified ID!'); window.location.href='item_display.php';</script>";
    }
} else {
    echo "<script>alert('Error deleting item: " . $stmt->error . "'); window.location.href='item_display.php';</script>";
}

$stmt->close();
$connection->close();
?>
