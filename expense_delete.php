<?php
session_start();
include('connection.php');

// Check if the ID is passed via the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Query to delete the record
    $deleteQuery = "DELETE FROM expense WHERE id = ?";
    $stmt = $connection->prepare($deleteQuery);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Check if the deleted ID is the highest ID in the table
        $maxIdQuery = "SELECT MAX(id) AS max_id FROM expense";
        $maxIdResult = mysqli_query($connection, $maxIdQuery);
        $maxIdRow = mysqli_fetch_assoc($maxIdResult);

        // Reset AUTO_INCREMENT if the deleted ID was the last ID
        if ($maxIdRow['max_id'] < $id) {
            $resetAutoIncrement = "ALTER TABLE expense_tracker AUTO_INCREMENT = " . ($id);
            mysqli_query($connection, $resetAutoIncrement);
        }

        echo "<script>alert('Record deleted successfully!'); window.location.href='expense_display.php';</script>";
    } else {
        echo "<script>alert('Error deleting record: " . mysqli_error($connection) . "'); window.location.href='expense_display.php';</script>";
    }

    $stmt->close();
} else {
    echo "<script>alert('Invalid request!'); window.location.href='expense_display.php';</script>";
}

mysqli_close($connection);
?>
