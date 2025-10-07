<?php
// Include your database connection file
include 'connection.php';

// Get the advance_doc_no from the URL
$advance_doc_no = $_GET['advance_doc_no'] ?? '';
$party_no = $_GET['party_no'] ?? '';

// Fetch advance ledger details from the advance_payments table based on advance_doc_no
$advance_details = [];
if (!empty($advance_doc_no)) {
    $stmt = $connection->prepare("SELECT * FROM advance_payments WHERE advance_doc_no = ? ORDER BY date ASC");
    $stmt->bind_param("s", $advance_doc_no);
    $stmt->execute();
    $advance_result = $stmt->get_result();
    $advance_details = $advance_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch vendor details from the contact table based on party_no (which is id in contact table)
$vendor_details = [];
if (!empty($party_no)) {
    $stmt = $connection->prepare("SELECT contact_person, company_name, mobile_no, whatsapp_no, email_id FROM contact_vendor WHERE id = ?");
    $stmt->bind_param("i", $party_no);
    $stmt->execute();
    $vendor_result = $stmt->get_result();
    $vendor_details = $vendor_result->fetch_assoc();
    $stmt->close();
}

// Calculate Total Advance Amount and Pending Amount
$total_advance = 0;
$total_pending = 0;
$first_entry = true;

if (!empty($advance_doc_no)) {
    foreach ($advance_details as $row) {
        if ($first_entry) {
            // First entry is the advance paid (negative)
            $total_advance += abs($row['amount']);
            $first_entry = false;
        } else {
            // Subsequent entries are utilization (positive)
            $total_pending += $row['amount'];
        }
    }
}

// Calculate Amount Utilized
$amount_utilized = $total_pending;
$total_pending = $total_advance - $amount_utilized;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advance Ledger Details</title>
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
        justify-content: right;
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
        background-color: #2c3e50;
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
    }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <h1>Advance Ledger Details</h1>
            <a href="party_ledger.php" style="position: absolute; top: 10px; right: 10px; text-decoration: none; font-size: 20px; color: #2c3e50; font-weight: bold; border-radius: 50%; width: 30px; height: 20px; display: flex; justify-content: center; align-items: center; background-color: #fff;">&times;</a>

            <!-- Vendor Details Card -->
            <div class="card">
                <h2>Vendor Details</h2>
                <div class="contact-details">
                    <p><strong>Contact Person:</strong> <?= htmlspecialchars($vendor_details['contact_person'] ?? 'N/A') ?></p>
                    <p><strong>Company Name:</strong> <?= htmlspecialchars($vendor_details['company_name'] ?? 'N/A') ?></p>
                    <p><strong>Mobile No:</strong> <?= htmlspecialchars($vendor_details['mobile_no'] ?? 'N/A') ?></p>
                    <p><strong>WhatsApp No:</strong> <?= htmlspecialchars($vendor_details['whatsapp_no'] ?? 'N/A') ?></p>
                    <p><strong>Email ID:</strong> <?= htmlspecialchars($vendor_details['email'] ?? 'N/A') ?></p>
                </div>
            </div>

            <!-- Amount Summary Section -->
            <div class="card">
      <h2>Advance Summary</h2>
      <div class="amount-summary">
          <div>
              <strong>Total Advance Paid</strong>
              <p style="color: green; font-weight: bold;">₹<?= number_format($total_advance, 2) ?></p>
          </div>
          <div>
              <strong>Amount Utilized</strong>
              <p style="color: red; font-weight: bold;">₹<?= number_format($amount_utilized, 2) ?></p>
          </div>
          <div>
              <strong>Pending Amount</strong>
              <p style="color: blue; font-weight: bold;">₹<?= number_format($total_pending, 2) ?></p>
          </div>
      </div>
  </div>


            <!-- Advance Ledger Details Table -->
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
                        <?php if (!empty($advance_details)): ?>
                            <?php foreach ($advance_details as $row): ?>
                                <?php
                                $amount = $row['amount'];
                                $pending_amount = $row['pending_amount'];
                                $amount_style = $amount >= 0 ? "style='color: green; font-weight: bold;'" : "style='color: red; font-weight: bold;'";
                                $pending_style = $pending_amount >= 0 ? "style='color: blue; font-weight: bold;'" : "style='color: red; font-weight: bold;'";
                                $payment_method = !empty($row['payment_method']) ? htmlspecialchars($row['payment_method']) : 'N/A';
                                $payment_details = !empty($row['payment_details']) ? htmlspecialchars($row['payment_details']) : 'N/A';
                                $payment_date = !empty($row['payment_date']) ? date("d-m-Y", strtotime($row['payment_date'])) : 'N/A';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['ledger_type']) ?></td>
                                    <td><?= htmlspecialchars($row['party_name']) ?></td>
                                    <td><?= htmlspecialchars($row['party_type']) ?></td>
                                    <td><?= htmlspecialchars($row['document_type']) ?></td>
                                    <td><?= htmlspecialchars($row['advance_doc_no']) ?></td>
                                    <td <?= $amount_style ?>>₹<?= number_format($amount, 2) ?></td>

                                    <td><?= htmlspecialchars($row['document_no']) ?></td>
                                    <td><?= $payment_method ?></td>
                                    <td><?= $payment_details ?></td>
                                    <td><?= $payment_date ?></td>
                                    <td><?= htmlspecialchars($row['date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="13" class="text-center">
                                    No advance details available for document no: <?= htmlspecialchars($advance_doc_no) ?>
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
