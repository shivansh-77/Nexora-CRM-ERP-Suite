<?php
require_once 'connection.php';

header('Content-Type: application/json');

try {
    // Get the last temp invoice number
    $result = $connection->query("SELECT temp_invoice_no FROM invoice_lot_tracking ORDER BY id DESC LIMIT 1");

    if ($result->num_rows > 0) {
        $lastInvoice = $result->fetch_assoc();
        $lastNumber = preg_replace('/[^0-9]/', '', $lastInvoice['temp_invoice_no']);
        $nextNumber = str_pad((int)$lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
        // No entries yet, start with TEMP0001
        $nextNumber = '0001';
    }

    $tempInvoiceNo = 'TEMP' . $nextNumber;

    echo json_encode([
        'success' => true,
        'temp_invoice_no' => $tempInvoiceNo
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
