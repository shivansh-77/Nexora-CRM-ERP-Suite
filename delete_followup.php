<?php
include('connection.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if ID is provided in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id']; // Get ID from the URL

    // Prepare the SQL query to delete the follow-up entry
    $sql = "DELETE FROM followup WHERE id = ?";
    $stmt = $connection->prepare($sql);

    if (!$stmt) {
        die("Preparation failed: " . $connection->error);
    }

    // Bind the ID parameter
    $stmt->bind_param("i", $id);

    // Execute the delete query
    if ($stmt->execute()) {
        // Reorder IDs after deletion
        $connection->query("SET @count = 0");
        $connection->query("UPDATE followup SET id = @count := @count + 1");
        $connection->query("ALTER TABLE followup AUTO_INCREMENT = 1");

        // Redirect to followup page with a success message
        echo "<script>alert('Follow-up deleted successfully.'); window.location.href='followup_display.php';</script>";
    } else {
        echo "Error deleting follow-up entry: " . $stmt->error;
    }
} else {
    die("ID not provided.");
}

// Close the connection
$connection->close();
?>
