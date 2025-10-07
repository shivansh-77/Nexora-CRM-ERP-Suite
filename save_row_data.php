<?php
session_start();
require_once 'connection.php';

$input = json_decode(file_get_contents('php://input'), true);

$rowId = $input['row_id'] ?? 0;
$itemCode = $input['item_code'] ?? '';
$stock = $input['stock'] ?? 0;
$unitValue = $input['unit_value'] ?? 1;
$tempInvoiceNo = $input['temp_invoice_no'] ?? '';

// Validate input
if (empty($itemCode) || empty($tempInvoiceNo)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Store in session
if (!isset($_SESSION['temp_invoice_data']['items'][$rowId])) {
    $_SESSION['temp_invoice_data']['items'][$rowId] = [
        'item_code' => $itemCode,
        'stock' => $stock,
        'unit_value' => $unitValue,
        'lot_data' => []
    ];
}

echo json_encode(['success' => true]);
?>
