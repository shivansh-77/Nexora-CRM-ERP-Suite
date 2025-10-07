<?php
include 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id']);
    $new_qytc = floatval($_POST['new_qytc']);
    $purchase_order_id = intval($_POST['purchase_order_id']);
    $received_qty = isset($_POST['received_qty']) ? floatval($_POST['received_qty']) : 0;

    try {
        // Start transaction
        $connection->begin_transaction();

        // First get current received_qty and po_invoice values
        $select_query = "SELECT received_qty, po_invoice FROM purchase_order_items WHERE id = ? AND purchase_order_id = ?";
        $select_stmt = $connection->prepare($select_query);
        $select_stmt->bind_param("ii", $item_id, $purchase_order_id);
        $select_stmt->execute();
        $result = $select_stmt->get_result();
        $current_values = $result->fetch_assoc();
        $select_stmt->close();

        if (!$current_values) {
            throw new Exception("Item not found");
        }

        // Calculate new values
        $new_received_qty = $current_values['received_qty'] + $received_qty;
        $new_po_invoice = $current_values['po_invoice'] + $received_qty;

        // Update all three fields (qytc, received_qty, and po_invoice)
        $update_query = "UPDATE purchase_order_items
                        SET qytc = ?,
                            received_qty = ?,
                            po_invoice = ?
                        WHERE id = ? AND purchase_order_id = ?";
        $stmt = $connection->prepare($update_query);
        $stmt->bind_param("ddiii", $new_qytc, $new_received_qty, $new_po_invoice, $item_id, $purchase_order_id);

        if ($stmt->execute()) {
            $connection->commit();
            echo json_encode(['success' => true, 'message' => 'Q.Y.T.C and received quantities updated successfully']);
        } else {
            $connection->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to update quantities']);
        }

        $stmt->close();
    } catch (Exception $e) {
        $connection->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$connection->close();
?>
