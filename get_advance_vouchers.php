<?php
include('connection.php');

$ledger_type = $_POST['ledger_type'];
$party_no = intval($_POST['party_no']);

$party_type = ($ledger_type == 'Customer Ledger') ? 'Customer' : 'Vendor';

// Query to get advance vouchers with pending amounts
$query = "SELECT advance_doc_no, pending_amount
          FROM advance_payments
          WHERE party_no = ? AND party_type = ? AND document_type = ? AND pending_amount > 0
          ORDER BY advance_doc_no DESC";

$document_type = ($party_type == 'Customer') ? 'Advance Payment' : 'Advance Paid';

$stmt = $connection->prepare($query);
$stmt->bind_param("iss", $party_no, $party_type, $document_type);
$stmt->execute();
$result = $stmt->get_result();

$advance_vouchers = [];
while ($row = $result->fetch_assoc()) {
    $advance_vouchers[] = [
        'advance_doc_no' => $row['advance_doc_no'],
        'pending_amount' => floatval($row['pending_amount'])
    ];
}

header('Content-Type: application/json');
echo json_encode($advance_vouchers);
?>
