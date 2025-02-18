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
    $deleteQuery = "DELETE FROM item_unit WHERE id = $id";

    if (mysqli_query($connection, $deleteQuery)) {
        // Check if the deleted ID is the highest ID in the table
        $maxIdQuery = "SELECT MAX(id) AS max_id FROM item_unit";
        $maxIdResult = mysqli_query($connection, $maxIdQuery);
        $maxIdRow = mysqli_fetch_assoc($maxIdResult);

        // Reset AUTO_INCREMENT if the deleted ID was the last ID
        if ($maxIdRow['max_id'] < $id) {
            $resetAutoIncrement = "ALTER TABLE gst AUTO_INCREMENT = " . ($id);
            mysqli_query($connection, $resetAutoIncrement);
        }

        echo "<script>alert('Record deleted successfully!'); window.location.href='item_unit_display.php';</script>";
    } else {
        echo "<script>alert('Error deleting record: " . mysqli_error($connection) . "'); window.location.href='item_unit_display.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request!'); window.location.href='item_unit_display.php';</script>";
}

mysqli_close($connection);
?>
