<?php
session_start();
include('connection.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Ensure the ID is an integer

    $query = "DELETE FROM designation WHERE id = ?";
    $stmt = mysqli_prepare($connection, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "Designation deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting designation: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Database error: " . mysqli_error($connection);
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: designation_display.php"); // Redirect to designation display page
exit();
?>
