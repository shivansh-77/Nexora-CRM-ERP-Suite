<?php
include 'connection.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the invoice ID
    $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;

    if ($invoice_id > 0) {
        // First, check if all lot details are complete
        $all_complete = true;
        $incomplete_items = [];

        // Get all items with lot tracking enabled
        $items_query = "
            SELECT
                pi.id,
                pi.product_id,
                pi.product_name,
                pi.quantity
            FROM
                purchase_invoice_items pi
            LEFT JOIN
                item i ON pi.product_id = i.item_code
            WHERE
                pi.invoice_id = ? AND i.lot_tracking = 1
        ";
        $stmt_items = $connection->prepare($items_query);
        $stmt_items->bind_param("i", $invoice_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        while ($item = $items_result->fetch_assoc()) {
            // Check if lot quantities match for this item
            $lots_query = "SELECT SUM(quantity) as total_quantity FROM purchase_order_item_lots WHERE invoice_id_main = ? AND product_id = ?";
            $stmt_lots = $connection->prepare($lots_query);
            $stmt_lots->bind_param("is", $invoice_id, $item['product_id']);
            $stmt_lots->execute();
            $lots_result = $stmt_lots->get_result();
            $lots_data = $lots_result->fetch_assoc();
            $stmt_lots->close();

            $total_lot_quantity = $lots_data ? floatval($lots_data['total_quantity']) : 0;

            // Check if quantities match
            if (abs($total_lot_quantity - $item['quantity']) >= 0.001) { // Using a small epsilon for float comparison
                $all_complete = false;
                $incomplete_items[] = $item['product_name'];
            }
        }
        $stmt_items->close();

        if (!$all_complete) {
            echo "Please complete the lot details for the following products: " . implode(", ", $incomplete_items);
            exit;
        }

        // All lot details are complete, proceed with saving

        // 1. Generate new invoice number
        $currentYear = date('y');
        $last_invoice_query = "SELECT MAX(CAST(SUBSTRING(invoice_no, 8) AS UNSIGNED)) AS last_invoice_no
                             FROM purchase_invoice WHERE invoice_no LIKE 'PI/$currentYear/%'";
        $last_invoice_result = $connection->query($last_invoice_query);
        $last_invoice = $last_invoice_result->fetch_assoc();
        $new_sequence_no = ($last_invoice['last_invoice_no'] ?? 0) + 1;
        $purchase_invoice_no = 'PI/' . $currentYear . '/' . str_pad($new_sequence_no, 4, '0', STR_PAD_LEFT);

        // 2. Insert entries into item_ledger_history
        $lot_items_query = "
            SELECT id, invoice_no, document_type, entry_type, product_id, product_name,
                   quantity, location, unit, date, value, invoice_itemid, lot_trackingid,
                   expiration_date, rate, invoice_id_main
            FROM purchase_order_item_lots
            WHERE invoice_id_main = ?
        ";
        $stmt_lot_items = $connection->prepare($lot_items_query);
        $stmt_lot_items->bind_param("i", $invoice_id);
        $stmt_lot_items->execute();
        $lot_items_result = $stmt_lot_items->get_result();

        // Prepare the insert statement for item_ledger_history
        $insert_ledger_query = "
            INSERT INTO item_ledger_history (
                invoice_no, document_type, entry_type, product_id, product_name,
                quantity, location, unit, date, value, invoice_itemid,
                lot_trackingid, expiration_date, rate, invoice_id_main
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt_insert_ledger = $connection->prepare($insert_ledger_query);

        while ($lot_item = $lot_items_result->fetch_assoc()) {
            $stmt_insert_ledger->bind_param(
                "sssssssssssssss",
                $lot_item['invoice_no'],
                $lot_item['document_type'],
                $lot_item['entry_type'],
                $lot_item['product_id'],
                $lot_item['product_name'],
                $lot_item['quantity'],
                $lot_item['location'],
                $lot_item['unit'],
                $lot_item['date'],
                $lot_item['value'],
                $lot_item['invoice_itemid'],
                $lot_item['lot_trackingid'],
                $lot_item['expiration_date'],
                $lot_item['rate'],
                $lot_item['invoice_id_main']
            );
            $stmt_insert_ledger->execute();
        }

        $stmt_lot_items->close();
        $stmt_insert_ledger->close();

        // 3. Insert entry into party_ledger
        // First, get the purchase invoice details
        $invoice_query = "SELECT vendor_id, vendor_name, net_amount FROM purchase_invoice WHERE id = ?";
        $stmt_invoice = $connection->prepare($invoice_query);
        $stmt_invoice->bind_param("i", $invoice_id);
        $stmt_invoice->execute();
        $invoice_result = $stmt_invoice->get_result();
        $invoice = $invoice_result->fetch_assoc();
        $stmt_invoice->close();

        if ($invoice) {
            // Insert into party_ledger
            $insert_party_query = "
                INSERT INTO party_ledger (
                    ledger_type, party_no, party_name, party_type,
                    document_type, document_no, amount, ref_doc_no, date
                ) VALUES (
                    'Vendor Ledger', ?, ?, 'Vendor', 'Purchase Invoice', ?, ?, '', NOW()
                )
            ";
            $stmt_insert_party = $connection->prepare($insert_party_query);
            $stmt_insert_party->bind_param(
                "sssd",
                $invoice['vendor_id'],
                $invoice['vendor_name'],
                $purchase_invoice_no,
                $invoice['net_amount']
            );
            $stmt_insert_party->execute();
            $stmt_insert_party->close();
        }

        // 4. Update purchase_invoice
        $update_query = "UPDATE purchase_invoice SET invoice_no = ?, status = 'Finalized', lot_details_completed = 1 WHERE id = ?";
        $stmt_update = $connection->prepare($update_query);
        $stmt_update->bind_param("si", $purchase_invoice_no, $invoice_id);
        $result = $stmt_update->execute();
        $stmt_update->close();

        if ($result) {
            echo "success";
        } else {
            echo "Error updating record: " . $connection->error;
        }
    } else {
        echo "Invalid invoice ID";
    }
} else {
    echo "Invalid request method";
}

$connection->close();
?>
