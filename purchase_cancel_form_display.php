<?php
include 'connection.php';

if (isset($_GET['id'])) {
    $invoice_id = intval($_GET['id']);

    // Fetch invoice details
    $invoice_query = "SELECT * FROM purchase_invoice_cancel WHERE id = ?";
    $stmt_invoice = $connection->prepare($invoice_query);
    $stmt_invoice->bind_param("i", $invoice_id);
    $stmt_invoice->execute();
    $invoice_result = $stmt_invoice->get_result();
    $invoice = $invoice_result->fetch_assoc();

    if ($invoice) {
        // Fetch invoice items with proper lot_tracking logic
        $items_query = "
            SELECT
                pi.id,
                pi.product_id,
                pi.product_name,
                pi.unit,
                pi.value,
                pi.quantity,
                pi.rate,
                pi.gst,
                pi.igst,
                pi.cgst,
                pi.sgst,
                pi.amount,

                pi.stock,
                i.lot_tracking
            FROM
                purchase_invoice_cancel_items pi
            LEFT JOIN
                item i ON pi.product_id = i.item_code
            WHERE
                pi.invoice_id = ?
        ";
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
    <title>Purchase Lot Tracking Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #2c3e50;
        }
        .invoice-container {
            max-width: 1000px;
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
        .save-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .uneditable {
            pointer-events: none;
            opacity: 0.6;
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
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
  <div class="invoice-container">
  <div class="header" style="position: relative;"> <!-- Added relative positioning -->
      <?php
      include('connection.php');
      $query = "SELECT company_logo FROM company_card WHERE id = 1";
      $result = mysqli_query($connection, $query);
      $company = mysqli_fetch_assoc($result);
      $company_logo = !empty($company['company_logo']) ? $company['company_logo'] : 'uploads/default_logo.png';
      ?>
      <img src="<?php echo $company_logo; ?>" alt="Logo" class="logo" />
      <h1>Purchase Cancel Lot Tracking Form</h1>
      <!-- Added cross button -->
      <a href="purchase_invoice_add_lot.php" style="position: absolute; top: -20px; right: -16px; font-size: 24px; color: #333; text-decoration: none;">×</a>
  </div>

        <div class="invoice-info">
            <p><strong>Invoice No:</strong> <?php echo isset($invoice['invoice_no']) ? htmlspecialchars($invoice['invoice_no']) : ''; ?></p>
            <p><strong>Date:</strong> <?php echo isset($invoice['invoice_date']) ? htmlspecialchars($invoice['invoice_date']) : ''; ?></p>
        </div>

        <div class="details">
            <?php if ($invoice) { ?>
                <div>
                    <h4>Bill From / Vendor</h4>
                    <p>Name: <?php echo htmlspecialchars($invoice['vendor_company_name']); ?></p>
                    <p>Contact Person: <?php echo htmlspecialchars($invoice['vendor_name']); ?></p>
                    <p>Phone: <?php echo htmlspecialchars($invoice['vendor_phone']); ?></p>
                    <p>Address: <?php echo htmlspecialchars($invoice['vendor_address']); ?></p>
                    <p>City: <?php echo htmlspecialchars($invoice['vendor_city']); ?></p>
                    <p>State: <?php echo htmlspecialchars($invoice['vendor_state']); ?></p>
                    <p>Country: <?php echo htmlspecialchars($invoice['vendor_country']); ?></p>
                    <p>Pincode: <?php echo htmlspecialchars($invoice['vendor_pincode']); ?></p>
                    <p>GSTIN: <?php echo htmlspecialchars($invoice['vendor_gstno']); ?></p>
                </div>
                <div>
                    <h4>Received By/ Receiver</h4>
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

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Unit</th>
                    <th>Quantity</th>
                    <th>Stock</th>
                    <th>Rate</th>
                    <th>Amount</th>
                    <th>Lot Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)) { ?>
                    <?php foreach ($items as $index => $item) {
                        $isEditable = ($item['lot_tracking'] == 1);
                    ?>
                        <tr class="<?php echo $isEditable ? 'editable' : 'uneditable'; ?>">
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($item['stock']); ?></td>
                            <td><?php echo htmlspecialchars($item['rate']); ?></td>
                            <td><?php echo htmlspecialchars($item['amount']); ?></td>
                            <td>
                                <?php if ($isEditable) { ?>
                                    <a href="purchase_cancel_lot_add.php?invoice_id=<?php echo $invoice_id; ?>&invoice_item_id=<?php echo htmlspecialchars($item['id']); ?>&product_id=<?php echo htmlspecialchars($item['product_id']); ?>">
                                        <button>+ Add Lots</button>
                                    </a>
                                <?php } else { ?>
                                    Not Required
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="8">No items found for this invoice.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
            <div>
                <button class="no-print" onclick="validateAndSave()">Save & Finalize Invoice</button>

            </div>
            <div class="amount">
                <p><strong>Base Amount:</strong> <?php echo isset($invoice['base_amount']) ? htmlspecialchars($invoice['base_amount']) : ''; ?></p>
                <p><strong>Total CGST:</strong> <?php echo isset($invoice['total_cgst']) ? htmlspecialchars($invoice['total_cgst']) : ''; ?></p>
                <p><strong>Total SGST:</strong> <?php echo isset($invoice['total_sgst']) ? htmlspecialchars($invoice['total_sgst']) : ''; ?></p>
                <p><strong>Total IGST:</strong> <?php echo isset($invoice['total_igst']) ? htmlspecialchars($invoice['total_igst']) : ''; ?></p>
                <p><strong>Gross Amount:</strong> <?php echo isset($invoice['gross_amount']) ? htmlspecialchars($invoice['gross_amount']) : ''; ?></p>
                <p><strong>Discount:</strong> <?php echo isset($invoice['discount']) ? htmlspecialchars($invoice['discount']) : ''; ?></p>
                <p><strong>Net Amount:</strong> <?php echo isset($invoice['net_amount']) ? htmlspecialchars($invoice['net_amount']) : ''; ?></p>
            </div>
        </div>
    </div>

    <script>
    function validateAndSave() {
        // AJAX call to validate lot details
        fetch('validate_purchase_cancel_lot_details.php?invoice_id=<?php echo $invoice_id; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    if (confirm('Are you sure you want to finalize this invoice?')) {
                        // Make a fetch call to finalize_invoice.php
                        fetch('finalize_purchase_cancel_invoice.php?invoice_id=<?php echo $invoice_id; ?>')
                            .then(response => response.json())
                            .then(result => {
                                if (result.already_finalized) {
                                    alert(result.message);
                                    window.location.href = 'purchase_invoice_display.php';
                                } else if (result.success) {
                                    // Show success message and redirect
                                    alert('Invoice finalized successfully with invoice number: ' + result.invoice_no);
                                    window.location.href = 'purchase_invoice_cancel_display.php';
                                } else {
                                    alert(result.message || 'An error occurred while finalizing the invoice');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while finalizing the invoice');
                            });
                    }
                } else {
                    // Build comprehensive error message
                    let errorMessage = '';

                    if (data.errors && data.errors.length > 0) {
                        errorMessage += 'Missing quantities:\n' + data.errors.join('\n') + '\n\n';
                    }

                    if (data.warnings && data.warnings.length > 0) {
                        errorMessage += 'Excess quantities:\n' + data.warnings.join('\n');
                    }

                    if (errorMessage === '') {
                        errorMessage = 'Please complete lot details for all items';
                    }

                    alert(errorMessage);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while validating lot details');
            });
    }
    </script>
</body>
</html>
