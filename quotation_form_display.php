<?php
include 'connection.php';

// Fetch company details for footer
$company_query = "SELECT address, city, pincode, email_id, gstno, contact_no FROM company_card WHERE id = 1";
$company_result = mysqli_query($connection, $company_query);
$company_details = mysqli_fetch_assoc($company_result);

if (isset($_GET['id'])) {
    // Get the ID from the URL and ensure it's an integer
    $quotation_id = intval($_GET['id']);

    // Step 1: Fetch all required details from the quotations table
    $quotation_query = "SELECT quotation_no, quotation_date, gross_amount, discount, net_amount,base_amount, total_igst, total_cgst, total_sgst,
                        client_name, client_address, client_phone, client_city, client_state, client_country, client_pincode, client_gstno,
                        shipper_company_name, shipper_address, shipper_city, shipper_state, shipper_country, shipper_pincode, shipper_phone, shipper_gstno, client_company_name, shipper_location_code
                        FROM quotations WHERE id = ?";

    $stmt_quotation = $connection->prepare($quotation_query);
    $stmt_quotation->bind_param("i", $quotation_id);
    $stmt_quotation->execute();
    $quotation_result = $stmt_quotation->get_result();
    $quotation = $quotation_result->fetch_assoc();

    if ($quotation) {
        // Step 2: Fetch data from quotation_items table
        $items_query = "SELECT * FROM quotation_items WHERE quotation_id = ?";
        $stmt_items = $connection->prepare($items_query);
        $stmt_items->bind_param("i", $quotation_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();
        $items = [];
        while ($row = $items_result->fetch_assoc()) {
            $product_id = $row['product_id'];

            // Get HSN/SAC code from item table
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

        // Fetch terms and conditions with fallback logic
        $terms_content = '';
        $quotation_no = $quotation['quotation_no'] ?? '';

        // 1. First try to get quotation-specific terms using document_no (quotation_no)
        $terms_query = "SELECT terms_and_conditions FROM invoice_terms_conditions WHERE document_no = ? AND type = 'Sales'";
        $stmt_terms = $connection->prepare($terms_query);
        $stmt_terms->bind_param("s", $quotation_no);
        $stmt_terms->execute();
        $terms_result = $stmt_terms->get_result();
        $terms = $terms_result->fetch_assoc();

        if ($terms && !empty($terms['terms_and_conditions'])) {
            $terms_content = $terms['terms_and_conditions'];
        }
        // 2. If not found, try to get location default terms using shipper_location_code
        else {
            $shipper_location_code = $quotation['shipper_location_code'] ?? null;
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

        // Close all prepared statements
        $stmt_items->close();
        $stmt_quotation->close();
        $stmt_terms->close();
    } else {
        echo "No quotation found for the given ID.<br>";
    }

    // Close the database connection
    $connection->close();
} else {
    echo "No ID provided.<br>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation</title>
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

    /* Header Section */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 25px;
        border-bottom: 2px solid #000;
        padding-bottom: 20px;
    }

    .logo {
        max-width: 200px;
        max-height: 120px;
        object-fit: contain;
        flex-shrink: 0;
    }

    .header-center {
        flex: 1;
        margin: 0 20px;
        text-align: center;
    }

    .company-name {
        font-size: 18px;
        color: #000;
        font-weight: bold;
        margin-bottom: 2px;
    }

    .address-box {
        padding: 0;
        background: transparent;
        border: none;
        border-radius: 5px;
        font-size: 9px;
        color: #000;
        font-weight: 600;
        line-height: 1.2;
    }

    .header h1 {
        margin: 0;
        font-size: 20px;
        color: #000;
        font-weight: bold;
        flex-shrink: 0;
    }

    /* Invoice Info */
    .invoice-info {
        display: flex;
        justify-content: space-between;
        margin: 5px 0 15px 0;
        font-size: 16px;
        font-weight: bold;
    }

    /* Client Details */
    .details {
        display: flex;
        justify-content: flex-start;
        margin: 15px 0;
        font-size: 12px;
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
        font-size: 13px;
        color: #000;
        font-weight: bold;
    }

    /* Products Table */
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 10px 0;
        font-size: 14px;
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

    /* Footer */
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

    /* Terms and Amount Section */
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
        font-weight: 800;
        color: #000;
        margin-top: 22px;
        align-self: flex-start;
        font-size: 14px;
    }

    .amount p {
        margin: 5px 0;
        font-weight: 800;
    }

    .amount strong {
        font-weight: 900;
        font-size: 14px;
    }

    /* Quill Editor */
    #editor {
        min-height: 150px;
        border: 1px solid #ddd;
        padding: 8px;
        background: white;
        width: 100%;
        font-size: 11px;
        color: #000;
        font-weight: normal;
        box-sizing: border-box;
    }

    .ql-editor {
        min-height: 150px !important;
        height: auto !important;
        overflow: visible !important;
        color: #000 !important;
    }

    .ql-container {
        height: auto !important;
    }

    /* Buttons */
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

    /* Print Styles */
    @media print {
      table th.hsn-col, table td.hsn-col {
          width: 80px;               /* Adjust as needed */
          white-space: nowrap;       /* Prevents breaking */
          text-align: left;
      }
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
            margin-top: 15px !important;
            margin-bottom: 15px !important;
            padding-bottom: 15px !important;
            border-bottom: 2px solid #000 !important;
            align-items: flex-start !important;
        }

        .header h1 {
            color: #000 !important;
            font-weight: bold !important;
            font-size: 17pt !important;
        }

        .logo {
            max-width: 160px !important;
            max-height: 100px !important;
        }

        .company-name {
            font-size: 18pt !important;
            color: #000 !important;
            font-weight: bold !important;
            margin-bottom: 1px !important;
        }

        .address-box {
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            border: none !important;
            font-size: 10pt !important;
            color: #000 !important;
            font-weight: 500 !important;
            line-height: 1.1 !important;
        }

        table th, table td {
            padding: 4px !important;
            font-size: 10pt !important;
        }

        .amount {
            font-size: 10pt !important;
            padding: 6px !important;
        }

        #editor {
    border: none !important;
    padding: 0 !important;
    background: transparent !important;
}

