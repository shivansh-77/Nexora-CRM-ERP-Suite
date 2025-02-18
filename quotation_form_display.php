<?php
include 'connection.php'; // Replace with your actual connection file

if (isset($_GET['id'])) {
    // Get the ID from the URL and ensure it's an integer
    $quotation_id = intval($_GET['id']);

    // Step 1: Fetch all required details from the quotations table
    $quotation_query = "SELECT quotation_no, quotation_date, gross_amount, discount, net_amount, total_igst, total_cgst, total_sgst,
                        client_name, client_address, client_phone, client_city, client_state, client_country, client_pincode, client_gstno,
                        shipper_company_name, shipper_address, shipper_city, shipper_state, shipper_country, shipper_pincode, shipper_phone, shipper_gstno
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
            $items[] = $row;
        }

        // Close all prepared statements
        $stmt_items->close();
        $stmt_quotation->close();
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Form</title>
    <style>
        /* Styles remain unchanged */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #2c3e50;
        }
        .quotation-container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            height: 800px;
        }
        .header {
            text-align: center;
            color: #2c3e50;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
        }
        .quotation-info {
            display: flex;
            justify-content: flex-end;
            font-size: 14px;
            margin-top: 10px;
        }
        .quotation-info p {
            margin-left: 15px; /* Spacing between Quotation No and Date */
        }
        .details {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }
        .details div {
            width: 48%;
        }
        .details div h4 {
            margin-bottom: 10px;
            font-size: 16px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ccc;
            text-align: center;
            padding: 8px;
        }
        table th {
            background: #f0f0f0;
        }
        .amount {
            text-align: right;
            margin-top: 20px;
        }
        .amount p {
            margin: 5px 0;
        }
        .footer {
            text-align: center;
            margin-top: -39px;
            /* padding-bottom: 20px; */
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="quotation-container">
        <div class="header">
            <h1>Quotation</h1>
            <div class="quotation-info" style="display: flex; flex-direction: column; justify-content: flex-end; align-items: flex-end; font-weight: bold;">
                <p>Quotation No: <?php echo isset($quotation['quotation_no']) ? htmlspecialchars($quotation['quotation_no']) : 'N/A'; ?></p>
                <p>Date: <?php echo isset($quotation['quotation_date']) ? htmlspecialchars($quotation['quotation_date']) : 'N/A'; ?></p>
            </div>
        </div>

        <!-- Display Client and Shipper Details -->
      <div class="details">
          <?php if ($quotation) { ?>
              <div>
                  <h4>Client</h4>
                  <p>Name: <?php echo htmlspecialchars($quotation['client_name']); ?></p>
                  <p>Phone: <?php echo htmlspecialchars($quotation['client_phone']); ?></p>
                  <p>Address: <?php echo htmlspecialchars($quotation['client_address']); ?></p>
                  <p>City: <?php echo htmlspecialchars($quotation['client_city']); ?></p>
                  <p>State: <?php echo htmlspecialchars($quotation['client_state']); ?></p>
                  <p>Country: <?php echo htmlspecialchars($quotation['client_country']); ?></p>
                  <p>Pincode: <?php echo htmlspecialchars($quotation['client_pincode']); ?></p>
                  <p>GSTIN: <?php echo htmlspecialchars($quotation['client_gstno']); ?></p>
              </div>
              <div>
                  <h4>Shipper</h4>
                  <p>Name: <?php echo htmlspecialchars($quotation['shipper_company_name']); ?></p>
                  <p>Phone: <?php echo htmlspecialchars($quotation['shipper_phone']); ?></p>
                  <p>Address: <?php echo htmlspecialchars($quotation['shipper_address']); ?></p>
                  <p>City: <?php echo htmlspecialchars($quotation['shipper_city']); ?></p>
                  <p>State: <?php echo htmlspecialchars($quotation['shipper_state']); ?></p>
                  <p>Country: <?php echo htmlspecialchars($quotation['shipper_country']); ?></p>
                  <p>Pincode: <?php echo htmlspecialchars($quotation['shipper_pincode']); ?></p>
                  <p>GSTIN: <?php echo htmlspecialchars($quotation['shipper_gstno']); ?></p>
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
                        <td colspan="10">No items found for this quotation.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Amount Section -->
        <div class="amount">
            <p><strong>Total CGST:</strong> <?php echo isset($quotation['total_cgst']) ? htmlspecialchars($quotation['total_cgst']) : 'N/A'; ?></p>
            <p><strong>Total SGST:</strong> <?php echo isset($quotation['total_sgst']) ? htmlspecialchars($quotation['total_sgst']) : 'N/A'; ?></p>
            <p><strong>Total IGST:</strong> <?php echo isset($quotation['total_igst']) ? htmlspecialchars($quotation['total_igst']) : 'N/A'; ?></p>
            <p><strong>Gross Amount:</strong> <?php echo isset($quotation['gross_amount']) ? htmlspecialchars($quotation['gross_amount']) : 'N/A'; ?></p>
            <p><strong>Discount:</strong> <?php echo isset($quotation['discount']) ? htmlspecialchars($quotation['discount']) : 'N/A'; ?></p>
            <p><strong>Net Amount:</strong> <?php echo isset($quotation['net_amount']) ? htmlspecialchars($quotation['net_amount']) : 'N/A'; ?></p>
        </div>
        <?php
        // Ensure this is placed where you have successfully fetched the $quotation_id
        if (isset($quotation_id)) {
            echo "<a href='tcpdf.php?id={$quotation_id}' class='btn btn-primary'>Download PDF</a>";
        }
        ?>
    </div>

    <div class="footer">
        <p>Thanks for your business! Please visit us again!</p>
    </div>
</body>
</html>
