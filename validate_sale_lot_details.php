<?php
include 'connection.php';

header('Content-Type: application/json');

if (isset($_GET['invoice_id'])) {
    $invoice_id = intval($_GET['invoice_id']);
    $response = [
        'valid' => true,
        'errors' => [],
        'warnings' => [],
        'debug' => []
    ];

    // Step 1: Get all invoice items
    $items_query = "
        SELECT
            ii.id as item_id,
            ii.product_id,
            ii.product_name,
            ii.quantity,
            i.lot_tracking
        FROM invoice_items ii
        JOIN item i ON ii.product_id = i.item_code
        WHERE ii.invoice_id = ?
    ";

    $stmt = $connection->prepare($items_query);
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $items_result = $stmt->get_result();

    while ($item = $items_result->fetch_assoc()) {
        $debug_info = [
            'item_id' => $item['item_id'],
            'product_id' => $item['product_id'],
            'product_name' => $item['product_name'],
            'quantity' => $item['quantity'],
            'lot_tracking' => $item['lot_tracking']
        ];

        // Step 3: Skip if lot tracking not required
        if ($item['lot_tracking'] == 0) {
            $debug_info['status'] = 'LOT_TRACKING_NOT_REQUIRED';
            $response['debug'][] = $debug_info;
            continue;
        }

        // Step 4: Get lot quantities for this item
        $lot_query = "
            SELECT SUM(quantity) as total_quantity
            FROM purchase_order_item_lots
            WHERE
                invoice_id_main = ? AND
                invoice_itemid = ?
        ";

        $lot_stmt = $connection->prepare($lot_query);
        $lot_stmt->bind_param("ii", $invoice_id, $item['item_id']);
        $lot_stmt->execute();
        $lot_result = $lot_stmt->get_result();
        $lot_data = $lot_result->fetch_assoc();

        $total_lot_quantity = $lot_data['total_quantity'] ?? 0;
        $debug_info['total_lot_quantity'] = $total_lot_quantity;

        // Quantity comparison (using quantity instead of stock for sales)
        if ($total_lot_quantity < $item['quantity']) {
            $response['valid'] = false;
            $shortage = $item['quantity'] - $total_lot_quantity;
            $error_msg = "{$item['product_name']}: Need {$shortage} more units";
            $response['errors'][] = $error_msg;
            $debug_info['status'] = "UNDER_QUANTITY ({$shortage} needed)";
        }
        elseif ($total_lot_quantity > $item['quantity']) {
            $response['valid'] = false;
            $excess = $total_lot_quantity - $item['quantity'];
            $warning_msg = "{$item['product_name']}: {$excess} excess units";
            $response['warnings'][] = $warning_msg;
            $debug_info['status'] = "OVER_QUANTITY ({$excess} extra)";
        }
        else {
            $debug_info['status'] = "VALID";
        }

        $response['debug'][] = $debug_info;
        $lot_stmt->close();
    }

    $stmt->close();
    echo json_encode($response);
} else {
    echo json_encode([
        'valid' => false,
        'message' => 'Invoice ID not provided'
    ]);
}

$connection->close();
?>
