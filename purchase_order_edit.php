<?php
include 'connection.php';

// Fetch purchase order data if ID is provided
$purchase_order = null;
$purchase_order_items = [];
$vendor = null;
$shipper = null;

if (isset($_GET['id'])) {
    $purchase_order_id = $_GET['id'];
    $query = "SELECT * FROM purchase_order WHERE id = $purchase_order_id";
    $result = $connection->query($query);
    $purchase_order = $result->fetch_assoc();

    if ($purchase_order) {
        // Fetch vendor details
        $vendor_query = "SELECT * FROM contact_vendor WHERE id = {$purchase_order['vendor_id']}";
        $vendor_result = $connection->query($vendor_query);
        $vendor = $vendor_result->fetch_assoc();

        // Fetch shipper details
        $shipper_query = "SELECT * FROM location_card WHERE id = {$purchase_order['shipper_id']}";
        $shipper_result = $connection->query($shipper_query);
        $shipper = $shipper_result->fetch_assoc();

        // Fetch purchase order items
        $items_query = "SELECT * FROM purchase_order_items WHERE purchase_order_id = $purchase_order_id";
        $items_result = $connection->query($items_query);
        $purchase_order_items = $items_result->fetch_all(MYSQLI_ASSOC);
    }
}

// Handle form submission for updating
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $purchase_order_id = $_POST['purchase_order_id'];
    $purchase_order_date = $_POST['purchase_order_date'];
    $vendor_id = $_POST['vendor_id'];
    $vendor_name = $_POST['vendor_name'];
    $shipper_id = $_POST['shipper_id'];
    $gross_amount = $_POST['gross_amount'];
    $discount = $_POST['discount'];
    $net_amount = $_POST['net_amount'];
    $base_amount = $_POST['base_amount'];
    $total_igst = $_POST['total_igst'];
    $total_cgst = $_POST['total_cgst'];
    $total_sgst = $_POST['total_sgst'];

    // Update purchase order in the database
    $update_purchase_order = "UPDATE purchase_order SET
        vendor_id = '$vendor_id',
        shipper_id = '$shipper_id',
        gross_amount = '$gross_amount',
        discount = '$discount',
        net_amount = '$net_amount',
        purchase_order_date = '$purchase_order_date',
        total_igst = '$total_igst',
        total_cgst = '$total_cgst',
        total_sgst = '$total_sgst',
        base_amount = '$base_amount',
        vendor_address = '{$_POST['vendor_address']}',
        vendor_phone = '{$_POST['vendor_phone']}',
        vendor_city = '{$_POST['vendor_city']}',
        vendor_state = '{$_POST['vendor_state']}',
        vendor_country = '{$_POST['vendor_country']}',
        vendor_pincode = '{$_POST['vendor_pincode']}',
        vendor_gstno = '{$_POST['vendor_gstno']}',
        shipper_company_name = '{$_POST['shipper_company_name']}',
        shipper_address = '{$_POST['shipper_address']}',
        shipper_city = '{$_POST['shipper_city']}',
        shipper_state = '{$_POST['shipper_state']}',
        shipper_country = '{$_POST['shipper_country']}',
        shipper_pincode = '{$_POST['shipper_pincode']}',
        shipper_phone = '{$_POST['shipper_phone']}',
        shipper_gstno = '{$_POST['shipper_gstno']}',
        vendor_name = '$vendor_name'
        WHERE id = $purchase_order_id";

    if ($connection->query($update_purchase_order)) {
        // Delete existing items for this purchase order
        $delete_items = "DELETE FROM purchase_order_items WHERE purchase_order_id = $purchase_order_id";
        $connection->query($delete_items);

        // Insert updated items
        $products = $_POST['product_name'];
        $product_names = $_POST['product_name_actual'];
        $quantities = $_POST['quantity'];
        $rates = $_POST['rate'];
        $gsts = $_POST['product_gst'];
        $amounts = $_POST['amount'];
        $units = $_POST['unit'];
        $igsts = $_POST['igst'];
        $cgsts = $_POST['cgst'];
        $sgsts = $_POST['sgst'];
        $receipt_dates = $_POST['receipt_date']; // Retrieve receipt dates

        for ($i = 0; $i < count($products); $i++) {
            $insert_item = "INSERT INTO purchase_order_items (purchase_order_id, product_id, product_name, quantity, rate, gst, amount, unit, igst, cgst, sgst, receipt_date)
                            VALUES ('$purchase_order_id', '{$products[$i]}', '{$product_names[$i]}', '{$quantities[$i]}',
                            '{$rates[$i]}', '{$gsts[$i]}', '{$amounts[$i]}', '{$units[$i]}', '{$igsts[$i]}',
                            '{$cgsts[$i]}', '{$sgsts[$i]}', '{$receipt_dates[$i]}')";
            $connection->query($insert_item);
        }

        echo "<script>alert('Purchase Order updated successfully!'); window.location.href='purchase_order_display.php';</script>";
    } else {
        echo "<p>Error updating purchase order: " . $connection->error . "</p>";
    }
}

