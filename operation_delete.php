<?php
// Database connection
include('connection.php');

// Check if ID parameter is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = mysqli_real_escape_string($connection, $_GET['id']);

    // Delete the operation record
    $query = "DELETE FROM operations WHERE id = $id";

    if (mysqli_query($connection, $query)) {
        // Success message and redirect
        echo "<script>
            alert('Operation deleted successfully!');
            window.location.href = 'operation_display.php';
        </script>";
    } else {
        // Error message and redirect
        echo "<script>
            alert('Error deleting operation: " . mysqli_error($connection) . "');
            window.location.href = 'operation_display.php';
        </script>";
    }
} else {
    // No ID provided, redirect back
    echo "<script>
        alert('No operation ID provided!');
        window.location.href = 'operation_display.php';
    </script>";
}

// Close connection
mysqli_close($connection);
?>
