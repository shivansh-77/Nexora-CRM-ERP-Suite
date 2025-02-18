<?php
include('connection.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Check if the record exists
    $query = "SELECT * FROM location_card WHERE id = $id";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        // Delete the record
        $deleteQuery = "DELETE FROM location_card WHERE id = $id";
        if (mysqli_query($connection, $deleteQuery)) {
            // Reset the auto-increment value
            $resetQuery = "ALTER TABLE location_card AUTO_INCREMENT =
                          (SELECT IFNULL(MAX(id), 0) + 1 FROM location_card)";
            mysqli_query($connection, $resetQuery);

            echo "<script>alert('Location entry deleted successfully.'); window.location.href='locationcard.php';</script>";
        } else {
            echo "<script>alert('Error deleting the location entry: " . mysqli_error($connection) . "'); window.location.href='locationcard.php';</script>";
        }
    } else {
        echo "<script>alert('Invalid Location ID'); window.location.href='locationcard.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.location.href='locationcard.php';</script>";
}
?>
