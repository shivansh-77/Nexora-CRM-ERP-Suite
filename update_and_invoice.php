<?php
include 'connection.php'; // Replace with your actual connection file

if (isset($_POST['purchase_order_id']) && isset($_POST['item_id']) && isset($_POST['received_qty']) && isset($_POST['new_qytc'])) {
    $purchase_order_id = intval($_POST['purchase_order_id']);
    $item_id = intval($_POST['item_id']);
    $received_qty = floatval($_POST['received_qty']);
    $new_qytc = floatval($_POST['new_qytc']);

    // // Check if an invoice already exists for this purchase order
    // $check_existing_invoice = "SELECT id FROM purchase_invoice WHERE purchase_order_item_id = $item_id";
    // $result_check = $connection->query($check_existing_invoice);
    //
    // if ($result_check->num_rows > 0) {
    //     echo json_encode(['success' => false, 'message' => 'Invoice already present for this purchase order.']);
    //     exit();
    // }

    // Update the Q.Y.T.C in purchase_order_items
    $update_qytc_query = "UPDATE purchase_order_items SET qytc = $new_qytc WHERE id = $item_id";
    if (!$connection->query($update_qytc_query)) {
        echo json_encode(['success' => false, 'message' => 'Error updating Q.Y.T.C: ' . $connection->error]);
        exit();
    }

    // Fetch purchase order details
    $purchase_order_query = "SELECT * FROM purchase_order WHERE id = $purchase_order_id";
    $purchase_order_result = $connection->query($purchase_order_query);
    $purchase_order = $purchase_order_result->fetch_assoc();

    if ($purchase_order) {
        // Get the current year and format it to get the last two digits
        $currentYear = date('y');

        // Generate the new invoice number
        $last_invoice_query = "
            SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_no, 8) AS UNSIGNED)), 0) AS last_invoice_no
            FROM purchase_invoice
            WHERE invoice_no LIKE 'PI/$currentYear/%'
        ";
        $last_invoice_result = $connection->query($last_invoice_query);
        $last_invoice = $last_invoice_result->fetch_assoc();

        // Calculate the new sequential number
        $new_sequence_no = $last_invoice['last_invoice_no'] + 1;

        // Format the new invoice number
        $invoice_no = 'PI/' . $currentYear . '/' . str_pad($new_sequence_no, 4, '0', STR_PAD_LEFT);

        // Fetch the specific purchase order item
        $item_query = "SELECT poi.*, i.lot_tracking, i.expiration_tracking, i.item_type
                       FROM purchase_order_items poi
                       LEFT JOIN item i ON poi.product_id = i.item_code
                       WHERE poi.id = $item_id";
        $item_result = $connection->query($item_query);
        $item = $item_result->fetch_assoc();

        if ($item) {
            // Calculate the amounts for the invoice based on the received quantity
            $base_amount = $item['rate'] * $received_qty;
            $gross_amount = $item['amount'] / $item['stock'] * $received_qty;
            $net_amount = $gross_amount; // Assuming net_amount is the same as gross_amount
            $total_igst = $item['igst'] / $item['stock'] * $received_qty;
            $total_cgst = $item['cgst'] / $item['stock'] * $received_qty;
            $total_sgst = $item['sgst'] / $item['stock'] * $received_qty;
            $pending_amount = $net_amount;

            // Insert into purchase_invoice table with the new invoice_no
            $insert_invoice_query = "INSERT INTO purchase_invoice (
                invoice_no, purchase_order_no, purchase_order_item_id, gross_amount, discount, net_amount, total_igst, total_cgst, total_sgst,
                vendor_name, vendor_address, vendor_phone, vendor_city, vendor_state, vendor_country,
                vendor_pincode, vendor_gstno, shipper_company_name, shipper_address, shipper_city,
                shipper_state, shipper_country, shipper_pincode, shipper_phone, shipper_gstno, vendor_id,
                shipper_location_code, shipper_id, base_amount, fy_code, pending_amount
            ) VALUES (
                '$invoice_no', '{$purchase_order['purchase_order_no']}', $item_id, $gross_amount, {$purchase_order['discount']},
                $net_amount, $total_igst, $total_cgst, $total_sgst,
                '{$purchase_order['vendor_name']}', '{$purchase_order['vendor_address']}', '{$purchase_order['vendor_phone']}', '{$purchase_order['vendor_city']}',
                '{$purchase_order['vendor_state']}', '{$purchase_order['vendor_country']}', '{$purchase_order['vendor_pincode']}',
                '{$purchase_order['vendor_gstno']}', '{$purchase_order['shipper_company_name']}', '{$purchase_order['shipper_address']}',
                '{$purchase_order['shipper_city']}', '{$purchase_order['shipper_state']}', '{$purchase_order['shipper_country']}',
                '{$purchase_order['shipper_pincode']}', '{$purchase_order['shipper_phone']}', '{$purchase_order['shipper_gstno']}',
                '{$purchase_order['vendor_id']}', '{$purchase_order['shipper_location_code']}', '{$purchase_order['shipper_id']}', $base_amount, '{$purchase_order['fy_code']}', $pending_amount
            )";

            if ($connection->query($insert_invoice_query) === TRUE) {
                $invoice_id = $connection->insert_id;

                // Calculate the amounts for the invoice based on the received quantity
                $base_amount = $item['rate'] * $received_qty;
                $gross_amount = $item['amount'] / $item['quantity'] * $received_qty;
                $net_amount = $gross_amount; // Assuming net_amount is the same as gross_amount
                $total_igst = $item['igst'] / $item['quantity'] * $received_qty;
                $total_cgst = $item['cgst'] / $item['quantity'] * $received_qty;
                $total_sgst = $item['sgst'] / $item['quantity'] * $received_qty;
                $pending_amount = $net_amount;
                // Insert into purchase_invoice_items
                $insert_item_query = "INSERT INTO purchase_invoice_items (
                    invoice_id, product_id, product_name, unit, value, quantity, rate, gst, igst, cgst, sgst, amount, lot_tracking, expiration_tracking, stock,lot_trackingid,expiration_date,receipt_date
                ) VALUES (
                    $invoice_id, '{$item['product_id']}', '{$item['product_name']}', '{$item['unit']}', '{$item['value']}',
                    $received_qty, {$item['rate']}, {$item['gst']}, $total_igst, $total_cgst,
                    $total_sgst, $gross_amount, '{$item['lot_tracking']}', '{$item['expiration_tracking']}', '{$item['stock']}','{$item['lot_trackingid']}','{$item['expiration_date']}','{$item['receipt_date']}'
                )";

                $connection->query($insert_item_query);
                $invoice_itemid = $connection->insert_id;

                if ($item['item_type'] === 'Inventory') {
                    // Insert into item_ledger_history
                    $document_type = 'Purchase';
                    $entry_type = 'Purchase Invoice';
                    $item_quantity = $received_qty;
                    $location = $purchase_order['shipper_location_code'];
                    $date = date('Y-m-d');
                    $item_value = $item['value'];

                    $insert_ledger_history = "INSERT INTO item_ledger_history (
                        document_type, entry_type, product_id, product_name, quantity, location, unit, date, value, invoice_itemid
                    ) VALUES (
                        '$document_type', '$entry_type', '{$item['product_id']}', '{$item['product_name']}',
                        $item_quantity, '$location', '{$item['unit']}', '$date', $item_value, $invoice_itemid
                    )";

                    $connection->query($insert_ledger_history);
                }

                // Return success response
                echo json_encode(['success' => true, 'invoice_id' => $invoice_id]);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'Error creating purchase invoice: ' . $connection->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
    }

    $connection->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
