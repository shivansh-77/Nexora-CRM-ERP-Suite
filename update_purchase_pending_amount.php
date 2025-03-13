<?php
session_start();
include('connection.php');

// Decode the JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate input data
if (!isset($data['id'], $data['amount_paid'], $data['new_pending_amount'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$id = $data['id'];
$amountPaid = $data['amount_paid'];
$newPendingAmount = $data['new_pending_amount'];

// Fetch invoice_no, vendor_id, and vendor_name from purchase_invoice table
$query_fetch_invoice = "SELECT invoice_no, vendor_id, vendor_name FROM purchase_invoice WHERE id = ?";
$stmt_fetch = mysqli_prepare($connection, $query_fetch_invoice);

if (!$stmt_fetch) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare fetch statement']);
    exit;
}

mysqli_stmt_bind_param($stmt_fetch, 'i', $id);
mysqli_stmt_execute($stmt_fetch);
mysqli_stmt_bind_result($stmt_fetch, $invoice_no, $vendor_id, $vendor_name);

if (!mysqli_stmt_fetch($stmt_fetch)) {
    echo json_encode(['success' => false, 'message' => 'Invoice not found']);
    mysqli_stmt_close($stmt_fetch);
    exit;
}

mysqli_stmt_close($stmt_fetch);

// Update the pending_amount in the purchase_invoice table
$query_update = "UPDATE purchase_invoice SET pending_amount = ? WHERE id = ?";
$stmt_update = mysqli_prepare($connection, $query_update);

if (!$stmt_update) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare update statement']);
    exit;
}

mysqli_stmt_bind_param($stmt_update, 'di', $newPendingAmount, $id);
mysqli_stmt_execute($stmt_update);
$updateSuccess = mysqli_stmt_affected_rows($stmt_update) > 0;
mysqli_stmt_close($stmt_update);

if ($updateSuccess) {
    // Insert record into party_ledger table including vendor_id (party_no) and vendor_name (party_name)
    $query_insert_ledger = "INSERT INTO party_ledger (ledger_type, party_type, document_type, document_no, amount, party_no, party_name, date)
                            VALUES ('Vendor Ledger', 'Vendor', 'Payment Paid', ?, ?, ?, ?, NOW())";
    $stmt_ledger = mysqli_prepare($connection, $query_insert_ledger);

    if (!$stmt_ledger) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare ledger insert statement']);
        exit;
    }

    mysqli_stmt_bind_param($stmt_ledger, 'sdis', $invoice_no, $amountPaid, $vendor_id, $vendor_name);
    mysqli_stmt_execute($stmt_ledger);
    mysqli_stmt_close($stmt_ledger);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update pending amount']);
}

mysqli_close($connection);
?>
