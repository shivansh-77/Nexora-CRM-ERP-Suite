<?php

require_once __DIR__ . '/vendor/autoload.php';
include 'connection.php'; // Include your database connection

if (isset($_GET['id'])) {
    $quotation_id = intval($_GET['id']);

    // Fetch the quotation details
    $quotation_query = "SELECT client_id, shipper_id, quotation_no, quotation_date, gross_amount, discount, net_amount, total_igst, total_cgst, total_sgst FROM quotations WHERE id = ?";
    $stmt_quotation = $connection->prepare($quotation_query);
    $stmt_quotation->bind_param("i", $quotation_id);
    $stmt_quotation->execute();
    $quotation_result = $stmt_quotation->get_result();
    $quotation = $quotation_result->fetch_assoc();

    if ($quotation) {
        $client_id = $quotation['client_id'];
        $shipper_id = $quotation['shipper_id'];

        // Fetch client and shipper details as in your original code

        // Start output buffering
        ob_start();
        include 'quotation_form_displayy.php'; // Include your HTML template
        $html = ob_get_clean();

        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Name');
        $pdf->SetTitle('Quotation');

        // Set margins to ensure content starts from the top
        $pdf->SetMargins(5, 5, 5); // Minimal margins (top, left, right)
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);
        $pdf->SetAutoPageBreak(TRUE, 0); // Disable automatic page break

        // Add a page
        $pdf->AddPage();

        // Set background color to white explicitly
        $pdf->SetFillColor(255, 255, 255); // White color
        $pdf->Rect(0, 0, $pdf->getPageWidth(), $pdf->getPageHeight(), 'F'); // Fill the entire page

        // Fit content to a single page
        $pdf->writeHTML($html, true, false, true, false, '');

        // Force content scaling if it doesn't fit
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('helvetica', '', 10); // Use smaller font if needed

        // Output the PDF
        $pdf->Output('quotation.pdf', 'D');
    } else {
        echo "No quotation found for the given ID.<br>";
    }

    $stmt_quotation->close();
    $connection->close();
} else {
    echo "No ID provided.<br>";
}

?>
