<?php
// Include the connection file
include 'connection.php'; // Ensure this file contains the $connection variable

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
    $invoice_date = $_POST['invoice_date'];
    $vendor_name = $_POST['vendor_name'];
    $shipper_location_code = $_POST['shipper_location_code'];
    $gross_amount = $_POST['gross_amount'];
    $discount = $_POST['discount'];
    $net_amount = $_POST['net_amount'];
    $base_amount = $_POST['base_amount'];
    $total_igst = $_POST['total_igst'];
    $total_cgst = $_POST['total_cgst'];
    $total_sgst = $_POST['total_sgst'];

    // Fetch vendor_id from contact_vendor table
    $vendor_result = $connection->query("SELECT id FROM contact_vendor WHERE contact_person = '$vendor_name'");
    $vendor_row = $vendor_result->fetch_assoc();
    $vendor_id = $vendor_row['id'];

    // Fetch shipper_id
    $shipper_result = $connection->query("SELECT id FROM location_card WHERE location_code = '$shipper_location_code'");
    $shipper_row = $shipper_result->fetch_assoc();
    $shipper_id = $shipper_row['id'];

    // Update purchase order in the database
    $update_purchase_order = "UPDATE purchase_order SET
        vendor_id = '$vendor_id',
        shipper_id = '$shipper_id',
        vendor_name = '$vendor_name',
        shipper_location_code = '$shipper_location_code',
        gross_amount = '$gross_amount',
        discount = '$discount',
        net_amount = '$net_amount',
        purchase_order_date = '$invoice_date',
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
        shipper_gstno = '{$_POST['shipper_gstno']}'
        WHERE id = $purchase_order_id";

    if ($connection->query($update_purchase_order)) {
        // Delete existing items for this purchase order
        $delete_items = "DELETE FROM purchase_order_items WHERE purchase_order_id = $purchase_order_id";

        // Check if delete operation was successful
        if (!$connection->query($delete_items)) {
            echo "<p>Error deleting existing items: " . $connection->error . "</p>";
            exit; // Stop execution if delete fails
        }

        // Insert updated items
        $products = $_POST['product_name'];
        $product_names = $_POST['product_name_actual'];
        $quantities = $_POST['quantity'];
        $rates = $_POST['rate'];
        $gsts = $_POST['product_gst'];
        $amounts = $_POST['amount'];
        $units = $_POST['unit'];
        $unit_values = $_POST['unit_value'];
        $igsts = $_POST['igst'];
        $cgsts = $_POST['cgst'];
        $sgsts = $_POST['sgst'];
        $lot_tracking_ids = $_POST['lot_tracking_id'];
        $expiration_dates = $_POST['expiring_date'];
        $receipt_dates = $_POST['receipt_date'];

        for ($i = 0; $i < count($products); $i++) {
            $product_code = $products[$i];
            $product_name = $product_names[$i];
            $quantity = $quantities[$i];
            $rate = $rates[$i];
            $gst = $gsts[$i];
            $amount = $amounts[$i];
            $unit = $units[$i];
            $unit_value = $unit_values[$i];
            $igst = $igsts[$i];
            $cgst = $cgsts[$i];
            $sgst = $sgsts[$i];
            $lot_tracking_id = $lot_tracking_ids[$i];
            $expiration_date = $expiration_dates[$i];
            $receipt_date = $receipt_dates[$i];

            // Insert into purchase_order_items
            $insert_item = "INSERT INTO purchase_order_items (
                purchase_order_id, product_name, product_id, quantity, rate, gst, amount, unit, value, igst, cgst, sgst,
                lot_trackingid, expiration_date, receipt_date
            ) VALUES (
                '$purchase_order_id', '$product_name', '$product_code', '$quantity', '$rate', '$gst', '$amount', '$unit',
                '$unit_value', '$igst', '$cgst', '$sgst', '$lot_tracking_id', '$expiration_date', '$receipt_date'
            )";

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
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Purchase Order</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #2c3e50;
            margin: 0;
            padding: 0;

        }
        .container {
            width: 95%;

            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow-x: scroll;
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

    /* Ensure input fields fill the cell and align correctly */
    input[type="text"],
    input[type="number"],
    select {
        width: 100%; /* Make inputs take the full width */
        height: 40px; /* Set a consistent height */
        padding: 5px; /* Padding for better usability */
        border: 1px solid #ccc; /* Light border */
        border-radius: 4px; /* Rounded corners */
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1); /* Subtle inset shadow */
        transition: border-color 0.3s ease; /* Transition for border color */
        box-sizing: border-box; /* Ensure padding and border are included in the total width */
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    select:focus {
        border-color: #3498db; /* Change border color on focus */
    }

    tr:hover {
        background-color: #f5f5f5; /* Highlight row on hover */
    }

    .remove-button {
        padding: 5px 10px;
        background-color: #e74c3c; /* Red background */
        color: white; /* White text */
        border: none; /* Remove border */
        border-radius: 4px; /* Rounded corners */
        cursor: pointer; /* Pointer cursor */

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
          max-width: 400px; /* Adjust the width of the section */
          margin-left: auto; /* Align the section to the right */
          margin-right: 0; /* Ensure it stays on the right side */
          text-align: right; /* Align text within the section to the right */
      }

      .summary-row {
          display: flex;
          justify-content: space-between; /* Keep labels and values horizontally aligned */
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
          width: 60%; /* Adjust label width */
          text-align: right; /* Labels remain left-aligned within the section */
      }

      .input-group span {
          font-size: 14px;
          font-weight: normal;
          text-align: right;
          width: 40%; /* Adjust value width */
          display: inline-block;
          padding: 0px 0;
          border-bottom: 1px solid #ccc; /* Add underline effect */
          margin-top: 15px;
      }
      .input-group input {
          font-size: 14px; /* Match input font size with label */
          font-weight: normal; /* Regular weight for input text */
          text-align: right; /* Align input text to the right */
          border: none; /* Remove default border */
          border-bottom: 1px solid #ccc; /* Add underline effect */
          width: 25%; /* Reduce width for a smaller input */
          max-width: 60px; /* Set a maximum width for the input field */
          margin-left: 10px; /* Add some space between label and input */
          padding: 3px; /* Add some vertical padding */
          box-shadow: none; /* Remove shadow for a flat look */
      }

      /* General styles for inputs and selects */

      .table-container {
          width: 100%;
          overflow-x: auto; /* Enable horizontal scrolling */
          margin-bottom: 20px;
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          border-radius: 8px;
          white-space: nowrap; /* Prevent table from wrapping */

      }

      #invoiceTable {
          width: max-content; /* Adjusts to content width */
          min-width: 2400px; /* Ensure a large minimum width */
          border-collapse: collapse;
          background-color: white;
      }

      #invoiceTable th,
      #invoiceTable td {
          padding: 10px 5px;
          text-align: center;
          border-bottom: 1px solid #e0e0e0;
          white-space: nowrap; /* Prevent text wrapping */
      }


