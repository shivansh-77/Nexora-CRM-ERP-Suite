<?php
session_start();
include('connection.php');

// Check if the request is authorized (add your own authorization logic)
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

// Get parameters
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$status = isset($_POST['status']) ? mysqli_real_escape_string($connection, $_POST['status']) : '';

if ($id == 0) {
    die("Invalid salary sheet ID");
}

// Update the status
$query = "UPDATE salary SET status = ? WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo "Status updated successfully";
} else {
    echo "Error updating status: " . $connection->error;
}

$stmt->close();
$connection->close();
?>
