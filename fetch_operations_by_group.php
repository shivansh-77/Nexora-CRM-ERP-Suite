<?php
header('Content-Type: application/json; charset=utf-8');
include('connection.php');

$group_id = isset($_POST['operation_group_id']) ? (int)$_POST['operation_group_id'] : 0;
if ($group_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, operation_name AS name FROM operations WHERE operation_group_id = $group_id ORDER BY operation_name";
$res = mysqli_query($connection, $sql);

$data = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = ['id' => (int)$row['id'], 'name' => $row['name']];
    }
}
echo json_encode($data);
