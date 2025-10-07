<?php
// This file generates the HTML content for PDF generation
// It's similar to invoice1.php but optimized for PDF output

include 'connection.php';

// Fetch company details for footer
$company_query = "SELECT address, city, pincode, email_id, gstno, contact_no FROM company_card WHERE id = 1";
$company_result = mysqli_query($connection, $company_query);
$company_details = mysqli_fetch_assoc($company_result);

if (isset($invoice_id)) {
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
            $product_id = $row['product_id'];
            // Fetch HSN/SAC code from item table using product_id
            $hsn_query = "SELECT hsn_sac_code FROM item WHERE item_code = ?";
            $stmt_hsn = $connection->prepare($hsn_query);
            $stmt_hsn->bind_param("s", $product_id);
            $stmt_hsn->execute();
            $hsn_result = $stmt_hsn->get_result();
            $hsn_data = $hsn_result->fetch_assoc();
            $row['hsn_sac_code'] = $hsn_data['hsn_sac_code'] ?? '';
            $items[] = $row;
            $stmt_hsn->close();
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
        // 1. First try to get invoice-specific terms
        $terms_query = "SELECT terms_and_conditions FROM invoice_terms_conditions WHERE invoice_id = ? AND type = 'Sales'";
        $stmt_terms = $connection->prepare($terms_query);
        $stmt_terms->bind_param("i", $invoice_id);
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
        $lot_query = "SELECT product_id, product_name, ABS(quantity) as quantity, location, lot_trackingid, expiration_date, invoice_id_main FROM item_ledger_history WHERE invoice_id_main = ? AND document_type = 'Sale'";
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
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Invoice</title>
    <style>
        /* PDF-optimized styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #000;
            font-size: 10pt;
            line-height: 1.2;
            font-weight: 600;
        }

        .invoice-container {
            width: 100%;
            margin: 0;
            padding: 15px;
            box-sizing: border-box;
        }

        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .logo {
            max-width: 160px;
            max-height: 100px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .header-center {
            flex: 1;
            margin: 0 20px;
            text-align: center;
        }

        .company-name {
            font-size: 16pt;
            color: #000;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .address-box {
            padding: 0;
            background: transparent;
            border: none;
            font-size: 9pt;
            color: #000;
            font-weight: 500;
            line-height: 1.1;
        }

        .header h1 {
            margin: 0;
            font-size: 16pt;
            color: #000;
            font-weight: bold;
            flex-shrink: 0;
        }

        /* Invoice Info */
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin: 5px 0 15px 0;
            font-size: 12pt;
            font-weight: bold;
        }

        /* Client Details */
        .details {
            display: flex;
            justify-content: flex-start;
            margin: 15px 0;
            font-size: 10pt;
            font-weight: 600;
        }

        .details div {
            width: 100%;
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #000;
            border-radius: 3px;
        }

        .details h4 {
            margin: 0 0 8px 0;
            font-size: 11pt;
            color: #000;
            font-weight: bold;
        }

        /* Products Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9pt;
            font-weight: 600;
        }

        table th, table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #000;
        }

        table th.hsn-col, table td.hsn-col {
            width: 80px;
            white-space: nowrap;
            text-align: center;
        }

        /* Summary Section */
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            align-items: flex-start;
        }

        .terms-conditions {
            width: 70%;
            padding-right: 15px;
        }

        .terms-conditions h4 {
            margin-top: 0;
            font-size: 11pt;
            color: #000;
            font-weight: bold;
        }

        .terms-content {
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
        }

        .amount {
            width: 28%;
            text-align: right;
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #000;
            border-radius: 3px;
            font-weight: 800;
            color: #000;
            font-size: 10pt;
        }

        .amount p {
            margin: 3px 0;
            font-weight: 800;
        }

        .amount strong {
            font-weight: 900;
        }

        /* Additional Details Section */
        .additional-details {
            margin-top: 15px;
            border-top: 2px solid #000;
            padding-top: 10px;
            page-break-inside: avoid;
        }

        .additional-details h3 {
            color: #000;
            margin-bottom: 8px;
            font-size: 11pt;
            font-weight: bold;
        }

        .additional-details h4 {
            color: #000;
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 10pt;
        }

        .compact-table {
            font-size: 8pt;
        }

        .compact-table th, .compact-table td {
            padding: 3px;
            color: #000;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 9pt;
            color: #000;
            font-weight: 600;
            line-height: 1.3;
            border-top: 1px solid #999;
            padding-top: 10px;
        }

        .footer a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <?php
            $query = "SELECT company_logo, company_name FROM company_card WHERE id = 1";
            $result = mysqli_query($connection, $query);
            $company = mysqli_fetch_assoc($result);
            $company_logo = !empty($company['company_logo']) ? $company['company_logo'] : 'uploads/default_logo.png';
            $company_name = !empty($company['company_name']) ? $company['company_name'] : 'Company Name';
            ?>
            <img src="<?php echo $company_logo; ?>" alt="Logo" class="logo" />
            <div class="header-center">
                <div class="company-name"><?php echo htmlspecialchars($company_name); ?></div>
                <?php if ($invoice) { ?>
                <div class="address-box">
                    <div><?php echo htmlspecialchars($invoice['shipper_address']); ?>, <?php echo htmlspecialchars($invoice['shipper_city']); ?></div>
                    <div><?php echo htmlspecialchars($invoice['shipper_state']); ?>, <?php echo htmlspecialchars($invoice['shipper_country']); ?> - <?php echo htmlspecialchars($invoice['shipper_pincode']); ?> | GST No.: <?php echo htmlspecialchars($company_details['gstno'] ?? ''); ?></div>
                    <div>Phone: <?php echo htmlspecialchars($invoice['shipper_phone']); ?> | Website: www.splendidinfotech.com</div>
                </div>
                <?php } ?>
            </div>
            <h1>SALES INVOICE</h1>
        </div>

        <div class="invoice-info">
            <p><strong>Invoice No:</strong> <?php echo isset($invoice['invoice_no']) ? htmlspecialchars($invoice['invoice_no']) : 'N/A'; ?></p>
            <p><strong>Date:</strong> <?php echo isset($invoice['invoice_date']) ? htmlspecialchars($invoice['invoice_date']) : 'N/A'; ?></p>
        </div>

        <div class="details">
            <?php if ($invoice) { ?>
                <div>
                    <h4>Bill To:</h4>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($invoice['client_company_name']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($invoice['client_name']); ?>, <?php echo htmlspecialchars($invoice['client_phone']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($invoice['client_address']); ?>, <?php echo htmlspecialchars($invoice['client_city']); ?>, <?php echo htmlspecialchars($invoice['client_state']); ?>, <?php echo htmlspecialchars($invoice['client_country']); ?> - <?php echo htmlspecialchars($invoice['client_pincode']); ?></p>
                    <p><strong>GSTIN:</strong> <?php echo htmlspecialchars($invoice['client_gstno']); ?></p>
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
                    <th>Qty</th>
                    <th>Rate</th>
                    <th class="hsn-col">HSN/SAC</th>
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
                            <td class="hsn-col"><?php echo htmlspecialchars($item['hsn_sac_code']); ?></td>
                            <td><?php echo htmlspecialchars($item['gst']); ?></td>
                            <td><?php echo htmlspecialchars($item['igst']); ?></td>
                            <td><?php echo htmlspecialchars($item['cgst']); ?></td>
                            <td><?php echo htmlspecialchars($item['sgst']); ?></td>
                            <td><?php echo htmlspecialchars($item['amount']); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="11">No items found for this invoice.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="summary-row">
            <div class="terms-conditions">
                <h4>Terms and Conditions</h4>
                <div class="terms-content">
                    <?php
                    if (!empty($terms_content)) {
                        echo strip_tags($terms_content, '<br><strong><em><u><strike>');
                    }
                    ?>
                </div>
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
        $show_additional_details = !empty($lot_details) || !empty($amc_details);
        if ($show_additional_details): ?>
        <div class="additional-details">
            <h3>Additional Details</h3>

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

            <?php if (!empty($amc_details)): ?>
            <div class="amc-details-section" style="margin-top: 10px;">
                <h4>AMC Details</h4>
                <table class="compact-table">
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
                        <?php foreach ($amc_details as $amc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($amc['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($amc['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($amc['amc_code']) . ' days'; ?></td>
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
            <p>Thank You for your Business!</p>
        </div>
    </div>
</body>
</html>
