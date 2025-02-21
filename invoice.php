<?php
include 'connection.php'; // Replace with your actual connection file

$update_message = '';
$invoice = null;
$items = [];

if (isset($_GET['id'])) {
    $invoice_id = intval($_GET['id']);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $lot_trackingids = $_POST['lot_trackingid'];
        $expiration_dates = $_POST['expiration_date'];
        $item_ids = $_POST['item_id'];

        // AMC Fields
        $amc_codes = $_POST['amc_code'];
        $amc_paid_dates = $_POST['amc_paid_date'];
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

            // Fetch tracking flags for the product
            $tracking_query = "SELECT lot_tracking, expiration_tracking, amc_tracking FROM item WHERE item_code = ?";
            $stmt_tracking = $connection->prepare($tracking_query);
            $stmt_tracking->bind_param("s", $product_id);
            $stmt_tracking->execute();
            $tracking_result = $stmt_tracking->get_result();
            $tracking_row = $tracking_result->fetch_assoc();
            $lot_tracking = $tracking_row['lot_tracking'];
            $expiration_tracking = $tracking_row['expiration_tracking'];
            $amc_tracking = $tracking_row['amc_tracking'];
            $stmt_tracking->close();

            // Validate tracking flags
            if ($lot_tracking == 1 && empty($lot_trackingids[$index])) {
                $validation_errors[] = "Lot Tracking ID is required for product: " . $product_name;
            }

            if ($expiration_tracking == 1 && empty($expiration_dates[$index])) {
                $validation_errors[] = "Expiration Date is required for product: " . $product_name;
            }

            if ($amc_tracking == 1 && (empty($amc_codes[$index]) || empty($amc_paid_dates[$index]) || empty($amc_due_dates[$index]))) {
                $validation_errors[] = "AMC details are required for product: " . $product_name;
            }
        }

        // If there are validation errors, show an alert and stop processing
        if (!empty($validation_errors)) {
            echo "<script>alert('" . implode("\\n", $validation_errors) . "'); window.history.back();</script>";
            exit();
        }

        // Generate the new invoice number before updating item_ledger_history
        $last_invoice_query = "SELECT MAX(CAST(SUBSTRING(invoice_no, 4) AS UNSIGNED)) AS last_invoice_no FROM invoices";
        $last_invoice_result = $connection->query($last_invoice_query);
        $last_invoice = $last_invoice_result->fetch_assoc();
        $new_invoice_no = 'INV' . str_pad($last_invoice['last_invoice_no'] + 1, 4, '0', STR_PAD_LEFT);

        // Prepare the update statements
        $update_invoice_query = "UPDATE invoice_items SET lot_trackingid = ?, expiration_date = ?, amc_code = ?, amc_paid_date = ?, amc_due_date = ?, amc_amount = ? WHERE id = ? AND invoice_id = ?";
        $stmt = $connection->prepare($update_invoice_query);

        $update_ledger_query = "UPDATE item_ledger_history SET invoice_no = ?, lot_trackingid = ?, expiration_date = ? WHERE invoice_itemid = ?";
        $stmt_ledger = $connection->prepare($update_ledger_query);

        if ($stmt && $stmt_ledger) {
            $update_successful = true;

            // Loop through each item and update both tables
            foreach ($item_ids as $index => $item_id) {
                $lot_trackingid = $lot_trackingids[$index];
                $expiration_date = $expiration_dates[$index];
                $amc_code = $amc_codes[$index];
                $amc_paid_date = $amc_paid_dates[$index];
                $amc_due_date = $amc_due_dates[$index];
                $amc_amount = $amc_amounts[$index];

                // Update invoice_items table (including AMC fields)
                $stmt->bind_param("ssssssii", $lot_trackingid, $expiration_date, $amc_code, $amc_paid_date, $amc_due_date, $amc_amount, $item_id, $invoice_id);
                if (!$stmt->execute()) {
                    $update_successful = false;
                }

                // Update item_ledger_history table (only necessary fields)
                $stmt_ledger->bind_param("ssii", $new_invoice_no, $lot_trackingid, $expiration_date, $item_id);
                if (!$stmt_ledger->execute()) {
                    $update_successful = false;
                }
            }

            // Close statements
            $stmt->close();
            $stmt_ledger->close();

            if ($update_successful) {
                // Update the invoice status to 'Finalized'
                $update_status_query = "UPDATE invoices SET status = 'Finalized', invoice_no = ? WHERE id = ?";
                $stmt_status = $connection->prepare($update_status_query);
                $stmt_status->bind_param("si", $new_invoice_no, $invoice_id);
                $stmt_status->execute();
                $stmt_status->close();

                // Update AMC references
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
                        $stmt_update_amc_invoice->bind_param("si", $new_invoice_no, $ref_invoice_id);
                        $stmt_update_amc_invoice->execute();
                        $stmt_update_amc_invoice->close();
                    }

                    $stmt_fetch_invoice_id->close();
                }

                $stmt_fetch_reference->close();

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
                        -net_amount AS amount,
                        reference_invoice_no AS ref_doc_no
                    FROM invoices
                    WHERE id = ?";

                $stmt_party_ledger = $connection->prepare($insert_party_ledger_query);
                $stmt_party_ledger->bind_param("si", $new_invoice_no, $invoice_id);

                if ($stmt_party_ledger->execute()) {
                    echo "<script>alert('Record Updated Successfully and Party Ledger Entry Created'); window.location.href='invoice_display.php';</script>";
                } else {
                    echo "<script>alert('Record Updated Successfully but Failed to Create Party Ledger Entry'); window.location.href='invoice_display.php';</script>";
                }

                $stmt_party_ledger->close();
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

