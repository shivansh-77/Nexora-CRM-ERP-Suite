<?php
header('Content-Type: application/json; charset=utf-8');
include('connection.php');

$group_id = isset($_POST['task_group_id']) ? (int)$_POST['task_group_id'] : 0;
if ($group_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, task_name AS name FROM tasks WHERE task_group_id = $group_id ORDER BY task_name";
$res = mysqli_query($connection, $sql);

$data = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = ['id' => (int)$row['id'], 'name' => $row['name']];
    }
}
echo json_encode($data);
