<?php
session_start();
include('connection.php');

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];
$amountPaid = $data['amount_paid'];
$newPendingAmount = $data['new_pending_amount'];

// Fetch invoice_no, reference_invoice_no, client_id, and client_name from invoices table
$query_fetch_invoice = "SELECT invoice_no, reference_invoice_no, client_id, client_name FROM invoices WHERE id = ?";
$stmt_fetch = mysqli_prepare($connection, $query_fetch_invoice);
mysqli_stmt_bind_param($stmt_fetch, 'i', $id);
mysqli_stmt_execute($stmt_fetch);
mysqli_stmt_bind_result($stmt_fetch, $invoice_no, $reference_invoice_no, $client_id, $client_name);
mysqli_stmt_fetch($stmt_fetch);
mysqli_stmt_close($stmt_fetch);

// Update the pending_amount in the invoices table
$query_update = "UPDATE invoices SET pending_amount = ? WHERE id = ?";
$stmt_update = mysqli_prepare($connection, $query_update);
mysqli_stmt_bind_param($stmt_update, 'di', $newPendingAmount, $id);
mysqli_stmt_execute($stmt_update);
$updateSuccess = mysqli_stmt_affected_rows($stmt_update) > 0;
mysqli_stmt_close($stmt_update);

if ($updateSuccess) {
    // Insert record into party_ledger table including client_id (party_no) and client_name (party_name)
    $query_insert_ledger = "INSERT INTO party_ledger (ledger_type, party_type, document_type, document_no, amount, ref_doc_no, party_no, party_name, date)
                            VALUES ('Customer Ledger', 'Customer', 'Payment Received', ?, ?, ?, ?, ?, NOW())";
    $stmt_ledger = mysqli_prepare($connection, $query_insert_ledger);
    mysqli_stmt_bind_param($stmt_ledger, 'sdsis', $invoice_no, $amountPaid, $reference_invoice_no, $client_id, $client_name);
    mysqli_stmt_execute($stmt_ledger);
    mysqli_stmt_close($stmt_ledger);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

mysqli_close($connection);
?>
