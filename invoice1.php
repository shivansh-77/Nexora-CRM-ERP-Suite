<?php
include 'connection.php'; // Replace with your actual connection file

if (isset($_GET['id'])) {
    $invoice_id = intval($_GET['id']);

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

        $items = [];
        while ($row = $items_result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt_items->close();
        $stmt_invoice->close();
    } else {
        echo "No invoice found for the given ID.";
    }

    $connection->close();
} else {
    echo "No ID provided.";
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
            background-color: #2c3e50;
        }
        .invoice-container {
            max-width: 900px;
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
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
          <?php
      include('connection.php'); // Include database connection

      // Fetch company logo from database
      $query = "SELECT company_logo FROM company_card WHERE id = 1"; // Change `1` to the correct company ID
      $result = mysqli_query($connection, $query);
      $company = mysqli_fetch_assoc($result);

      // Set logo path (fallback to default if not available)
      $company_logo = !empty($company['company_logo']) ? $company['company_logo'] : 'uploads/default_logo.png';
      ?>

      <!-- Display Dynamic Logo -->
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
                    <th>Lot Tracking ID</th>
                    <th>Expiration Date</th>
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
                            <td><?php echo htmlspecialchars($item['lot_trackingid']); ?></td>
                            <td><?php echo htmlspecialchars($item['expiration_date']); ?></td>
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
            <button onclick="window.print()">Print Invoice</button>
        </div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        var quill = new Quill('#editor', {
            theme: 'snow'
        });

        function printInvoice() {
            window.print();
        }
    </script>
</body>
</html>
