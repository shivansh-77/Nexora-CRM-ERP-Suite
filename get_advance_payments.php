<?php
session_start();
include('connection.php');

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$party_no = $data['party_no'];

$query = "SELECT advance_doc_no, pending_amount
          FROM advance_payments
          WHERE party_no = ? AND party_type = 'Customer' AND document_type = 'Advance Payment' AND pending_amount > 0
          ORDER BY date DESC";
$stmt = $connection->prepare($query);
$stmt->bind_param("s", $party_no);
$stmt->execute();
$result = $stmt->get_result();

$advances = [];
while ($row = $result->fetch_assoc()) {
    $advances[] = $row;
}

echo json_encode($advances);
?>
