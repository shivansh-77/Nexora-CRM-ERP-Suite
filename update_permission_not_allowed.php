<?php
include('connection.php');

if (isset($_GET['fy_code'])) {
    $fy_code = mysqli_real_escape_string($connection, $_GET['fy_code']);

    // Update all permissions for the specified fy_code to 0
    $query = "UPDATE emp_fy_permission SET permission = 0 WHERE fy_code = '$fy_code'";
    if (mysqli_query($connection, $query)) {
        echo "<script>
                alert('Permissions set to not allowed for all the Users!');
                window.location.href = '" . $_SERVER['HTTP_REFERER'] . "';
              </script>";
    } else {
        echo "<script>
                alert('Error updating permissions: " . mysqli_error($connection) . "');
                window.location.href = '" . $_SERVER['HTTP_REFERER'] . "';
              </script>";
    }
} else {
    echo "<script>
            alert('FY Code is not specified.');
            window.location.href = '" . $_SERVER['HTTP_REFERER'] . "';
          </script>";
}
?>
