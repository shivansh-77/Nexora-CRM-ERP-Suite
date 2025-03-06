<?php
session_start();
include('connection.php'); // Ensure connection.php is correctly included

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize the input

    // Check if the ID exists in the department table before deleting
    $check_query = "SELECT * FROM department WHERE id = $id";
    $check_result = mysqli_query($connection, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $query = "DELETE FROM department WHERE id = $id";
        $result = mysqli_query($connection, $query);

        if ($result) {
            $_SESSION['message'] = "Department deleted successfully.";
        } else {
            $_SESSION['message'] = "Error deleting department: " . mysqli_error($connection);
        }
    } else {
        $_SESSION['message'] = "Department not found.";
    }
} else {
    $_SESSION['message'] = "Invalid request.";
}

header("Location: department_display.php"); // Redirect back to the display page
exit();
?>
