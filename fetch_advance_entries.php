<?php
include('connection.php');

$client_id = $_GET['client_id'] ?? 0;

$query = "SELECT advance_doc_no, pending_amount
          FROM advance_payments
          WHERE party_no = ? AND party_type = 'Customer' AND document_type = 'Advance Payment' AND pending_amount > 0";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