#invoiceTable th {
    background-color: #2c3e50;
    color: white;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 14px;
    position: sticky;
    top: 0;
    z-index: 10;

}

#invoiceTable tr:nth-child(even) {
    background-color: #f8f9fa;
}

#invoiceTable tr:hover {
    background-color: #e9ecef;
}

#invoiceTable input[type="text"],
#invoiceTable input[type="number"],
#invoiceTable input[type="date"],
#invoiceTable select {
    width: 100%;
    padding: 11px 1px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    justify-content: center;

}

#invoiceTable input[type="text"]:focus,
#invoiceTable input[type="number"]:focus,
#invoiceTable input[type="date"]:focus,
#invoiceTable select:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.small-input {
    width: 100px; /* Set a fixed width */
    max-width: 100px; /* Prevent exceeding the specified width */
    padding: 5px; /* Adjust padding for better usability */

}

.medium-input {
    width: 120px; /* Set a fixed width */
    max-width: 120px; /* Prevent exceeding the specified width */
    padding: 5px; /* Adjust padding for better usability */

}

.large-input {
    width: 150px; /* Set a fixed width */
    max-width: 150px; /* Prevent exceeding the specified width */
    padding: 5px; /* Adjust padding for better usability */

}



.remove-button {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
}

.remove-button:hover {
    background-color: #c82333;
}

/* Adjust column widths */



/* ... (keep the rest of your styles) ... */


.unit-field dropdown {
  cursor: pointer;
}
    </style>
</head>


