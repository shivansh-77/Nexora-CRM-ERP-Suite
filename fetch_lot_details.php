<?php
include 'connection.php';

if (isset($_POST['item_id'])) {
    $item_id = intval($_POST['item_id']);

    // Fetch lot details based on invoice_id
    $lot_query = "SELECT quantity, lot_number, expiration_date FROM purchase_order_item_lots WHERE invoice_id = ?";
    $stmt_lot = $connection->prepare($lot_query);
    $stmt_lot->bind_param("i", $item_id);
    $stmt_lot->execute();
    $lot_result = $stmt_lot->get_result();

    $lots = [];
    while ($row = $lot_result->fetch_assoc()) {
        $lots[] = $row;
    }

    echo json_encode($lots);

    $stmt_lot->close();
    $connection->close();
} else {
    echo json_encode(['error' => 'Invalid item ID']);
}
?>
