<?php
require_once('tcpdf/tcpdf.php');
include 'connection.php';

if (!isset($_GET['id'])) {
    die('No invoice ID provided');
}

$invoice_id = intval($_GET['id']);

// Fetch all the same data as invoice1.php
// Fetch company details for footer
$company_query = "SELECT address, city, pincode, email_id, gstno, contact_no FROM company_card WHERE id = 1";
$company_result = mysqli_query($connection, $company_query);
$company_details = mysqli_fetch_assoc($company_result);

// Fetch invoice details
$invoice_query = "SELECT * FROM invoices WHERE id = ?";
$stmt_invoice = $connection->prepare($invoice_query);
$stmt_invoice->bind_param("i", $invoice_id);
$stmt_invoice->execute();
$invoice_result = $stmt_invoice->get_result();
$invoice = $invoice_result->fetch_assoc();

if (!$invoice) {
    die('Invoice not found');
}

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

// Fetch terms and conditions
$terms_content = '';
$terms_query = "SELECT terms_and_conditions FROM invoice_terms_conditions WHERE invoice_id = ? AND type = 'Sales'";
$stmt_terms = $connection->prepare($terms_query);
$stmt_terms->bind_param("i", $invoice_id);
$stmt_terms->execute();
$terms_result = $stmt_terms->get_result();
$terms = $terms_result->fetch_assoc();
if ($terms && !empty($terms['terms_and_conditions'])) {
    $terms_content = $terms['terms_and_conditions'];
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Splendid Infotech');
$pdf->SetAuthor('Splendid Infotech');
$pdf->SetTitle('Sales Invoice - ' . $invoice['invoice_no']);
$pdf->SetSubject('Sales Invoice');

// Set default header data
$pdf->SetHeaderData('', 0, 'SALES INVOICE', 'Invoice No: ' . $invoice['invoice_no'] . ' | Date: ' . $invoice['invoice_date']);

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Build HTML content
$html = '
<style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #000; padding: 5px; text-align: left; }
    th { background-color: #f2f2f2; font-weight: bold; }
    .header { text-align: center; margin-bottom: 20px; }
    .company-name { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
    .invoice-info { margin: 20px 0; }
    .client-details { background: #f9f9f9; padding: 10px; border: 1px solid #000; margin: 10px 0; }
    .amount-summary { text-align: right; margin-top: 20px; }
    .terms { margin-top: 20px; }
</style>

<div class="header">
    <div class="company-name">SPLENDID INFOTECH</div>
    <div>' . htmlspecialchars($invoice['shipper_address']) . ', ' . htmlspecialchars($invoice['shipper_city']) . '</div>
    <div>' . htmlspecialchars($invoice['shipper_state']) . ', ' . htmlspecialchars($invoice['shipper_country']) . ' - ' . htmlspecialchars($invoice['shipper_pincode']) . '</div>
    <div>GST No.: ' . htmlspecialchars($company_details['gstno'] ?? '') . '</div>
    <div>Phone: ' . htmlspecialchars($invoice['shipper_phone']) . '</div>
</div>

<div class="invoice-info">
    <strong>Invoice No:</strong> ' . htmlspecialchars($invoice['invoice_no']) . '<br>
    <strong>Date:</strong> ' . htmlspecialchars($invoice['invoice_date']) . '
</div>

<div class="client-details">
    <h4>Bill To:</h4>
    <strong>Name:</strong> ' . htmlspecialchars($invoice['client_company_name']) . '<br>
    <strong>Contact:</strong> ' . htmlspecialchars($invoice['client_name']) . ', ' . htmlspecialchars($invoice['client_phone']) . '<br>
    <strong>Address:</strong> ' . htmlspecialchars($invoice['client_address']) . ', ' . htmlspecialchars($invoice['client_city']) . ', ' . htmlspecialchars($invoice['client_state']) . ', ' . htmlspecialchars($invoice['client_country']) . ' - ' . htmlspecialchars($invoice['client_pincode']) . '<br>
    <strong>GSTIN:</strong> ' . htmlspecialchars($invoice['client_gstno']) . '
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Product</th>
            <th>Unit</th>
            <th>Qty</th>
            <th>Rate</th>
            <th>HSN/SAC</th>
            <th>GST%</th>
            <th>IGST</th>
            <th>CGST</th>
            <th>SGST</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>';

foreach ($items as $index => $item) {
    $html .= '<tr>
        <td>' . ($index + 1) . '</td>
        <td>' . htmlspecialchars($item['product_name']) . '</td>
        <td>' . htmlspecialchars($item['unit']) . '</td>
        <td>' . htmlspecialchars($item['quantity']) . '</td>
        <td>' . htmlspecialchars($item['rate']) . '</td>
        <td>' . htmlspecialchars($item['hsn_sac_code']) . '</td>
        <td>' . htmlspecialchars($item['gst']) . '</td>
        <td>' . htmlspecialchars($item['igst']) . '</td>
        <td>' . htmlspecialchars($item['cgst']) . '</td>
        <td>' . htmlspecialchars($item['sgst']) . '</td>
        <td>' . htmlspecialchars($item['amount']) . '</td>
    </tr>';
}

$html .= '</tbody>
</table>

<div class="amount-summary">
    <strong>Base Amount:</strong> ' . number_format($invoice['base_amount'], 2) . '<br>
    <strong>Total CGST:</strong> ' . number_format($invoice['total_cgst'], 2) . '<br>
    <strong>Total SGST:</strong> ' . number_format($invoice['total_sgst'], 2) . '<br>
    <strong>Total IGST:</strong> ' . number_format($invoice['total_igst'], 2) . '<br>
    <strong>Gross Amount:</strong> ' . number_format($invoice['gross_amount'], 2) . '<br>
    <strong>Discount:</strong> ' . number_format($invoice['discount'], 2) . '<br>
    <strong>Net Amount:</strong> ' . number_format($invoice['net_amount'], 2) . '
</div>';

if (!empty($terms_content)) {
    $html .= '<div class="terms">
        <h4>Terms and Conditions</h4>
        ' . $terms_content . '
    </div>';
}

$html .= '<div style="text-align: center; margin-top: 30px; font-size: 10px;">
    ' . htmlspecialchars($company_details['address'] ?? '') . ', ' . htmlspecialchars($company_details['city'] ?? '') . ' - ' . htmlspecialchars($company_details['pincode'] ?? '') . '<br>
    Email: ' . htmlspecialchars($company_details['email_id'] ?? '') . ' | Website: https://splendidinfotech.com/<br>
    GST No.: ' . htmlspecialchars($company_details['gstno'] ?? '') . ' | Phone: ' . htmlspecialchars($company_details['contact_no'] ?? '') . '<br>
    <strong>Thank You for your Business!</strong>
</div>';

// Print text using writeHTMLCell()
$pdf->writeHTML($html, true, false, true, false, '');

// Create filename
$filename = 'Invoice_' . $invoice['invoice_no'] . '_' . date('Y-m-d') . '.pdf';

// Ensure temp_pdfs directory exists
$temp_dir = __DIR__ . '/temp_pdfs';
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

// Close and output PDF document
$pdf->Output($temp_dir . '/' . $filename, 'F');

// Return the file path for email attachment
echo json_encode([
    'success' => true,
    'filename' => $filename,
    'filepath' => $temp_dir . '/' . $filename,
    'download_url' => 'temp_pdfs/' . $filename,
    'invoice_no' => $invoice['invoice_no'],
    'client_name' => $invoice['client_company_name'],
    'net_amount' => number_format($invoice['net_amount'], 2)
]);

$connection->close();
?>