<body>

  <div class="container">
      <a style="text-decoration: none; margin-left: 1347px; padding: 0px; position: relative; top: -34px; transform: translateY(-50%);" href="purchase_order_display.php" class="close-btn">&times;</a>

      <form id="invoiceForm" method="POST" action="">
          <input type="hidden" name="purchase_order_id" value="<?php echo isset($purchase_order) ? $purchase_order['id'] : ''; ?>">

          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: -25px; width: 100%;">
              <div style="flex-grow: 1; text-align: center;">
                  <h1 style="margin-left: 150px;">Edit Purchase Order</h1>
              </div>
              <div style="margin-right: 10px;">
                  <label for="invoice_date"><strong>Date:</strong></label>
                  <input type="date" id="invoice_date" name="invoice_date" value="<?php echo isset($purchase_order) ? $purchase_order['purchase_order_date'] : date('Y-m-d'); ?>" style="margin-left: 5px;" />
              </div>
          </div>

          <h3>Vendor & Shipper Details</h3>

          <div class="form-section">
              <div class="column">
                  <h4>Vendor Details</h4>
                  <label>Name:</label>
                  <select id="vendor_name" name="vendor_name" required>
                      <option value="" disabled>Select Vendor</option>
                      <?php
                      $vendors_result->data_seek(0); // Reset result pointer
                      while ($row = $vendors_result->fetch_assoc()) {
                          $selected = isset($purchase_order) && $row['contact_person'] == $purchase_order['vendor_name'] ? 'selected' : '';
                          echo "<option value='{$row['contact_person']}'
                              data-phone='{$row['mobile_no']}'
                              data-address='{$row['address']}'
                              data-city='{$row['city']}'
                              data-gstno='{$row['gstno']}'
                              data-state='{$row['state']}'
                              data-country='{$row['country']}'
                              data-pincode='{$row['pincode']}' $selected>{$row['contact_person']}</option>";
                      }
                      ?>
                  </select>
                  <label>Address:</label>
                  <input type="text" id="vendor_address" name="vendor_address" value="<?php echo isset($purchase_order) ? $purchase_order['vendor_address'] : ''; ?>" readonly>
                  <label>Phone:</label>
                  <input type="text" id="vendor_phone" name="vendor_phone" value="<?php echo isset($purchase_order) ? $purchase_order['vendor_phone'] : ''; ?>" readonly>
                  <label>City:</label>
                  <input type="text" id="vendor_city" name="vendor_city" value="<?php echo isset($purchase_order) ? $purchase_order['vendor_city'] : ''; ?>" readonly>
                  <label>State:</label>
                  <input type="text" id="vendor_state" name="vendor_state" value="<?php echo isset($purchase_order) ? $purchase_order['vendor_state'] : ''; ?>" readonly>
                  <label>Country:</label>
                  <input type="text" id="vendor_country" name="vendor_country" value="<?php echo isset($purchase_order) ? $purchase_order['vendor_country'] : ''; ?>" readonly>
                  <label>Pincode:</label>
                  <input type="text" id="vendor_pincode" name="vendor_pincode" value="<?php echo isset($purchase_order) ? $purchase_order['vendor_pincode'] : ''; ?>" readonly>
                  <label>GST No.:</label>
                  <input type="text" id="vendor_gstno" name="vendor_gstno" value="<?php echo isset($purchase_order) ? $purchase_order['vendor_gstno'] : ''; ?>" readonly>
              </div>

              <div class="column">
                  <h4>Shipper Details</h4>
                  <label>Location Code:</label>
                  <select id="shipper_location_code" name="shipper_location_code" required>
                      <option value="" disabled>Select Location Code</option>
                      <?php
                      $shippers_result->data_seek(0); // Reset result pointer
                      while ($row = $shippers_result->fetch_assoc()) {
                          $selected = isset($purchase_order) && $row['location_code'] == $purchase_order['shipper_location_code'] ? 'selected' : '';
                          echo "<option value='{$row['location_code']}'
                              data-company='{$row['company_name']}'
                              data-address='{$row['location']}'
                              data-city='{$row['city']}'
                              data-state='{$row['state']}'
                              data-gstno='{$row['gstno']}'
                              data-country='{$row['country']}'
                              data-phone='{$row['contact_no']}'
                              data-pincode='{$row['pincode']}' $selected>{$row['location_code']}</option>";
                      }
                      ?>
                  </select>
                  <label>Company Name:</label>
                  <input type="text" id="shipper_company_name" name="shipper_company_name" value="<?php echo isset($purchase_order) ? $purchase_order['shipper_company_name'] : ''; ?>" readonly>
                  <label>Address:</label>
                  <input type="text" id="shipper_address" name="shipper_address" value="<?php echo isset($purchase_order) ? $purchase_order['shipper_address'] : ''; ?>" readonly>
                  <label>City:</label>
                  <input type="text" id="shipper_city" name="shipper_city" value="<?php echo isset($purchase_order) ? $purchase_order['shipper_city'] : ''; ?>" readonly>
                  <label>State:</label>
                  <input type="text" id="shipper_state" name="shipper_state" value="<?php echo isset($purchase_order) ? $purchase_order['shipper_state'] : ''; ?>" readonly>
                  <label>Country:</label>
                  <input type="text" id="shipper_country" name="shipper_country" value="<?php echo isset($purchase_order) ? $purchase_order['shipper_country'] : ''; ?>" readonly>
                  <label>Pincode:</label>
                  <input type="text" id="shipper_pincode" name="shipper_pincode" value="<?php echo isset($purchase_order) ? $purchase_order['shipper_pincode'] : ''; ?>" readonly>
                  <label>Phone:</label>
                  <input type="text" id="shipper_phone" name="shipper_phone" value="<?php echo isset($purchase_order) ? $purchase_order['shipper_phone'] : ''; ?>" readonly>
                  <label>GST No.:</label>
                  <input type="text" id="shipper_gstno" name="shipper_gstno" value="<?php echo isset($purchase_order) ? $purchase_order['shipper_gstno'] : ''; ?>" readonly>
              </div>
          </div>

          <h3>Product Details</h3>
          <div class="table-container">
              <table id="invoiceTable" border="1">
                  <thead>
                      <tr>
                          <th>Product</th>
                          <th>Unit</th>
                          <th>Qty</th>
                          <th>Rate</th>
                          <th>GST (%)</th>
                          <th>IGST</th>
                          <th>CGST</th>
                          <th>SGST</th>
                          <th>Amount</th>
                          <th>Lot Tracking Id</th>
                          <th>Expiration Date</th>
                          <th>Receipt Date</th>
                          <th>Action</th>
                      </tr>
                  </thead>
                  <tbody>
                    <?php if (isset($purchase_order_items) && count($purchase_order_items) > 0): ?>
                        <?php foreach ($purchase_order_items as $item): ?>
                            <tr>
                                <td>
                                    <select name="product_name[]" onchange="fetchProductDetails(this)" class="large-input" required>
                                        <option value="" disabled>Select Product</option>
                                        <?php
                                        $product_result = $connection->query("SELECT * FROM item");
                                        while ($row = $product_result->fetch_assoc()) {
                                            $selected = ($row['item_code'] == $item['product_id']) ? 'selected' : '';
                                            echo "<option value='" . $row['item_code'] . "'
                                                data-rate='" . $row['sales_price'] . "'
                                                data-unit='" . $row['unit_of_measurement_code'] . "'
                                                data-gst='" . $row['gst_code'] . "'
                                                data-name='" . $row['item_name'] . "'
                                                data-lot-tracking='" . $row['lot_tracking'] . "'
                                                data-expiration-tracking='" . $row['expiration_tracking'] . "' $selected>" . $row['item_name'] . "</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="unit[]" value="<?php echo $item['unit']; ?>" readonly required onclick="loadUnitOptions(this)" placeholder="Click to select" class="small-input">
                                    <div class="unit-dropdown" style="display:none; position:absolute; background:#fff; border:1px solid #ccc; z-index:1000;">
                                        <ul></ul>
                                    </div>
                                </td>
                                <td><input type="number" name="quantity[]" value="<?php echo $item['quantity']; ?>" step="any" oninput="calculateRow(this)" placeholder="Quantity" class="small-input" required></td>
                                <td><input type="number" name="rate[]" value="<?php echo $item['rate']; ?>" placeholder="Rate" class="small-input"></td>
                                <td>
                                    <select class="product-gst" name="product_gst[]" onchange="calculateRow(this)" required>
                                        <option value="" disabled>Select GST %</option>
                                        <?php
                                        $gst_result = $connection->query("SELECT * FROM gst");
                                        while ($row = $gst_result->fetch_assoc()) {
                                            $selected = ($row['percentage'] == $item['gst']) ? 'selected' : '';
                                            echo "<option value='" . $row['percentage'] . "' $selected>" . $row['percentage'] . "%</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <input type="hidden" name="product_name_actual[]" value="<?php echo $item['product_name']; ?>">
                                <input type="hidden" name="unit_value[]" value="<?php echo $item['value'] ?? 1; ?>" id="unitValueField">
                                <td><input type="text" name="igst[]" value="<?php echo $item['igst']; ?>" placeholder="IGST" class="small-input" readonly></td>
                                <td><input type="text" name="cgst[]" value="<?php echo $item['cgst']; ?>" placeholder="CGST" class="small-input" readonly></td>
                                <td><input type="text" name="sgst[]" value="<?php echo $item['sgst']; ?>" placeholder="SGST" class="small-input" readonly></td>
                                <td><input type="text" name="amount[]" value="<?php echo $item['amount']; ?>" placeholder="Amount" class="small-input" readonly></td>
                                <td><input type="text" name="lot_tracking_id[]" value="<?php echo $item['lot_trackingid']; ?>" placeholder="Enter Lot ID" class="small-input"></td>
                                <td><input type="date" name="expiring_date[]" value="<?php echo $item['expiration_date']; ?>" placeholder="Expiry Date" class="small-input"></td>
                                <td><input type="date" name="receipt_date[]" value="<?php echo $item['receipt_date']; ?>" placeholder="Receipt Date" class="small-input" required></td>
                                <td><button type="button" onclick="removeRow(this)" class="remove-button">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
              </table>
          </div>

          <button type="button" onclick="addRow()">+ Add Product</button>

          <h2>Summary</h2>

          <div class="summary-section">
              <div class="summary-row">
                  <input type="hidden" name="base_amount" id="baseAmountInput" value="<?php echo isset($purchase_order) ? $purchase_order['base_amount'] : '0.00'; ?>">
                  <div class="input-group">
                      <label for="baseAmount">Base Value:</label>
                      <span id="baseAmount">₹<?php echo isset($purchase_order) ? $purchase_order['base_amount'] : '0.00'; ?></span>
                  </div>
              </div>
              <div class="summary-row">
                  <input type="hidden" name="total_cgst" id="totalCGSTInput" value="<?php echo isset($purchase_order) ? $purchase_order['total_cgst'] : '0.00'; ?>">
                  <div class="input-group">
                      <label for="totalCGST">Total CGST:</label>
                      <span id="totalCGST">₹<?php echo isset($purchase_order) ? $purchase_order['total_cgst'] : '0.00'; ?></span>
                  </div>
              </div>
              <div class="summary-row">
                  <input type="hidden" name="total_sgst" id="totalSGSTInput" value="<?php echo isset($purchase_order) ? $purchase_order['total_sgst'] : '0.00'; ?>">
                  <div class="input-group">
                      <label for="totalSGST">Total SGST:</label>
                      <span id="totalSGST">₹<?php echo isset($purchase_order) ? $purchase_order['total_sgst'] : '0.00'; ?></span>
                  </div>
              </div>
              <div class="summary-row">
                  <input type="hidden" name="total_igst" id="totalIGSTInput" value="<?php echo isset($purchase_order) ? $purchase_order['total_igst'] : '0.00'; ?>">
                  <div class="input-group">
                      <label for="totalIGST">Total IGST:</label>
                      <span id="totalIGST">₹<?php echo isset($purchase_order) ? $purchase_order['total_igst'] : '0.00'; ?></span>
                  </div>
              </div>
              <div class="summary-row">
                  <input type="hidden" name="gross_amount" id="grossAmountInput" value="<?php echo isset($purchase_order) ? $purchase_order['gross_amount'] : '0.00'; ?>">
                  <div class="input-group">
                      <label for="grossAmount">Gross Amount:</label>
                      <span id="grossAmount">₹<?php echo isset($purchase_order) ? $purchase_order['gross_amount'] : '0.00'; ?></span>
                  </div>
              </div>
              <div class="summary-row">
                  <div class="input-group">
                      <label for="discount">Discount:</label>
                      <input type="text" id="discount" name="discount" value="<?php echo isset($purchase_order) ? $purchase_order['discount'] : '0.00'; ?>" oninput="calculateTotal()" />
                  </div>
              </div>
              <div class="summary-row">
                  <input type="hidden" name="net_amount" id="netAmountInput" value="<?php echo isset($purchase_order) ? $purchase_order['net_amount'] : '0.00'; ?>">
                  <div class="input-group ">
                      <label for="netAmount">Net Amount:</label>
                      <span style="font-weight:bold;" id="netAmount">₹<?php echo isset($purchase_order) ? $purchase_order['net_amount'] : '0.00'; ?></span>
                  </div>
              </div>
          </div>

          <button type="submit">Update Purchase Order</button>
          <div id="message">
      </form>
  </div>

  <script>
      document.addEventListener('DOMContentLoaded', function() {
          // Initialize calculations when page loads
          calculateTotal();

          const form = document.getElementById('invoiceForm');

          form.addEventListener('submit', function(event) {
              event.preventDefault();

              let isValid = true;
              const rows = document.querySelectorAll('#invoiceTable tbody tr');

              rows.forEach(row => {
                  const productSelect = row.querySelector('select[name="product_name[]"]');
                  const productCode = productSelect.value;

                  // Fetch product details
                  const productOption = productSelect.querySelector(`option[value="${productCode}"]`);
                  const lotTracking = productOption.getAttribute('data-lot-tracking') === '1';
                  const expirationTracking = productOption.getAttribute('data-expiration-tracking') === '1';

                  const lotInput = row.querySelector('input[name="lot_tracking_id[]"]');
                  const expirationInput = row.querySelector('input[name="expiring_date[]"]');
                  const receiptInput = row.querySelector('input[name="receipt_date[]"]'); // New receipt date field

                  // Check and clear lot tracking
                  if (!lotTracking && lotInput.value.trim()) {
                      alert(`Lot Tracking is not required for ${productOption.textContent}`);
                      lotInput.value = '';
                  } else if (lotTracking && !lotInput.value.trim()) {
                      alert(`Please enter Lot Tracking ID for ${productOption.textContent}`);
                      isValid = false;
                  }

                  // Check and clear expiration tracking
                  if (!expirationTracking && expirationInput.value) {
                      alert(`Expiration Date is not required for ${productOption.textContent}`);
                      expirationInput.value = '';
                  } else if (expirationTracking && !expirationInput.value) {
                      alert(`Please enter Expiration Date for ${productOption.textContent}`);
                      isValid = false;
                  }

                  // Check and clear receipt date (if required)
                  if (!receiptInput.value) {
                      alert(`Please enter Receipt Date for ${productOption.textContent}`);
                      isValid = false;
                  }
              });

              if (isValid) {
                  form.submit();
              }
          });

          // If there are no items, add an empty row
          if (document.querySelectorAll('#invoiceTable tbody tr').length === 0) {
              addRow();
          }
      });

      document.getElementById("vendor_name").addEventListener("change", function () {
          let selectedOption = this.options[this.selectedIndex];
          document.getElementById("vendor_address").value = selectedOption.getAttribute("data-address");
          document.getElementById("vendor_phone").value = selectedOption.getAttribute("data-phone");
          document.getElementById("vendor_city").value = selectedOption.getAttribute("data-city");
          document.getElementById("vendor_state").value = selectedOption.getAttribute("data-state");
          document.getElementById("vendor_country").value = selectedOption.getAttribute("data-country");
          document.getElementById("vendor_pincode").value = selectedOption.getAttribute("data-pincode");
          document.getElementById("vendor_gstno").value = selectedOption.getAttribute("data-gstno");
      });

      document.getElementById("shipper_location_code").addEventListener("change", function () {
          let selectedOption = this.options[this.selectedIndex];
          document.getElementById("shipper_company_name").value = selectedOption.getAttribute("data-company");
          document.getElementById("shipper_address").value = selectedOption.getAttribute("data-address");
          document.getElementById("shipper_city").value = selectedOption.getAttribute("data-city");
          document.getElementById("shipper_state").value = selectedOption.getAttribute("data-state");
          document.getElementById("shipper_country").value = selectedOption.getAttribute("data-country");
          document.getElementById("shipper_phone").value = selectedOption.getAttribute("data-phone");
          document.getElementById("shipper_pincode").value = selectedOption.getAttribute("data-pincode");
          document.getElementById("shipper_gstno").value = selectedOption.getAttribute("data-gstno");
      });

      function addRow() {
          let table = document.getElementById("invoiceTable").getElementsByTagName("tbody")[0];
          let row = table.insertRow();

          row.innerHTML = `
              <td>
                  <select name="product_name[]" onchange="fetchProductDetails(this)" class="large-input" required>
                      <option value="" disabled selected>Select Product</option>
                      <?php
                      $product_result = $connection->query("SELECT * FROM item");
                      while ($row = $product_result->fetch_assoc()) {
                          echo "<option value='" . $row['item_code'] . "'
                              data-rate='" . $row['sales_price'] . "'
                              data-unit='" . $row['unit_of_measurement_code'] . "'
                              data-gst='" . $row['gst_code'] . "'
                              data-name='" . $row['item_name'] . "'
                              data-lot-tracking='" . $row['lot_tracking'] . "'
                              data-expiration-tracking='" . $row['expiration_tracking'] . "'>" . $row['item_name'] . "</option>";
                      }
                      ?>
                  </select>
              </td>

              <td>
                  <input type="text" name="unit[]" readonly required onclick="loadUnitOptions(this)" placeholder="Click to select" class="small-input">
                  <div class="unit-dropdown" style="display:none; position:absolute; background:#fff; border:1px solid #ccc; z-index:1000;">
                      <ul></ul>
                  </div>
              </td>

              <td><input type="number" name="quantity[]" step="any" oninput="calculateRow(this)" placeholder="Quantity" class="small-input" required></td>
              <td><input type="number" name="rate[]" placeholder="Rate" class="small-input"></td>
              <td>
                  <select class="product-gst" name="product_gst[]" onchange="calculateRow(this)" required>
                      <option value="" disabled selected>Select GST %</option>
                      <?php
                      $gst_result = $connection->query("SELECT * FROM gst");
                      while ($row = $gst_result->fetch_assoc()) {
                          echo "<option value='" . $row['percentage'] . "'>" . $row['percentage'] . "%</option>";
                      }
                      ?>
                  </select>
              </td>
              <input type="hidden" name="product_name_actual[]">
              <input type="hidden" name="unit_value[]" value="1" id="unitValueField">
              <td><input type="text" name="igst[]" placeholder="IGST" class="small-input" readonly></td>
              <td><input type="text" name="cgst[]" placeholder="CGST" class="small-input" readonly></td>
              <td><input type="text" name="sgst[]" placeholder="SGST" class="small-input" readonly></td>
              <td><input type="text" name="amount[]" placeholder="Amount" class="small-input" readonly></td>
              <td><input type="text" name="lot_tracking_id[]" placeholder="Enter Lot ID" class="small-input"></td>
              <td><input type="date" name="expiring_date[]" placeholder="Expiry Date" class="small-input"></td>
              <td><input type="date" name="receipt_date[]" placeholder="Receipt Date" class="small-input" required></td>
              <td><button type="button" onclick="removeRow(this)" class="remove-button">Remove</button></td>
          `;

          styleRowInputs(row);
      }

      function loadUnitOptions(inputField) {
          const unitDropdown = inputField.nextElementSibling;
          const productSelect = inputField.closest('tr').querySelector('select[name="product_name[]"]');
          const selectedOption = productSelect.options[productSelect.selectedIndex];
          const itemCode = selectedOption.value;

          const ul = unitDropdown.querySelector('ul');
          ul.innerHTML = '';

          fetchUnits(itemCode).then(units => {
              units.forEach(unitObj => {
                  const li = document.createElement('li');
                  li.textContent = `${unitObj.unit_name} - ${unitObj.value}`;
                  li.onclick = function() {
                      inputField.value = unitObj.unit_name;
                      inputField.dataset.unitValue = unitObj.value;
                      unitDropdown.style.display = 'none';
                      const hiddenInput = inputField.closest('tr').querySelector('input[name="unit_value[]"]');
                      if (hiddenInput) {
                          hiddenInput.value = unitObj.value;
                      }
                  };
                  ul.appendChild(li);
              });

              unitDropdown.style.display = 'block';
          });
      }

      function fetchUnits(itemCode) {
          return new Promise((resolve, reject) => {
              const xhr = new XMLHttpRequest();
              xhr.open('POST', 'fetch_units.php', true);
              xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
              xhr.onreadystatechange = function() {
                  if (xhr.readyState === 4) {
                      if (xhr.status === 200) {
                          try {
                              resolve(JSON.parse(xhr.responseText));
                          } catch (error) {
                              reject("Invalid JSON response");
                          }
                      } else {
                          reject("Failed to fetch units");
                      }
                  }
              };
              xhr.send('item_code=' + encodeURIComponent(itemCode));
          });
      }

      function styleRowInputs(row) {
          const inputs = row.querySelectorAll('input[type="text"], input[type="number"], select');
          inputs.forEach(input => {
              input.style.width = "100%";
              input.style.padding = "10px";
              input.style.border = "1px solid #ccc";
              input.style.borderRadius = "4px";
              input.style.boxShadow = "inset 0 1px 3px rgba(0, 0, 0, 0.1)";
              input.style.transition = "border-color 0.3s ease";
          });

          const removeButton = row.querySelector('.remove-button');
          removeButton.style.padding = "5px 10px";
          removeButton.style.backgroundColor = "#e74c3c";
          removeButton.style.color = "white";
          removeButton.style.border = "none";
          removeButton.style.borderRadius = "4px";
          removeButton.style.cursor = "pointer";
      }

      function fetchProductDetails(selectElement) {
          let selectedOption = selectElement.options[selectElement.selectedIndex];
          let unit = selectedOption.getAttribute("data-unit") || "";
          let rate = selectedOption.getAttribute("data-rate") || "";
          let gst = selectedOption.getAttribute("data-gst") || "";
          let itemName = selectedOption.getAttribute("data-name") || "";
          let itemCode = selectedOption.value;
          let row = selectElement.closest("tr");

          row.querySelector("input[name='unit[]']").value = unit;
          row.querySelector("input[name='rate[]']").value = rate;
          row.querySelector("input[name='product_name_actual[]']").value = itemName;

          let gstSelect = row.querySelector("select[name='product_gst[]']");
          if (gstSelect) {
              gstSelect.value = gst;
          }

          calculateRow(row.querySelector("input[name='quantity[]']"));
      }

      function calculateRow(input) {
          let row = input.parentElement.parentElement;
          let qty = parseFloat(row.cells[2].querySelector("input").value) || 0;
          let rate = parseFloat(row.cells[3].querySelector("input").value) || 0;
          let gstPercentage = parseFloat(row.querySelector(".product-gst").value) || 0;

          let amount = qty * rate;
          let gstAmount = (amount * gstPercentage) / 100;

          let vendorState = document.getElementById("vendor_state").value;
          let shipperState = document.getElementById("shipper_state").value;

          let igst = 0, cgst = 0, sgst = 0;
          if (vendorState && shipperState) {
              if (vendorState === shipperState) {
                  cgst = gstAmount / 2;
                  sgst = gstAmount / 2;
              } else {
                  igst = gstAmount;
              }
          }

          row.cells[5].querySelector("input").value = igst.toFixed(2);
          row.cells[6].querySelector("input").value = cgst.toFixed(2);
          row.cells[7].querySelector("input").value = sgst.toFixed(2);
          row.cells[8].querySelector("input").value = (amount + gstAmount).toFixed(2);

          updateGSTTotals();
          calculateTotal();
      }

      function calculateTotal() {
          let rows = document.querySelectorAll("#invoiceTable tbody tr");
          let gross = 0;

          rows.forEach(row => {
              gross += parseFloat(row.cells[8].querySelector("input").value) || 0;
          });

          document.getElementById("grossAmount").innerText = "₹" + gross.toFixed(2);
          document.getElementById("grossAmountInput").value = gross.toFixed(2);

          let discount = parseFloat(document.getElementById("discount").value) || 0;
          let netAmount = gross - discount;
          document.getElementById("netAmount").innerText = "₹" + netAmount.toFixed(2);
          document.getElementById("netAmountInput").value = netAmount.toFixed(2);

          let totalGST = 0;
          rows.forEach(row => {
              let igst = parseFloat(row.cells[5].querySelector("input").value) || 0;
              let cgst = parseFloat(row.cells[6].querySelector("input").value) || 0;
              let sgst = parseFloat(row.cells[7].querySelector("input").value) || 0;
              totalGST += igst + cgst + sgst;
          });

          let baseAmount = gross - totalGST;
          document.getElementById("baseAmount").innerText = "₹" + baseAmount.toFixed(2);
          document.getElementById("baseAmountInput").value = baseAmount.toFixed(2);

          updateGSTTotals();
      }

      function updateGSTTotals() {
          let totalIGST = 0, totalCGST = 0, totalSGST = 0;

          document.querySelectorAll("#invoiceTable tbody tr").forEach(row => {
              totalIGST += parseFloat(row.cells[5].querySelector("input").value) || 0;
              totalCGST += parseFloat(row.cells[6].querySelector("input").value) || 0;
              totalSGST += parseFloat(row.cells[7].querySelector("input").value) || 0;
          });

          document.getElementById("totalIGST").innerText = "₹" + totalIGST.toFixed(2);
          document.getElementById("totalIGSTInput").value = totalIGST.toFixed(2);
          document.getElementById("totalCGST").innerText = "₹" + totalCGST.toFixed(2);
          document.getElementById("totalCGSTInput").value = totalCGST.toFixed(2);
          document.getElementById("totalSGST").innerText = "₹" + totalSGST.toFixed(2);
          document.getElementById("totalSGSTInput").value = totalSGST.toFixed(2);
      }

      function removeRow(button) {
          button.parentElement.parentElement.remove();
          calculateTotal();
      }
  </script>
</body>
</html>
