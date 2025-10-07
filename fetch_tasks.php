<?php
include('connection.php');

if (!isset($_POST['task_group_id']) || empty($_POST['task_group_id'])) {
    echo '<option value="">Select Task</option>';
    exit;
}

$group_id = (int) $_POST['task_group_id'];
$q = "SELECT id, task_name FROM tasks WHERE task_group_id = $group_id ORDER BY task_name";
$res = mysqli_query($connection, $q);

if ($res && mysqli_num_rows($res) > 0) {
    echo '<option value="">Select Task</option>';
    while ($row = mysqli_fetch_assoc($res)) {
        $id = htmlspecialchars($row['id']);
        $name = htmlspecialchars($row['task_name']);
        echo "<option value=\"$id\">$name</option>";
    }
} else {
    echo '<option value="">No tasks found</option>';
}
