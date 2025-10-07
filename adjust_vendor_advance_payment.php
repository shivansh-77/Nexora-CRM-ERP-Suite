<?php
include('connection.php');

$data = json_decode(file_get_contents("php://input"), true);

$invoice_id = $data['invoice_id'];
$invoice_no = $data['invoice_no'];
$vendor_id = $data['vendor_id'];
$amount = $data['amount'];
$advance_doc_no = $data['advance_doc_no'];
$payment_details = $data['payment_details'];
$payment_date = $data['payment_date'];

// Get vendor_name from purchase invoice
$stmt = $connection->prepare("SELECT vendor_name FROM purchase_invoice WHERE id = ?");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
$invoice = $result->fetch_assoc();
$vendor_name = $invoice['vendor_name'] ?? '';

$connection->begin_transaction();

try {
  // 1. Insert adjustment entry (with negative amount for vendor)
$positive_amount = abs($amount); // Make sure amount is negative for vendor
$insert = $connection->prepare("INSERT INTO advance_payments
  (ledger_type, party_no, party_name, party_type, document_type, document_no, amount, pending_amount, advance_doc_no, payment_method, payment_details, payment_date)
  VALUES ('Vendor Ledger', ?, ?, 'Vendor', 'Advance Adjusted', ?, ?, NULL, ?, 'Advance', ?, ?)");
$insert->bind_param("issdsss", $vendor_id, $vendor_name, $invoice_no, $positive_amount, $advance_doc_no, $payment_details, $payment_date);
$insert->execute();

    // 2. Update original advance entry pending_amount
    $updateAdvance = $connection->prepare("UPDATE advance_payments SET pending_amount = pending_amount - ?
        WHERE advance_doc_no = ? AND party_no = ? AND document_type = 'Advance Paid'");
    $updateAdvance->bind_param("dsi", $amount, $advance_doc_no, $vendor_id);
    $updateAdvance->execute();

    // 3. Update invoice's pending_amount and set advance_used to 1
    $updateInvoice = $connection->prepare("UPDATE purchase_invoice SET pending_amount = pending_amount - ?, advance_used = 1 WHERE id = ?");
    $updateInvoice->bind_param("di", $amount, $invoice_id);
    $updateInvoice->execute();

    $connection->commit();
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    $connection->rollback();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
