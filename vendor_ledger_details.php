<?php
// Include your database connection file
include 'connection.php';

// Get the document_no and party_no from the URL or request safely
$document_no = $_GET['document_no'] ?? '';
$party_no = $_GET['party_no'] ?? '';

// Initialize variables
$ledger_details = [];
$amount_payable = 0;
$amount_paid = 0;

if (!empty($document_no) && !empty($party_no)) {
    // First, fetch the Purchase Invoice entry from party_ledger table
    $stmt = $connection->prepare("SELECT id, 'party_ledger' as source, ledger_type, party_name, party_type, 'Purchase Invoice' as document_type, document_no, amount, ref_doc_no as effective_ref_doc_no, payment_method, payment_details, payment_date, date FROM party_ledger WHERE document_no = ? AND party_no = ? AND document_type = 'Purchase Invoice'");
    $stmt->bind_param("si", $document_no, $party_no);
    $stmt->execute();
    $party_ledger_result = $stmt->get_result();

    while ($row = $party_ledger_result->fetch_assoc()) {
        $ledger_details[] = $row;
        // Use absolute value for amount payable (in case it's negative)
        $amount_payable += abs($row['amount']);
    }
    $stmt->close();

    // Then, fetch Payment Received and Advance Adjusted entries from advance_payments table
    $stmt = $connection->prepare("SELECT id, 'advance_payments' as source, ledger_type, party_name, party_type, document_type, document_no, amount, IF(advance_doc_no IS NOT NULL AND advance_doc_no != '', advance_doc_no, ref_doc_no) AS effective_ref_doc_no, payment_method, payment_details, payment_date, date FROM advance_payments WHERE document_no = ? AND party_no = ? AND document_type IN ('Payment Paid', 'Advance Adjusted')");
    $stmt->bind_param("si", $document_no, $party_no);
    $stmt->execute();
    $advance_payments_result = $stmt->get_result();

    while ($row = $advance_payments_result->fetch_assoc()) {
        $ledger_details[] = $row;
        // Add to amount paid (use absolute value to ensure positive)
        $amount_paid += abs($row['amount']);
    }
    $stmt->close();
}

// Calculate amount yet to be paid
$amount_yet_to_be_paid = $amount_payable - $amount_paid;

