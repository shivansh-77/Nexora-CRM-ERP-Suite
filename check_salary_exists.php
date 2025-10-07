<?php
include('connection.php');

$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
$month_id = isset($_GET['month_id']) ? intval($_GET['month_id']) : 0;

$query = "SELECT id FROM salary WHERE employee_id = ? AND month_id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("ii", $employee_id, $month_id);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: application/json');
echo json_encode(['exists' => $result->num_rows > 0]);
?>
