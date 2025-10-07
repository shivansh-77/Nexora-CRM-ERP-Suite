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
            pi.id as item_id,
            pi.product_id,
            pi.product_name,
            pi.stock,
            i.lot_tracking
        FROM purchase_invoice_cancel_items pi
        JOIN item i ON pi.product_id = i.item_code
        WHERE pi.invoice_id = ?
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
            'stock' => $item['stock'],
            'lot_tracking' => $item['lot_tracking']
        ];

        // Step 3: Skip if lot tracking not required
        if ($item['lot_tracking'] == 0) {
            $debug_info['status'] = 'LOT_TRACKING_NOT_REQUIRED';
            $response['debug'][] = $debug_info;
            continue;
        }

        // Step 4: Get individual lot quantities for this item
        $lot_query = "
            SELECT
                lot_trackingid,
                expiration_date,
                location,
                quantity as assigned_quantity
            FROM cancelled_item_lot
            WHERE
                invoice_id_main = ? AND
                invoice_itemid = ?
        ";

        $lot_stmt = $connection->prepare($lot_query);
        $lot_stmt->bind_param("ii", $invoice_id, $item['item_id']);
        $lot_stmt->execute();
        $lot_result = $lot_stmt->get_result();

        $total_lot_quantity = 0;
        $lot_validation_errors = [];

        while ($lot = $lot_result->fetch_assoc()) {
            $total_lot_quantity += $lot['assigned_quantity'];

            // Check available quantity in item_ledger_history for this specific lot
            $available_qty_query = "
                SELECT COALESCE(SUM(quantity), 0) as available_quantity
                FROM item_ledger_history
                WHERE
                    product_id = ? AND
                    location = ? AND
                    lot_trackingid = ? AND
                    expiration_date = ?
            ";

            $available_qty_stmt = $connection->prepare($available_qty_query);
            $available_qty_stmt->bind_param(
                "ssss",
                $item['product_id'],
                $lot['location'],
                $lot['lot_trackingid'],
                $lot['expiration_date']
            );
            $available_qty_stmt->execute();
            $available_qty_result = $available_qty_stmt->get_result();
            $available_qty_data = $available_qty_result->fetch_assoc();
            $available_qty = $available_qty_data['available_quantity'];

            if ($available_qty == 0) {
                $lot_validation_errors[] = "{$item['product_name']}: Lot {$lot['lot_trackingid']} not found at {$lot['location']}";
            } elseif ($lot['assigned_quantity'] > $available_qty) {
                $shortage = $lot['assigned_quantity'] - $available_qty;
                $lot_validation_errors[] = "{$item['product_name']}: Lot {$lot['lot_trackingid']} at {$lot['location']} has {$shortage} less than assigned";
            }

            $available_qty_stmt->close();
        }

        $debug_info['total_lot_quantity'] = $total_lot_quantity;

        // Quantity comparison (original validation)
        if ($total_lot_quantity < $item['stock']) {
            $response['valid'] = false;
            $shortage = $item['stock'] - $total_lot_quantity;
            $error_msg = "{$item['product_name']}: Need {$shortage} more units";
            $response['errors'][] = $error_msg;
            $debug_info['status'] = "UNDER_QUANTITY ({$shortage} needed)";
        }
        elseif ($total_lot_quantity > $item['stock']) {
            $response['valid'] = false;
            $excess = $total_lot_quantity - $item['stock'];
            $warning_msg = "{$item['product_name']}: {$excess} excess units";
            $response['warnings'][] = $warning_msg;
            $debug_info['status'] = "OVER_QUANTITY ({$excess} extra)";
        }
        else {
            $debug_info['status'] = "VALID";
        }

        // Add lot validation errors if any
        if (!empty($lot_validation_errors)) {
            $response['valid'] = false;
            $response['errors'] = array_merge($response['errors'], $lot_validation_errors);
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
