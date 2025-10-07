<?php
include('connection.php');

if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid Task Group ID'); window.location.href='task_group_display.php';</script>";
    exit();
}

$id = intval($_GET['id']);

$query = "DELETE FROM task_group WHERE id=$id";
if (mysqli_query($connection, $query)) {
    echo "<script>alert('Task Group deleted successfully!'); window.location.href='task_group_display.php';</script>";
} else {
    echo "<script>alert('Error deleting task group.'); window.location.href='task_group_display.php';</script>";
}
?>
