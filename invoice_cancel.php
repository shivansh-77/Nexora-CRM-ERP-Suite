<?php
include 'connection.php'; // Replace with your actual connection file

if (isset($_GET['id'])) {
    $invoice_id = intval($_GET['id']); // Get invoice ID from the URL

    // Step 1: Update the status in the invoices table
    $update_status_query = "UPDATE invoices SET status = 'Closed' WHERE id = $invoice_id";
    $connection->query($update_status_query);

    // Step 2: Fetch the shipper_location_code and invoice_no from the invoices table
    $shipper_location_query = "SELECT shipper_location_code, invoice_no FROM invoices WHERE id = $invoice_id";
    $shipper_result = $connection->query($shipper_location_query);

    if ($shipper_result && $shipper_result->num_rows > 0) {
        $shipper_row = $shipper_result->fetch_assoc();
        $shipper_location_code = $shipper_row['shipper_location_code'];
        $invoice_no = $shipper_row['invoice_no'];
    } else {
        $shipper_location_code = null; // Handle case where no matching record is found
        $invoice_no = null;
    }

    // Step 3: Fetch all entries from item_ledger_history with document_type = 'Sale' for this invoice
    $ledger_query = "SELECT
                        invoice_no,
                        document_type,
                        product_id,
                        product_name,
                        quantity,
                        location,
                        unit,
                        date,
                        value,
                        invoice_itemid,
                        lot_trackingid,
                        expiration_date,
                        rate,
                        invoice_id_main,
                        invoice_id
                    FROM item_ledger_history
                    WHERE document_type = 'Sale'
                    AND invoice_id_main = $invoice_id";
    $ledger_result = $connection->query($ledger_query);

    while ($ledger_entry = $ledger_result->fetch_assoc()) {
        // Prepare all values for insertion
        $document_type = 'Sale'; // As it's an invoice for sale
        $entry_type = 'Sale Returned'; // Entry type
        $product_code = $ledger_entry['product_id'];
        $product_name = $ledger_entry['product_name'];
        $quantity = abs($ledger_entry['quantity']); // Convert to positive quantity
        $location = $shipper_location_code;
        $unit = $ledger_entry['unit'];
        $date = date('Y-m-d'); // Use today's date
        $item_value = $ledger_entry['value'];
        $invoice_itemid = $ledger_entry['invoice_itemid'];
        $lot_tracking_id = $ledger_entry['lot_trackingid'];
        $expiration_date = $ledger_entry['expiration_date'];
        $rate = $ledger_entry['rate'];
        $invoice_id_main = $ledger_entry['invoice_id_main'];
        $invoice_id_entry = $ledger_entry['invoice_id'];

        // Insert into item_ledger_history with positive quantity
        $insert_ledger_history = "INSERT INTO item_ledger_history (
            invoice_no,
            document_type,
            entry_type,
            product_id,
            product_name,
            quantity,
            location,
            unit,
            date,
            value,
            invoice_itemid,
            lot_trackingid,
            expiration_date,
            rate,
            invoice_id_main,
            invoice_id
        ) VALUES (
            '$invoice_no',
            '$document_type',
            '$entry_type',
            '$product_code',
            '$product_name',
            '$quantity',
            '$location',
            '$unit',
            '$date',
            '$item_value',
            '$invoice_itemid',
            '$lot_tracking_id',
            '$expiration_date',
            '$rate',
            '$invoice_id_main',
            '$invoice_id_entry'
        )";
        $connection->query($insert_ledger_history);
    }

    $connection->close();

    // Redirect with success message
    echo "<script>alert('Invoice Returned Successfully'); window.location.href='invoice_display.php';</script>";
    exit(); // Ensure no further code is executed
}

// If the id is not set, redirect or show an error
echo "<script>alert('No invoice ID provided.'); window.location.href='invoice_display.php';</script>";
?>