// Fetch all vendors and shippers for dropdowns
$vendors_query = "SELECT * FROM contact_vendor";
$vendors_result = $connection->query($vendors_query);

$shippers_query = "SELECT * FROM location_card";
$shippers_result = $connection->query($shippers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Purchase Order</title>
    <!-- Include your CSS here -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #2c3e50;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        h2, h3 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: border-color 0.3s;
        }
        input:focus, select:focus {
            border-color: #2c3e50;
            outline: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ccc;
        }
        th {
            background-color: #2c3e50;
            color: white;
            text-align: center;
        }
        input[type="text"], input[type="number"], select {
            width: 100%;
            height: 40px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus, input[type="number"]:focus, select:focus {
            border-color: #3498db;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .remove-button {
            padding: 5px 10px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button {
            padding: 10px 15px;
            background-color: #2c3e50;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 15px;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s, transform 0.3s;
        }
        button:hover {
            background-color: #34495e;
            transform: scale(1.05);
        }
        .form-section {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 20px;
        }
        .column {
            width: 48%;
        }
        .summary-section {
            width: 100%;
            max-width: 400px;
            margin-left: auto;
            margin-right: 0;
            text-align: right;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .input-group {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }
        .input-group label {
            font-size: 14px;
            font-weight: bold;
            width: 60%;
            text-align: right;
        }
        .input-group span {
            font-size: 14px;
            font-weight: normal;
            text-align: right;
            width: 40%;
            display: inline-block;
            padding: 0px 0;
            border-bottom: 1px solid #ccc;
            margin-top: 15px;
        }
        .input-group input {
            font-size: 14px;
            font-weight: normal;
            text-align: right;
            border: none;
            border-bottom: 1px solid #ccc;
            width: 25%;
            max-width: 60px;
            margin-left: 10px;
            padding: 3px;
            box-shadow: none;
        }
        input[name="igst[]"], input[name="cgst[]"], input[name="sgst[]"] {
            width: 80%;
        }
        .close-btn {
            font-size: 20px;
            color: #2c3e50;
            transition: color 0.3s;
        }
        .close-btn:hover {
            color: darkred;
        }
        .unit-dropdown ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .unit-dropdown ul li {
            padding: 10px;
            cursor: pointer;
        }
        .unit-dropdown ul li:hover {
            background-color: #f0f0f0;
        }
        .submit {
            margin-left: 420px;
        }
    </style>
</head>
<body>
  <div class="container">
      <a style="text-decoration: none; margin-left: 1027px; padding: 0px; position: relative; top: -34px; transform: translateY(-50%);" href="purchase_order_display.php" class="close-btn">&times;</a>
      <form id="editPurchaseOrderForm" method="POST" action="">
          <input type="hidden" name="purchase_order_id" value="<?php echo $purchase_order['id']; ?>">
          <input type="hidden" name="vendor_id" id="vendor_id" value="<?php echo $purchase_order['vendor_id']; ?>">
          <input type="hidden" name="shipper_id" id="shipper_id" value="<?php echo $purchase_order['shipper_id']; ?>">

          <!-- Date input -->
          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: -25px; width: 100%;">
              <div style="flex-grow: 1; text-align: center;">
                  <h1 style="margin-left: 150px;">Edit Purchase Order</h1>
              </div>
              <div style="margin-right: 10px;">
                  <label for="purchase_order_date"><strong>Date:</strong></label>
                  <input
                      type="date"
                      id="purchase_order_date"
                      name="purchase_order_date"
                      value="<?php echo $purchase_order ? $purchase_order['purchase_order_date'] : date('Y-m-d'); ?>"
                      style="margin-left: 5px;" />
              </div>
          </div>

          <div class="form-section">
              <div class="column">
                  <h4>Vendor Details</h4>
                  <select id="vendor_name" name="vendor_name" required>
                      <?php
                      while ($row = $vendors_result->fetch_assoc()) {
                          $selected = ($row['id'] == $purchase_order['vendor_id']) ? 'selected' : '';
                          echo "<option value='{$row['id']}' {$selected}
                              data-phone='{$row['mobile_no']}'
                              data-address='{$row['address']}'
                              data-city='{$row['city']}'
                              data-gstno='{$row['gstno']}'
                              data-state='{$row['state']}'
                              data-country='{$row['country']}'
                              data-pincode='{$row['pincode']}'>{$row['contact_person']}</option>";
                      }
                      ?>
                  </select>
                  <label>Address:</label>
                  <input type="text" id="vendor_address" name="vendor_address" value="<?php echo htmlspecialchars($vendor['address']); ?>" readonly>
                  <label>Phone:</label>
                  <input type="text" id="vendor_phone" name="vendor_phone" value="<?php echo htmlspecialchars($vendor['mobile_no']); ?>" readonly>
                  <label>City:</label>
                  <input type="text" id="vendor_city" name="vendor_city" value="<?php echo htmlspecialchars($vendor['city']); ?>" readonly>
                  <label>State:</label>
                  <input type="text" id="vendor_state" name="vendor_state" value="<?php echo htmlspecialchars($vendor['state']); ?>" readonly>
                  <label>Country:</label>
                  <input type="text" id="vendor_country" name="vendor_country" value="<?php echo htmlspecialchars($vendor['country']); ?>" readonly>
                  <label>Pincode:</label>
                  <input type="text" id="vendor_pincode" name="vendor_pincode" value="<?php echo htmlspecialchars($vendor['pincode']); ?>" readonly>
                  <label>GST No.:</label>
                  <input type="text" id="vendor_gstno" name="vendor_gstno" value="<?php echo htmlspecialchars($vendor['gstno']); ?>" readonly>
              </div>

              <div class="column">
                  <h4>Shipper Details</h4>
                  <select id="shipper_location_code" name="shipper_location_code" required>
                      <?php
                      while ($row = $shippers_result->fetch_assoc()) {
                          $selected = ($row['id'] == $purchase_order['shipper_id']) ? 'selected' : '';
                          echo "<option value='{$row['id']}' {$selected}
                              data-company='{$row['company_name']}'
                              data-address='{$row['location']}'
                              data-city='{$row['city']}'
                              data-state='{$row['state']}'
                              data-gstno='{$row['gstno']}'
                              data-country='{$row['country']}'
                              data-phone='{$row['contact_no']}'
                              data-pincode='{$row['pincode']}'>{$row['location_code']}</option>";
                      }
                      ?>
                  </select>
                  <label>Company Name:</label>
                  <input type="text" id="shipper_company_name" name="shipper_company_name" value="<?php echo htmlspecialchars($shipper['company_name']); ?>" readonly>
                  <label>Address:</label>
                  <input type="text" id="shipper_address" name="shipper_address" value="<?php echo htmlspecialchars($shipper['location']); ?>" readonly>
                  <label>City:</label>
                  <input type="text" id="shipper_city" name="shipper_city" value="<?php echo htmlspecialchars($shipper['city']); ?>" readonly>
                  <label>State:</label>
                  <input type="text" id="shipper_state" name="shipper_state" value="<?php echo htmlspecialchars($shipper['state']); ?>" readonly>
                  <label>Country:</label>
                  <input type="text" id="shipper_country" name="shipper_country" value="<?php echo htmlspecialchars($shipper['country']); ?>" readonly>
                  <label>Pincode:</label>
                  <input type="text" id="shipper_pincode" name="shipper_pincode" value="<?php echo htmlspecialchars($shipper['pincode']); ?>" readonly>
                  <label>Phone:</label>
                  <input type="text" id="shipper_phone" name="shipper_phone" value="<?php echo htmlspecialchars($shipper['contact_no']); ?>" readonly>
                  <label>GST No.:</label>
                  <input type="text" id="shipper_gstno" name="shipper_gstno" value="<?php echo htmlspecialchars($shipper['gstno']); ?>" readonly>
              </div>
          </div>

          <h3>Product Details</h3>
          <table id="productTable">
              <thead>
                  <tr>
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
                      <th>Action</th>
                  </tr>
              </thead>
              <tbody>
                  <?php foreach ($purchase_order_items as $item): ?>
                      <tr>
                          <td>
                              <select name="product_name[]" onchange="fetchProductDetails(this)" required>
                                  <?php
                                  $product_result = $connection->query("SELECT * FROM item");
                                  while ($row = $product_result->fetch_assoc()) {
                                      $selected = ($row['item_code'] == $item['product_id']) ? 'selected' : '';
                                      echo "<option value='{$row['item_code']}' {$selected}
                                          data-rate='{$row['sales_price']}'
                                          data-unit='{$row['unit_of_measurement_code']}'
                                          data-gst='{$row['gst_code']}'
                                          data-name='{$row['item_name']}'>{$row['item_name']}</option>";
                                  }
                                  ?>
                              </select>
                              <input type="hidden" name="product_name_actual[]" value="<?php echo htmlspecialchars($item['product_name']); ?>">
                          </td>
                          <td><input type="text" name="unit[]" value="<?php echo htmlspecialchars($item['unit']); ?>" readonly></td>
                          <td><input type="number" name="quantity[]" value="<?php echo htmlspecialchars($item['quantity']); ?>" oninput="calculateRow(this)"></td>
                          <td><input type="number" name="rate[]" value="<?php echo htmlspecialchars($item['rate']); ?>" readonly></td>
                          <td>
                              <select class="product-gst" name="product_gst[]" onchange="calculateRow(this)">
                                  <?php
                                  $gst_result = $connection->query("SELECT * FROM gst");
                                  while ($row = $gst_result->fetch_assoc()) {
                                      $selected = ($row['percentage'] == $item['gst']) ? 'selected' : '';
                                      echo "<option value='{$row['percentage']}' {$selected}>{$row['percentage']}%</option>";
                                  }
                                  ?>
                              </select>
                          </td>
                          <td><input type="text" name="igst[]" value="<?php echo htmlspecialchars($item['igst']); ?>" readonly></td>
                          <td><input type="text" name="cgst[]" value="<?php echo htmlspecialchars($item['cgst']); ?>" readonly></td>
                          <td><input type="text" name="sgst[]" value="<?php echo htmlspecialchars($item['sgst']); ?>" readonly></td>
                          <td><input type="text" name="amount[]" value="<?php echo htmlspecialchars($item['amount']); ?>" readonly></td>
                          <td><input type="date" name="receipt_date[]" value="<?php echo htmlspecialchars($item['receipt_date']); ?>" required></td>
                          <td><button type="button" class="remove-button" onclick="removeRow(this)">Remove</button></td>
                      </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
          <button type="button" onclick="addRow()">Add Product</button>


          <h3>Summary</h3>
    <div class="summary-section">
        <div class="summary-row">
            <input type="hidden" name="base_amount" id="baseAmountInput" value="<?php echo $purchase_order['base_amount']; ?>">
            <div class="input-group">
                <label for="baseAmount">Base Value:</label>
                <span id="baseAmount">₹<?php echo number_format($purchase_order['base_amount'], 2); ?></span>
            </div>
        </div>
        <div class="summary-row">
            <input type="hidden" name="total_cgst" id="totalCGSTInput" value="<?php echo $purchase_order['total_cgst']; ?>">
            <div class="input-group">
                <label for="totalCGST">Total CGST:</label>
                <span id="totalCGST">₹<?php echo number_format($purchase_order['total_cgst'], 2); ?></span>
            </div>
        </div>
        <div class="summary-row">
            <input type="hidden" name="total_sgst" id="totalSGSTInput" value="<?php echo $purchase_order['total_sgst']; ?>">
            <div class="input-group">
                <label for="totalSGST">Total SGST:</label>
                <span id="totalSGST">₹<?php echo number_format($purchase_order['total_sgst'], 2); ?></span>
            </div>
        </div>
        <div class="summary-row">
            <input type="hidden" name="total_igst" id="totalIGSTInput" value="<?php echo $purchase_order['total_igst']; ?>">
            <div class="input-group">
                <label for="totalIGST">Total IGST:</label>
                <span id="totalIGST">₹<?php echo number_format($purchase_order['total_igst'], 2); ?></span>
            </div>
        </div>
        <div class="summary-row">
            <input type="hidden" name="gross_amount" id="grossAmountInput" value="<?php echo $purchase_order['gross_amount']; ?>">
            <div class="input-group">
                <label for="grossAmount">Gross Amount:</label>
                <span id="grossAmount">₹<?php echo number_format($purchase_order['gross_amount'], 2); ?></span>
            </div>
        </div>
        <div class="summary-row">
            <div class="input-group">
                <label for="discount">Discount:</label>
                <input type="text" id="discount" name="discount" value="<?php echo $purchase_order['discount']; ?>" oninput="calculateTotal()" />
            </div>
        </div>
        <div class="summary-row">
            <input type="hidden" name="net_amount" id="netAmountInput" value="<?php echo $purchase_order['net_amount']; ?>">
            <div class="input-group">
                <label for="netAmount">Net Amount:</label>
                <span id="netAmount">₹<?php echo number_format($purchase_order['net_amount'], 2); ?></span>
            </div>
        </div>
    </div>

    <button class="submit" type="submit">Update Purchase Order</button>
    </form>
    </div>

    <script>
    function addRow() {
  const table = document.getElementById("productTable").getElementsByTagName("tbody")[0];
  const newRow = table.insertRow(table.rows.length);
  newRow.innerHTML = `
      <td>
          <select name="product_name[]" onchange="fetchProductDetails(this)" required>
              <option value="" disabled selected>Select Product</option>
              <?php
              $product_result = $connection->query("SELECT * FROM item");
              while ($row = $product_result->fetch_assoc()) {
                  echo "<option value='{$row['item_code']}'
                      data-rate='{$row['sales_price']}'
                      data-unit='{$row['unit_of_measurement_code']}'
                      data-gst='{$row['gst_code']}'
                      data-name='{$row['item_name']}'>{$row['item_name']}</option>";
              }
              ?>
          </select>
          <input type="hidden" name="product_name_actual[]">
      </td>
      <td><input type="text" name="unit[]" readonly></td>
      <td><input type="number" name="quantity[]" oninput="calculateRow(this)"></td>
      <td><input type="number" name="rate[]" readonly></td>
      <td>
          <select class="product-gst" name="product_gst[]" onchange="calculateRow(this)">
              <option value="" disabled selected>Select GST %</option>
              <?php
              $gst_result = $connection->query("SELECT * FROM gst");
              while ($row = $gst_result->fetch_assoc()) {
                  echo "<option value='{$row['percentage']}'>{$row['percentage']}%</option>";
              }
              ?>
          </select>
      </td>
      <td><input type="text" name="igst[]" readonly></td>
      <td><input type="text" name="cgst[]" readonly></td>
      <td><input type="text" name="sgst[]" readonly></td>
      <td><input type="text" name="amount[]" readonly></td>
      <td><input type="date" name="receipt_date[]" required></td>
      <td><button class="remove-button" type="button" onclick="removeRow(this)">Remove</button></td>
  `;
}

        function removeRow(button) {
            const row = button.parentNode.parentNode;
            row.parentNode.removeChild(row);
            calculateTotal();
        }

        function fetchProductDetails(select) {
            const selectedOption = select.options[select.selectedIndex];
            const row = select.closest("tr");
            row.querySelector('input[name="unit[]"]').value = selectedOption.dataset.unit;
            row.querySelector('input[name="rate[]"]').value = selectedOption.dataset.rate;
            row.querySelector('select[name="product_gst[]"]').value = selectedOption.dataset.gst;
            row.querySelector('input[name="product_name_actual[]"]').value = selectedOption.dataset.name;
            calculateRow(row.querySelector('input[name="quantity[]"]'));
        }

        function calculateRow(input) {
            const row = input.closest("tr");
            const quantity = Number.parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
            const rate = Number.parseFloat(row.querySelector('input[name="rate[]"]').value) || 0;
            const gstPercentage = Number.parseFloat(row.querySelector('select[name="product_gst[]"]').value) || 0;

            const amount = quantity * rate;
            const gstAmount = (amount * gstPercentage) / 100;

            const vendorState = document.getElementById("vendor_state").value;
            const shipperState = document.getElementById("shipper_state").value;

            let igst = 0, cgst = 0, sgst = 0;
            if (vendorState !== shipperState) {
                igst = gstAmount;
            } else {
                cgst = gstAmount / 2;
                sgst = gstAmount / 2;
            }

            row.querySelector('input[name="igst[]"]').value = igst.toFixed(2);
            row.querySelector('input[name="cgst[]"]').value = cgst.toFixed(2);
            row.querySelector('input[name="sgst[]"]').value = sgst.toFixed(2);
            row.querySelector('input[name="amount[]"]').value = (amount + gstAmount).toFixed(2);

            calculateTotal();
        }

        function calculateTotal() {
          const rows = document.querySelectorAll("#productTable tbody tr")
          let baseAmount = 0,
            totalCGST = 0,
            totalSGST = 0,
            totalIGST = 0,
            grossAmount = 0

          rows.forEach((row) => {
            baseAmount +=
              Number.parseFloat(row.querySelector('input[name="rate[]"]').value) *
                Number.parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0
            totalCGST += Number.parseFloat(row.querySelector('input[name="cgst[]"]').value) || 0
            totalSGST += Number.parseFloat(row.querySelector('input[name="sgst[]"]').value) || 0
            totalIGST += Number.parseFloat(row.querySelector('input[name="igst[]"]').value) || 0
            grossAmount += Number.parseFloat(row.querySelector('input[name="amount[]"]').value) || 0
          })

          const discount = Number.parseFloat(document.getElementById("discount").value) || 0
          const netAmount = grossAmount - discount

          document.getElementById("baseAmount").textContent = "₹" + baseAmount.toFixed(2)
          document.getElementById("baseAmountInput").value = baseAmount.toFixed(2)
          document.getElementById("totalCGST").textContent = "₹" + totalCGST.toFixed(2)
          document.getElementById("totalCGSTInput").value = totalCGST.toFixed(2)
          document.getElementById("totalSGST").textContent = "₹" + totalSGST.toFixed(2)
          document.getElementById("totalSGSTInput").value = totalSGST.toFixed(2)
          document.getElementById("totalIGST").textContent = "₹" + totalIGST.toFixed(2)
          document.getElementById("totalIGSTInput").value = totalIGST.toFixed(2)
          document.getElementById("grossAmount").textContent = "₹" + grossAmount.toFixed(2)
          document.getElementById("grossAmountInput").value = grossAmount.toFixed(2)
          document.getElementById("netAmount").textContent = "₹" + netAmount.toFixed(2)
          document.getElementById("netAmountInput").value = netAmount.toFixed(2)
        }

        function updateVendorDetails() {
    const selectedOption =
      document.getElementById("vendor_name").options[document.getElementById("vendor_name").selectedIndex];
    document.getElementById("vendor_id").value = selectedOption.value;
    document.getElementById("vendor_address").value = selectedOption.getAttribute("data-address") || "";
    document.getElementById("vendor_phone").value = selectedOption.getAttribute("data-phone") || "";
    document.getElementById("vendor_city").value = selectedOption.getAttribute("data-city") || "";
    document.getElementById("vendor_state").value = selectedOption.getAttribute("data-state") || "";
    document.getElementById("vendor_country").value = selectedOption.getAttribute("data-country") || "";
    document.getElementById("vendor_pincode").value = selectedOption.getAttribute("data-pincode") || "";
    document.getElementById("vendor_gstno").value = selectedOption.getAttribute("data-gstno") || "";
    calculateTotal();
  }

  function updateShipperDetails() {
    const selectedOption =
      document.getElementById("shipper_location_code").options[
        document.getElementById("shipper_location_code").selectedIndex
      ];
    document.getElementById("shipper_id").value = selectedOption.value;
    document.getElementById("shipper_company_name").value = selectedOption.getAttribute("data-company") || "";
    document.getElementById("shipper_address").value = selectedOption.getAttribute("data-address") || "";
    document.getElementById("shipper_city").value = selectedOption.getAttribute("data-city") || "";
    document.getElementById("shipper_state").value = selectedOption.getAttribute("data-state") || "";
    document.getElementById("shipper_country").value = selectedOption.getAttribute("data-country") || "";
    document.getElementById("shipper_pincode").value = selectedOption.getAttribute("data-pincode") || "";
    document.getElementById("shipper_phone").value = selectedOption.getAttribute("data-phone") || "";
    document.getElementById("shipper_gstno").value = selectedOption.getAttribute("data-gstno") || "";
    calculateTotal();
  }

  document.addEventListener("DOMContentLoaded", () => {
    updateVendorDetails();
    updateShipperDetails();
    calculateTotal();

    document.getElementById("vendor_name").addEventListener("change", updateVendorDetails);
    document.getElementById("shipper_location_code").addEventListener("change", updateShipperDetails);
  });


        </script>
    </body>
    </html>
