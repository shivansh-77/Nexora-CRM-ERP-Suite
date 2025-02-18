<?php
include('connection.php');

// Check if 'id' is provided in the URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize the ID

    // Prepare and execute the delete query
    $stmt = $connection->prepare("DELETE FROM login_db WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Reorder IDs after deletion
        

        echo "<script>alert('Record deleted successfully!'); window.location.href = 'display.php';</script>";
    } else {
        echo "<script>alert('Error deleting record: " . $stmt->error . "'); window.location.href = 'display.php';</script>";
    }

    $stmt->close(); // Close the prepared statement
} else {
    echo "<script>alert('No ID provided!'); window.location.href = 'display.php';</script>";
}

// Close the connection
$connection->close();
?>
