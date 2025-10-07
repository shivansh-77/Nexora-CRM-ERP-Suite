<?php
include('connection.php');

if (!isset($_POST['operation_group_id']) || empty($_POST['operation_group_id'])) {
    echo '<option value="">Select Operation</option>';
    exit;
}

$group_id = (int) $_POST['operation_group_id'];
$q = "SELECT id, operation_name FROM operations WHERE operation_group_id = $group_id ORDER BY operation_name";
$res = mysqli_query($connection, $q);

if ($res && mysqli_num_rows($res) > 0) {
    echo '<option value="">Select Operation</option>';
    while ($row = mysqli_fetch_assoc($res)) {
        $id = htmlspecialchars($row['id']);
        $name = htmlspecialchars($row['operation_name']);
        echo "<option value=\"$id\">$name</option>";
    }
} else {
    echo '<option value="">No operations found</option>';
}
