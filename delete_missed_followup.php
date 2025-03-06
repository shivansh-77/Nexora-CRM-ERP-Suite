<?php
include('connection.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if ID is provided in the URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize the ID

    // Check if the ID exists in the table before attempting to delete
    $check_query = "SELECT id FROM followup WHERE id = ?";
    $check_stmt = $connection->prepare($check_query);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // ID exists, proceed with deletion
        $delete_query = "DELETE FROM followup WHERE id = ?";
        $delete_stmt = $connection->prepare($delete_query);
        $delete_stmt->bind_param("i", $id);

        if ($delete_stmt->execute()) {
            echo "<script>alert('Follow-up deleted successfully.'); window.location.href='missed_followup.php';</script>";
        } else {
            echo "<script>alert('Error deleting follow-up entry: " . addslashes($delete_stmt->error) . "'); window.location.href='missed_followup.php';</script>";
        }

        $delete_stmt->close(); // Close the prepared statement
    } else {
        // ID does not exist
        echo "<script>alert('Follow-up entry not found!'); window.location.href='missed_followup.php';</script>";
    }

    $check_stmt->close(); // Close the check statement
} else {
    echo "<script>alert('ID not provided.'); window.location.href='missed_followup.php';</script>";
}

// Close the connection
$connection->close();
?>
