<?php
include 'connection.php'; // Replace with your actual connection file

if (isset($_GET['id'])) {
    // Get the ID from the URL and ensure it's an integer
    $purchase_order_id = intval($_GET['id']);

    // Step 1: Fetch all required details from the purchase_order table
    $purchase_order_query = "SELECT purchase_order_no, purchase_order_date, gross_amount, discount, net_amount, total_igst, total_cgst, total_sgst,
                             vendor_name, vendor_address, vendor_phone, vendor_city, vendor_state, vendor_country, vendor_pincode, vendor_gstno,
                             shipper_company_name, shipper_address, shipper_city, shipper_state, shipper_country, shipper_pincode, shipper_phone, shipper_gstno
                             FROM purchase_order WHERE id = ?";

    $stmt_purchase_order = $connection->prepare($purchase_order_query);
    $stmt_purchase_order->bind_param("i", $purchase_order_id);
    $stmt_purchase_order->execute();
    $purchase_order_result = $stmt_purchase_order->get_result();
    $purchase_order = $purchase_order_result->fetch_assoc();

    if ($purchase_order) {
        // Step 2: Fetch data from purchase_order_items table
        $items_query = "SELECT * FROM purchase_order_items WHERE purchase_order_id = ?";
        $stmt_items = $connection->prepare($items_query);
        $stmt_items->bind_param("i", $purchase_order_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        $items = [];
        while ($row = $items_result->fetch_assoc()) {
            $items[] = $row;
        }

        // Close all prepared statements
        $stmt_items->close();
        $stmt_purchase_order->close();
    } else {
        echo "No purchase order found for the given ID.<br>";
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
    <title>Purchase Order Form</title>
    <style>
        /* Styles remain unchanged */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #2c3e50;
        }
        .purchase-order-container {
            max-width: 900px;
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
        .purchase-order-info {
            display: flex;
            justify-content: flex-end;
            font-size: 14px;
            margin-top: 10px;
        }
        .purchase-order-info p {
            margin-left: 15px; /* Spacing between Purchase Order No and Date */
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
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="purchase-order-container">
        <div class="header">
            <h1>Purchase Order</h1>
            <div class="purchase-order-info" style="display: flex; flex-direction: column; justify-content: flex-end; align-items: flex-end; font-weight: bold;">
                <p>Purchase Order No: <?php echo isset($purchase_order['purchase_order_no']) ? htmlspecialchars($purchase_order['purchase_order_no']) : 'N/A'; ?></p>
                <p>Date: <?php echo isset($purchase_order['purchase_order_date']) ? htmlspecialchars($purchase_order['purchase_order_date']) : 'N/A'; ?></p>
            </div>
        </div>

        <!-- Display Vendor and Shipper Details -->
        <div class="details">
            <?php if ($purchase_order) { ?>
                <div>
                    <h4>Vendor</h4>
                    <p>Name: <?php echo htmlspecialchars($purchase_order['vendor_name']); ?></p>
                    <p>Phone: <?php echo htmlspecialchars($purchase_order['vendor_phone']); ?></p>
                    <p>Address: <?php echo htmlspecialchars($purchase_order['vendor_address']); ?></p>
                    <p>City: <?php echo htmlspecialchars($purchase_order['vendor_city']); ?></p>
                    <p>State: <?php echo htmlspecialchars($purchase_order['vendor_state']); ?></p>
                    <p>Country: <?php echo htmlspecialchars($purchase_order['vendor_country']); ?></p>
                    <p>Pincode: <?php echo htmlspecialchars($purchase_order['vendor_pincode']); ?></p>
                    <p>GSTIN: <?php echo htmlspecialchars($purchase_order['vendor_gstno']); ?></p>
                </div>
                <div>
                    <h4>Shipper</h4>
                    <p>Name: <?php echo htmlspecialchars($purchase_order['shipper_company_name']); ?></p>
                    <p>Phone: <?php echo htmlspecialchars($purchase_order['shipper_phone']); ?></p>
                    <p>Address: <?php echo htmlspecialchars($purchase_order['shipper_address']); ?></p>
                    <p>City: <?php echo htmlspecialchars($purchase_order['shipper_city']); ?></p>
                    <p>State: <?php echo htmlspecialchars($purchase_order['shipper_state']); ?></p>
                    <p>Country: <?php echo htmlspecialchars($purchase_order['shipper_country']); ?></p>
                    <p>Pincode: <?php echo htmlspecialchars($purchase_order['shipper_pincode']); ?></p>
                    <p>GSTIN: <?php echo htmlspecialchars($purchase_order['shipper_gstno']); ?></p>
                </div>
            <?php } else { ?>
                <p>No details found for the given purchase order ID.</p>
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
                    <th>Receipt Date</th> <!-- New column for receipt date -->
                    <th>Action</th> <!-- New column for action -->
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
                            <td><?php echo htmlspecialchars($item['receipt_date']); ?></td> <!-- Display receipt date -->
                            <td>
                                <a style="text-decoration:None; " title="Create an Invoice only for this Product" href="purchase_invoice_register_item.php?purchase_order_id=<?php echo $purchase_order_id; ?>&item_id=<?php echo $item['id']; ?>">
                                    🧾
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="12">No items found for this purchase order.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Amount Section -->
        <div class="amount">
            <p><strong>Total CGST:</strong> <?php echo isset($purchase_order['total_cgst']) ? htmlspecialchars($purchase_order['total_cgst']) : 'N/A'; ?></p>
            <p><strong>Total SGST:</strong> <?php echo isset($purchase_order['total_sgst']) ? htmlspecialchars($purchase_order['total_sgst']) : 'N/A'; ?></p>
            <p><strong>Total IGST:</strong> <?php echo isset($purchase_order['total_igst']) ? htmlspecialchars($purchase_order['total_igst']) : 'N/A'; ?></p>
            <p><strong>Gross Amount:</strong> <?php echo isset($purchase_order['gross_amount']) ? htmlspecialchars($purchase_order['gross_amount']) : 'N/A'; ?></p>
            <p><strong>Discount:</strong> <?php echo isset($purchase_order['discount']) ? htmlspecialchars($purchase_order['discount']) : 'N/A'; ?></p>
            <p><strong>Net Amount:</strong> <?php echo isset($purchase_order['net_amount']) ? htmlspecialchars($purchase_order['net_amount']) : 'N/A'; ?></p>
        </div>
    </div>

    <div class="footer">
        <p>Thanks for your business! Please visit us again!</p>
    </div>
</body>
</html>
