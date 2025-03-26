<?php
include 'connection.php';

if (isset($_GET['id'])) {
    $purchase_order_id = intval($_GET['id']);

    // Fetch purchase order details
    $purchase_order_query = "SELECT purchase_order_no, purchase_order_date, gross_amount, discount, net_amount,
                            total_igst, total_cgst, total_sgst, vendor_name, vendor_address, vendor_phone,
                            vendor_city, vendor_state, vendor_country, vendor_pincode, vendor_gstno,
                            shipper_company_name, shipper_address, shipper_city, shipper_state,
                            shipper_country, shipper_pincode, shipper_phone, shipper_gstno, base_amount
                            FROM purchase_order WHERE id = ?";

    $stmt_purchase_order = $connection->prepare($purchase_order_query);
    $stmt_purchase_order->bind_param("i", $purchase_order_id);
    $stmt_purchase_order->execute();
    $purchase_order_result = $stmt_purchase_order->get_result();
    $purchase_order = $purchase_order_result->fetch_assoc();

    if ($purchase_order) {
        // Fetch items including qytc
        $items_query = "SELECT id, product_name, unit, quantity, qytc, rate, gst, igst, cgst, sgst, amount, receipt_date
                        FROM purchase_order_items WHERE purchase_order_id = ?";
        $stmt_items = $connection->prepare($items_query);
        $stmt_items->bind_param("i", $purchase_order_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        $items = [];
        while ($row = $items_result->fetch_assoc()) {
            // Initialize qytc with quantity if not set
            if ($row['qytc'] == 0) {
                $row['qytc'] = $row['quantity'];
            }
            $items[] = $row;
        }

        $stmt_items->close();
        $stmt_purchase_order->close();
    } else {
        echo "No purchase order found for the given ID.<br>";
    }
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

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #2c3e50;
        }
        .purchase-order-container {
            max-width: 930px;
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


        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 400px;
            border-radius: 5px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .modal-footer {
            margin-top: 20px;
            text-align: right;
        }

        .modal-footer button {
            padding: 8px 16px;
            margin-left: 10px;
            cursor: pointer;
        }

        .qytc-input {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            box-sizing: border-box;
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
                    <th>Qty</th>
                    <th>Q.Y.T.C</th>
                    <th>Rate</th>
                    <th>GST (%)</th>
                    <th>IGST</th>
                    <th>CGST</th>
                    <th>SGST</th>
                    <th>Amount</th>
                    <th>Receipt Date</th>
                    <th>Action</th>
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
                            <td class="qytc-cell" data-item-id="<?php echo $item['id']; ?>" data-current-qytc="<?php echo $item['qytc']; ?>">
                                <?php echo htmlspecialchars($item['qytc']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['rate']); ?></td>
                            <td><?php echo htmlspecialchars($item['gst']); ?></td>
                            <td><?php echo htmlspecialchars($item['igst']); ?></td>
                            <td><?php echo htmlspecialchars($item['cgst']); ?></td>
                            <td><?php echo htmlspecialchars($item['sgst']); ?></td>
                            <td><?php echo htmlspecialchars($item['amount']); ?></td>
                            <td><?php echo htmlspecialchars($item['receipt_date']); ?></td>
                            <td>
                                <a style="text-decoration:None;" title="Create an Invoice only for this Product" href="purchase_invoice_register_item.php?purchase_order_id=<?php echo $purchase_order_id; ?>&item_id=<?php echo $item['id']; ?>">
                                    🧾
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="13">No items found for this purchase order.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

                <!-- Amount Section -->
                <div class="amount">
                  <p><strong>Base Amount:</strong> <?php echo isset($purchase_order['base_amount']) ? htmlspecialchars($purchase_order['base_amount']) : 'N/A'; ?></p>
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
    </div>

    <!-- Q.Y.T.C Update Modal -->
<div id="qytcModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Update Quantity Yet To Be Cleared</h3>
        <form id="qytcForm">
            <input type="hidden" id="itemId" name="item_id">
            <input type="hidden" id="purchaseOrderId" name="purchase_order_id" value="<?php echo $purchase_order_id; ?>">

            <div>
                <label for="currentQytc">Total Quantity Yet to be Received:</label>
                <input type="number" id="currentQytc" class="qytc-input" readonly>
            </div>

            <div>
                <label for="receivedQty">Quantity Received:</label>
                <input type="number" id="receivedQty" class="qytc-input" step="any" min="0">
                <span id="quantityError" style="color: red; display: none;">Quantity received cannot exceed total quantity yet to be received</span>
            </div>

            <div>
                <label for="newQytc">Current Quantity Yet to be Recceived:</label>
                <input type="number" id="newQytc" class="qytc-input" readonly>
            </div>

            <div class="modal-footer">
                <button type="button" id="updateBtn">Update</button>
                <button type="button" id="updateAndInvoiceBtn" style="display:none;">Upd. & INV.</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Get all necessary elements
    const modal = document.getElementById('qytcModal');
    const closeBtn = document.querySelector('.close');
    const receivedQtyInput = document.getElementById('receivedQty');
    const updateBtn = document.getElementById('updateBtn');
    const updateAndInvoiceBtn = document.getElementById('updateAndInvoiceBtn');
    const newQytcInput = document.getElementById('newQytc');
    const quantityError = document.getElementById('quantityError');

    // Function to show/hide the Update & Invoice button
    function toggleUpdateAndInvoiceBtn() {
        const currentQytc = parseFloat(document.getElementById('currentQytc').value) || 0;
        const receivedQty = parseFloat(receivedQtyInput.value) || 0;

        // Show button only if:
        // 1. Received quantity is greater than 0
        // 2. Received quantity is less than or equal to current Q.Y.T.C
        const shouldShow = receivedQty > 0 && receivedQty <= currentQytc;

        updateAndInvoiceBtn.style.display = shouldShow ? 'inline-block' : 'none';
    }

    // When clicking on Q.Y.T.C cells
    document.querySelectorAll('.qytc-cell').forEach(cell => {
        cell.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            const currentQytc = parseFloat(this.getAttribute('data-current-qytc')) || 0;

            // Set values in the modal
            document.getElementById('itemId').value = itemId;
            document.getElementById('currentQytc').value = currentQytc;
            receivedQtyInput.value = '';
            newQytcInput.value = '';
            quantityError.style.display = 'none';

            // Reset button visibility
            updateAndInvoiceBtn.style.display = 'none';

            // Show the modal
            modal.style.display = 'block';
        });
    });

    // Close modal when clicking X
    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }

    // Calculate new Q.Y.T.C when received quantity changes
    receivedQtyInput.addEventListener('input', function() {
        const currentQytc = parseFloat(document.getElementById('currentQytc').value) || 0;
        const receivedQty = parseFloat(this.value) || 0;

        // Validate if received quantity exceeds current Q.Y.T.C
        if (receivedQty > currentQytc) {
            quantityError.style.display = 'inline';
            newQytcInput.value = '';
            updateBtn.disabled = true;
            updateAndInvoiceBtn.style.display = 'none';
            return;
        } else {
            quantityError.style.display = 'none';
            updateBtn.disabled = false;
        }

        const newQytc = Math.max(0, currentQytc - receivedQty); // Ensure not negative
        newQytcInput.value = newQytc;

        // Update button visibility
        toggleUpdateAndInvoiceBtn();
    });

    // Handle Update button click
    updateBtn.addEventListener('click', function() {
        const itemId = document.getElementById('itemId').value;
        const newQytc = newQytcInput.value;
        const purchaseOrderId = document.getElementById('purchaseOrderId').value;
        const currentQytc = parseFloat(document.getElementById('currentQytc').value) || 0;
        const receivedQty = parseFloat(receivedQtyInput.value) || 0;

        if (!newQytc || isNaN(newQytc)) {
            alert('Please enter a valid received quantity');
            return;
        }

        // Additional validation to prevent receiving more than needed
        if (receivedQty > currentQytc) {
            alert('Quantity received cannot exceed total quantity yet to be received');
            return;
        }

        // AJAX request to update Q.Y.T.C
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_qytc.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Update the table cell
                        const cell = document.querySelector(`.qytc-cell[data-item-id="${itemId}"]`);
                        cell.textContent = newQytc;
                        cell.setAttribute('data-current-qytc', newQytc);

                        // Close modal
                        modal.style.display = 'none';
                        alert('Q.Y.T.C updated successfully');
                    } else {
                        alert('Error: ' + (response.message || 'Failed to update Q.Y.T.C'));
                    }
                } catch (e) {
                    alert('Error parsing server response');
                }
            } else {
                alert('Server error: ' + xhr.status);
            }
        };
        xhr.onerror = function() {
            alert('Network error occurred');
        };
        xhr.send(`item_id=${itemId}&new_qytc=${newQytc}&purchase_order_id=${purchaseOrderId}`);
    });

    // Handle Update & Invoice button click
    updateAndInvoiceBtn.addEventListener('click', function() {
        const itemId = document.getElementById('itemId').value;
        const receivedQty = parseFloat(receivedQtyInput.value) || 0;
        const newQytc = newQytcInput.value;
        const purchaseOrderId = document.getElementById('purchaseOrderId').value;
        const currentQytc = parseFloat(document.getElementById('currentQytc').value) || 0;

        if (receivedQty <= 0) {
            alert('Received quantity must be greater than 0');
            return;
        }

        // Additional validation to prevent receiving more than needed
        if (receivedQty > currentQytc) {
            alert('Quantity received cannot exceed total quantity yet to be received');
            return;
        }

        // AJAX request to update and create invoice
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_and_invoice.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Update the table cell
                        const cell = document.querySelector(`.qytc-cell[data-item-id="${itemId}"]`);
                        cell.textContent = newQytc;
                        cell.setAttribute('data-current-qytc', newQytc);

                        // Close modal
                        modal.style.display = 'none';

                        // Alert the user
                        alert('Stock Updated and Invoice Generated Successfully');
                    } else {
                        alert('Error: ' + (response.message || 'Operation failed'));
                    }
                } catch (e) {
                    alert('Error parsing server response');
                }
            } else {
                alert('Server error: ' + xhr.status);
            }
        };
        xhr.onerror = function() {
            alert('Network error occurred');
        };
        xhr.send(`item_id=${itemId}&received_qty=${receivedQty}&new_qytc=${newQytc}&purchase_order_id=${purchaseOrderId}`);
    });
</script>
</body>
</html>
