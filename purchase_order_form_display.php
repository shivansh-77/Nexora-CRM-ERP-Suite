<?php
include 'connection.php';

// Handle status update if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $poId = intval($_POST['po_id']);
    $status = 'Completed'; // We only allow updating to Completed

    // Update status in database
    $updateStmt = $connection->prepare("UPDATE purchase_order SET status = ? WHERE id = ?");
    $updateStmt->bind_param("si", $status, $poId);

    if ($updateStmt->execute()) {
        // Success - return JSON response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        exit;
    } else {
        // Error - return JSON response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        exit;
    }
}

// Original GET request handling
if (isset($_GET['id'])) {
    $purchase_order_id = intval($_GET['id']);

    // Fetch purchase order details
    $purchase_order_query = "SELECT id, purchase_order_no, purchase_order_date, gross_amount, discount, net_amount,
                            total_igst, total_cgst, total_sgst, vendor_name, vendor_address, vendor_phone,
                            vendor_city, vendor_state, vendor_country, vendor_pincode, vendor_gstno,
                            shipper_company_name, shipper_address, shipper_city, shipper_state,
                            shipper_country, shipper_pincode, shipper_phone, shipper_gstno, base_amount, status,vendor_company_name
                            FROM purchase_order WHERE id = ?";

    $stmt_purchase_order = $connection->prepare($purchase_order_query);
    $stmt_purchase_order->bind_param("i", $purchase_order_id);
    $stmt_purchase_order->execute();
    $purchase_order_result = $stmt_purchase_order->get_result();
    $purchase_order = $purchase_order_result->fetch_assoc();

    if ($purchase_order) {
    // Fetch company logo
    $company_logo_query = "SELECT company_logo FROM company_card WHERE id = 1";
    $stmt_company = $connection->prepare($company_logo_query);
    $stmt_company->execute();
    $company_result = $stmt_company->get_result();
    $company_data = $company_result->fetch_assoc();
    $stmt_company->close();

    // Fetch items including qytc and invoiced quantities
    $items_query = "SELECT
              poi.id,
              poi.product_id,
              poi.product_name,
              poi.unit,
              poi.quantity,
              poi.qytc,
              poi.rate,
              poi.gst,
              poi.igst,
              poi.cgst,
              poi.sgst,
              poi.amount,
              poi.receipt_date,
              poi.stock,
              poi.received_qty,
              poi.po_invoice,
              poi.value,
              COALESCE((
                  SELECT SUM(poil.quantity) / poi.value
                  FROM purchase_order_item_lot_tracking poil
                  WHERE poil.invoice_itemid = poi.id
                  AND poil.invoice_registered = 0
              ), 0) AS invoiced_quantity
          FROM purchase_order_items poi
          WHERE poi.purchase_order_id = ?";

        $stmt_items = $connection->prepare($items_query);
        $stmt_items->bind_param("i", $purchase_order_id);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();

        $items = [];
        while ($row = $items_result->fetch_assoc()) {
            // Check if lot_tracking is enabled for this product
            $lot_tracking_query = "SELECT lot_tracking FROM item WHERE item_code = ?";
            $stmt_lot = $connection->prepare($lot_tracking_query);
            $stmt_lot->bind_param("s", $row['product_id']);
            $stmt_lot->execute();
            $lot_result = $stmt_lot->get_result();
            $lot_info = $lot_result->fetch_assoc();
            $row['lot_tracking'] = $lot_info ? $lot_info['lot_tracking'] : 0;

            $items[] = $row;
            $stmt_lot->close();
        }


        // Fetch terms and conditions
        $terms_query = "SELECT terms_and_conditions FROM invoice_terms_conditions WHERE invoice_id = ? AND type = 'Purchase'";
        $stmt_terms = $connection->prepare($terms_query);
        $stmt_terms->bind_param("i", $purchase_order_id);
        $stmt_terms->execute();
        $terms_result = $stmt_terms->get_result();
        $terms = $terms_result->fetch_assoc();

        $stmt_items->close();
        $stmt_purchase_order->close();
        $stmt_terms->close();
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
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #2c3e50;
    }
    .purchase-order-container {
      max-width: 1360px;
      margin: 20px auto;
      background: #fff;
      padding: 30px;
      border: 1px solid #ccc;
      border-radius: 5px;
      position: relative;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .header-container {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 20px;
      position: relative;
    }
    .company-logo {
      flex-shrink: 0;
      margin-right: 20px;
    }
    .company-logo img {
      max-height: 200px;
      max-width: 200px;
      object-fit: contain;
    }
    .header-content {
      flex-grow: 1;
      text-align: center;
      color: #2c3e50;
    }
    .header-content h1 {
      margin: 0;
      font-size: 28px;
      font-weight: 600;
      color: #2c3e50;
      padding-bottom: 5px;
      border-bottom: 2px solid #2c3e50;
      display: inline-block;
      margin-right: 200px;
    }
    .header-content p {
      margin: 5px 0;
      font-size: 14px;
    }
    .purchase-order-info {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      font-weight: bold;
      margin-top: 10px;
    }
    .purchase-order-info p {
      margin: 3px 0;
      font-size: 14px;
    }
    .details {
      display: flex;
      justify-content: space-between;
      margin: 20px 0;
      background: #f9f9f9;
      padding: 15px;
      border-radius: 5px;
    }
    .details div {
      width: 48%;
    }
    .details div h4 {
      margin-bottom: 10px;
      font-size: 16px;
      text-transform: uppercase;
      color: #2c3e50;
      border-bottom: 1px solid #ddd;
      padding-bottom: 5px;
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
      font-weight: 600;
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
      margin-top: 30px;
      font-size: 14px;
      color: #666;
    }
    .terms-conditions {
      margin-top: 20px;
    }
    #editor {
      height: 200px;
      border: 1px solid #ddd;
      padding: 10px;
      background: white;
    }
    @media print {
      body {
        background-color: #fff;
      }
      .purchase-order-container {
        border: none;
        margin: 0;
        padding: 0;
        box-shadow: none;
      }
      .no-print, .ql-toolbar {
        display: none;
      }
      #editor {
        border: none;
        padding: 0;
      }
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
    .highlight-red {
      color: red;
      font-weight: bold;
      cursor: pointer;
    }
    th.full-height-header {
      font-size: 14px;
      padding: 0;
      margin: 0;
      height: 100%;
      line-height: normal;
      vertical-align: middle;
    }
    .action-btn {
      padding: 8px 16px;
      background-color: #2c3e50;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-right: 10px;
    }
    .action-btn:hover {
      background-color: #45a049;
    }
    .item-checkbox {
      transform: scale(1.3);
      cursor: pointer;
    }
    #bulkInvoiceBtn {
      transition: transform 0.3s ease;
    }
    #bulkInvoiceBtn:hover {
      background-color: #397bbd;
    }
    .highlight-red {
      background-color: #ffeeee;
      color: #d32f2f;
      font-weight: bold;
    }
    .highlight-green {
      background-color: #f0fff0;
      color: #2e7d32;
      font-weight: bold;
    }
    .completed-badge {
      display: inline-block;
      padding: 6px 8px;
      border-radius: 12px;
      background-color: #4caf50;
      color: white;
      font-size: 0.85em;
      font-weight: bold;
      cursor: pointer;
    }
    .status-btn {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      color: white;
      font-weight: bold;
      cursor: pointer;
      margin-top: 10px;
      font-size: 14px;
    }
    .status-btn.pending {
      background-color: #ff4444;
    }
    .status-btn.completed {
      background-color: #00C851;
      cursor: default;
    }

    /* Lot Tracking Modal Styles */
    .lot-summary {
      background: #f5f5f5;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
    }
    .lot-summary p {
      margin: 5px 0;
      font-weight: bold;
    }
    .lot-table th, .lot-table td {
      padding: 8px;
      text-align: left;
      border: 1px solid #ddd;
    }
    .lot-table input {
      width: 100%;
      padding: 5px;
      box-sizing: border-box;
    }
    .remove-lot {
      background: #ff4444;
      color: white;
      border: none;
      border-radius: 3px;
      padding: 3px 8px;
      cursor: pointer;
    }
    .remove-lot:hover {
      background: #cc0000;
    }

    /* Back button styles */
    .back-button {
      position: absolute;
      top: 10px;
      right: 10px;
      padding: 8px 16px;
      background-color: #6c757d;
      color: white;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
      cursor: pointer;
      z-index: 100;
      transition: background-color 0.3s;
    }
    .back-button:hover {
      background-color: #5a6268;
    }
    </style>
