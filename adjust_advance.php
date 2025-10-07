<?php
include('connection.php'); // Your database connection file

header('Content-Type: application/json');

$response = ['success' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $invoice_id = isset($input['invoice_id']) ? (int)$input['invoice_id'] : 0;
    $client_id = isset($input['client_id']) ? (int)$input['client_id'] : 0;
    $invoice_no = isset($input['invoice_no']) ? $input['invoice_no'] : '';
    $client_name = isset($input['client_name']) ? $input['client_name'] : '';
    $selected_advance_id = isset($input['selected_advance_id']) ? (int)$input['selected_advance_id'] : 0;
    $selected_advance_doc_no = isset($input['selected_advance_doc_no']) ? $input['selected_advance_doc_no'] : '';
    $amount_to_adjust = isset($input['amount_to_adjust']) ? (float)$input['amount_to_adjust'] : 0.0;
    $payment_details = isset($input['payment_details']) ? $input['payment_details'] : '';
    $payment_date = isset($input['payment_date']) ? $input['payment_date'] : date('Y-m-d');

    if ($invoice_id > 0 && $client_id > 0 && $selected_advance_id > 0 && $amount_to_adjust > 0) {
        // Start transaction
        $connection->begin_transaction();

        try {
            // Step 1: Insert new 'Advance Adjusted' record
            $stmt1 = $connection->prepare("INSERT INTO advance_payments (ledger_type, party_no, party_name, party_type, document_type, document_no, amount, pending_amount, date, payment_method, payment_details, payment_date, advance_doc_no) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, CURDATE(), ?, ?, ?, ?)");
            $ledger_type = 'Customer Ledger';
            $party_type = 'Customer';
            $document_type = 'Advance Adjusted';
            $amount_negative = -$amount_to_adjust; // Amount is negative for adjustment
            $payment_method = 'Advance';
            $stmt1->bind_param("sissssssss", $ledger_type, $client_id, $client_name, $party_type, $document_type, $invoice_no, $amount_negative, $payment_method, $payment_details, $payment_date, $selected_advance_doc_no);
            $stmt1->execute();
            $stmt1->close();

            // Step 2: Update original 'Advance Payment' record
            $stmt2 = $connection->prepare("UPDATE advance_payments SET pending_amount = pending_amount - ? WHERE id = ? AND document_type = 'Advance Payment'");
            $stmt2->bind_param("di", $amount_to_adjust, $selected_advance_id);
            $stmt2->execute();
            $stmt2->close();

            // Step 3: Update invoice pending_amount
            $stmt3 = $connection->prepare("UPDATE invoices SET pending_amount = pending_amount - ? WHERE id = ?");
            $stmt3->bind_param("di", $amount_to_adjust, $invoice_id);
            $stmt3->execute();
            $stmt3->close();

            // Commit transaction
            $connection->commit();
            $response['success'] = true;

        } catch (mysqli_sql_exception $e) {
            // Rollback transaction on error
            $connection->rollback();
            $response['error'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['error'] = 'Missing or invalid input parameters.';
    }
} else {
    $response['error'] = 'Invalid request method.';
}

echo json_encode($response);
$connection->close();
?>
