<?php
include 'connection.php';

if (isset($_POST['purchase_order_id']) && isset($_POST['item_ids'])) {
    $purchase_order_id = intval($_POST['purchase_order_id']);
    $item_ids = $_POST['item_ids']; // This will be an array of selected item IDs

    // Start transaction
    $connection->begin_transaction();

    try {
        // Fetch purchase order details
        $purchase_order_query = "SELECT * FROM purchase_order WHERE id = $purchase_order_id";
        $purchase_order_result = $connection->query($purchase_order_query);
        $purchase_order = $purchase_order_result->fetch_assoc();

        if (!$purchase_order) {
            throw new Exception('Purchase Order not found.');
        }

        // Generate invoice number (same for all items)
        $currentYear = date('y');
        $last_invoice_query = "
            SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_no, 8) AS UNSIGNED)), 0) AS last_invoice_no
            FROM purchase_invoice
            WHERE invoice_no LIKE 'PI/$currentYear/%'
        ";
        $last_invoice_result = $connection->query($last_invoice_query);
        $last_invoice = $last_invoice_result->fetch_assoc();
        $new_sequence_no = $last_invoice['last_invoice_no'] + 1;
        $invoice_no = 'PI/' . $currentYear . '/' . str_pad($new_sequence_no, 4, '0', STR_PAD_LEFT);

        // Initialize totals for the invoice
        $total_base_amount = 0;
        $total_gross_amount = 0;
        $total_igst = 0;
        $total_cgst = 0;
        $total_sgst = 0;
        $total_net_amount = 0;

        // Create the main invoice record
        $insert_invoice_query = "INSERT INTO purchase_invoice (
            invoice_no, purchase_order_no, gross_amount, discount, net_amount, total_igst, total_cgst, total_sgst,
            vendor_name, vendor_address, vendor_phone, vendor_city, vendor_state, vendor_country,
            vendor_pincode, vendor_gstno, shipper_company_name, shipper_address, shipper_city,
            shipper_state, shipper_country, shipper_pincode, shipper_phone, shipper_gstno, vendor_id,
            shipper_location_code, shipper_id, base_amount, fy_code, pending_amount , vendor_company_name
        ) VALUES (
            '$invoice_no', '{$purchase_order['purchase_order_no']}', 0, {$purchase_order['discount']},
            0, 0, 0, 0,
            '{$purchase_order['vendor_name']}', '{$purchase_order['vendor_address']}', '{$purchase_order['vendor_phone']}', '{$purchase_order['vendor_city']}',
            '{$purchase_order['vendor_state']}', '{$purchase_order['vendor_country']}', '{$purchase_order['vendor_pincode']}',
            '{$purchase_order['vendor_gstno']}', '{$purchase_order['shipper_company_name']}', '{$purchase_order['shipper_address']}',
            '{$purchase_order['shipper_city']}', '{$purchase_order['shipper_state']}', '{$purchase_order['shipper_country']}',
            '{$purchase_order['shipper_pincode']}', '{$purchase_order['shipper_phone']}', '{$purchase_order['shipper_gstno']}',
            '{$purchase_order['vendor_id']}', '{$purchase_order['shipper_location_code']}', '{$purchase_order['shipper_id']}', 0, '{$purchase_order['fy_code']}', 0, '{$purchase_order['vendor_company_name']}'
        )";

        if (!$connection->query($insert_invoice_query)) {
            throw new Exception('Error creating purchase invoice: ' . $connection->error);
        }

        $invoice_id = $connection->insert_id;

        // Process each selected item
        foreach ($item_ids as $item_id) {
            $item_id = intval($item_id);

            // Fetch the item details including po_invoice quantity
            $item_query = "SELECT poi.*, i.lot_tracking, i.expiration_tracking, i.item_type,
                 poi.po_invoice as original_quantity,
                 (poi.po_invoice * poi.value) as quantity_to_invoice,
                 poi.value
                 FROM purchase_order_items poi
                 LEFT JOIN item i ON poi.product_id = i.item_code
                 WHERE poi.id = $item_id";
            $item_result = $connection->query($item_query);
            $item = $item_result->fetch_assoc();

            if (!$item) {
                throw new Exception("Item with ID $item_id not found.");
            }

            $quantity_to_invoice = floatval($item['quantity_to_invoice']);

            // Skip items with zero quantity to invoice
            if ($quantity_to_invoice <= 0) {
                continue;
            }

            // Calculate amounts for this item
            $base_amount = $item['rate'] * $quantity_to_invoice;
            $gross_amount = $item['amount'] / $item['stock'] * $quantity_to_invoice;
            $net_amount = $gross_amount;
            $item_igst = $item['igst'] / $item['stock'] * $quantity_to_invoice;
            $item_cgst = $item['cgst'] / $item['stock'] * $quantity_to_invoice;
            $item_sgst = $item['sgst'] / $item['stock'] * $quantity_to_invoice;
            $pending_amount = $net_amount;

            // Update totals for the invoice
            $total_base_amount += $base_amount;
            $total_gross_amount += $gross_amount;
            $total_igst += $item_igst;
            $total_cgst += $item_cgst;
            $total_sgst += $item_sgst;
            $total_net_amount += $net_amount;

            // Insert into purchase_invoice_items
            $insert_item_query = "INSERT INTO purchase_invoice_items (
                invoice_id, product_id, product_name, unit, value, quantity, rate, gst, igst, cgst, sgst, amount,
                lot_tracking, expiration_tracking, stock, lot_trackingid, expiration_date, receipt_date
            ) VALUES (
                $invoice_id, '{$item['product_id']}', '{$item['product_name']}', '{$item['unit']}', '{$item['value']}',
                $quantity_to_invoice, {$item['rate']}, {$item['gst']}, $item_igst, $item_cgst,
                $item_sgst, $gross_amount, '{$item['lot_tracking']}', '{$item['expiration_tracking']}',
                '{$item['stock']}', '{$item['lot_trackingid']}', '{$item['expiration_date']}', '{$item['receipt_date']}'
            )";

            $reset_po_invoice_query = "UPDATE purchase_order_items SET po_invoice = 0 WHERE id = $item_id";
            if (!$connection->query($reset_po_invoice_query)) {
                throw new Exception('Error resetting po_invoice for item ' . $item_id);
            }

            if (!$connection->query($insert_item_query)) {
                throw new Exception('Error creating invoice item: ' . $connection->error);
            }

            $invoice_itemid = $connection->insert_id;

            // Update purchase_order_item_lots with invoice details
            $update_lots_query = "UPDATE purchase_order_item_lot_tracking
                                  SET invoice_id_main = $invoice_id, invoice_no = '$invoice_no'
                                  WHERE invoice_itemid = $item_id";

            if (!$connection->query($update_lots_query)) {
                throw new Exception('Error updating lot entries: ' . $connection->error);
            }
        }

        // After inserting invoice item ($invoice_itemid is available)
        // Fetch and update purchase_order_item_lots entries
        $lots_query = "SELECT * FROM purchase_order_item_lot_tracking
                       WHERE invoice_id_main = $invoice_id
                       AND invoice_registered = 0";
        $lots_result = $connection->query($lots_query);

        if (!$lots_result) {
            throw new Exception('Error fetching lot entries: ' . $connection->error);
        }

        while ($lot = $lots_result->fetch_assoc()) {
            // First update the original record in purchase_order_item_lots
            $update_lot_query = "UPDATE purchase_order_item_lot_tracking SET
                                invoice_registered = 1,
                                invoice_no = '" . $connection->real_escape_string($invoice_no) . "',
                                location = '" . $connection->real_escape_string($purchase_order['shipper_location_code']) . "'
                                WHERE id = " . intval($lot['id']);

            if (!$connection->query($update_lot_query)) {
                throw new Exception('Error updating lot entry: ' . $connection->error);
            }

            // Now prepare the data for ledger history with the UPDATED values
            $ledger_data = $lot; // Start with original data
            $ledger_data['invoice_no'] = $invoice_no; // Updated invoice_no
            $ledger_data['location'] = $purchase_order['shipper_location_code']; // Updated location

            // Remove columns we don't want to copy
            unset($ledger_data['id']); // Skip auto-increment PK
            unset($ledger_data['invoice_registered']); // Extra column

            // Prepare columns and values with proper escaping
            $columns = [];
            $values = [];
            foreach ($ledger_data as $key => $value) {
                $columns[] = $key;
                $values[] = "'" . $connection->real_escape_string($value) . "'";
            }
            $columns_str = implode(", ", $columns);
            $values_str = implode(", ", $values);

            // Insert into ledger history with updated values
            $insert_ledger_query = "INSERT INTO item_ledger_history ($columns_str)
                                   VALUES ($values_str)";

            if (!$connection->query($insert_ledger_query)) {
                throw new Exception('Error creating ledger entry: ' . $connection->error);
            }
        }

        // Insert into party_ledger
        $party_ledger_query = "INSERT INTO party_ledger (
            ledger_type, party_no, party_name, party_type, document_type, document_no, amount, date
        ) VALUES (
            'Vendor Ledger', '{$purchase_order['vendor_id']}', '{$purchase_order['vendor_name']}', 'Vendor',
            'Purchase Invoice', '$invoice_no', -$total_net_amount, NOW()
        )";

        if (!$connection->query($party_ledger_query)) {
            throw new Exception('Error creating party ledger entry: ' . $connection->error);
        }

        // // Insert same entry into advance_payments
        // $advance_payments_query = "INSERT INTO advance_payments (
        //     ledger_type, party_no, party_name, party_type, document_type, document_no, amount, date
        // ) VALUES (
        //     'Vendor Ledger', '{$purchase_order['vendor_id']}', '{$purchase_order['vendor_name']}', 'Vendor',
        //     'Purchase Invoice', '$invoice_no', -$total_net_amount, NOW()
        // )";
        //
        // if (!$connection->query($advance_payments_query)) {
        //     throw new Exception('Error creating advance payments entry: ' . $connection->error);
        // }

        // Update the main invoice with the calculated totals
        $update_invoice_query = "UPDATE purchase_invoice SET
            base_amount = $total_base_amount,
            gross_amount = $total_gross_amount,
            net_amount = $total_net_amount,
            total_igst = $total_igst,
            total_cgst = $total_cgst,
            total_sgst = $total_sgst,
            pending_amount = $total_net_amount
            WHERE id = $invoice_id";

        if (!$connection->query($update_invoice_query)) {
            throw new Exception('Error updating invoice totals: ' . $connection->error);
        }

        // Commit transaction
        $connection->commit();

        echo json_encode([
            'success' => true,
            'invoice_id' => $invoice_id,
            'invoice_no' => $invoice_no,
            'message' => 'Invoice created successfully for selected items'
        ]);

    } catch (Exception $e) {
        $connection->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    $connection->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
