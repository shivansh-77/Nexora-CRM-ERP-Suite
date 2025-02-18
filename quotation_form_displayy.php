<?php
include 'connection.php'; // Replace with your actual connection file

if (isset($_GET['id'])) {
    // Get the ID from the URL and ensure it's an integer
    $quotation_id = intval($_GET['id']);

    // Step 1: Fetch client_id, shipper_id, quotation_no, and date from the quotations table
    $quotation_query = "SELECT client_id, shipper_id, quotation_no, quotation_date, gross_amount, discount, net_amount,total_igst , total_cgst , total_sgst FROM quotations WHERE id = ?";
    $stmt_quotation = $connection->prepare($quotation_query);
    $stmt_quotation->bind_param("i", $quotation_id);
    $stmt_quotation->execute();
    $quotation_result = $stmt_quotation->get_result();
    $quotation = $quotation_result->fetch_assoc();

    if ($quotation) {
        // Extract required details
        $client_id = $quotation['client_id'];
        $shipper_id = $quotation['shipper_id'];

        // Step 2: Fetch client details from the contact table
        $client_query = "SELECT contact_person, company_name, mobile_no, address, country, state, city, pincode FROM contact WHERE id = ?";
        $stmt_client = $connection->prepare($client_query);
        $stmt_client->bind_param("i", $client_id);
        $stmt_client->execute();
        $client_result = $stmt_client->get_result();
        $client = $client_result->fetch_assoc();

        // Step 3: Fetch shipper details from the location_card table
        $shipper_query = "SELECT company_name, location, contact_no, city, state, country, pincode, gstno FROM location_card WHERE id = ?";
        $stmt_shipper = $connection->prepare($shipper_query);
        $stmt_shipper->bind_param("i", $shipper_id);
        $stmt_shipper->execute();
        $shipper_result = $stmt_shipper->get_result();
        $shipper = $shipper_result->fetch_assoc();

        // Step 4: Fetch data from quotation_items table
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
        $stmt_client->close();
        $stmt_shipper->close();
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
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
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
    <p>Quotation No: <?php echo isset($quotation['quotation_no']) ? htmlspecialchars($quotation['quotation_no']) : 'N/A'; ?></p> <!-- Display Quotation No -->
    <p>Date: <?php echo isset($quotation['quotation_date']) ? htmlspecialchars($quotation['quotation_date']) : 'N/A'; ?></p> <!-- Display Quotation Date -->
</div>

        </div>

        <!-- Display Client and Shipper Details -->
        <div class="details">
            <?php if (isset($client) && isset($shipper)) { ?>
                <div>
                    <h4>Client</h4>
                    <p>Name: <?php echo htmlspecialchars($client['contact_person']); ?></p>
                    <p>Company Name: <?php echo htmlspecialchars($client['company_name']); ?></p>
                    <p>Mobile No.: <?php echo htmlspecialchars($client['mobile_no']); ?></p>
                    <p>Address: <?php echo htmlspecialchars($client['address']); ?></p>
                    <p>City: <?php echo htmlspecialchars($client['city']); ?></p>
                    <p>State: <?php echo htmlspecialchars($client['state']); ?></p>
                    <p>Country: <?php echo htmlspecialchars($client['country']); ?></p>
                    <p>Pincode: <?php echo htmlspecialchars($client['pincode']); ?></p>
                </div>
                <div>
                    <h4>Shipper</h4>
                    <p>Company Name: <?php echo htmlspecialchars($shipper['company_name']); ?></p>
                    <p>Location: <?php echo htmlspecialchars($shipper['location']); ?></p>
                    <p>Contact: <?php echo htmlspecialchars($shipper['contact_no']); ?></p>
                    <p>City: <?php echo htmlspecialchars($shipper['city']); ?></p>
                    <p>State: <?php echo htmlspecialchars($shipper['state']); ?></p>
                    <p>Country: <?php echo htmlspecialchars($shipper['country']); ?></p>
                    <p>Pincode: <?php echo htmlspecialchars($shipper['pincode']); ?></p>
                    <p>GSTIN: <?php echo htmlspecialchars($shipper['gstno']); ?></p>
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
                      <td><?php echo htmlspecialchars($item['product_name']); // Fetch product details if needed ?></td>
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
                  <td colspan="6">No items found for this quotation.</td>
              </tr>
          <?php } ?>
      </tbody>
  </table>


  <!-- Amount Section -->
  <div class="amount">
      <p><strong>Total IGST:</strong> <?php echo isset($quotation['total_igst']) ? htmlspecialchars($quotation['total_igst']) : 'N/A'; ?></p>
      <p><strong>Total CGST:</strong> <?php echo isset($quotation['total_cgst']) ? htmlspecialchars($quotation['total_cgst']) : 'N/A'; ?></p>
      <p><strong>Net SGST:</strong> <?php echo isset($quotation['total_sgst']) ? htmlspecialchars($quotation['total_sgst']) : 'N/A'; ?></p>
      <p><strong>Gross Amount:</strong> <?php echo isset($quotation['gross_amount']) ? htmlspecialchars($quotation['gross_amount']) : 'N/A'; ?></p>
      <p><strong>Discount:</strong> <?php echo isset($quotation['discount']) ? htmlspecialchars($quotation['discount']) : 'N/A'; ?></p>
      <p><strong>Net Amount:</strong> <?php echo isset($quotation['net_amount']) ? htmlspecialchars($quotation['net_amount']) : 'N/A'; ?></p>
  </div>





  </div>

        <div class="footer">
            <p>Thanks for your business! Please visit us again!</p>
        </div>
    </div>
</body>
</html>
