<?php
$conn = new mysqli("localhost", "root", "", "lead_management");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch invoice details if an ID is provided
$invoice_id = isset($_GET['id']) ? $_GET['id'] : null;
$invoice = null;
$invoice_items = [];

if ($invoice_id) {
    $invoice_query = "SELECT * FROM invoices WHERE id = ?";
    $stmt = $conn->prepare($invoice_query);
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();

    $items_query = "SELECT * FROM invoice_items WHERE invoice_id = ?";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $invoice_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle form submission for updating
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update invoice details
    $update_invoice = "UPDATE invoices SET
        invoice_date = ?
        WHERE id = ?";
    $stmt = $conn->prepare($update_invoice);
    $stmt->bind_param("si", $_POST['invoice_date'], $invoice_id);
    $stmt->execute();

    // Update invoice items
    foreach ($_POST['item_id'] as $index => $item_id) {
        $update_item = "UPDATE invoice_items SET
            lot_trackingid = ?,
            expiration_date = ?,
            amc_code = ?,
            amc_term = ?,
            amc_paid_date = ?,
            amc_due_date = ?,
            amc_amount = ?
            WHERE id = ?";
        $stmt = $conn->prepare($update_item);
        $stmt->bind_param("ssssssdi",
            $_POST['lot_tracking_id'][$index],
            $_POST['expiring_date'][$index],
            $_POST['amc_code'][$index],
            $_POST['amc_term'][$index],
            $_POST['amc_paid_date'][$index],
            $_POST['amc_due_date'][$index],
            $_POST['amc_amount'][$index],
            $item_id
        );
        $stmt->execute();
    }

    echo "<script>alert('Invoice Updated Successfully'); window.location.href='invoice_display.php';</script>";
    exit;
}
?>

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
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            overflow-x: auto; /* Allow horizontal scrolling */
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
            table-layout: fixed; /* Ensure table columns have fixed width */
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 6px; /* Reduced padding */
            text-align: left;
            font-size: 12px; /* Reduced font size */
            word-wrap: break-word; /* Allow text to wrap */
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
            font-size: 12px; /* Reduced font size */
        }

        table td {
            max-width: 100px; /* Adjust this value as needed */
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
                            <td><input type="text" name="amc_code[]" value="<?php echo htmlspecialchars($item['amc_code']); ?>" maxlength="30"></td>
                            <td><input type="date" name="amc_paid_date[]" value="<?php echo htmlspecialchars($item['amc_paid_date']); ?>"></td>
                            <td><input type="date" name="amc_due_date[]" value="<?php echo htmlspecialchars($item['amc_due_date']); ?>"></td>
                            <td><input type="text" name="amc_amount[]" value="<?php echo htmlspecialchars($item['amc_amount']); ?>"></td>
                            <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="16">No items found for this invoice.</td>
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
    // Add any necessary JavaScript for dynamic calculations or validations
    function updateDueDateFromAMC(element) {
        let row = element.closest("tr");
        let amcCodeDropdown = row.querySelector('select[name="amc_code[]"]');
        let paidDateInput = row.querySelector('input[name="amc_paid_date[]"]');
        let dueDateInput = row.querySelector('input[name="amc_due_date[]"]');

        // Fetch AMC term based on selected AMC code
        let amcTerm = 0; // You need to implement a way to get the AMC term based on the selected code
        let baseDate = paidDateInput.value ? new Date(paidDateInput.value) : new Date();

        baseDate.setDate(baseDate.getDate() + amcTerm);

        let formattedDate = baseDate.toISOString().split('T')[0];
        dueDateInput.value = formattedDate;
    }

    // Add event listeners to AMC code and paid date inputs
    document.querySelectorAll('select[name="amc_code[]"], input[name="amc_paid_date[]"]').forEach(element => {
        element.addEventListener('change', function() {
            updateDueDateFromAMC(this);
        });
    });
</script>

</body>
</html>
