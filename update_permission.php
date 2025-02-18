<?php
// Database connection
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $permission = $_POST['permission'];

    // Update query
    $query = "UPDATE emp_fy_permission SET permission = ? WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ii", $permission, $id);

    if ($stmt->execute()) {
        echo "Permission updated successfully";
    } else {
        echo "Failed to update permission";
    }

    $stmt->close();
}

$connection->close();
?>
