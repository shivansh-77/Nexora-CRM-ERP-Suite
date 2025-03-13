<?php
include 'connection.php'; // Replace with your actual connection file

if (isset($_GET['id'])) {
    $invoice_id = intval($_GET['id']); // Get invoice ID from the URL

    // Step 1: Update the status in the purchase_invoice table
    $update_status_query = "UPDATE purchase_invoice SET status = 'Closed' WHERE id = $invoice_id";
    $connection->query($update_status_query);

    // Step 2: Fetch the shipper_location_code and invoice_no from the purchase_invoice table
    $shipper_location_query = "SELECT shipper_location_code, invoice_no FROM purchase_invoice WHERE id = $invoice_id";
    $shipper_result = $connection->query($shipper_location_query);

    if ($shipper_result && $shipper_result->num_rows > 0) {
        $shipper_row = $shipper_result->fetch_assoc();
        $shipper_location_code = $shipper_row['shipper_location_code'];
        $invoice_no = $shipper_row['invoice_no'];
    } else {
        $shipper_location_code = null; // Handle case where no matching record is found
        $invoice_no = null;
    }

    // Step 3: Fetch all items from purchase_invoice_items related to this invoice
    $items_query = "SELECT id AS invoice_itemid, product_name, product_id, quantity, rate, gst, amount, unit, value, igst, cgst, sgst, lot_trackingid, expiration_date, receipt_date
                    FROM purchase_invoice_items WHERE invoice_id = $invoice_id";
    $items_result = $connection->query($items_query);

    while ($item = $items_result->fetch_assoc()) {
        $invoice_itemid = $item['invoice_itemid'];
        $product_name = $item['product_name'];
        $product_code = $item['product_id'];
        $quantity = $item['quantity'];
        $rate = $item['rate'];
        $gst = $item['gst'];
        $amount = $item['amount'];
        $unit_value = $item['value'];
        $lot_tracking_id = $item['lot_trackingid'];
        $expiration_date = $item['expiration_date'];
        $receipt_date = $item['receipt_date']; // New field for receipt date

        // Fetch unit_of_measurement_code from item table using product_id
        $unit_query = "SELECT unit_of_measurement_code FROM item WHERE item_code = '$product_code'";
        $unit_result = $connection->query($unit_query);

        if ($unit_result->num_rows > 0) {
            $unit_row = $unit_result->fetch_assoc();
            $unit = $unit_row['unit_of_measurement_code']; // Get the unit from the item table
        } else {
            $unit = ''; // Default empty if not found
        }

        // Insert into item_ledger_history
        $document_type = 'Purchase'; // As it's a purchase invoice
        $entry_type = 'Purchase Returned'; // Entry type
        $item_quantity = (-(float)$quantity * (float)$unit_value); // Calculate quantity
        $date = date('Y-m-d'); // Use today's date
        $item_value = $unit_value; // Store the unit value as the value in the item_ledger_history

        $insert_ledger_history = "INSERT INTO item_ledger_history (invoice_no, document_type, entry_type, product_id, product_name, quantity, location, unit, date, value, invoice_itemid, lot_trackingid, expiration_date)
                                  VALUES ('$invoice_no', '$document_type', '$entry_type', '$product_code', '$product_name', '$item_quantity', '$shipper_location_code', '$unit', '$date', '$item_value', '$invoice_itemid', '$lot_tracking_id', '$expiration_date')";
        $connection->query($insert_ledger_history);
    }

    $connection->close();

    // Redirect with success message
    echo "<script>alert('Purchase Invoice Closed Successfully'); window.location.href='purchase_invoice_display.php';</script>";
    exit(); // Ensure no further code is executed
}

// If the id is not set, redirect or show an error
echo "<script>alert('No invoice ID provided.'); window.location.href='purchase_invoice_display.php';</script>";
?>