// Fetch vendor details if party_no is provided
$vendor_details = [];
if (!empty($party_no)) {
    $stmt = $connection->prepare("SELECT contact_person, company_name, mobile_no, whatsapp_no, email_id FROM contact_vendor WHERE id = ?");
    $stmt->bind_param("i", $party_no);
    $stmt->execute();
    $vendor_result = $stmt->get_result();
    $vendor_details = $vendor_result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Ledger Details</title>
    <link rel="stylesheet" href="style.css">
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #2c3e50;
        margin: 0;
        padding: 0;
        height: 150vh;
    }
    .wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 0;
        height: 100%;
    }
    .container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
        width: 120%;
        max-width: 1200px;
        max-height: 5500px;
        height: 100%;
    }
    .card, .table-container {
        border: 1px solid #ccc;
        border-radius: 10px;
        padding: 20px;
        width: 100%;
        background-color: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .card h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }
    .contact-details {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        text-align: left;
    }
    .contact-details p {
        margin: 0;
        font-size: 16px;
    }
    .contact-details strong {
        color: #2c3e50;
    }
    .table-container {
        max-height: 400px;
        overflow-y: auto;
    }
    .table-container table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .table-container table th,
    .table-container table td {
        border: 1px solid #ccc;
        padding: 10px;
        text-align: center;
        font-size: 14px;
    }
    .table-container table th {
        background-color: #f4f4f4;
        color: #333;
    }
    .table-container table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .table-container table tr:hover {
        background-color: #f1f1f1;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .card-header h2 {
        margin: 0;
        margin-left: 400px;
        padding: 0;
        margin-bottom: 8px;
    }
    .export-button-container button {
        padding: 7px 15px;
        font-size: 16px;
        background-color: #2c3e50;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    .export-button-container button:hover {
        background-color: #34495e;
    }
    .amount-summary {
        display: flex;
        justify-content: space-around;
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .amount-summary div {
        text-align: center;
    }
    .amount-summary strong {
        font-size: 18px;
        color: #2c3e50;
    }
    .amount-summary p {
        font-size: 16px;
        margin: 5px 0;
    }
    h1 {
        margin-bottom: 5px;
        color: #2c3e50;
    }

    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <h1>Vendor Ledger Details</h1>
            <a href="party_ledger.php" style="position: absolute; top: 10px; right: 10px; text-decoration: none; font-size: 20px; color: #2c3e50; font-weight: bold; border-radius: 50%; width: 30px; height: 20px; display: flex; justify-content: center; align-items: center; background-color: #fff;">&times;</a>

            <!-- Vendor Details Card -->
            <div class="card">
                <h2>Vendor Details</h2>
                <div class="contact-details">
                    <p><strong>Contact Person:</strong> <?= htmlspecialchars($vendor_details['contact_person'] ?? '') ?></p>
                    <p><strong>Company Name:</strong> <?= htmlspecialchars($vendor_details['company_name'] ?? '') ?></p>
                    <p><strong>Mobile No:</strong> <?= htmlspecialchars($vendor_details['mobile_no'] ?? '') ?></p>
                    <p><strong>WhatsApp No:</strong> <?= htmlspecialchars($vendor_details['whatsapp_no'] ?? '') ?></p>
                    <p><strong>Email ID:</strong> <?= htmlspecialchars($vendor_details['email_id'] ?? '') ?></p>
                </div>
            </div>

            <!-- Amount Summary Section -->
            <div class="card">
                <h2>Payment Summary</h2>
                <div class="amount-summary">
                    <div>
                        <strong>Amount Payable</strong>
                        <p style="color: red; font-weight: bold;">₹<?= number_format($amount_payable, 2) ?></p>
                    </div>
                    <div>
                        <strong>Amount Paid</strong>
                        <p style="color: green; font-weight: bold;">₹<?= number_format($amount_paid, 2) ?></p>
                    </div>
                    <div>
                        <strong>Amount to Be Paid</strong>
                        <p style="color: <?= $amount_yet_to_be_paid > 0 ? 'red' : 'green' ?>; font-weight: bold;">₹<?= number_format($amount_yet_to_be_paid, 2) ?></p>
                    </div>
                </div>
            </div>

            <!-- Ledger Details Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>

                            <th>Ledger Type</th>
                            <th>Party Name</th>
                            <th>Party Type</th>
                            <th>Document Type</th>
                            <th>Document No</th>
                            <th>Amount</th>
                            <th>Reference Doc No</th>
                            <th>Payment Method</th>
                            <th>Payment Details</th>
                            <th>Payment Date</th>
                            <th>Transaction Date</th>
                        </tr>
                    </thead>
                    <tbody>
        <?php if (!empty($ledger_details)): ?>
            <?php foreach ($ledger_details as $row): ?>
                <?php
                    $amount = $row['amount'];
                    // Set color and row class based on document type
                    if ($row['document_type'] == 'Purchase Invoice') {
                        $amount_style = "style='color: red; font-weight: bold;'";
                        $row_class = "purchase-invoice-row";
                    } else {
                        $amount_style = "style='color: green; font-weight: bold;'";
                        $row_class = "payment-row";
                    }

                    // Format dates
                    $payment_date = !empty($row['payment_date']) && $row['payment_date'] != "0000-00-00"
                        ? date("d-m-Y", strtotime($row['payment_date']))
                        : 'N/A';
                    $transaction_date = !empty($row['date']) && $row['date'] != "0000-00-00"
                        ? date("d-m-Y", strtotime($row['date']))
                        : 'N/A';
                ?>
                <tr class="<?= $row_class ?>">
                    <td><?= htmlspecialchars($row['id']) ?></td>

                    <td><?= htmlspecialchars($row['ledger_type'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['party_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['party_type'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['document_type']) ?></td>
                    <td><?= htmlspecialchars($row['document_no'] ?? '-') ?></td>
                    <td <?= $amount_style ?>>₹<?= number_format(abs($amount), 2) ?></td>
                    <td><?= htmlspecialchars($row['effective_ref_doc_no'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['payment_method'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['payment_details'] ?? 'N/A') ?></td>
                    <td><?= $payment_date ?></td>
                    <td><?= $transaction_date ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="13" style="text-align: center;">
                    No ledger details available for document no: <?= htmlspecialchars($document_no) ?>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