.ql-editor {
    border: none !important;
    padding: 0 !important;
    background: transparent !important;
    overflow: visible !important;
    white-space: normal !important;
}

    }

    .footer-separator {
    border-top: 1px solid #999;
    margin: 5px 0 5px 0;  /* Top and bottom margin */
}

/* Limit the width of HSN/SAC column */
table th.hsn-col, table td.hsn-col {
    width: 80px;               /* Adjust as needed */
    white-space: nowrap;       /* Prevents breaking */
    text-align: left;
}


    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <?php
            include('connection.php');
            $query = "SELECT company_logo, company_name FROM company_card WHERE id = 1";
            $result = mysqli_query($connection, $query);
            $company = mysqli_fetch_assoc($result);
            $company_logo = !empty($company['company_logo']) ? $company['company_logo'] : 'uploads/default_logo.png';
            $company_name = !empty($company['company_name']) ? $company['company_name'] : 'Company Name';
            ?>
            <img src="<?php echo $company_logo; ?>" alt="Logo" class="logo" />
            <div class="header-center">
                <div class="company-name"><?php echo htmlspecialchars($company_name); ?></div>
                <?php if ($quotation) { ?>
                <div class="address-box">
                    <div><?php echo htmlspecialchars($quotation['shipper_address']); ?>, <?php echo htmlspecialchars($quotation['shipper_city']); ?>, <?php echo htmlspecialchars($quotation['shipper_state']); ?> - <?php echo htmlspecialchars($quotation['shipper_pincode']); ?></div>
                    <div>GST: <?php echo htmlspecialchars($company_details['gstno'] ?? ''); ?> | Phone: <?php echo htmlspecialchars($quotation['shipper_phone']); ?></div>
                    <div> Website: www.splendidinfotech.com </div>
                </div>
                <?php } ?>
            </div>
            <h1>QUOTATION</h1>
        </div>

        <div class="invoice-info">
            <p><strong>Quotation No:</strong> <?php echo isset($quotation['quotation_no']) ? htmlspecialchars($quotation['quotation_no']) : 'N/A'; ?></p>
            <p><strong>Date:</strong> <?php echo isset($quotation['quotation_date']) ? htmlspecialchars($quotation['quotation_date']) : 'N/A'; ?></p>
        </div>

        <div class="details">
            <?php if ($quotation) { ?>
                <div>
                    <h4>Bill To:</h4>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($quotation['client_company_name']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($quotation['client_name']); ?>, <?php echo htmlspecialchars($quotation['client_phone']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($quotation['client_address']); ?>, <?php echo htmlspecialchars($quotation['client_city']); ?>, <?php echo htmlspecialchars($quotation['client_state']); ?>, <?php echo htmlspecialchars($quotation['client_country']); ?> - <?php echo htmlspecialchars($quotation['client_pincode']); ?></p>
                    <p><strong>GSTIN:</strong> <?php echo htmlspecialchars($quotation['client_gstno']); ?></p>
                </div>
            <?php } else { ?>
                <p>No details found for the given quotation ID.</p>
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
                            <td class="hsn-col" ><?php echo htmlspecialchars($item['hsn_sac_code']); ?></td>
                            <td><?php echo htmlspecialchars($item['gst']); ?></td>
                            <td><?php echo htmlspecialchars($item['igst']); ?></td>
                            <td><?php echo htmlspecialchars($item['cgst']); ?></td>
                            <td><?php echo htmlspecialchars($item['sgst']); ?></td>
                            <td><?php echo htmlspecialchars($item['amount']); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="10">No items found for this quotation.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="summary-row">
            <div class="terms-conditions">
                <h4 style="margin-top: 0;">Terms and Conditions</h4>
                <form id="terms-form" method="POST" action="save_terms.php">
                    <input type="hidden" name="invoice_id" value="<?php echo isset($quotation_id) ? intval($quotation_id) : 0; ?>">
                    <input type="hidden" name="document_no" value="<?php echo isset($quotation['quotation_no']) ? htmlspecialchars($quotation['quotation_no']) : ''; ?>">
                    <input type="hidden" name="type" value="Sales">
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
                    <button type="button" class="no-print" onclick="window.print()">Print Quotation</button>
                </form>

            </div>

            <div class="amount">
                <p><strong>Base Amount:</strong> <?php echo isset($quotation['base_amount']) ? number_format($quotation['base_amount'], 2) : '0.00'; ?></p>
                <p><strong>Total CGST:</strong> <?php echo isset($quotation['total_cgst']) ? number_format($quotation['total_cgst'], 2) : '0.00'; ?></p>
                <p><strong>Total SGST:</strong> <?php echo isset($quotation['total_sgst']) ? number_format($quotation['total_sgst'], 2) : '0.00'; ?></p>
                <p><strong>Total IGST:</strong> <?php echo isset($quotation['total_igst']) ? number_format($quotation['total_igst'], 2) : '0.00'; ?></p>
                <p><strong>Gross Amount:</strong> <?php echo isset($quotation['gross_amount']) ? number_format($quotation['gross_amount'], 2) : '0.00'; ?></p>
                <p><strong>Discount:</strong> <?php echo isset($quotation['discount']) ? number_format($quotation['discount'], 2) : '0.00'; ?></p>
                <p><strong>Net Amount:</strong> <?php echo isset($quotation['net_amount']) ? number_format($quotation['net_amount'], 2) : '0.00'; ?></p>
            </div>
        </div>


        <div class="footer">
            <div class="footer-separator"></div>
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
