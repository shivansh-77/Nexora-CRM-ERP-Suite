<?php
include 'connection.php';

header('Content-Type: application/json');

if (isset($_GET['invoice_id'])) {
    $invoice_id = intval($_GET['invoice_id']);
    $response = ['success' => false];

    // First, check if the invoice is already finalized
    $check_status_query = "SELECT status, invoice_no FROM purchase_invoice_cancel WHERE id = ?";
    $check_status_stmt = $connection->prepare($check_status_query);
    $check_status_stmt->bind_param("i", $invoice_id);
    $check_status_stmt->execute();
    $check_status_result = $check_status_stmt->get_result();
    $invoice_status = $check_status_result->fetch_assoc();
    $check_status_stmt->close();

    // If invoice is already finalized, return early with a message
    if ($invoice_status && $invoice_status['status'] === 'Finalized') {
        echo json_encode([
            'already_finalized' => true,
            'message' => 'Invoice has already been finalized and registered successfully.',
            'invoice_no' => $invoice_status['invoice_no']
        ]);
        exit;
    }

    // Begin transaction
    $connection->begin_transaction();

    try {
        // 1. Get invoice details
        $invoice_query = "SELECT * FROM purchase_invoice_cancel WHERE id = ?";
        $invoice_stmt = $connection->prepare($invoice_query);
        $invoice_stmt->bind_param("i", $invoice_id);
        $invoice_stmt->execute();
        $invoice_result = $invoice_stmt->get_result();
        $invoice = $invoice_result->fetch_assoc();
        $invoice_stmt->close();

        if (!$invoice) {
            throw new Exception("Invoice not found");
        }

        // 2. Generate invoice number
        $currentYear = date('y');
        $last_invoice_query = "
            SELECT MAX(CAST(SUBSTRING(invoice_no, 8) AS UNSIGNED)) AS last_invoice_no
            FROM purchase_invoice_cancel WHERE invoice_no LIKE ?
        ";
        $pattern = "PSR/$currentYear/%";
        $last_invoice_stmt = $connection->prepare($last_invoice_query);
        $last_invoice_stmt->bind_param("s", $pattern);
        $last_invoice_stmt->execute();
        $last_invoice_result = $last_invoice_stmt->get_result();
        $last_invoice = $last_invoice_result->fetch_assoc();
        $last_invoice_stmt->close();

        $new_sequence_no = ($last_invoice['last_invoice_no'] ?? 0) + 1;
        $purchase_invoice_no = 'PSR/' . $currentYear . '/' . str_pad($new_sequence_no, 4, '0', STR_PAD_LEFT);

        // 3. Insert lot items into item_ledger_history
        $lot_items_query = "
            SELECT * FROM cancelled_item_lot
            WHERE invoice_id_main = ?
        ";
        $lot_items_stmt = $connection->prepare($lot_items_query);
        $lot_items_stmt->bind_param("i", $invoice_id);
        $lot_items_stmt->execute();
        $lot_items_result = $lot_items_stmt->get_result();
        $lot_items_stmt->close();

        $insert_ledger_query = "
        INSERT INTO item_ledger_history (
            invoice_no, document_type, entry_type, product_id, product_name,
            quantity, location, unit, date, value, invoice_itemid,
            lot_trackingid, expiration_date, rate, invoice_id_main
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)
    ";
    $insert_ledger_stmt = $connection->prepare($insert_ledger_query);

    $doc_type = 'Purchase';
    $entry_type = 'Purchase Return';

    while ($item = $lot_items_result->fetch_assoc()) {
        // Convert quantity to negative for returns
        $negative_quantity = -abs($item['quantity']);

        $insert_ledger_stmt->bind_param(
            "sssssdssdissdi",
            $purchase_invoice_no,
            $doc_type,
            $entry_type,
            $item['product_id'],
            $item['product_name'],
            $negative_quantity, // Use the negative quantity here
            $item['location'],
            $item['unit'],
            $item['value'],
            $item['invoice_itemid'],
            $item['lot_trackingid'],
            $item['expiration_date'],
            $item['rate'],
            $invoice_id
        );



          if (!$insert_ledger_stmt->execute()) {
              throw new Exception("Error inserting into item_ledger_history: " . $insert_ledger_stmt->error);
          }
      }
      $insert_ledger_stmt->close();

        // 4. Insert into party_ledger
        $party_ledger_query = "
            INSERT INTO party_ledger (
                ledger_type, party_type, party_no, party_name,
                document_type, document_no, amount, ref_doc_no, date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NOW())
        ";
        $party_ledger_stmt = $connection->prepare($party_ledger_query);

        $ledger_type = 'Customer Ledger';
        $party_type = 'Customer';
        $doc_type_party = 'Purchase Return';
        $negative_amount = $invoice['net_amount'];

        $party_ledger_stmt->bind_param(
            "ssisssd",
            $ledger_type,
            $party_type,
            $invoice['vendor_id'],
            $invoice['vendor_name'],
            $doc_type_party,
            $purchase_invoice_no,
            $negative_amount
        );

        if (!$party_ledger_stmt->execute()) {
            throw new Exception("Error inserting into party_ledger: " . $party_ledger_stmt->error);
        }
        $party_ledger_stmt->close();

        // 5. Update invoice
        $update_invoice_query = "
            UPDATE purchase_invoice_cancel
            SET invoice_no = ?, status = 'Finalized'
            WHERE id = ?
        ";
        $update_invoice_stmt = $connection->prepare($update_invoice_query);
        $update_invoice_stmt->bind_param("si", $purchase_invoice_no, $invoice_id);

        if (!$update_invoice_stmt->execute()) {
            throw new Exception("Error updating purchase_invoice: " . $update_invoice_stmt->error);
        }
        $update_invoice_stmt->close();

        // Commit transaction
        $connection->commit();

        $response['invoice_no'] = $purchase_invoice_no;
        $response['success'] = true;
        $response['message'] = "Invoice finalized successfully";
        echo json_encode($response);

    } catch (Exception $e) {
        $connection->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error processing invoice: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invoice ID not provided']);
}

$connection->close();
?>
