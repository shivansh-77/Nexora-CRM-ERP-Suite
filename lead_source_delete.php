<?php
// Include database connection
include('connection.php');

// Check if an ID is provided in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete the record from the database
    $query = "DELETE FROM lead_sourc WHERE id = $id";
    if (mysqli_query($connection, $query)) {
        // Redirect to the lead source display page after successful deletion
        header("Location: lead_source_display.php");
        exit();
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($connection) . "</p>";
    }
} else {
    echo "<p style='color:red;'>Error: No ID provided.</p>";
}

// Close the database connection
mysqli_close($connection);
?>
