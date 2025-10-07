<?php
include 'connection.php'; // Replace with your actual connection file

if (isset($_GET['id'])) {
    $purchase_order_id = $_GET['id']; // Directly using input without sanitization

    // Check if an invoice already exists for this purchase order
    $check_existing_invoice = "SELECT id FROM purchase_invoice WHERE purchase_order_id = $purchase_order_id";
    $result_check = $connection->query($check_existing_invoice);

    if ($result_check->num_rows > 0) {
        echo "<script>alert('Invoice already present for this purchase order.'); window.history.back();</script>";
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

        // Insert into purchase_invoice table with the new invoice_no
        $insert_invoice_query = "INSERT INTO purchase_invoice (
            invoice_no, purchase_order_no, purchase_order_id, gross_amount, discount, net_amount, total_igst, total_cgst, total_sgst,
            vendor_name, vendor_address, vendor_phone, vendor_city, vendor_state, vendor_country,
            vendor_pincode, vendor_gstno, shipper_company_name, shipper_address, shipper_city,
            shipper_state, shipper_country, shipper_pincode, shipper_phone, shipper_gstno, vendor_id,
            shipper_location_code, shipper_id, base_amount, fy_code,pending_amount
        ) VALUES (
            '$invoice_no', '{$purchase_order['purchase_order_no']}', $purchase_order_id, {$purchase_order['gross_amount']}, {$purchase_order['discount']},
            {$purchase_order['net_amount']}, {$purchase_order['total_igst']}, {$purchase_order['total_cgst']}, {$purchase_order['total_sgst']},
            '{$purchase_order['vendor_name']}', '{$purchase_order['vendor_address']}', '{$purchase_order['vendor_phone']}', '{$purchase_order['vendor_city']}',
            '{$purchase_order['vendor_state']}', '{$purchase_order['vendor_country']}', '{$purchase_order['vendor_pincode']}',
            '{$purchase_order['vendor_gstno']}', '{$purchase_order['shipper_company_name']}', '{$purchase_order['shipper_address']}',
            '{$purchase_order['shipper_city']}', '{$purchase_order['shipper_state']}', '{$purchase_order['shipper_country']}',
            '{$purchase_order['shipper_pincode']}', '{$purchase_order['shipper_phone']}', '{$purchase_order['shipper_gstno']}',
            '{$purchase_order['vendor_id']}', '{$purchase_order['shipper_location_code']}', '{$purchase_order['shipper_id']}', '{$purchase_order['base_amount']}', '{$purchase_order['fy_code']}','{$purchase_order['net_amount']}'
        )";

        if ($connection->query($insert_invoice_query) === TRUE) {
            $invoice_id = $connection->insert_id;

            // Fetch purchase order items
            $items_query = "SELECT poi.*, i.lot_tracking, i.expiration_tracking, i.item_type
                            FROM purchase_order_items poi
                            LEFT JOIN item i ON poi.product_id = i.item_code
                            WHERE poi.purchase_order_id = $purchase_order_id";
            $items_result = $connection->query($items_query);

            // Insert items into purchase_invoice_items table
            while ($item = $items_result->fetch_assoc()) {
                $item_type = $item['item_type']; // Define $item_type inside the loop

                $insert_item_query = "INSERT INTO purchase_invoice_items (
                    invoice_id, product_id, product_name, unit, value, quantity, rate, gst, igst, cgst, sgst, amount, lot_tracking, expiration_tracking, receipt_date
                ) VALUES (
                    $invoice_id, '{$item['product_id']}', '{$item['product_name']}', '{$item['unit']}', '{$item['value']}',
                    {$item['quantity']}, {$item['rate']}, {$item['gst']}, {$item['igst']}, {$item['cgst']},
                    {$item['sgst']}, {$item['amount']}, '{$item['lot_tracking']}', '{$item['expiration_tracking']}' ,'{$item['receipt_date']}'
                )";

                $connection->query($insert_item_query);
                $invoice_itemid = $connection->insert_id;

                if ($item_type === 'Inventory') {
                    // Insert into item_ledger_history
                    $document_type = 'Purchase';
                    $entry_type = 'Purchase Invoice';
                    $item_quantity = (float)$item['quantity'] * (float)$item['value'];
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
            }

            // Redirect to the invoice page with success message
            echo "<script>alert('Purchase Invoice generated successfully!'); window.location.href='purchase_order_display.php';</script>";
            exit();
        } else {
            echo "Error creating purchase invoice: " . $connection->error;
        }
    } else {
        echo "Purchase Order not found.";
    }

    $connection->close();
}
?>
