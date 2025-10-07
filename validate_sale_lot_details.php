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

    // Fetch shipper_location_code from invoices table
    $shipper_query = "
        SELECT shipper_location_code
        FROM invoices
        WHERE id = ?
    ";

    $shipper_stmt = $connection->prepare($shipper_query);
    $shipper_stmt->bind_param("i", $invoice_id);
    $shipper_stmt->execute();
    $shipper_result = $shipper_stmt->get_result();
    $shipper_data = $shipper_result->fetch_assoc();

    if (!$shipper_data) {
        echo json_encode([
            'valid' => false,
            'message' => 'Shipper location code not found for the given invoice ID'
        ]);
        $shipper_stmt->close();
        $connection->close();
        exit;
    }

    $shipper_location_code = $shipper_data['shipper_location_code'];
    $shipper_stmt->close();

    // NEW STEP: First check total quantities required vs available in ledger
    $total_quantities_query = "
        SELECT
            ii.product_id,
            i.item_name as product_name,
            SUM(ii.stock) as total_required_quantity,
            i.lot_tracking
        FROM invoice_items ii
        JOIN item i ON ii.product_id = i.item_code
        WHERE ii.invoice_id = ? AND i.lot_tracking = 1
        GROUP BY ii.product_id, i.item_name, i.lot_tracking
    ";

    $total_stmt = $connection->prepare($total_quantities_query);
    $total_stmt->bind_param("i", $invoice_id);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();

    // Check available quantities for each product
    while ($product = $total_result->fetch_assoc()) {
        $product_id = $product['product_id'];
        $product_name = $product['product_name'];
        $total_required = $product['total_required_quantity'];

        // Get available quantity from item_ledger_history
        $ledger_query = "
            SELECT SUM(quantity) as available_quantity
            FROM item_ledger_history
            WHERE product_id = ? AND location = ?
        ";

        $ledger_stmt = $connection->prepare($ledger_query);
        $ledger_stmt->bind_param("ss", $product_id, $shipper_location_code);
        $ledger_stmt->execute();
        $ledger_result = $ledger_stmt->get_result();
        $ledger_data = $ledger_result->fetch_assoc();

        $available_quantity = $ledger_data['available_quantity'] ?? 0;

        // Add debug info
        $response['debug'][] = [
            'validation_type' => 'LEDGER_CHECK',
            'product_id' => $product_id,
            'product_name' => $product_name,
            'total_required' => $total_required,
            'available_quantity' => $available_quantity
        ];

        // Check if available quantity is sufficient
        if ($available_quantity < $total_required) {
            $response['valid'] = false;
            $shortage = $total_required - $available_quantity;
            $error_msg = "Quantity required for {$product_name} (ID: {$product_id}) exceeds the quantity in the Item Ledger Records currently (Short by {$shortage} units)<br>";
            $response['errors'][] = $error_msg;
        }

        $ledger_stmt->close();
    }

    $total_stmt->close();

    // EXISTING CODE: Step 1: Get all invoice items
    $items_query = "
        SELECT
            ii.id as item_id,
            ii.product_id,
            ii.product_name,
            ii.stock,
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
            'stock' => $item['stock'],
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
                invoice_itemid = ? AND
                document_type = 'Sale' AND
                location = ?
        ";

        $lot_stmt = $connection->prepare($lot_query);
        $lot_stmt->bind_param("iis", $invoice_id, $item['item_id'], $shipper_location_code);
        $lot_stmt->execute();
        $lot_result = $lot_stmt->get_result();
        $lot_data = $lot_result->fetch_assoc();

        $total_lot_quantity = $lot_data['total_quantity'] ?? 0;
        $debug_info['total_lot_quantity'] = $total_lot_quantity;

        // Quantity comparison (using stock instead of quantity for sales)
        if ($total_lot_quantity < $item['stock']) {
            $response['valid'] = false;
            $shortage = $item['stock'] - $total_lot_quantity;
            $error_msg = "{$item['product_name']} (ID: {$item['product_id']}): Need {$shortage} more units<br>";
            $response['errors'][] = $error_msg;
            $debug_info['status'] = "UNDER_QUANTITY ({$shortage} needed)";
        }
        elseif ($total_lot_quantity > $item['stock']) {
            $response['valid'] = false;
            $excess = $total_lot_quantity - $item['stock'];
            $warning_msg = "{$item['product_name']} (ID: {$item['product_id']}): {$excess} excess units<br>";
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

    // NEW VALIDATION STEP: Check lot quantities against ledger history
    $lots_query = "
        SELECT pol.lot_trackingid, pol.product_id, i.item_name as product_name, SUM(pol.quantity) as total_lot_quantity
        FROM purchase_order_item_lots pol
        JOIN item i ON pol.product_id = i.item_code
        WHERE pol.invoice_id_main = ? AND pol.document_type = 'Sale' AND pol.location = ?
        GROUP BY pol.lot_trackingid, pol.product_id
    ";

    $lots_stmt = $connection->prepare($lots_query);
    $lots_stmt->bind_param("is", $invoice_id, $shipper_location_code);
    $lots_stmt->execute();
    $lots_result = $lots_stmt->get_result();

    while ($lot = $lots_result->fetch_assoc()) {
        $lot_trackingid = $lot['lot_trackingid'];
        $product_id = $lot['product_id'];
        $product_name = $lot['product_name'];
        $total_lot_quantity = $lot['total_lot_quantity'];

        // Get net quantity from item_ledger_history
        $net_quantity_query = "
            SELECT
                SUM(CASE WHEN document_type = 'Purchase' THEN quantity ELSE quantity END) as net_quantity
            FROM item_ledger_history
            WHERE lot_trackingid = ? AND product_id = ? AND location = ?
        ";

        $net_stmt = $connection->prepare($net_quantity_query);
        $net_stmt->bind_param("sis", $lot_trackingid, $product_id, $shipper_location_code);
        $net_stmt->execute();
        $net_result = $net_stmt->get_result();
        $net_data = $net_result->fetch_assoc();

        $net_quantity = $net_data['net_quantity'] ?? 0;

        // Add debug info
        $response['debug'][] = [
            'validation_type' => 'LOT_VS_LEDGER_CHECK',
            'lot_trackingid' => $lot_trackingid,
            'product_id' => $product_id,
            'product_name' => $product_name,
            'total_lot_quantity' => $total_lot_quantity,
            'net_quantity' => $net_quantity
        ];

        // Check if net quantity is sufficient
        if ($net_quantity < $total_lot_quantity) {
            $response['valid'] = false;
            $shortage = $total_lot_quantity - $net_quantity;
            $error_msg = "Lot quantity for {$product_name} (ID: {$product_id}) with lot tracking ID {$lot_trackingid} exceeds the net quantity in the Item Ledger Records currently (Short by {$shortage} units)<br>";
            $response['errors'][] = $error_msg;
        } elseif ($net_quantity == 0) {
            $response['valid'] = false;
            $error_msg = "No lot found for {$product_name} (ID: {$product_id}) with lot tracking ID {$lot_trackingid} in the Item Ledger Records<br>";
            $response['errors'][] = $error_msg;
        }

        $net_stmt->close();
    }

    $lots_stmt->close();

    echo json_encode($response);
} else {
    echo json_encode([
        'valid' => false,
        'message' => 'Invoice ID not provided'
    ]);
}

$connection->close();
?>
