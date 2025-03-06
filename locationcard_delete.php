<?php
include('connection.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize the ID

    // Check if the record exists
    $check_query = "SELECT id FROM location_card WHERE id = ?";
    $check_stmt = $connection->prepare($check_query);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // Record exists, proceed with deletion
        $delete_query = "DELETE FROM location_card WHERE id = ?";
        $delete_stmt = $connection->prepare($delete_query);
        $delete_stmt->bind_param("i", $id);

        if ($delete_stmt->execute()) {
            echo "<script>alert('Location entry deleted successfully.'); window.location.href='locationcard.php';</script>";
        } else {
            echo "<script>alert('Error deleting the location entry: " . addslashes($delete_stmt->error) . "'); window.location.href='locationcard.php';</script>";
        }

        $delete_stmt->close(); // Close the prepared statement
    } else {
        // Record does not exist
        echo "<script>alert('Invalid Location ID'); window.location.href='locationcard.php';</script>";
    }

    $check_stmt->close(); // Close the check statement
} else {
    echo "<script>alert('Invalid request.'); window.location.href='locationcard.php';</script>";
}

// Close the connection
mysqli_close($connection);
?>