</head>
<body>
  <div class="purchase-order-container">
    <div class="purchase-order-container">
      <!-- Back button positioned at top right -->
      <a href="purchase_order_pending_display.php" class="back-button">Back</a>

      <div class="header-container">
        <?php if (isset($company_data['company_logo']) && !empty($company_data['company_logo'])): ?>
        <div class="company-logo">
          <img src="<?php echo htmlspecialchars($company_data['company_logo']); ?>" alt="Company Logo">
        </div>
        <?php endif; ?>

        <div class="header-content">
          <h1>PURCHASE ORDER</h1>
          <div class="purchase-order-info">
            <p>Purchase Order No: <?php echo isset($purchase_order['purchase_order_no']) ? htmlspecialchars($purchase_order['purchase_order_no']) : 'N/A'; ?></p>
            <p>Date: <?php echo isset($purchase_order['purchase_order_date']) ? htmlspecialchars($purchase_order['purchase_order_date']) : 'N/A'; ?></p>
            <button id="statusButton"
                    class="status-btn <?php
                        $status = isset($purchase_order['status']) ? strtolower(trim($purchase_order['status'])) : 'pending';
                        echo ($status === 'completed') ? 'completed' : 'pending';
                    ?>"
                    data-po-id="<?php echo $purchase_order['id'] ?? ''; ?>"
                    onclick="updateStatus(this)">
                <?php
                    echo (isset($purchase_order['status']) && strtolower(trim($purchase_order['status'])) === 'completed')
                        ? 'Completed' : 'Pending';
                ?>
            </button>
          </div>
        </div>
      </div>

      <div class="details">
        <?php if ($purchase_order) { ?>
            <div>
                <h4>Vendor</h4>
                <p>Name: <?php echo htmlspecialchars($purchase_order['vendor_company_name']); ?></p>
                <p>Contact Person: <?php echo htmlspecialchars($purchase_order['vendor_name']); ?></p>
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
                <th style="font-size:15px; padding:1; margin:0; height:100%; line-height:normal; vertical-align:middle;">QTY. Base U.O.M</th>
                <th style="font-size:15px; padding:1; margin:0; height:100%; line-height:normal; vertical-align:middle;">Outsending QTY.</th>
                <th style="font-size:15px; padding:1; margin:0; height:100%; line-height:normal; vertical-align:middle;">QTY. Received</th>
                <th style="font-size:15px; padding:1; margin:0; height:100%; line-height:normal; vertical-align:middle;">QTY. To Receive</th>
                <th style="font-size:15px; padding:1; margin:0; height:100%; line-height:normal; vertical-align:middle;">P.O To Invoice</th>
                <th>Rate</th>
                <th>GST (%)</th>
                <th>Base Amount</th>
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
                <?php
                foreach ($items as $index => $item) {
                    $qytc = $item['qytc'];
                    if ($qytc > 0) {
                        $qytcClass = 'highlight-red';
                        $qytcDisplay = htmlspecialchars($qytc);
                    } else {
                        $qytcClass = 'highlight-green';
                        $qytcDisplay = '<span class="completed-badge"> Completed</span>';
                    }

                    // Calculate base amount
                    $po_invoice_qty = $item['lot_tracking'] == 0 ? ($item['po_invoice'] ?? 0) : $item['invoiced_quantity'];
                    $rate = floatval($item['rate']);
                    $gst_percentage = floatval($item['gst']);
                    $value = floatval($item['value']);

                    $base_amount = $po_invoice_qty * $rate * $value;
                    $gst_amount = ($base_amount * $gst_percentage) / 100;

                    // Determine GST distribution based on vendor and shipper states
                    $vendor_state = strtolower(trim($purchase_order['vendor_state']));
                    $shipper_state = strtolower(trim($purchase_order['shipper_state']));

                    if ($vendor_state === $shipper_state) {
                        // Same state: Split GST equally between CGST and SGST
                        $igst = 0;
                        $cgst = $gst_amount / 2;
                        $sgst = $gst_amount / 2;
                    } else {
                        // Different states: All GST goes to IGST
                        $igst = $gst_amount;
                        $cgst = 0;
                        $sgst = 0;
                    }

                    $final_amount = $base_amount + $gst_amount;
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="item-checkbox"
                                   data-item-id="<?php echo $item['id']; ?>"
                                   data-base-amount="<?php echo $base_amount; ?>"
                                   data-igst="<?php echo $igst; ?>"
                                   data-cgst="<?php echo $cgst; ?>"
                                   data-sgst="<?php echo $sgst; ?>"
                                   data-final-amount="<?php echo $final_amount; ?>">
                        </td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($item['stock']); ?></td>
                        <td title="Update the Outsending Quantity" class="qytc-cell <?php echo $qytcClass; ?>"
                            data-item-id="<?php echo $item['id']; ?>"
                            data-current-qytc="<?php echo $qytc; ?>">
                            <?php echo $qytcDisplay; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['received_qty'] - $item['po_invoice'] ?? 0); ?></td>
                        <td><?php echo htmlspecialchars($item['po_invoice'] ?? 0); ?></td>
                        <td><?php echo htmlspecialchars($po_invoice_qty); ?></td>
                        <td><?php echo htmlspecialchars($item['rate']); ?></td>
                        <td><?php echo htmlspecialchars($item['gst']); ?>%</td>
                        <td><?php echo number_format($base_amount, 2); ?></td>
                        <td><?php echo number_format($igst, 2); ?></td>
                        <td><?php echo number_format($cgst, 2); ?></td>
                        <td><?php echo number_format($sgst, 2); ?></td>
                        <td><?php echo number_format($final_amount, 2); ?></td>
                        <td><?php echo htmlspecialchars($item['receipt_date']); ?></td>
                        <td>
                            <a style="text-decoration:None;" title="Track Purchase Lot" href="purchase_lot_tracking.php?item_id=<?php echo $item['id']; ?>">
                              ✚
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="18">No items found for this purchase order.</td>
                </tr>
            <?php } ?>
        </tbody>
      </table>

      <div class="action-buttons" style="display: flex; justify-content: space-between; margin-top: 20px; cursor:pointer;">
        <div class="left-actions">
            <button type="button" id="bulkInvoiceBtn" class="action-btn" style="display: none;">Invoice Selected</button>
        </div>
        <div class="right-actions">
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; margin-top: 20px;">
        <div class="terms-conditions" style="width: 48%;">
            <h4>Terms and Conditions</h4>
            <form id="terms-form" method="POST" action="save_terms.php">
                <input type="hidden" name="invoice_id" value="<?php echo htmlspecialchars($purchase_order_id); ?>">
                <input type="hidden" name="document_no" value="<?php echo isset($purchase_order['purchase_order_no']) ? htmlspecialchars($purchase_order['purchase_order_no']) : ''; ?>">
                <input type="hidden" name="type" value="Purchase">
                <div id="editor">
                    <?php
                    if (isset($terms['terms_and_conditions'])) {
                        $content = strip_tags($terms['terms_and_conditions'], '<br><strong><em><u><strike>');
                        echo $content;
                    }
                    ?>
                </div>
                <input type="hidden" name="terms_and_conditions" id="hidden-terms">
                <br>
                <button type="submit" class="no-print">Save Terms and Conditions</button>
                <button type="button" class="no-print" onclick="window.print()">Print Invoice</button>
            </form>
        </div>
        <div class="amount" style="width: 48%;">
            <p><strong>Base Amount:</strong> <span id="totalBaseAmount">0.00</span></p>
            <p><strong>Total CGST:</strong> <span id="totalCGST">0.00</span></p>
            <p><strong>Total SGST:</strong> <span id="totalSGST">0.00</span></p>
            <p><strong>Total IGST:</strong> <span id="totalIGST">0.00</span></p>
            <p><strong>Gross Amount:</strong> <span id="totalGrossAmount">0.00</span></p>
            <p><strong>Discount:</strong> <?php echo isset($purchase_order['discount']) ? htmlspecialchars($purchase_order['discount']) : '0.00'; ?></p>
            <p><strong>Net Amount:</strong> <span id="totalNetAmount">0.00</span></p>
        </div>
      </div>

      <div class="footer">
        <p>Thanks for your business! Please visit us again!</p>
      </div>

      <!-- Rest of your modal and JavaScript code remains the same -->
      <div id="qytcModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Update Quantity Yet To Be Received</h3>
            <form id="qytcForm">
                <input type="hidden" id="itemId" name="item_id">
                <input type="hidden" id="purchaseOrderId" name="purchase_order_id" value="<?php echo $purchase_order_id; ?>">
                <div>
                    <label for="currentQytc">Outsending Quantity:</label>
                    <input type="number" id="currentQytc" class="qytc-input" readonly>
                </div>
                <div>
                    <label for="receivedQty">Quantity to Receive:</label>
                    <input type="number" id="receivedQty" class="qytc-input" step="any" min="0">
                    <span id="quantityError" style="color: red; display: none;">Quantity received cannot exceed total quantity yet to be received</span>
                </div>
                <div>
                    <label for="newQytc">Quantity Left:</label>
                    <input type="number" id="newQytc" class="qytc-input" readonly>
                </div>
                <div class="modal-footer">
                    <button type="button" id="updateBtn">Update</button>
                    <button type="button" id="updateAndInvoiceBtn" style="display:none;">Upd. & INV.</button>
                </div>
            </form>
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

        document.querySelector('form').onsubmit = function() {
            var termsContent = quill.root.innerHTML;
            termsContent = termsContent.replace(/<p>/g, '').replace(/<\/p>/g, '<br>');
            document.querySelector('#hidden-terms').value = termsContent;
        };

        const modal = document.getElementById('qytcModal');
        const closeBtn = document.querySelector('.close');
        const receivedQtyInput = document.getElementById('receivedQty');
        const updateBtn = document.getElementById('updateBtn');
        const updateAndInvoiceBtn = document.getElementById('updateAndInvoiceBtn');
        const newQytcInput = document.getElementById('newQytc');
        const quantityError = document.getElementById('quantityError');
        const bulkInvoiceBtn = document.getElementById('bulkInvoiceBtn');

        // Function to update totals based on selected checkboxes
        function updateTotals() {
            let totalBaseAmount = 0;
            let totalIGST = 0;
            let totalCGST = 0;
            let totalSGST = 0;
            let totalGrossAmount = 0;

            // Get all checked checkboxes
            const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');

            checkedBoxes.forEach(checkbox => {
                totalBaseAmount += parseFloat(checkbox.dataset.baseAmount) || 0;
                totalIGST += parseFloat(checkbox.dataset.igst) || 0;
                totalCGST += parseFloat(checkbox.dataset.cgst) || 0;
                totalSGST += parseFloat(checkbox.dataset.sgst) || 0;
                totalGrossAmount += parseFloat(checkbox.dataset.finalAmount) || 0;
            });

            // Update the display
            document.getElementById('totalBaseAmount').textContent = totalBaseAmount.toFixed(2);
            document.getElementById('totalIGST').textContent = totalIGST.toFixed(2);
            document.getElementById('totalCGST').textContent = totalCGST.toFixed(2);
            document.getElementById('totalSGST').textContent = totalSGST.toFixed(2);
            document.getElementById('totalGrossAmount').textContent = totalGrossAmount.toFixed(2);

            // Calculate net amount (gross amount - discount)
            const discount = parseFloat('<?php echo isset($purchase_order['discount']) ? $purchase_order['discount'] : 0; ?>') || 0;
            const netAmount = totalGrossAmount - discount;
            document.getElementById('totalNetAmount').textContent = netAmount.toFixed(2);
        }

        function toggleUpdateAndInvoiceBtn() {
            const currentQytc = parseFloat(document.getElementById('currentQytc').value) || 0;
            const receivedQty = parseFloat(receivedQtyInput.value) || 0;
            const shouldShow = receivedQty > 0 && receivedQty <= currentQytc;
            updateAndInvoiceBtn.style.display = shouldShow ? 'inline-block' : 'none';
        }

        document.querySelectorAll('.qytc-cell').forEach(cell => {
            cell.addEventListener('click', function() {
                const itemId = this.getAttribute('data-item-id');
                const currentQytc = parseFloat(this.getAttribute('data-current-qytc')) || 0;

                document.getElementById('itemId').value = itemId;
                document.getElementById('currentQytc').value = currentQytc;
                receivedQtyInput.value = '';
                newQytcInput.value = '';
                quantityError.style.display = 'none';
                updateAndInvoiceBtn.style.display = 'none';
                modal.style.display = 'block';
            });
        });

        closeBtn.onclick = () => modal.style.display = 'none';
        window.onclick = (event) => { if (event.target === modal) modal.style.display = 'none'; }

        receivedQtyInput.addEventListener('input', function() {
            const currentQytc = parseFloat(document.getElementById('currentQytc').value) || 0;
            const receivedQty = parseFloat(this.value) || 0;

            if (receivedQty > currentQytc) {
                quantityError.style.display = 'inline';
                newQytcInput.value = '';
                updateBtn.disabled = true;
                updateAndInvoiceBtn.style.display = 'none';
                return;
            }

            quantityError.style.display = 'none';
            updateBtn.disabled = false;
            newQytcInput.value = Math.max(0, currentQytc - receivedQty);
            toggleUpdateAndInvoiceBtn();
        });

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

            if (receivedQty > currentQytc) {
                alert('Quantity received cannot exceed total quantity yet to be received');
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_qytc.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            const cell = document.querySelector(`.qytc-cell[data-item-id="${itemId}"]`);
                            cell.textContent = newQytc;
                            cell.setAttribute('data-current-qytc', newQytc);
                            modal.style.display = 'none';
                            alert('Quantities updated successfully');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            alert('Error: ' + (response.message || 'Failed to update quantities'));
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
            xhr.send(`item_id=${itemId}&new_qytc=${newQytc}&purchase_order_id=${purchaseOrderId}&received_qty=${receivedQty}`);
        });

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

            if (receivedQty > currentQytc) {
                alert('Quantity received cannot exceed total quantity yet to be received');
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_and_invoice.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            const cell = document.querySelector(`.qytc-cell[data-item-id="${itemId}"]`);
                            cell.textContent = newQytc;
                            cell.setAttribute('data-current-qytc', newQytc);
                            modal.style.display = 'none';
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

        document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const bulkInvoiceBtn = document.getElementById('bulkInvoiceBtn');

        // Store additional data on checkboxes
        checkboxes.forEach(checkbox => {
            const row = checkbox.closest('tr');
            const poQtyCell = row.querySelector('td:nth-child(8)'); // QTY. To Receive (column 8)
            const poInvoiceCell = row.querySelector('td:nth-child(9)'); // P.O To Invoice (column 9)

            checkbox.dataset.poQty = parseFloat(poQtyCell.textContent) || 0;
            checkbox.dataset.poInvoice = parseFloat(poInvoiceCell.textContent) || 0;
        });

        // Checkbox change handler
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const validCheckedBoxes = Array.from(document.querySelectorAll('.item-checkbox:checked'))
                    .filter(cb => {
                        const poQty = parseFloat(cb.dataset.poQty);
                        const poInvoice = parseFloat(cb.dataset.poInvoice);
                        return poQty > 0 && poQty === poInvoice;
                    });

                bulkInvoiceBtn.style.display = validCheckedBoxes.length > 0 ? 'inline-block' : 'none';

                // Update totals when checkbox changes
                updateTotals();
            });
        });

        // Checkbox click handler (prevent selection if conditions not met)
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('click', function(e) {
                const poQty = parseFloat(this.dataset.poQty);
                const poInvoice = parseFloat(this.dataset.poInvoice);

                if (poQty <= 0) {
                    alert("Cannot create invoice - PO Quantity must be greater than 0");
                    this.checked = false;
                    e.preventDefault();
                    return;
                }

                if (poQty !== poInvoice) {
                    alert("Cannot create invoice - QTY. To Receive must match P.O To Invoice value");
                    this.checked = false;
                    e.preventDefault();
                }
            });
        });

            bulkInvoiceBtn.addEventListener('click', function() {
                const selectedItems = Array.from(document.querySelectorAll('.item-checkbox:checked'))
                    .filter(checkbox => parseFloat(checkbox.dataset.poQty) > 0)
                    .map(checkbox => checkbox.dataset.itemId);

                if (selectedItems.length === 0) {
                    alert('Please select at least one item with PO Quantity > 0');
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.textContent = 'Processing...';

                const formData = new FormData();
                formData.append('purchase_order_id', <?php echo $purchase_order_id; ?>);
                selectedItems.forEach(id => {
                    formData.append('item_ids[]', id);
                });

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'update_invoice_items.php', true);
                xhr.onload = function() {
                    btn.disabled = false;
                    btn.textContent = 'Invoice Selected';

                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                alert(`Invoice ${response.invoice_no} created successfully!`);
                                window.location.reload();
                            } else {
                                alert('Error: ' + (response.message || 'Invoice creation failed'));
                            }
                        } catch (e) {
                            alert('Error parsing server response: ' + e.message);
                        }
                    } else {
                        alert('Server error: ' + xhr.status);
                    }
                };
                xhr.onerror = function() {
                    btn.disabled = false;
                    btn.textContent = 'Invoice Selected';
                    alert('Network error occurred');
                };
                xhr.send(formData);
            });
        });

        function updateStatus(button) {
            const poId = button.getAttribute('data-po-id');
            const currentStatus = button.textContent.trim().toLowerCase();

            if (currentStatus === 'completed') {
                alert('This order is already completed.');
                return;
            }

            if (!confirm('Are you sure you want to mark this Purchase Order as Completed?')) {
                return;
            }

            const originalText = button.textContent;
            const originalClass = button.className;

            button.disabled = true;
            button.innerHTML = '<span class="loading-spinner"></span>Updating...';
            button.classList.add('loading');

            const formData = new FormData();
            formData.append('update_status', '1');
            formData.append('po_id', poId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    button.innerHTML = 'Completed';
                    button.className = originalClass.replace('pending', 'completed');
                    button.onclick = null;
                    alert('Status updated to Completed successfully!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Update failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.textContent = originalText;
                button.className = originalClass;
                alert('Error updating status: ' + error.message);
            })
            .finally(() => {
                button.disabled = false;
                button.classList.remove('loading');
            });
        }
    </script>

</body>
</html>
