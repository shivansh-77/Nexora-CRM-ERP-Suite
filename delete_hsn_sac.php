<?php
// Database connection
include('connection.php');

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if the ID is passed via the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize the ID

    // Check if the ID exists in the table before attempting to delete
    $check_query = "SELECT id FROM hsn_sac WHERE id = ?";
    $check_stmt = $connection->prepare($check_query);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // ID exists, proceed with deletion
        $delete_query = "DELETE FROM hsn_sac WHERE id = ?";
        $delete_stmt = $connection->prepare($delete_query);
        $delete_stmt->bind_param("i", $id);

        if ($delete_stmt->execute()) {
            echo "<script>alert('Record deleted successfully!'); window.location.href='hsn_sac_display.php';</script>";
        } else {
            echo "<script>alert('Error deleting record: " . addslashes($delete_stmt->error) . "'); window.location.href='hsn_sac_display.php';</script>";
        }

        $delete_stmt->close(); // Close the prepared statement
    } else {
        // ID does not exist
        echo "<script>alert('Record not found!'); window.location.href='hsn_sac_display.php';</script>";
    }

    $check_stmt->close(); // Close the check statement
} else {
    echo "<script>alert('Invalid request!'); window.location.href='hsn_sac_display.php';</script>";
}

mysqli_close($connection);
?>
