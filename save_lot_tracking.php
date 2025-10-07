<?php
include 'connection.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $itemId = intval($_POST['item_id']);
    $poId = intval($_POST['purchase_order_id']);
    $lotEntries = json_decode($_POST['lot_entries'], true);

    if (!$itemId || !$poId || !is_array($lotEntries)) {
        throw new Exception('Invalid input data');
    }

    // Start transaction
    $connection->begin_transaction();

    // Save each lot entry
    foreach ($lotEntries as $lot) {
        $quantity = floatval($lot['quantity']);
        $lotNumber = $connection->real_escape_string($lot['lot_number']);
        $expirationDate = $connection->real_escape_string($lot['expiration_date']);

        $stmt = $connection->prepare("INSERT INTO purchase_order_item_lots
                                    (item_id, quantity, lot_number, expiration_date)
                                    VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $itemId, $quantity, $lotNumber, $expirationDate);
        $stmt->execute();
        $stmt->close();
    }

    // Update received quantity in purchase_order_items
    $updateStmt = $connection->prepare("UPDATE purchase_order_items
                                      SET received_qty = received_qty + ?
                                      WHERE id = ?");
    $totalReceived = array_sum(array_column($lotEntries, 'quantity'));
    $updateStmt->bind_param("di", $totalReceived, $itemId);
    $updateStmt->execute();
    $updateStmt->close();

    // Commit transaction
    $connection->commit();

    echo json_encode(['success' => true, 'message' => 'Lot information saved successfully']);
} catch (Exception $e) {
    $connection->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$connection->close();
?>
