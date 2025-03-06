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
        die("<script>alert('Preparation failed: " . $connection->error . "'); window.location.href='closed_followup.php';</script>");
    }

    // Bind the ID parameter
    $stmt->bind_param("i", $id);

    // Execute the delete query
    if ($stmt->execute()) {
        // Redirect to follow-up page with a success message
        echo "<script>alert('Follow-up deleted successfully.'); window.location.href='closed_followup.php';</script>";
    } else {
        echo "<script>alert('Error deleting follow-up entry: " . $stmt->error . "'); window.location.href='closed_followup.php';</script>";
    }
} else {
    die("<script>alert('ID not provided.'); window.location.href='closed_followup.php';</script>");
}

// Close the prepared statement
$stmt->close();

// Close the connection
$connection->close();
?>
