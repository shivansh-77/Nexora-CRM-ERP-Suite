<?php
include 'connection.php';

// Fetch company details for footer
$company_query = "SELECT address, city, pincode, email_id, gstno, contact_no FROM company_card WHERE id = 1";
$company_result = mysqli_query($connection, $company_query);
$company_details = mysqli_fetch_assoc($company_result);

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
        // Store invoice_no from the main invoice table
        $invoice_no = $invoice['invoice_no'];

        // Fetch invoice items
        $items_query = "SELECT id, product_id, product_name, unit, value, quantity, rate, gst, igst, cgst, sgst, amount, receipt_date FROM purchase_invoice_cancel_items WHERE invoice_id = ?";
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
                    'amc_code' => $item['amc_code'] ?? '',
                    'amc_paid_date' => $item['amc_paid_date'] ?? '',
                    'amc_due_date' => $item['amc_due_date'] ?? '',
                    'amc_amount' => $item['amc_amount'] ?? ''
                ];
            }

            $stmt_amc_check->close();
        }

        // Fetch terms and conditions with fallback logic
        $terms_content = '';

        // 1. First try to get invoice-specific terms with invoice_no
        $terms_query = "SELECT terms_and_conditions FROM invoice_terms_conditions
                        WHERE invoice_id = ? AND type = 'Purchase' AND invoice_no = ?";
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
                $location_terms_query = "SELECT terms_conditions FROM location_tc WHERE location_code = ? AND tc_type = 'Purchase'";
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
        $lot_query = "SELECT product_id, product_name, quantity, location, lot_trackingid, expiration_date, invoice_id_main FROM cancelled_item_lot WHERE invoice_id_main = ? AND document_type = 'Purchase' AND entry_type = 'Purchase Return'";
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
    <title>Purchase Invoice (Cancelled)</title>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        /* Base Styles - Screen Display */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #2c3e50;
            color: #000;
            font-size: 12px;
            line-height: 1.4;
            font-weight: 600;
        }
        .invoice-container {
            max-width: 950px;
            margin: 10px auto;
            background: #fff;
            padding: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .logo {
            max-width: 150px;
            max-height: 80px;
            object-fit: contain;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #000;
            font-weight: bold;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin: 5px 0 10px 0;
            font-size: 16px;
            font-weight: bold;
        }
        .details {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            font-size: 12px;
            font-weight: 600;
        }
        .details div {
            width: 48%;
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #000;
            border-radius: 3px;
        }
        .details h4 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #000;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 13px;
            font-weight: 600;
        }
        table th, table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #000;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 11px;
            color: #000;
            font-weight: 600;
        }
        .additional-details {
            margin-top: 15px;
            border-top: 2px solid #000;
            padding-top: 15px;
        }
        .additional-details h3 {
            color: #000;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: bold;
        }
        .additional-details h4 {
            color: #000;
            font-weight: bold;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            align-items: flex-start;
        }
        .terms-conditions {
            width: 72%;
            padding-right: 15px;
        }
        .amount {
            width: 25%;
            text-align: right;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #000;
            border-radius: 3px;
            font-weight: bold;
            color: #000;
            margin-top: 22px;
            align-self: flex-start;
        }
        .amount p {
            margin: 5px 0;
        }
        #editor {
            min-height: 150px;
            border: 1px solid #ddd;
            padding: 8px;
            background: white;
            width: 100%;
            font-size: 11px;
            color: #555;
            font-weight: normal;
            box-sizing: border-box;
        }
        .ql-editor {
            min-height: 150px !important;
            height: auto !important;
            overflow: visible !important;
        }
        .ql-container {
            height: auto !important;
        }
        .no-print {
            margin-top: 10px;
        }
        button {
            padding: 6px 12px;
            margin-right: 8px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
            font-weight: bold;
        }
        button:hover {
            background: #1a252f;
        }

        /* Print Styles - Optimized for Single Page */
        @media print {
            @page {
                size: A4;
                margin: 8mm;
                margin-top: 0;
                margin-bottom: 0;
            }

            html {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            body {
                background-color: #fff !important;
                font-size: 9pt !important;
                line-height: 1.1 !important;
                color: #000 !important;
                font-weight: 600 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .invoice-container {
                border: none;
                margin: 0;
                padding: 0;
                width: 100%;
                box-shadow: none;
                display: flex !important;
                flex-direction: column !important;
                min-height: 100vh !important;
            }

            .no-print, .ql-toolbar {
                display: none !important;
            }

            .header {
                margin-bottom: 8px !important;
                padding-bottom: 8px !important;
                border-bottom: 2px solid #000 !important;
            }

            .header h1 {
                color: #000 !important;
                font-weight: bold !important;
                font-size: 18pt !important;
            }

            .logo {
                max-width: 120px !important;
                max-height: 70px !important;
            }

            .invoice-info {
                font-size: 11pt !important;
                font-weight: bold !important;
                color: #000 !important;
                margin: 4px 0 6px 0 !important;
            }

            .details {
                margin: 8px 0 !important;
                font-size: 9pt !important;
            }

            .details div {
                padding: 6px !important;
            }

            .details h4 {
                margin-bottom: 4px !important;
                font-size: 10pt !important;
            }

            table {
                page-break-inside: avoid;
                border: 1px solid #000 !important;
                font-size: 9pt !important;
                margin: 6px 0 !important;
            }

            table th, table td {
                border: 1px solid #000 !important;
                color: #000 !important;
                font-weight: 600 !important;
                padding: 4px !important;
            }

            table th {
                background-color: #f2f2f2 !important;
                font-weight: bold !important;
                color: #000 !important;
            }

            .summary-row {
                page-break-inside: avoid;
                margin-top: 8px !important;
                align-items: flex-start !important;
            }

            .terms-conditions {
                width: 72% !important;
                padding-right: 10px !important;
            }

            .amount {
                width: 25% !important;
                border: 1px solid #000 !important;
                font-weight: bold !important;
                color: #000 !important;
                padding: 6px !important;
                margin-top: 15px !important;
                height: auto !important;
                align-self: flex-start !important;
            }

            .amount p {
                margin: 2px 0 !important;
                font-size: 8pt !important;
            }

            .terms-conditions h4 {
                color: #000 !important;
                font-weight: bold !important;
                margin-bottom: 4px !important;
                font-size: 9pt !important;
            }

            #editor {
                border: none !important;
                padding: 0 !important;
                height: auto !important;
                min-height: auto !important;
                overflow: visible !important;
                color: #555 !important;
                font-weight: normal !important;
                font-size: 8pt !important;
            }

            .ql-editor {
                white-space: normal !important;
                height: auto !important;
                min-height: auto !important;
                padding: 0 !important;
                color: #555 !important;
                font-weight: normal !important;
                overflow: visible !important;
            }

            .ql-container {
                border: none !important;
                height: auto !important;
            }

            .ql-container.ql-snow {
                border: none !important;
            }

            .additional-details {
                page-break-inside: avoid;
                border-top: 2px solid #000 !important;
                margin-top: 8px !important;
                padding-top: 8px !important;
            }

            .additional-details h3 {
                color: #000 !important;
                font-weight: bold !important;
                margin-bottom: 6px !important;
                font-size: 10pt !important;
            }

            .additional-details h4 {
                color: #000 !important;
                font-weight: bold !important;
                margin-bottom: 4px !important;
                font-size: 9pt !important;
            }

            .additional-details .compact-table {
                font-size: 8pt !important;
            }

            .additional-details .compact-table td {
                color: #000 !important;
                font-weight: 600 !important;
                padding: 3px !important;
            }

            .footer {
                position: static !important;
                margin-top: auto !important;
                color: #000 !important;
                font-weight: 600 !important;
                font-size: 7pt !important;
                line-height: 1.2 !important;
            }

            .footer div {
                margin-bottom: 2px !important;
            }

            .footer a {
                color: #000 !important;
                font-weight: 600 !important;
            }

            a[href]:after {
                content: none !important;
            }

            /* Force most content to be darker */
            * {
                color: #000 !important;
                font-weight: 600 !important;
            }

            /* Keep terms content lighter */
            #editor, #editor *, .ql-editor, .ql-editor * {
                color: #555 !important;
                font-weight: normal !important;
            }
        }

        /* Compact styles for better fit */
        .compact-table th, .compact-table td {
            padding: 6px 8px;
        }
        .compact-table {
            font-size: 12px;
        }
        .small-text {
            font-size: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 11px;
            color: #000;
            font-weight: 600;
            line-height: 1.5;
        }
        .footer a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 600;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .terms-conditions h4 {
            margin-top: 0;
            margin-bottom: 8px;
            font-weight: bold;
            color: #000;
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
            <h1>Purchase Invoice (Cancelled)</h1>
        </div>

        <div class="invoice-info">
            <p><strong>Invoice No:</strong> <?php echo isset($invoice['invoice_no']) ? htmlspecialchars($invoice['invoice_no']) : ''; ?></p>
            <p><strong>Date:</strong> <?php echo isset($invoice['invoice_date']) ? htmlspecialchars($invoice['invoice_date']) : ''; ?></p>
        </div>

        <div class="details">
            <?php if ($invoice) { ?>
                <div>
                    <h4>Bill From / Vendor</h4>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($invoice['vendor_company_name']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($invoice['vendor_name']); ?>, <?php echo htmlspecialchars($invoice['vendor_phone']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($invoice['vendor_address']); ?>, <?php echo htmlspecialchars($invoice['vendor_city']); ?>, <?php echo htmlspecialchars($invoice['vendor_state']); ?>, <?php echo htmlspecialchars($invoice['vendor_country']); ?> - <?php echo htmlspecialchars($invoice['vendor_pincode']); ?></p>
                    <p><strong>GSTIN:</strong> <?php echo htmlspecialchars($invoice['vendor_gstno']); ?></p>
                </div>
                <div>
                    <h4>Ship To:</h4>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($invoice['shipper_company_name']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($invoice['shipper_phone']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($invoice['shipper_address']); ?>, <?php echo htmlspecialchars($invoice['shipper_city']); ?>, <?php echo htmlspecialchars($invoice['shipper_state']); ?>, <?php echo htmlspecialchars($invoice['shipper_country']); ?> - <?php echo htmlspecialchars($invoice['shipper_pincode']); ?></p>
                    <p><strong>GSTIN:</strong> <?php echo htmlspecialchars($invoice['shipper_gstno']); ?></p>
                </div>
            <?php } else { ?>
                <p>No details found for the given invoice ID.</p>
            <?php } ?>
        </div>

        <table class="compact-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Unit</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>GST%</th>
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
                        <td colspan="10">No items found for this invoice.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="summary-row">
            <div class="terms-conditions">
                <h4 style="margin-top: 0;">Terms and Conditions</h4>
                <form id="terms-form" method="POST" action="save_terms.php">
                    <input type="hidden" name="invoice_id" value="<?php echo htmlspecialchars($invoice_id); ?>">
                    <input type="hidden" name="document_no" value="<?php echo isset($invoice['invoice_no']) ? htmlspecialchars($invoice['invoice_no']) : ''; ?>">
                    <input type="hidden" name="type" value="Purchase">
                    <div id="editor">
                        <?php
                        if (!empty($terms_content)) {
                            $content = strip_tags($terms_content, '<br><strong><em><u><strike>');
                            echo $content;
                        }
                        ?>
                    </div>
                    <input type="hidden" name="terms_and_conditions" id="hidden-terms">
                    <br>
                    <button type="submit" class="no-print">Save Terms</button>
                    <button type="button" class="no-print" onclick="window.print()">Print Invoice</button>
                </form>
            </div>
            <div class="amount">
                <p><strong>Base Amount:</strong> <?php echo isset($invoice['base_amount']) ? number_format($invoice['base_amount'], 2) : '0.00'; ?></p>
                <p><strong>Total CGST:</strong> <?php echo isset($invoice['total_cgst']) ? number_format($invoice['total_cgst'], 2) : '0.00'; ?></p>
                <p><strong>Total SGST:</strong> <?php echo isset($invoice['total_sgst']) ? number_format($invoice['total_sgst'], 2) : '0.00'; ?></p>
                <p><strong>Total IGST:</strong> <?php echo isset($invoice['total_igst']) ? number_format($invoice['total_igst'], 2) : '0.00'; ?></p>
                <p><strong>Gross Amount:</strong> <?php echo isset($invoice['gross_amount']) ? number_format($invoice['gross_amount'], 2) : '0.00'; ?></p>
                <p><strong>Discount:</strong> <?php echo isset($invoice['discount']) ? number_format($invoice['discount'], 2) : '0.00'; ?></p>
                <p><strong>Net Amount:</strong> <?php echo isset($invoice['net_amount']) ? number_format($invoice['net_amount'], 2) : '0.00'; ?></p>
            </div>
        </div>

        <!-- Additional Details Section -->
        <?php
        // Check if we need to show additional details
        $show_additional_details = !empty($lot_details) || !empty($amc_details);
        if ($show_additional_details): ?>
        <div class="additional-details">
            <h3>Additional Details</h3>

            <!-- Lot Details Section - Only show if there are lot details -->
            <?php if (!empty($lot_details)): ?>
            <div class="lot-details-section">
                <h4>Lot Details</h4>
                <table class="compact-table">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Qty</th>
                            <th>Location</th>
                            <th>Lot ID</th>
                            <th>Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lot_details as $lot): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lot['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($lot['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($lot['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($lot['location']); ?></td>
                                <td><?php echo htmlspecialchars($lot['lot_trackingid']); ?></td>
                                <td><?php echo htmlspecialchars($lot['expiration_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- AMC Details Section - Only show if there are AMC details -->
            <?php if (!empty($amc_details)): ?>
            <div class="amc-details-section" style="margin-top: 10px;">
                <h4>AMC Details</h4>
                <table class="compact-table">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>AMC Term</th>
                            <th>AMC Paid Date</th>
                            <th>AMC Due Date</th>
                            <th>AMC Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($amc_details as $amc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($amc['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($amc['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($amc['amc_code']) . ' days'; ?></td>
                                <td><?php echo htmlspecialchars($amc['amc_paid_date']); ?></td>
                                <td><?php echo htmlspecialchars($amc['amc_due_date']); ?></td>
                                <td><?php echo htmlspecialchars($amc['amc_amount']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Footer Section -->
        <div class="footer">
            <div style="text-align: center; margin-bottom: 5px;">
                <?php echo htmlspecialchars($company_details['address'] ?? ''); ?>,
                <?php echo htmlspecialchars($company_details['city'] ?? ''); ?> -
                <?php echo htmlspecialchars($company_details['pincode'] ?? ''); ?>
            </div>
            <div style="text-align: center; margin-bottom: 5px;">
                Email: <?php echo htmlspecialchars($company_details['email_id'] ?? ''); ?> |
                Website: <a href="https://splendidinfotech.com/" target="_blank">https://splendidinfotech.com/</a>
            </div>
            <div style="text-align: center;">
                GST No.: <?php echo htmlspecialchars($company_details['gstno'] ?? ''); ?> |
                Phone: <?php echo htmlspecialchars($company_details['contact_no'] ?? ''); ?>
            </div>
            <p>Thank You for your Business !</p>
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

        // Auto-resize editor based on content
        function resizeEditor() {
            const editor = document.querySelector('#editor');
            const qlEditor = document.querySelector('.ql-editor');
            const qlContainer = document.querySelector('.ql-container');

            if (qlEditor && qlContainer) {
                // Reset height to auto to get natural content height
                qlEditor.style.height = 'auto';
                qlContainer.style.height = 'auto';

                // Get the scroll height (natural content height)
                const contentHeight = qlEditor.scrollHeight;

                // Set minimum height of 150px, but allow it to grow
                const newHeight = Math.max(150, contentHeight + 20); // +20 for padding

                qlEditor.style.height = newHeight + 'px';
                qlContainer.style.height = newHeight + 'px';
                editor.style.height = newHeight + 'px';
            }
        }

        // Listen for text changes to auto-resize
        quill.on('text-change', function(delta, oldDelta, source) {
            setTimeout(resizeEditor, 10); // Small delay to ensure content is rendered
        });

        // Initial resize after content is loaded
        setTimeout(resizeEditor, 100);

        // Sync Quill content to hidden input and remove <p> tags
        document.querySelector('form').onsubmit = function() {
            var termsContent = quill.root.innerHTML;
            // Remove <p> tags but preserve other formatting
            termsContent = termsContent.replace(/<p>/g, '').replace(/<\/p>/g, '<br>');
            document.querySelector('#hidden-terms').value = termsContent;
        };

        // Remove URLs when printing and optimize print layout
        window.onbeforeprint = function() {
            var links = document.getElementsByTagName('a');
            for (var i = 0; i < links.length; i++) {
                links[i].href = '#';
            }

            // Make editor content fully visible
            var editor = document.querySelector('#editor');
            if (editor) {
                editor.style.height = 'auto';
                editor.style.overflow = 'visible';
                var qlEditor = document.querySelector('.ql-editor');
                if (qlEditor) {
                    qlEditor.style.height = 'auto';
                    qlEditor.style.overflow = 'visible';
                    qlEditor.style.whiteSpace = 'normal';
                }
            }

            // Force footer to bottom
            document.querySelector('.invoice-container').style.display = 'flex';
            document.querySelector('.invoice-container').style.flexDirection = 'column';
            document.querySelector('.footer').style.marginTop = 'auto';
        };
    </script>
</body>
</html>
