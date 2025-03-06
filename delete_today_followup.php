<?php
include('connection.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if ID is provided in the URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize the ID

    // Prepare the SQL query to delete the follow-up entry
    $sql = "DELETE FROM followup WHERE id = ?";
    $stmt = $connection->prepare($sql);

    if (!$stmt) {
        echo "<script>alert('Error preparing statement.'); window.location.href='today_followup.php';</script>";
        exit();
    }

    // Bind the ID parameter
    $stmt->bind_param("i", $id);

    // Execute the delete query
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Redirect to followup page with a success message
            echo "<script>alert('Follow-up deleted successfully.'); window.location.href='today_followup.php';</script>";
        } else {
            // No rows affected means the ID was not found
            echo "<script>alert('No follow-up found with this ID.'); window.location.href='today_followup.php';</script>";
        }
    } else {
        // Handle deletion error (e.g., foreign key constraint)
        echo "<script>alert('Error deleting follow-up. It may be referenced elsewhere.'); window.location.href='today_followup.php';</script>";
    }

    // Close the prepared statement
    $stmt->close();
} else {
    echo "<script>alert('ID not provided.'); window.location.href='today_followup.php';</script>";
}

// Close the connection
$connection->close();
?>