$connection->close();
?>

<!-- HTML form and other UI elements go here -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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
            /* max-width: 800px; */
            width: 115%;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
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
            justify-content: space-between;
            margin: 20px 0;
        }
        .details div {
            width: 48%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
            max-width: 150px; /* Adjust this value as needed */
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
      
    </style>
</head>
<body>
    <form method="post" action="">
    <div class="invoice-container">
        <?php if (!empty($update_message)): ?>
            <div class="update-message"><?php echo $update_message; ?></div>
        <?php endif; ?>
        <div class="header">
            <img src="splendid.png" alt="Splendid Logo" class="logo">
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
                    <p><?php echo htmlspecialchars($invoice['client_name']); ?></p>
                    <p><?php echo htmlspecialchars($invoice['client_address']); ?></p>
                    <p><?php echo htmlspecialchars($invoice['client_city']) . ', ' . htmlspecialchars($invoice['client_state']) . ' ' . htmlspecialchars($invoice['client_pincode']); ?></p>
                    <p><?php echo htmlspecialchars($invoice['client_country']); ?></p>
                    <p>Phone: <?php echo htmlspecialchars($invoice['client_phone']); ?></p>
                    <p>GSTIN: <?php echo htmlspecialchars($invoice['client_gstno']); ?></p>
                </div>
                <div>
                    <h4>Ship To:</h4>
                    <p><?php echo htmlspecialchars($invoice['shipper_company_name']); ?></p>
                    <p><?php echo htmlspecialchars($invoice['shipper_address']); ?></p>
                    <p><?php echo htmlspecialchars($invoice['shipper_city']) . ', ' . htmlspecialchars($invoice['shipper_state']) . ' ' . htmlspecialchars($invoice['shipper_pincode']); ?></p>
                    <p><?php echo htmlspecialchars($invoice['shipper_country']); ?></p>
                    <p>Phone: <?php echo htmlspecialchars($invoice['shipper_phone']); ?></p>
                    <p>GSTIN: <?php echo htmlspecialchars($invoice['shipper_gstno']); ?></p>
                </div>
            <?php } else { ?>
                <p>No details found for the given invoice ID.</p>
            <?php } ?>
        </div>

        <table>
            <thead>
                        <tr>
                 <th>#</th>
                 <th>Product</th>
                 <th>Unit</th>
                 <th>Quantity</th>
                 <th>Rate</th>
                 <th>GST (%)</th>
                 <th>IGST</th>
                 <th>CGST</th>
                 <th>SGST</th>
                 <th>Amount</th>
                 <th>Lot ID</th>
                 <th>Expiration Date</th>
                 <th>AMC Code</th>
                 <th>AMC Paid Date</th>
                 <th>AMC Due Date</th>

                 <th>AMC Amount</th>
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
                         <td><?php echo htmlspecialchars($item['gst']); ?></td>
                         <td><?php echo htmlspecialchars($item['igst']); ?></td>
                         <td><?php echo htmlspecialchars($item['cgst']); ?></td>
                         <td><?php echo htmlspecialchars($item['sgst']); ?></td>
                         <td><?php echo htmlspecialchars($item['amount']); ?></td>
                         <td><input type="text" name="lot_trackingid[]" value="<?php echo htmlspecialchars($item['lot_trackingid']); ?>" maxlength="20"></td>
                         <td><input type="date" name="expiration_date[]" value="<?php echo htmlspecialchars($item['expiration_date']); ?>"></td>
                         <td>
       <select name="amc_code[]" onchange="updateDueDate(this)">
           <option value="">Select Code</option>
           <?php
           // Database connection
           $conn = new mysqli("localhost", "root", "", "lead_management");
           if ($conn->connect_error) {
               die("Connection failed: " . $conn->connect_error);
           }

           // Fetch AMC data
           $query = "SELECT id, code, value FROM amc";
           $result = $conn->query($query);

           // Populate dropdown
           while ($row = $result->fetch_assoc()) {
               echo '<option value="' . htmlspecialchars($row['value']) . '">' . htmlspecialchars($row['code']) . '</option>';
           }
           $conn->close();
           ?>
       </select>

       <!-- Input field to store the AMC value -->
       <input type="text" name="amc_value[]" value="" readonly>
   </td>

                         <td><input type="date" name="amc_paid_date[]" value="<?php echo htmlspecialchars($item['amc_paid_date']); ?>"></td>
                         <td>
    <!-- AMC Due Date (To be auto-updated) -->
    <input type="date" name="amc_due_date[]" value="">
</td>
                         <td><input type="text" name="amc_amount[]" value="<?php echo htmlspecialchars($item['amc_amount']); ?>"></td>
                         <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">

                     </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="12">No items found for this invoice.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
            <div class="terms-conditions" style="width: 48%;">
                <h4>Terms and Conditions</h4>
                <div id="editor">
                    <p>1. Payment is due within 30 days</p>
                    <p>2. Please include the invoice number on your check</p>
                    <p>3. For questions concerning this invoice, please contact our accounting department</p>
                </div>
            </div>
            <div class="amount" style="width: 48%;">
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
            <!-- <button onclick="window.print()">Print Invoice</button> -->
            <button type="submit">Save Changes</button>
        </div>
    </div>
    </form>

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

</script>
</body>
</html>
