<?php
include('connection.php');

$data = json_decode(file_get_contents("php://input"), true);

$invoice_id = $data['invoice_id'];
$invoice_no = $data['invoice_no'];
$client_id = $data['client_id'];
$amount = $data['amount'];
$advance_doc_no = $data['advance_doc_no'];
$payment_details = $data['payment_details'];
$payment_date = $data['payment_date'];

// Get client_name from invoice
$stmt = $connection->prepare("SELECT client_name FROM invoices WHERE id = ?");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
$invoice = $result->fetch_assoc();
$client_name = $invoice['client_name'] ?? '';

$connection->begin_transaction();

try {
    // 1. Insert adjustment entry (with negative amount)
    $negative_amount = -abs($amount); // Make sure amount is negative
    $insert = $connection->prepare("INSERT INTO advance_payments
        (ledger_type, party_no, party_name, party_type, document_type, document_no, amount, pending_amount, advance_doc_no, payment_method, payment_details, payment_date)
        VALUES ('Customer Ledger', ?, ?, 'Customer', 'Advance Adjusted', ?, ?, NULL, ?, 'Advance', ?, ?)");
    $insert->bind_param("issdsss", $client_id, $client_name, $invoice_no, $negative_amount, $advance_doc_no, $payment_details, $payment_date);
    $insert->execute();

    // 2. Update original advance entry pending_amount
    $updateAdvance = $connection->prepare("UPDATE advance_payments SET pending_amount = pending_amount - ?
        WHERE advance_doc_no = ? AND party_no = ? AND document_type = 'Advance Payment'");
    $updateAdvance->bind_param("dsi", $amount, $advance_doc_no, $client_id);
    $updateAdvance->execute();

    // 3. Update invoice's pending_amount and set advance_used to 1
    $updateInvoice = $connection->prepare("UPDATE invoices SET pending_amount = pending_amount - ?, advance_used = 1 WHERE id = ?");
    $updateInvoice->bind_param("di", $amount, $invoice_id);
    $updateInvoice->execute();

    $connection->commit();
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    $connection->rollback();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
