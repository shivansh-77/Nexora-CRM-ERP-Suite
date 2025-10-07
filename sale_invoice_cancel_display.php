<?php
include 'connection.php';

if (isset($_GET['id'])) {
    $invoice_id = intval($_GET['id']);

    // Fetch invoice details
    $invoice_query = "SELECT * FROM invoices_cancel WHERE id = ?";
    $stmt_invoice = $connection->prepare($invoice_query);
    $stmt_invoice->bind_param("i", $invoice_id);
    $stmt_invoice->execute();
    $invoice_result = $stmt_invoice->get_result();
    $invoice = $invoice_result->fetch_assoc();

    if ($invoice) {
        $invoice_no = $invoice['invoice_no']; // Get the invoice number from the invoice record

        // Fetch invoice items
        $items_query = "SELECT * FROM invoice_items_cancel WHERE invoice_id = ?";
        $stmt_items = $connection->prepare($items_query);
        $stmt_items->bind_param("i", $invoice_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        $items = [];
        while ($row = $items_result->fetch_assoc()) {
            $items[] = $row;
        }

        // Fetch AMC details for items with amc_tracking=1
        $amc_details = [];
        foreach ($items as $item) {
            $product_id = $item['product_id'];

            // Check if the product has AMC tracking enabled
            $amc_check_query = "SELECT amc_tracking FROM item WHERE item_code = ?";
            $stmt_amc_check = $connection->prepare($amc_check_query);
            $stmt_amc_check->bind_param("s", $product_id);
            $stmt_amc_check->execute();
            $amc_check_result = $stmt_amc_check->get_result();
            $amc_check = $amc_check_result->fetch_assoc();

            if ($amc_check && $amc_check['amc_tracking'] == 1) {
                // This product has AMC tracking, add it to the AMC details
                $amc_details[] = [
                    'product_id' => $product_id,
                    'product_name' => $item['product_name'],
                    'amc_code' => $item['amc_code'],
                    'amc_paid_date' => $item['amc_paid_date'],
                    'amc_due_date' => $item['amc_due_date'],
                    'amc_amount' => $item['amc_amount']
                ];
            }

            $stmt_amc_check->close();
        }

        // Fetch terms and conditions with fallback logic
        $terms_content = '';

        // 1. First try to get invoice-specific terms with both invoice_id and invoice_no
        $terms_query = "SELECT terms_and_conditions FROM invoice_terms_conditions
                       WHERE invoice_id = ? AND type = 'Sales' AND invoice_no = ?";
        $stmt_terms = $connection->prepare($terms_query);
        $stmt_terms->bind_param("is", $invoice_id, $invoice_no);
        $stmt_terms->execute();
        $terms_result = $stmt_terms->get_result();
        $terms = $terms_result->fetch_assoc();

        if ($terms && !empty($terms['terms_and_conditions'])) {
            $terms_content = $terms['terms_and_conditions'];
        }

        // 2. If not found, try to get location default terms using shipper_location_code
        else {
            $shipper_location_code = $invoice['shipper_location_code'] ?? null;
            if ($shipper_location_code) {
                $location_terms_query = "SELECT terms_conditions FROM location_tc WHERE location_code = ? AND tc_type = 'Sales'";
                $stmt_loc_terms = $connection->prepare($location_terms_query);
                $stmt_loc_terms->bind_param("s", $shipper_location_code);
                $stmt_loc_terms->execute();
                $loc_terms_result = $stmt_loc_terms->get_result();
                $loc_terms = $loc_terms_result->fetch_assoc();

                if ($loc_terms && !empty($loc_terms['terms_conditions'])) {
                    $terms_content = $loc_terms['terms_conditions'];
                }
                $stmt_loc_terms->close();
            }
        }

        // Fetch lot details
        $lot_details = [];
        $lot_query = "SELECT product_id, product_name,  ABS(quantity) as quantity, location, lot_trackingid, expiration_date, invoice_id_main FROM cancelled_item_lot WHERE invoice_id_main = ? AND document_type = 'Sale'";
        $stmt_lot = $connection->prepare($lot_query);
        $stmt_lot->bind_param("i", $invoice_id);
        $stmt_lot->execute();
        $lot_result = $stmt_lot->get_result();

        while ($lot_row = $lot_result->fetch_assoc()) {
            $lot_details[] = $lot_row;
        }

        $stmt_items->close();
        $stmt_invoice->close();
        $stmt_terms->close();
        $stmt_lot->close();
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
    <title>Sales Invoice</title>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
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
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .additional-details {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .additional-details h3 {
            color: #333;
            margin-bottom: 15px;
        }

        /* Summary Row Styles */
        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 20px;
        }
        .terms-conditions {
            width: 70%;
        }
        .amount {
            width: 28%;
            text-align: right;
        }
        #editor {
            height: 200px;
            border: 1px solid #ddd;
            padding: 10px;
            background: white;
            width: 100%;
        }

        /* Print Styles */
        @media print {
            body {
                background-color: #fff;
            }
            .invoice-container {
                border: none;
                margin: 0;
                padding: 0;
                width: 100%;
            }
            .no-print, .ql-toolbar {
                display: none;
            }
            #editor {
                border: none;
                padding: 0;
                height: auto;
                overflow: visible;
            }
            .summary-row {
                display: flex;
                justify-content: space-between;
                gap: 20px;
            }
            .terms-conditions {
                width: 75% !important;
            }
            .amount {
                width: 28% !important;
            }
            .details, .additional-details {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <?php
            include('connection.php');
            $query = "SELECT company_logo FROM company_card WHERE id = 1";
            $result = mysqli_query($connection, $query);
            $company = mysqli_fetch_assoc($result);
            $company_logo = !empty($company['company_logo']) ? $company['company_logo'] : 'uploads/default_logo.png';
            ?>
            <img src="<?php echo $company_logo; ?>" alt="Logo" class="logo" />
            <h1>Sales Invoice</h1>
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

                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="12">No items found for this invoice.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="summary-row">
    <div class="terms-conditions">
        <h4>Terms and Conditions</h4>
        <form id="terms-form" method="POST" action="save_terms.php">
            <input type="hidden" name="invoice_id" value="<?php echo htmlspecialchars($invoice_id); ?>">
            <input type="hidden" name="document_no" value="<?php echo isset($invoice['invoice_no']) ? htmlspecialchars($invoice['invoice_no']) : ''; ?>">
            <input type="hidden" name="type" value="Sales">
            <div id="editor">
                <?php
                if (!empty($terms_content)) {
                    // Remove <p> tags when displaying
                    $content = strip_tags($terms_content, '<br><strong><em><u><strike>');
                    echo $content;
                }
                ?>
            </div>
            <input type="hidden" name="terms_and_conditions" id="hidden-terms">
            <br>
            <button type="submit" class="no-print">Save Terms and Conditions</button>
            <button type="button" class="no-print" onclick="window.print()">Print Invoice</button>
        </form>
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
        <!-- Additional Details Section -->
        <div class="additional-details">
            <h3>Additional Details</h3>

            <!-- Lot Details Section -->
            <div class="lot-details-section">
                <h4>Lot Details</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Location</th>
                            <th>Lot Tracking ID</th>
                            <th>Expiration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($lot_details)) { ?>
                            <?php foreach ($lot_details as $lot) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($lot['product_id']); ?></td>
                                    <td><?php echo htmlspecialchars($lot['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($lot['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($lot['location']); ?></td>
                                    <td><?php echo htmlspecialchars($lot['lot_trackingid']); ?></td>
                                    <td><?php echo htmlspecialchars($lot['expiration_date']); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="6">No lot details found for this invoice.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- AMC Details Section -->
            <div class="amc-details-section" style="margin-top: 20px;">
                <h4>AMC Details</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>AMC Term</th>

                            <th>AMC Due Date</th>
                            <th>AMC Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($amc_details)) { ?>
                            <?php foreach ($amc_details as $amc) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($amc['product_id']); ?></td>
                                    <td><?php echo htmlspecialchars($amc['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($amc['amc_code']) . ' days'; ?></td>

                                    <td><?php echo htmlspecialchars($amc['amc_due_date']); ?></td>
                                    <td><?php echo htmlspecialchars($amc['amc_amount']); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="6">No AMC details found for this invoice.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
        </div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'header': 1 }, { 'header': 2 }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'direction': 'rtl' }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'font': [] }],
                    [{ 'align': [] }],
                    ['clean']
                ]
            }
        });

        // Set initial content if it exists
        <?php if (!empty($terms_content)): ?>
            quill.clipboard.dangerouslyPasteHTML(<?php echo json_encode($terms_content); ?>);
        <?php endif; ?>

        // Sync Quill content to hidden input and remove <p> tags
        document.querySelector('form').onsubmit = function() {
            var termsContent = quill.root.innerHTML;
            // Remove <p> tags but preserve other formatting
            termsContent = termsContent.replace(/<p>/g, '').replace(/<\/p>/g, '<br>');
            document.querySelector('#hidden-terms').value = termsContent;
        };
    </script>
</body>
</html>
