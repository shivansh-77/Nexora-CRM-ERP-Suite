<?php
// Database connection
include('connection.php');

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if the ID is passed via the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Query to delete the record
    $deleteQuery = "DELETE FROM amc WHERE id = $id";

    if (mysqli_query($connection, $deleteQuery)) {
        echo "<script>alert('Record deleted successfully!'); window.location.href='amc_display.php';</script>";
    } else {
        echo "<script>alert('Error deleting record: " . mysqli_error($connection) . "'); window.location.href='amc_display.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request!'); window.location.href='amc_display.php';</script>";
}

mysqli_close($connection);
?>
