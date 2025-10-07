<?php
include('connection.php');

if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid Operation Group ID'); window.location.href='operation_group_display.php';</script>";
    exit();
}

$id = intval($_GET['id']);

$query = "DELETE FROM operation_group WHERE id=$id";
if (mysqli_query($connection, $query)) {
    echo "<script>alert('Operation Group deleted successfully!'); window.location.href='operation_group_display.php';</script>";
} else {
    echo "<script>alert('Error deleting operation group.'); window.location.href='operation_group_display.php';</script>";
}
?>
