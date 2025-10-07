<?php
include 'connection.php'; // Replace with your actual connection file

$update_message = '';
$invoice = null;
$items = [];

if (isset($_GET['id'])) {
    $invoice_id = intval($_GET['id']);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $item_ids = $_POST['item_id'];

        // AMC Fields
        $amc_codes = $_POST['amc_code'];
        // $amc_paid_dates = $_POST['amc_paid_date'];
        $amc_due_dates = $_POST['amc_due_date'];
        $amc_amounts = $_POST['amc_amount'];

        // Validate each item
        $validation_errors = [];
        foreach ($item_ids as $index => $item_id) {
            // Fetch product_id and product_name for the current item
            $product_query = "SELECT product_id, product_name FROM invoice_items WHERE id = ?";
            $stmt_product = $connection->prepare($product_query);
            $stmt_product->bind_param("i", $item_id);
            $stmt_product->execute();
            $product_result = $stmt_product->get_result();
            $product_row = $product_result->fetch_assoc();
            $product_id = $product_row['product_id'];
            $product_name = $product_row['product_name']; // Get product name
            $stmt_product->close();

            // Fetch tracking flags for the product (only AMC tracking now)
            $tracking_query = "SELECT amc_tracking FROM item WHERE item_code = ?";
            $stmt_tracking = $connection->prepare($tracking_query);
            $stmt_tracking->bind_param("s", $product_id);
            $stmt_tracking->execute();
            $tracking_result = $stmt_tracking->get_result();
            $tracking_row = $tracking_result->fetch_assoc();
            $amc_tracking = $tracking_row['amc_tracking'];
            $stmt_tracking->close();

            // Validate AMC tracking flag
            if ($amc_tracking == 1 && (empty($amc_codes[$index])  || empty($amc_due_dates[$index]) || empty($amc_amounts[$index]))) {
                $validation_errors[] = "AMC details are required for product: " . $product_name;
            } elseif ($amc_tracking == 0 && (!empty($amc_codes[$index]) || !empty($amc_due_dates[$index]) || !empty($amc_amounts[$index]))) {
                $validation_errors[] = "AMC details are not required for product: " . $product_name . ". Please remove the entries.";
            }
        }

        // If there are validation errors, show an alert and stop processing
        if (!empty($validation_errors)) {
            echo "<script>alert('" . implode("\\n", $validation_errors) . "'); window.history.back();</script>";
            exit();
        }

        // Get the current year and format it to get the last two digits
        $currentYear = date('y');

        // Generate the new invoice number before updating item_ledger_history
        $last_invoice_query = "
            SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_no, 8) AS UNSIGNED)), 0) AS last_invoice_no
            FROM invoices
            WHERE invoice_no LIKE 'INV/$currentYear/%'
        ";
        $last_invoice_result = $connection->query($last_invoice_query);
        $last_invoice = $last_invoice_result->fetch_assoc();

        // Calculate the new sequential number
        $new_sequence_no = $last_invoice['last_invoice_no'] + 1;

        // Format the new invoice number
        $invoice_no = 'INV/' . $currentYear . '/' . str_pad($new_sequence_no, 4, '0', STR_PAD_LEFT);

        // ---------------------------------------------------------------------
        // Clone Quotation T&C to Invoice T&C (type = 'Sales')
        // ---------------------------------------------------------------------
        try {
            // 1) Fetch quotation_no for this invoice
            $sql_qno = "SELECT quotation_no FROM invoices WHERE id = ?";
            if ($stmt_qno = $connection->prepare($sql_qno)) {
                $stmt_qno->bind_param("i", $invoice_id);
                $stmt_qno->execute();
                $res_qno = $stmt_qno->get_result();
                $row_qno = $res_qno->fetch_assoc();
                $stmt_qno->close();

                if (!empty($row_qno) && !empty($row_qno['quotation_no'])) {
                    $quotation_no = $row_qno['quotation_no'];

                    // 2) Fetch Quotation T&C by document_no = quotation_no
                    $sql_fetch_tc = "SELECT terms_and_conditions FROM invoice_terms_conditions WHERE document_no = ? LIMIT 1";
                    if ($stmt_fetch_tc = $connection->prepare($sql_fetch_tc)) {
                        $stmt_fetch_tc->bind_param("s", $quotation_no);
                        $stmt_fetch_tc->execute();
                        $res_tc = $stmt_fetch_tc->get_result();
                        $row_tc = $res_tc->fetch_assoc();
                        $stmt_fetch_tc->close();

                        // If quotation T&C exists, create/update invoice T&C
                        if (!empty($row_tc) && isset($row_tc['terms_and_conditions'])) {
                            $tc_text = $row_tc['terms_and_conditions'];

                            // 3) Does an entry already exist for this new invoice_no?
                            $sql_exists = "SELECT id FROM invoice_terms_conditions WHERE document_no = ? LIMIT 1";
                            if ($stmt_exists = $connection->prepare($sql_exists)) {
                                $stmt_exists->bind_param("s", $invoice_no);
                                $stmt_exists->execute();
                                $res_exists = $stmt_exists->get_result();
                                $row_exists = $res_exists->fetch_assoc();
                                $stmt_exists->close();

                                if (!empty($row_exists)) {
                                    // 3a) UPDATE existing row
                                    $existing_id = (int)$row_exists['id'];
                                    $sql_update = "UPDATE invoice_terms_conditions
                                                   SET terms_and_conditions = ?, type = 'Sales'
                                                   WHERE id = ?";
                                    if ($stmt_update = $connection->prepare($sql_update)) {
                                        $stmt_update->bind_param("si", $tc_text, $existing_id);
                                        $stmt_update->execute();
                                        $stmt_update->close();
                                    }
                                } else {
                                    // 3b) INSERT new row
                                    $sql_insert = "INSERT INTO invoice_terms_conditions
                                                   (invoice_id, document_no, type, terms_and_conditions, created_at, updated_at)
                                                   VALUES (?, ?, 'Sales', ?, NOW(), NOW())";
                                    if ($stmt_insert = $connection->prepare($sql_insert)) {
                                        $stmt_insert->bind_param("iss", $invoice_id, $invoice_no, $tc_text);
                                        $stmt_insert->execute();
                                        $stmt_insert->close();
                                    }
                                }
                            }
                        }
                        // If no quotation T&C found, silently skip (per your instruction)
                    }
                }
                // If no quotation_no found, silently skip (per your instruction)
            }
        } catch (Throwable $e) {
            // Silently ignore any errors in this auxiliary step (do not break your existing flow)
            // You can log $e->getMessage() if you have a logger.
        }
        // ---------------------------------------------------------------------
        // End Clone T&C
        // ---------------------------------------------------------------------

        // Prepare the update statements
        $update_invoice_query = "UPDATE invoice_items SET amc_code = ?, amc_paid_date = ?, amc_due_date = ?, amc_amount = ? WHERE id = ? AND invoice_id = ?";
        $stmt = $connection->prepare($update_invoice_query);

        $update_ledger_query = "UPDATE item_ledger_history SET invoice_no = ? WHERE invoice_itemid = ?";
        $stmt_ledger = $connection->prepare($update_ledger_query);

        if ($stmt && $stmt_ledger) {
            $update_successful = true;

            // Get all product names first to check for AMC products
            $product_names = [];
            foreach ($item_ids as $index => $item_id) {
                $product_query = "SELECT product_name FROM invoice_items WHERE id = ?";
                $stmt_product = $connection->prepare($product_query);
                $stmt_product->bind_param("i", $item_id);
                $stmt_product->execute();
                $product_result = $stmt_product->get_result();
                $product_row = $product_result->fetch_assoc();
                $product_names[$item_id] = $product_row['product_name'];
                $stmt_product->close();
            }

            // Loop through each item and update both tables
            foreach ($item_ids as $index => $item_id) {
                $amc_code = $amc_codes[$index];
                // $amc_paid_date = $amc_paid_dates[$index];
                $amc_due_date = $amc_due_dates[$index];
                $amc_amount = $amc_amounts[$index];

                // Update invoice_items table (including AMC fields)
                $stmt->bind_param("ssssii", $amc_code, $amc_paid_date, $amc_due_date, $amc_amount, $item_id, $invoice_id);
                if (!$stmt->execute()) {
                    $update_successful = false;
                }

                // Update item_ledger_history table
                $stmt_ledger->bind_param("si", $invoice_no, $item_id);
                if (!$stmt_ledger->execute()) {
                    $update_successful = false;
                }
            }

            // Close statements
            $stmt->close();
            $stmt_ledger->close();

            if ($update_successful) {
                // Check if any product name ends with "-AMC"
                $has_amc_product = false;
                foreach ($product_names as $product_name) {
                    if (strpos($product_name, '-AMC') !== false) {
                        $has_amc_product = true;
                        break;
                    }
                }

                // Update the invoice status to 'Finalized' and populate pending_amount
                if ($has_amc_product) {
                    // For AMC products, set pending_amount to the sum of all AMC amounts
                    $update_status_query = "UPDATE invoices
                                          SET status = 'Finalized',
                                              invoice_no = ?,
                                              pending_amount = (
                                                  SELECT SUM(amc_amount)
                                                  FROM invoice_items
                                                  WHERE invoice_id = ?
                                              )
                                          WHERE id = ?";

                    // Prepare and execute invoice update
                    $stmt_status = $connection->prepare($update_status_query);
                    $stmt_status->bind_param("sii", $invoice_no, $invoice_id, $invoice_id);

                    if (!$stmt_status->execute()) {
                        $update_successful = false;
                    }
                    $stmt_status->close();

                    // Insert entry into party_ledger table using pending_amount (AMC total)
                    $insert_party_ledger_query = "INSERT INTO party_ledger
                                                (ledger_type, party_no, party_name, party_type,
                                                 document_type, document_no, amount, ref_doc_no)
                                                SELECT
                                                    'Customer Ledger' AS ledger_type,
                                                    client_id AS party_no,
                                                    client_name AS party_name,
                                                    'Customer' AS party_type,
                                                    'Sales Invoice' AS document_type,
                                                    ? AS document_no,
                                                    -pending_amount AS amount,
                                                    reference_invoice_no AS ref_doc_no
                                                FROM invoices
                                                WHERE id = ?";

                    $stmt_ledger = $connection->prepare($insert_party_ledger_query);
                    $stmt_ledger->bind_param("si", $invoice_no, $invoice_id);

                    if (!$stmt_ledger->execute()) {
                        $update_successful = false;
                    }
                    $stmt_ledger->close();

                    // // Insert corresponding entries into advance_payments
                    // if ($update_successful) {
                    //     $insert_advance_query = "INSERT INTO advance_payments
                    //         (ledger_type, party_no, party_name, party_type, document_type, document_no, amount, ref_doc_no)
                    //         SELECT
                    //             ledger_type,
                    //             party_no,
                    //             party_name,
                    //             party_type,
                    //             document_type,
                    //             document_no,
                    //             amount,
                    //             ref_doc_no
                    //         FROM party_ledger WHERE document_no = ?";
                    //
                    //     $stmt_adv = $connection->prepare($insert_advance_query);
                    //     $stmt_adv->bind_param("s", $invoice_no);
                    //     $stmt_adv->execute();
                    //     $stmt_adv->close();
                    // }

                    // Update AMC references (your new code)
                    $fetch_reference_query = "SELECT reference_invoice_no FROM invoice_items WHERE invoice_id = ? AND reference_invoice_no IS NOT NULL";
                    $stmt_fetch_reference = $connection->prepare($fetch_reference_query);
                    $stmt_fetch_reference->bind_param("i", $invoice_id);
                    $stmt_fetch_reference->execute();
                    $result_fetch_reference = $stmt_fetch_reference->get_result();

                    while ($row = $result_fetch_reference->fetch_assoc()) {
                        $reference_invoice_no = $row['reference_invoice_no'];

                        $fetch_invoice_id_query = "SELECT id FROM invoices WHERE invoice_no = ?";
                        $stmt_fetch_invoice_id = $connection->prepare($fetch_invoice_id_query);
                        $stmt_fetch_invoice_id->bind_param("s", $reference_invoice_no);
                        $stmt_fetch_invoice_id->execute();
                        $result_invoice_id = $stmt_fetch_invoice_id->get_result();

                        if ($invoice_row = $result_invoice_id->fetch_assoc()) {
                            $ref_invoice_id = $invoice_row['id'];

                            $update_amc_invoice_query = "UPDATE invoice_items SET new_amc_invoice_no = ?, new_amc_invoice_gen_date = NOW() WHERE invoice_id = ?";
                            $stmt_update_amc_invoice = $connection->prepare($update_amc_invoice_query);
                            $stmt_update_amc_invoice->bind_param("si", $invoice_no, $ref_invoice_id);
                            $stmt_update_amc_invoice->execute();
                            $stmt_update_amc_invoice->close();
                        }

                        $stmt_fetch_invoice_id->close();
                    }

                    $stmt_fetch_reference->close();

                    // Immediately redirect to invoice_draft.php after AMC processing
                    header("Location: invoice_draft.php");
                    exit();

                } else {
                    // For non-AMC products, set pending_amount to net_amount
                    $update_status_query = "UPDATE invoices
                                            SET status = 'Finalized',
                                                invoice_no = ?,
                                                pending_amount = net_amount
                                            WHERE id = ?";

                    $stmt_status = $connection->prepare($update_status_query);
                    $stmt_status->bind_param("si", $invoice_no, $invoice_id);
                    $stmt_status->execute();
                    $stmt_status->close();

                    // Insert entry into party_ledger table
                    $insert_party_ledger_query = "INSERT INTO party_ledger
                        (ledger_type, party_no, party_name, party_type, document_type, document_no, amount, ref_doc_no)
                        SELECT
                            'Customer Ledger' AS ledger_type,
                            client_id AS party_no,
                            client_name AS party_name,
                            'Customer' AS party_type,
                            'Sales Invoice' AS document_type,
                            ? AS document_no,
                            net_amount AS amount,
                            reference_invoice_no AS ref_doc_no
                        FROM invoices
                        WHERE id = ?";

                    $stmt_party_ledger = $connection->prepare($insert_party_ledger_query);
                    $stmt_party_ledger->bind_param("si", $invoice_no, $invoice_id);

                    if ($stmt_party_ledger->execute()) {
                        // Insert corresponding entries into advance_payments
                        $insert_advance_query = "INSERT INTO advance_payments
                            (ledger_type, party_no, party_name, party_type, document_type, document_no, amount, ref_doc_no)
                            SELECT
                                ledger_type,
                                party_no,
                                party_name,
                                party_type,
                                document_type,
                                document_no,
                                amount,
                                ref_doc_no
                            FROM party_ledger WHERE document_no = ?";

                        $stmt_adv = $connection->prepare($insert_advance_query);
                        $stmt_adv->bind_param("s", $invoice_no);

                        $advance_insert_success = false;
                        if ($stmt_adv->execute()) {
                            $advance_insert_success = true;
                        }
                        $stmt_adv->close();

                        if ($advance_insert_success) {
                            echo "<script>alert('Invoice Generated Successfully and Party Ledger & Advance Payment Entry Created!'); window.location.href='invoice_display.php';</script>";
                        } else {
                            echo "<script>alert('Invoice Generated Successfully and Party Ledger Entry Created but Failed to create Advance Payment entry!'); window.location.href='invoice_display.php';</script>";
                        }
                    } else {
                        echo "<script>alert('Invoice Generated Successfully but Failed to Create Party Ledger Entry!'); window.location.href='invoice_display.php';</script>";
                    }
                    $stmt_party_ledger->close();

                    // Fetch entries from purchase_order_item_lots and update their invoice_no
                    $fetch_lots_query = "SELECT * FROM purchase_order_item_lots WHERE invoice_id_main = ?";
                    $stmt_fetch_lots = $connection->prepare($fetch_lots_query);
                    $stmt_fetch_lots->bind_param("i", $invoice_id);
                    $stmt_fetch_lots->execute();
                    $lots_result = $stmt_fetch_lots->get_result();

                    while ($lot_row = $lots_result->fetch_assoc()) {
                        $lot_id = $lot_row['id'];

                        // Update invoice_no in purchase_order_item_lots
                        $update_lot_query = "UPDATE purchase_order_item_lots SET invoice_no = ? WHERE id = ?";
                        $stmt_update_lot = $connection->prepare($update_lot_query);
                        $stmt_update_lot->bind_param("si", $invoice_no, $lot_id);
                        $stmt_update_lot->execute();
                        $stmt_update_lot->close();

                        // Insert into item_ledger_history
                        $insert_ledger_history_query = "INSERT INTO item_ledger_history
                            (invoice_no, document_type, entry_type, product_id, product_name, quantity, location, unit, date, value, invoice_itemid, lot_trackingid, expiration_date, rate, invoice_id_main)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                        // Convert quantity to negative
                        $negative_quantity = -1 * abs($lot_row['quantity']);

                        $stmt_insert_ledger = $connection->prepare($insert_ledger_history_query);
                        $stmt_insert_ledger->bind_param("sssssssssssssss",
                            $invoice_no,
                            $lot_row['document_type'],
                            $lot_row['entry_type'],
                            $lot_row['product_id'],
                            $lot_row['product_name'],
                            $negative_quantity,  // Using the negative quantity here
                            $lot_row['location'],
                            $lot_row['unit'],
                            $lot_row['date'],
                            $lot_row['value'],
                            $lot_row['invoice_itemid'],
                            $lot_row['lot_trackingid'],
                            $lot_row['expiration_date'],
                            $lot_row['rate'],
                            $lot_row['invoice_id_main']
                        );
                        $stmt_insert_ledger->execute();
                        $stmt_insert_ledger->close();
                    }
                    $stmt_fetch_lots->close();
                }

            } else {
                $update_message = "Error updating records.";
            }
        } else {
            $update_message = "Error preparing statement: " . $connection->error;
        }
    }

    // Fetch invoice details
    $invoice_query = "SELECT * FROM invoices WHERE id = ?";
    $stmt_invoice = $connection->prepare($invoice_query);
    $stmt_invoice->bind_param("i", $invoice_id);
    $stmt_invoice->execute();
    $invoice_result = $stmt_invoice->get_result();
    $invoice = $invoice_result->fetch_assoc();

    if ($invoice) {
        // Fetch invoice items
        $items_query = "SELECT * FROM invoice_items WHERE invoice_id = ?";
        $stmt_items = $connection->prepare($items_query);
        $stmt_items->bind_param("i", $invoice_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        while ($row = $items_result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt_items->close();
    } else {
        $update_message = "No invoice found for the given ID.";
    }

    $stmt_invoice->close();
} else {
    $update_message = "No ID provided.";
}

?>




<!-- HTML form and other UI elements go here -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
        }

        .invoice-container {
            width: 95%;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            position: relative;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo {
            max-width: 150px;
            height: auto;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            color: #333;
        }

        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 20px 0;
        }

        .details div {
            flex: 1;
            min-width: 48%;
        }

        .scrollable-table-container {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
        }

        .amount {
            text-align: right;
            margin-top: 20px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .terms-conditions {
            margin-top: 20px;
        }

        #editor {
            height: 200px;
        }

        @media print {
            body {
                background-color: #fff;
            }
            .invoice-container {
                border: none;
                margin: 0;
                padding: 0;
            }
            .no-print, .ql-toolbar {
                display: none;
            }
            #editor {
                border: none;
            }
        }

        table input[type="text"], table input[type="date"] {
            width: 100%;
            box-sizing: border-box;
            padding: 4px;
        }

        table td {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .update-message {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .close-button {
            position: absolute;
            top: 1px;
            right: 2px;
            background: none;
            border: none;
            font-size: 11px;
            cursor: pointer;
            text-decoration: None;
        }

        .loading-spinner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .scrollable-table-container {
    width: 100%; /* Adjust the width as needed */
    max-height: 500px; /* Adjust the height as needed */
    overflow: auto; /* Enable both vertical and horizontal scrolling */
    border: 1px solid #ddd; /* Add a border for visual separation */
    margin: 20px 0; /* Add some margin for spacing */
}

.scrollable-table-container table {
    width: 100%; /* Ensure the table takes the full width of the container */
    border-collapse: collapse; /* Collapse borders for a cleaner look */
    min-width: 1200px; /* Set a minimum width to ensure horizontal scrolling */
}

.scrollable-table-container th,
.scrollable-table-container td {
    padding: 12px 15px; /* Add padding for better readability */
    text-align: left; /* Align text to the left */
    border: 1px solid #ddd; /* Add borders to cells */
    min-width: 100px; /* Set a minimum width for cells */
}

.scrollable-table-container th {
    background-color: #f2f2f2; /* Add a background color to headers */
    font-weight: bold; /* Make header text bold */
    position: sticky; /* Make headers sticky */
    top: 0; /* Stick to the top */
}

.scrollable-table-container tr:nth-child(even) {
    background-color: #f9f9f9; /* Add a background color to even rows for better readability */
}

.scrollable-table-container tr:hover {
    background-color: #f1f1f1; /* Add a hover effect to rows */
}

.scrollable-table-container input[type="text"],
.scrollable-table-container input[type="date"],
.scrollable-table-container select {
    width: 100%; /* Ensure inputs and selects take the full width of the cell */
    padding: 8px; /* Add padding for better readability */
    box-sizing: border-box; /* Include padding and border in the element's total width and height */
    border: 1px solid #ddd; /* Add a border for visual separation */
}

.scrollable-table-container .btn {
    padding: 8px 12px; /* Add padding to buttons */
    background-color: #007bff; /* Add a background color to buttons */
    color: white; /* Set button text color to white */
    border: none; /* Remove border */
    border-radius: 4px; /* Add border radius for rounded corners */
    cursor: pointer; /* Change cursor to pointer on hover */
    text-decoration: none; /* Remove underline from links */
    display: inline-block; /* Ensure buttons are inline-block */
}

.scrollable-table-container .btn:hover {
    background-color: #0056b3; /* Change button background color on hover */
}

    </style>
</head>
<body>
    <div id="loadingSpinner" class="loading-spinner">
        <div class="spinner"></div>
    </div>

    <form id="invoiceForm" method="post">
        <div class="invoice-container">
            <a href="invoice_draft.php" class="close-button" onclick="closeForm()">✖</a>

            <?php if (!empty($update_message)): ?>
                <div class="update-message"><?php echo $update_message; ?></div>
            <?php endif; ?>

            <div class="header">
                <?php
                include('connection.php');
                $query = "SELECT company_logo FROM company_card WHERE id = 1";
                $result = mysqli_query($connection, $query);
                $company = mysqli_fetch_assoc($result);
                $company_logo = !empty($company['company_logo']) ? $company['company_logo'] : 'uploads/default_logo.png';
                ?>
                <img src="<?php echo $company_logo; ?>" alt="Logo" class="logo" />
                <h1>Invoice</h1>
            </div>

            <div class="invoice-info">
                <p><strong>Invoice No:</strong> <?php echo isset($invoice['invoice_no']) ? htmlspecialchars($invoice['invoice_no']) : ''; ?></p>
                <p><strong>Date:</strong> <?php echo isset($invoice['invoice_date']) ? htmlspecialchars($invoice['invoice_date']) : ''; ?></p>
            </div>

            <div class="details">
                <?php if ($invoice) { ?>
                    <div>
                        <h4>Bill To:</h4>
                        <p>Name: <?php echo htmlspecialchars($invoice['client_company_name']); ?></p>
                        <p>Contact Person: <?php echo htmlspecialchars($invoice['client_name']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($invoice['client_phone']); ?></p>
                        <p>Address: <?php echo htmlspecialchars($invoice['client_address']); ?></p>
                        <p>City: <?php echo htmlspecialchars($invoice['client_city']); ?></p>
                        <p>State: <?php echo htmlspecialchars($invoice['client_state']); ?></p>
                        <p>Country: <?php echo htmlspecialchars($invoice['client_country']); ?></p>
                        <p>Pincode: <?php echo htmlspecialchars($invoice['client_pincode']); ?></p>
                        <p>GSTIN: <?php echo htmlspecialchars($invoice['client_gstno']); ?></p>
                    </div>
                    <div>
                        <h4>Ship To:</h4>
                        <p>Name: <?php echo htmlspecialchars($invoice['shipper_company_name']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($invoice['shipper_phone']); ?></p>
                        <p>Address: <?php echo htmlspecialchars($invoice['shipper_address']); ?></p>
                        <p>City: <?php echo htmlspecialchars($invoice['shipper_city']); ?></p>
                        <p>State: <?php echo htmlspecialchars($invoice['shipper_state']); ?></p>
                        <p>Country: <?php echo htmlspecialchars($invoice['shipper_country']); ?></p>
                        <p>Pincode: <?php echo htmlspecialchars($invoice['shipper_pincode']); ?></p>
                        <p>GSTIN: <?php echo htmlspecialchars($invoice['shipper_gstno']); ?></p>
                    </div>
                <?php } else { ?>
                    <p>No details found for the given invoice ID.</p>
                <?php } ?>
            </div>

            <div class="scrollable-table-container">
      <table>
          <thead>
              <tr>
                  <th>#</th>
                  <th>Product</th>
                  <th>Unit</th>
                  <th>Quantity</th>
                  <th>Rate</th>
                  <th>Stock</th>
                  <th>GST (%)</th>
                  <th>IGST</th>
                  <th>CGST</th>
                  <th>SGST</th>
                  <th>Amount</th>
                  <th>AMC Code</th>
                  <th>AMC Due Date</th>
                  <th>AMC Amount</th>
                  <th>Lot Details</th>
              </tr>
          </thead>
          <tbody>
              <?php if (!empty($items)) { ?>
                  <?php foreach ($items as $index => $item) { ?>
                      <tr>
                          <td><?php echo $index + 1; ?></td>
                          <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                          <td><?php echo htmlspecialchars($item['unit']); ?></td>
                          <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                          <td><?php echo htmlspecialchars($item['rate']); ?></td>
                          <td><?php echo htmlspecialchars($item['stock']); ?></td>
                          <td><?php echo htmlspecialchars($item['gst']); ?></td>
                          <td><?php echo htmlspecialchars($item['igst']); ?></td>
                          <td><?php echo htmlspecialchars($item['cgst']); ?></td>
                          <td><?php echo htmlspecialchars($item['sgst']); ?></td>
                          <td><?php echo htmlspecialchars($item['amount']); ?></td>
                          <td>
                              <select name="amc_code[]" onchange="updateDueDate(this)">
                                  <option value="">Select Code</option>
                                  <?php
                                  $query = "SELECT id, code, value FROM amc";
                                  $result = $connection->query($query);
                                  while ($row = $result->fetch_assoc()) {
                                      $selected = ($row['value'] == $item['amc_code']) ? 'selected' : '';
                                      echo '<option value="' . htmlspecialchars($row['value']) . '" ' . $selected . '>' . htmlspecialchars($row['code']) . '</option>';
                                  }
                                  ?>
                              </select>
                              <input type="text" name="amc_value[]" value="<?php echo htmlspecialchars($item['amc_code']); ?>" readonly>
                          </td>
                          <td>
                              <input type="date" name="amc_due_date[]" value="<?php echo htmlspecialchars($item['amc_due_date']); ?>">
                          </td>
                          <td><input type="text" name="amc_amount[]" value="<?php echo htmlspecialchars($item['amc_amount']); ?>"></td>
                          <td>
                              <a href="invoice_draft_sale_lot_add.php?invoice_id=<?php echo $invoice_id; ?>&item_id=<?php echo $item['id']; ?>&product_id=<?php echo $item['product_id']; ?>"
                                 class="btn btn-primary"
                                 title="View/Add Lot Details">
                                  Lot Details
                              </a>
                          </td>
                          <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                      </tr>
                  <?php } ?>
              <?php } else { ?>
                  <tr>
                      <td colspan="14">No items found for this invoice.</td>
                  </tr>
              <?php } ?>
          </tbody>
      </table>
  </div>


            <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <div class="amount" style="width: 48%;">
                    <p><strong>Base Amount:</strong> <?php echo isset($invoice['base_amount']) ? htmlspecialchars($invoice['base_amount']) : ''; ?></p>
                    <p><strong>Total CGST:</strong> <?php echo isset($invoice['total_cgst']) ? htmlspecialchars($invoice['total_cgst']) : ''; ?></p>
                    <p><strong>Total SGST:</strong> <?php echo isset($invoice['total_sgst']) ? htmlspecialchars($invoice['total_sgst']) : ''; ?></p>
                    <p><strong>Total IGST:</strong> <?php echo isset($invoice['total_igst']) ? htmlspecialchars($invoice['total_igst']) : ''; ?></p>
                    <p><strong>Gross Amount:</strong> <?php echo isset($invoice['gross_amount']) ? htmlspecialchars($invoice['gross_amount']) : ''; ?></p>
                    <p><strong>Discount:</strong> <?php echo isset($invoice['discount']) ? htmlspecialchars($invoice['discount']) : ''; ?></p>
                    <p><strong>Net Amount:</strong> <?php echo isset($invoice['net_amount']) ? htmlspecialchars($invoice['net_amount']) : ''; ?></p>
                </div>
            </div>

            <div class="footer">
                <p>Thank you for your business!</p>
            </div>

            <div class="no-print">
      <button type="submit" onclick="return confirmDraftToInvoice()">Save Changes</button>
  </div>

  <script>
  function confirmDraftToInvoice() {
      return confirm("Do you want to convert this draft to an invoice?");
  }
  </script>

        </div>
    </form>
</body>
</html>

      <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
      <script>
          var quill = new Quill('#editor', {
              theme: 'snow'
          });
          function printInvoice() {
              window.print();
          }
      </script>
      <script>
  function updateValue(selectElement) {
      // Get selected value and set it to the corresponding input field
      var selectedValue = selectElement.value;
      var inputField = selectElement.nextElementSibling;
      inputField.value = selectedValue;
  }

  function updateDueDate(selectElement) {
      var selectedValue = parseInt(selectElement.value); // Get the selected value (days)
      var inputField = selectElement.nextElementSibling; // Reference to the AMC value field
      inputField.value = selectedValue; // Set the AMC value field
      // Calculate the new due date
      if (!isNaN(selectedValue)) {
          var today = new Date();
          today.setDate(today.getDate() + selectedValue); // Add selected days
          var formattedDate = today.toISOString().split('T')[0]; // Format as YYYY-MM-DD
          // Update the AMC due date field in the same row
          var row = selectElement.closest("tr");
          var dueDateField = row.querySelector("input[name='amc_due_date[]']");
          if (dueDateField) {
              dueDateField.value = formattedDate;
          }
      }
  }

  // Function to validate lot details before form submission
  function validateLotDetails(invoiceId) {
      return new Promise((resolve, reject) => {
          // Show loading spinner
          document.getElementById('loadingSpinner').style.display = 'flex';

          // Make an AJAX request to validate_sale_lot_details.php
          fetch('validate_sale_lot_details.php?invoice_id=' + invoiceId)
              .then(response => {
                  if (!response.ok) {
                      throw new Error('Network response was not ok');
                  }
                  return response.json();
              })
              .then(data => {
                  // Hide loading spinner
                  document.getElementById('loadingSpinner').style.display = 'none';

                  if (data.valid) {
                      // If validation passes, resolve the promise
                      resolve(true);
                  } else {
                      // If validation fails, show error messages
                      let errorMessage = "Lot validation failed:\n";

                      // Add error messages
                      if (data.errors && data.errors.length > 0) {
                          errorMessage += "\nErrors:\n" + data.errors.join("\n");
                      }

                      // Add warning messages
                      if (data.warnings && data.warnings.length > 0) {
                          errorMessage += "\nWarnings:\n" + data.warnings.join("\n");
                      }

                      // Show alert with error messages
                      alert(errorMessage);
                      reject(false);
                  }
              })
              .catch(error => {
                  // Hide loading spinner
                  document.getElementById('loadingSpinner').style.display = 'none';

                  console.error("Error validating lot details:", error);
                  alert("Error validating lot details. Please try again.");
                  reject(error);
              });
      });
  }

  // Set up form submission handler
  document.addEventListener('DOMContentLoaded', function() {
      const invoiceForm = document.getElementById('invoiceForm');

      if (invoiceForm) {
          invoiceForm.addEventListener('submit', async function(event) {
              event.preventDefault();

              // Get the invoice ID from the URL
              const urlParams = new URLSearchParams(window.location.search);
              const invoiceId = urlParams.get('id');

              if (!invoiceId) {
                  alert("Invoice ID not found");
                  return;
              }

              try {
                  // Validate lot details first
                  const isValid = await validateLotDetails(invoiceId);

                  if (isValid) {
                      // If validation passes, submit the form
                      this.submit();
                  }
              } catch (error) {
                  // Validation failed, form submission is prevented
                  console.log("Validation failed, form not submitted");
              }
          });
      }
  });
  </script>
  </body>
  </html>
