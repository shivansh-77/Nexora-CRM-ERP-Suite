<?php
session_start();
include('connection.php');

// Decode the JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate input data
if (!isset($data['id'], $data['amount_paid'], $data['new_pending_amount'], $data['payment_method'], $data['payment_details'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

$id = $data['id'];
$amountPaid = $data['amount_paid'];
$newPendingAmount = $data['new_pending_amount'];
$paymentMethod = $data['payment_method'];
$paymentDetails = $data['payment_details'];
$paymentDate = $data['payment_date']; // Added payment_date

// Validate payment date
if (!strtotime($paymentDate)) {
    $paymentDate = date('Y-m-d'); // Fallback to today if invalid
}

// Fetch invoice_no, vendor_id, and vendor_name from purchase_invoice table
$query_fetch_invoice = "SELECT invoice_no, vendor_id, vendor_name FROM purchase_invoice_cancel WHERE id = ?";
$stmt_fetch = mysqli_prepare($connection, $query_fetch_invoice);

if (!$stmt_fetch) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare fetch statement: ' . mysqli_error($connection)]);
    exit;
}

mysqli_stmt_bind_param($stmt_fetch, 'i', $id);
mysqli_stmt_execute($stmt_fetch);
mysqli_stmt_bind_result($stmt_fetch, $invoice_no, $vendor_id, $vendor_name);

if (!mysqli_stmt_fetch($stmt_fetch)) {
    echo json_encode(['success' => false, 'error' => 'Invoice not found', 'id' => $id]);
    mysqli_stmt_close($stmt_fetch);
    exit;
}

mysqli_stmt_close($stmt_fetch);

// Debug - Check if we got the invoice data
if (!$invoice_no || !$vendor_id) {
    echo json_encode(['success' => false, 'error' => 'Failed to fetch invoice data', 'id' => $id]);
    exit;
}

// Update the pending_amount in the purchase_invoice table
$query_update = "UPDATE purchase_invoice_cancel SET pending_amount = ? WHERE id = ?";
$stmt_update = mysqli_prepare($connection, $query_update);

if (!$stmt_update) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare update statement: ' . mysqli_error($connection)]);
    exit;
}

mysqli_stmt_bind_param($stmt_update, 'di', $newPendingAmount, $id);
$update_result = mysqli_stmt_execute($stmt_update);
$updateSuccess = mysqli_stmt_affected_rows($stmt_update) > 0;
mysqli_stmt_close($stmt_update);

if ($updateSuccess) {
    // Insert record into party_ledger table including payment_date
    $query_insert_ledger = "INSERT INTO party_ledger
                           (ledger_type, party_type, document_type, document_no,
                            amount, party_no, party_name,
                            date, payment_date, payment_method, payment_details)
                           VALUES
                           ('Vendor Ledger', 'Vendor', 'Payment Received', ?,
                            ?, ?, ?,
                            NOW(), ?, ?, ?)";

    $stmt_ledger = mysqli_prepare($connection, $query_insert_ledger);

    if (!$stmt_ledger) {
        echo json_encode(['success' => false, 'error' => 'Prepare statement failed: ' . mysqli_error($connection)]);
        exit;
    }

    // Negate the amountPaid value
    $negativeAmountPaid = -$amountPaid;

    mysqli_stmt_bind_param($stmt_ledger, 'sdissss',
        $invoice_no,
        $negativeAmountPaid, // Use the negated value
        $vendor_id,
        $vendor_name,
        $paymentDate, // Added payment_date parameter
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
