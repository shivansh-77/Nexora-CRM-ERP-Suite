<?php
include('connection.php');

// Check if 'id' is provided in the URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize the ID

    // Check if the ID exists in the table before attempting to delete
    $check_stmt = $connection->prepare("SELECT id FROM login_db WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // ID exists, proceed with deletion
        $delete_stmt = $connection->prepare("DELETE FROM login_db WHERE id = ?");
        $delete_stmt->bind_param("i", $id);

        if ($delete_stmt->execute()) {
            echo "<script>alert('Record deleted successfully!'); window.location.href = 'display.php';</script>";
        } else {
            echo "<script>alert('Error deleting record: " . addslashes($delete_stmt->error) . "'); window.location.href = 'display.php';</script>";
        }

        $delete_stmt->close(); // Close the prepared statement
    } else {
        // ID does not exist
        echo "<script>alert('Record not found!'); window.location.href = 'display.php';</script>";
    }

    $check_stmt->close(); // Close the check statement
} else {
    echo "<script>alert('No ID provided!'); window.location.href = 'display.php';</script>";
}

// Close the connection
$connection->close();
?>
