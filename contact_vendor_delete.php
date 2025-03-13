<?php
include('connection.php');

// Check if an ID is provided in the URL
if (isset($_GET['id'])) {
    $contactId = $_GET['id'];

    // Prepare the SQL query to delete the record
    $sql = "DELETE FROM contact_vendor WHERE id = ?";

    // Prepare the statement
    $stmt = $connection->prepare($sql);

    // Check if the prepare was successful
    if (!$stmt) {
        die("Preparation failed: " . $connection->error);
    }

    // Bind the ID parameter
    $stmt->bind_param('i', $contactId);

    // Execute the statement
    if ($stmt->execute()) {
        echo "<script>alert('Record deleted successfully!'); window.location.href = 'contact_vendor_display.php';</script>";
        exit();
    } else {
        echo "Error deleting record: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $connection->close();
} else {
    // If no ID is provided, redirect to the display page
    header("Location: contact_vendor_display.php");
    exit();
}
?>
