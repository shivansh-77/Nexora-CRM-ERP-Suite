<?php
// Include your database connection file
include 'connection.php';

// Get the invoice_id from the query parameter
$invoice_id = $_GET['invoice_id'];

// Initialize the response array
$response = ['valid' => true, 'errors' => []];

// Fetch entries from purchase_order_item_lots with the same invoice_id_main
$query = "SELECT * FROM purchase_order_item_lots WHERE invoice_id_main = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();

$entries = [];
while ($row = $result->fetch_assoc()) {
    $entries[] = $row;
}

// Process each entry
foreach ($entries as $entry) {
    $lot_trackingid = $entry['lot_trackingid'];
    $expiration_date = $entry['expiration_date'];

    // Fetch sum of quantity from purchase_order_item_lots with the same lot_trackingid and expiration_date
    $query = "SELECT SUM(quantity) as total_quantity FROM purchase_order_item_lots WHERE invoice_id_main = ? AND lot_trackingid = ?";
    $params = [$invoice_id, $lot_trackingid];

    if ($expiration_date) {
        $query .= " AND expiration_date = ?";
        $params[] = $expiration_date;
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $purchase_quantity = $result->fetch_assoc()['total_quantity'];

    // Fetch sum of quantity from item_ledger_history with the same lot_trackingid and expiration_date
    $query = "SELECT SUM(quantity) as total_quantity FROM item_ledger_history WHERE lot_trackingid = ?";
    $params = [$lot_trackingid];

    if ($expiration_date) {
        $query .= " AND expiration_date = ?";
        $params[] = $expiration_date;
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $ledger_quantity = $result->fetch_assoc()['total_quantity'];

    // Compare quantities
    if ($purchase_quantity > $ledger_quantity) {
        $response['valid'] = false;
        $response['errors'][] = "The item '{$entry['product_name']}' with lot number '{$lot_trackingid}' has {$purchase_quantity - $ledger_quantity} quantity less to proceed.";
    }
}

// Return the response as JSON
echo json_encode($response);
?>
