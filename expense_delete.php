<?php
session_start();
include('connection.php');

if (!$connection) {
    echo "<script>alert('Database connection failed!'); window.location.href='expense_display.php';</script>";
    exit();
}

// Check if the ID is passed via the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']); // Ensure ID is an integer

    // Prepare the delete query
    $stmt = $connection->prepare("DELETE FROM expense WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<script>alert('Record deleted successfully!'); window.location.href='expense_display.php';</script>";
        } else {
            echo "<script>alert('No record found with this ID!'); window.location.href='expense_display.php';</script>";
        }
    } else {
        echo "<script>alert('Error deleting record. It may be referenced elsewhere.'); window.location.href='expense_display.php';</script>";
    }

    $stmt->close();
} else {
    echo "<script>alert('Invalid request!'); window.location.href='expense_display.php';</script>";
}

mysqli_close($connection);
?>
