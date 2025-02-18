<?php
include('connection.php');

// Check if the ID is set in the URL
if (isset($_GET['id'])) {
    $itemId = $_GET['id'];

    // Prepare the DELETE statement
    $stmt = $connection->prepare("DELETE FROM item WHERE id = ?");
    $stmt->bind_param("i", $itemId);

    // Execute the statement
    if ($stmt->execute()) {
        // Check if the row was deleted
        if ($stmt->affected_rows > 0) {
            echo "<script>alert('Item deleted successfully!'); window.location.href='item_display.php';</script>";
        } else {
            echo "<script>alert('No item found with the specified ID!'); window.location.href='item_display.php';</script>";
        }
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "<script>alert('No item ID specified!'); window.location.href='item_display.php';</script>";
}

// Close the connection
$connection->close();
?>
