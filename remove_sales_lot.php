<?php
// Database connection
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lotNumber = $_POST['lot_number'];
    $invoiceItemId = $_POST['invoice_item_id'];

    // Prepare and execute the delete query
    $stmt = $connection->prepare("DELETE FROM purchase_order_item_lots WHERE lot_trackingid = ? AND invoice_itemid = ?");
    $stmt->bind_param("si", $lotNumber, $invoiceItemId);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$connection->close();
?>
