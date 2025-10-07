<?php
include('connection.php');

$vendor_id = $_GET['vendor_id'] ?? 0;

$query = "SELECT advance_doc_no, pending_amount
          FROM advance_payments
          WHERE party_no = ? AND party_type = 'Vendor' AND document_type = 'Advance Paid' AND pending_amount > 0";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
