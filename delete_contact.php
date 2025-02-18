<?php
include('connection.php');

// Check if 'id' is provided in the URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize the ID

    // Prepare and execute the delete query for followup entries first
    $stmt_followup = $connection->prepare("DELETE FROM followup WHERE contact_id = ?");
    $stmt_followup->bind_param("i", $id);

    if (!$stmt_followup->execute()) {
        echo "<script>alert('Error deleting followup records: " . $stmt_followup->error . "'); window.location.href = 'contact_display.php';</script>";
        $stmt_followup->close(); // Close the prepared statement
        exit();
    }
    $stmt_followup->close(); // Close the prepared statement

    // Prepare and execute the delete query for the contact
    $stmt_contact = $connection->prepare("DELETE FROM contact WHERE id = ?");
    $stmt_contact->bind_param("i", $id);

    if ($stmt_contact->execute()) {
        // Reorganize IDs after successful deletion
        $connection->query("SET @autoid := 0;");
        $connection->query("UPDATE contact SET id = (@autoid := @autoid + 1) ORDER BY id;");
        $connection->query("ALTER TABLE contact AUTO_INCREMENT = 1;");

        echo "<script>alert('Record deleted successfully!'); window.location.href = 'contact_display.php';</script>";
    } else {
        echo "<script>alert('Error deleting record: " . $stmt_contact->error . "'); window.location.href = 'contact_display.php';</script>";
    }

    $stmt_contact->close(); // Close the prepared statement
} else {
    echo "<script>alert('No ID provided!'); window.location.href = 'contact_display.php';</script>";
}

// Close the connection
$connection->close();
?>
