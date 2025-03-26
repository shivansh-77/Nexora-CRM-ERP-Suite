<?php
include 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id']);
    $new_qytc = floatval($_POST['new_qytc']);
    $purchase_order_id = intval($_POST['purchase_order_id']);

    try {
        // Update Q.Y.T.C in database
        $update_query = "UPDATE purchase_order_items SET qytc = ? WHERE id = ? AND purchase_order_id = ?";
        $stmt = $connection->prepare($update_query);
        $stmt->bind_param("dii", $new_qytc, $item_id, $purchase_order_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Q.Y.T.C updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update Q.Y.T.C']);
        }

        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$connection->close();
?>
