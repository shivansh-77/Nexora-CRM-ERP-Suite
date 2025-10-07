<?php
session_start();
include('connection.php');

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];
$amountPaid = $data['amount_paid'];
$newPendingAmount = $data['new_pending_amount'];
$paymentMethod = $data['payment_method'];
$paymentDetails = $data['payment_details'];
$paymentDate = $data['payment_date'];

// Validate payment date
if (!strtotime($paymentDate)) {
    $paymentDate = date('Y-m-d'); // Fallback to today if invalid
}

// Debug - Log the amountPaid value
error_log("Amount Paid: " . $amountPaid);

// Fetch invoice_no, reference_invoice_no, client_id, and client_name from invoices table
$query_fetch_invoice = "SELECT invoice_no, reference_invoice_no, client_id, client_name FROM invoices_cancel WHERE id = ?";
$stmt_fetch = mysqli_prepare($connection, $query_fetch_invoice);
mysqli_stmt_bind_param($stmt_fetch, 'i', $id);
mysqli_stmt_execute($stmt_fetch);
mysqli_stmt_bind_result($stmt_fetch, $invoice_no, $reference_invoice_no, $client_id, $client_name);
mysqli_stmt_fetch($stmt_fetch);
mysqli_stmt_close($stmt_fetch);

// Debug - Check if we got the invoice data
if (!$invoice_no || !$client_id) {
    echo json_encode(['success' => false, 'error' => 'Failed to fetch invoice data', 'id' => $id]);
    exit;
}

// Update the pending_amount in the invoices table
$query_update = "UPDATE invoices_cancel SET pending_amount = ? WHERE id = ?";
$stmt_update = mysqli_prepare($connection, $query_update);
mysqli_stmt_bind_param($stmt_update, 'di', $newPendingAmount, $id);
$update_result = mysqli_stmt_execute($stmt_update);
$updateSuccess = mysqli_stmt_affected_rows($stmt_update) > 0;
mysqli_stmt_close($stmt_update);

if ($updateSuccess) {
    // Insert record into party_ledger table including payment date
    $query_insert_ledger = "INSERT INTO party_ledger
                          (ledger_type, party_type, document_type, document_no,
                           amount, ref_doc_no, party_no, party_name,
                           date, payment_date, payment_method, payment_details)
                          VALUES
                          ('Customer Ledger', 'Customer', 'Payment Paid', ?,
                           ?, ?, ?, ?,
                           NOW(), ?, ?, ?)";

    $stmt_ledger = mysqli_prepare($connection, $query_insert_ledger);

    if (!$stmt_ledger) {
        echo json_encode(['success' => false, 'error' => 'Prepare statement failed: ' . mysqli_error($connection)]);
        exit;
    }

    // Ensure amountPaid is positive
    $positiveAmountPaid = abs($amountPaid);
    error_log("Positive Amount Paid: " . $positiveAmountPaid);

    mysqli_stmt_bind_param($stmt_ledger, 'sdsissss',
        $invoice_no,
        $positiveAmountPaid, // Use the positive value
        $reference_invoice_no,
        $client_id,
        $client_name,
        $paymentDate,
        $paymentMethod,
        $paymentDetails
    );

    $ledger_result = mysqli_stmt_execute($stmt_ledger);

    if (!$ledger_result) {
        echo json_encode(['success' => false, 'error' => 'Ledger insert failed: ' . mysqli_stmt_error($stmt_ledger)]);
        exit;
    }

    mysqli_stmt_close($stmt_ledger);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update invoice', 'update_result' => $update_result]);
}

mysqli_close($connection);

?>
