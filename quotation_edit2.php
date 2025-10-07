<?php
include("connection.php"); // Include your database connection

// Check if quotation_id is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('No quotation selected!'); window.location.href='quotation_display.php';</script>";
    exit;
}

$quotation_id = $_GET['id'];

// Handle form submission for updating
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $quotation_date = $_POST['quotation_date'];
    $client_name = $_POST['client_name'];
    $shipper_location_code = $_POST['shipper_location_code'];
    $gross_amount = $_POST['gross_amount'];
    $discount = $_POST['discount'];
    $net_amount = $_POST['net_amount'];
    $base_amount = $_POST['base_amount'];

    // Retrieve IGST, CGST, and SGST values from the form
    $total_igst = $_POST['total_igst'];
    $total_cgst = $_POST['total_cgst'];
    $total_sgst = $_POST['total_sgst'];

    // Retrieve form data for new columns (Client Details)
    $client_address = $_POST['client_address'];
    $client_phone = $_POST['client_phone'];
    $client_city = $_POST['client_city'];
    $client_state = $_POST['client_state'];
    $client_country = $_POST['client_country'];
    $client_pincode = $_POST['client_pincode'];
    $client_gstno = $_POST['client_gstno'];
    $client_company_name = $_POST['client_company_name']; // New field

    // Retrieve form data for new columns (Shipper Details)
    $shipper_company_name = $_POST['shipper_company_name'];
    $shipper_address = $_POST['shipper_address'];
    $shipper_city = $_POST['shipper_city'];
    $shipper_state = $_POST['shipper_state'];
    $shipper_country = $_POST['shipper_country'];
    $shipper_pincode = $_POST['shipper_pincode'];
    $shipper_phone = $_POST['shipper_phone'];
    $shipper_gstno = $_POST['shipper_gstno'];

    // Fetch client_id based on client_name
    $client_result = $connection->query("SELECT id FROM contact WHERE contact_person = '$client_name'");
    $client_row = $client_result->fetch_assoc();
    $client_id = $client_row['id'];

    // Fetch shipper_id based on shipper_location_code
    $shipper_result = $connection->query("SELECT id FROM location_card WHERE location_code = '$shipper_location_code'");
    $shipper_row = $shipper_result->fetch_assoc();
    $shipper_id = $shipper_row['id'];

    // Update data in the quotations table
    $update_quotation = "UPDATE quotations SET
        client_name = '$client_name',
        shipper_location_code = '$shipper_location_code',
        client_id = '$client_id',
        shipper_id = '$shipper_id',
        gross_amount = '$gross_amount',
        discount = '$discount',
        net_amount = '$net_amount',
        quotation_date = '$quotation_date',
        total_igst = '$total_igst',
        total_cgst = '$total_cgst',
        total_sgst = '$total_sgst',
        base_amount = '$base_amount',
        client_address = '$client_address',
        client_phone = '$client_phone',
        client_city = '$client_city',
        client_state = '$client_state',
        client_country = '$client_country',
        client_pincode = '$client_pincode',
        client_gstno = '$client_gstno',
        client_company_name = '$client_company_name', -- New field
        shipper_company_name = '$shipper_company_name',
        shipper_address = '$shipper_address',
        shipper_city = '$shipper_city',
        shipper_state = '$shipper_state',
        shipper_country = '$shipper_country',
        shipper_pincode = '$shipper_pincode',
        shipper_phone = '$shipper_phone',
        shipper_gstno = '$shipper_gstno'
        WHERE id = '$quotation_id'";

    if ($connection->query($update_quotation) === TRUE) {
        // Delete existing items for this quotation
        $delete_items = "DELETE FROM quotation_items WHERE quotation_id = '$quotation_id'";
        $connection->query($delete_items);

        // Retrieve form data
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
        $stocks = $_POST['stock'];

        // Inside the for loop where you insert items:
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
            $stock = $stocks[$i];

            // Insert into quotation_items with stock
            $insert_item = "INSERT INTO quotation_items (quotation_id, product_name, product_id, quantity, rate, gst, amount, unit, value, igst, cgst, sgst, stock) VALUES
                ('$quotation_id', '$product_name', '$product_code', '$quantity', '$rate', '$gst', '$amount', '$unit', '$unit_value', '$igst', '$cgst', '$sgst', '$stock')";
            $connection->query($insert_item);
        }

        echo "<script>alert('Sales Quotation Updated!'); window.location.href='quotation_display.php';</script>";
    } else {
        echo "<p>Error updating quotation: " . $connection->error . "</p>";
    }
    exit;
}

// Fetch quotation data
$quotation_query = "SELECT * FROM quotations WHERE id = '$quotation_id'";
$quotation_result = $connection->query($quotation_query);

if ($quotation_result->num_rows == 0) {
    echo "<script>alert('Quotation not found!'); window.location.href='quotation_display.php';</script>";
    exit;
}

$quotation = $quotation_result->fetch_assoc();

// Fetch quotation items
$items_query = "SELECT * FROM quotation_items WHERE quotation_id = '$quotation_id'";
$items_result = $connection->query($items_query);
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quotation</title>
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
        border-collapse: collapse; /* Ensure borders collapse into a single border */
    }

    th, td {
        padding: 10px; /* Space in table cells */
        text-align: left; /* Left-align text */
        border: 1px solid #ccc; /* Add a border for cells */
    }

    th {
        background-color: #2c3e50; /* Header background */
        color: white; /* Header text color */
        text-align: center;
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

        input[type="text"],
input[type="number"],
select {
    width: 100%; /* Make inputs take the full width */
    padding: 10px; /* Padding for better usability */
    border: 1px solid #ccc; /* Light border */
    border-radius: 4px; /* Rounded corners */
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1); /* Subtle inset shadow */
    transition: border-color 0.3s ease; /* Transition for border color */
}

input[type="text"]:focus,
input[type="number"]:focus,
select:focus {
    border-color: #3498db; /* Change border color on focus */
}

/* Set a default width for all input fields */
input[type="text"],
input[type="number"],
select {
    width: 100%; /* Full width for all inputs */
    height: 40px; /* Consistent height */
    padding: 5px; /* Padding for usability */
    border: 1px solid #ccc; /* Light border */
    border-radius: 4px; /* Rounded corners */
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    transition: border-color 0.3s ease; /* Transition for focus */
    box-sizing: border-box; /* Include padding and border in total width */
}

/* Specific styles for IGST, CGST, and SGST fields to make them smaller */
input[name="igst[]"],
input[name="cgst[]"],
input[name="sgst[]"] {
    width: 80%; /* Set width to 80% */
}

/* Focus styles for all input fields */
input[type="text"]:focus,
input[type="number"]:focus,
select:focus {
    border-color: #3498db; /* Change border color on focus */
}

td {
    padding: 10px; /* Space in table cells */
    text-align: left; /* Left-align text */
    border: 1px solid #ccc; /* Add border */
}

tr:hover {
    background-color: #f5f5f5; /* Highlight row on hover */
}

/* Set a default width for all input fields */
input[type="text"],
input[type="number"],
select {
    width: 100%; /* Full width for all inputs */
    height: 40px; /* Consistent height */
    padding: 5px; /* Padding for usability */
    border: 1px solid #ccc; /* Light border */
    border-radius: 4px; /* Rounded corners */
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    transition: border-color 0.3s ease; /* Transition for focus */
    box-sizing: border-box; /* Include padding and border in total width */
}

/* Specific styles for IGST, CGST, and SGST fields to make them smaller */
input[name="igst[]"],
input[name="cgst[]"],
input[name="sgst[]"] {
    width: 80%; /* Set width to 80% */
}

/* Increase the width of specific columns */
th:nth-child(1), /* Product */
td:nth-child(1) {
    width: 12%; /* Adjust width for Product column */
}

th:nth-child(4), /* Rate */
td:nth-child(4) {
    width: 12%; /* Adjust width for Rate column */
}

th:nth-child(5), /* GST (%) */
td:nth-child(5) {
    width: 10%; /* Adjust width for GST column */
}

/* Focus styles for all input fields */
input[type="text"]:focus,
input[type="number"]:focus,
select:focus {
    border-color: #3498db; /* Change border color on focus */
}

td {
    padding: 10px; /* Space in table cells */
    text-align: left; /* Left-align text */
    border: 1px solid #ccc; /* Add border */
}

tr:hover {
    background-color: #f5f5f5; /* Highlight row on hover */
}

.close-btn {
    font-size: 20px; /* Adjust size as needed */
    color: #2c3e50; /* Change color to fit your design */
    transition: color 0.3s; /* Smooth color transition on hover */
}

.close-btn:hover {
    color: darkred; /* Change color on hover for feedback */
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
    background-color: #f0f0f0; /* Highlight on hover */
}



    </style>
</head>
<body>

  <div class="container">
      <a style="text-decoration: none; margin-left: 1137px; padding: 0px; position: relative; top: -34px; transform: translateY(-50%);" href="quotation_display.php" class="close-btn">&times;</a>

      <form id="invoiceForm" method="POST" action="">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: -25px; width: 100%;">
              <div style="flex-grow: 1; text-align: center;">
                  <h1 style="margin-left: 150px;">Edit Sales Quotation</h1>
                  <p>Quotation #: <?php echo $quotation['quotation_no']; ?></p>
              </div>
              <div style="margin-right: 10px;">
                  <label for="quotation_date"><strong>Date:</strong></label>
                  <input
                      type="date"
                      id="quotation_date"
                      name="quotation_date"
                      value="<?php echo $quotation['quotation_date']; ?>"
                      style="margin-left: 5px;" />
              </div>
          </div>

          <h3>Client & Shipper Details</h3>

          <div class="form-section">
              <div class="column">
                  <h4>Client Details</h4>
                  <label>Name:</label>
  <select id="client_name" name="client_name" required>
      <option value="" disabled>Select Client</option>
      <?php
      $result = $connection->query("SELECT * FROM contact");
      while ($row = $result->fetch_assoc()) {
          $selected = ($row['contact_person'] == $quotation['client_name']) ? 'selected' : '';
          echo "<option value='{$row['contact_person']}'
                data-phone='{$row['mobile_no']}'
                data-address='{$row['address']}'
                data-city='{$row['city']}'
                data-gstno='{$row['gstno']}'
                data-state='{$row['state']}'
                data-country='{$row['country']}'
                data-pincode='{$row['pincode']}'
                data-company='{$row['company_name']}' $selected>
                {$row['contact_person']} - {$row['company_name']}</option>";
      }
      ?>
  </select>

                  </select>
                  <label>Company Name:</label>
                  <input type="text" id="client_company_name" name="client_company_name" value="<?php echo $quotation['client_company_name']; ?>" readonly>
                  <label>Address:</label>
                  <input type="text" id="client_address" name="client_address" value="<?php echo $quotation['client_address']; ?>" readonly>
                  <label>Phone:</label>
                  <input type="text" id="client_phone" name="client_phone" value="<?php echo $quotation['client_phone']; ?>" readonly>
                  <label>City:</label>
                  <input type="text" id="client_city" name="client_city" value="<?php echo $quotation['client_city']; ?>" readonly>
                  <label>State:</label>
                  <input type="text" id="client_state" name="client_state" value="<?php echo $quotation['client_state']; ?>" readonly>
                  <label>Country:</label>
                  <input type="text" id="client_country" name="client_country" value="<?php echo $quotation['client_country']; ?>" readonly>
                  <label>Pincode:</label>
                  <input type="text" id="client_pincode" name="client_pincode" value="<?php echo $quotation['client_pincode']; ?>" readonly>
                  <label>GST No.:</label>
                  <input type="text" id="client_gstno" name="client_gstno" value="<?php echo $quotation['client_gstno']; ?>" readonly>
              </div>

              <div class="column">
                  <h4>Shipper Details</h4>
                  <label>Location Code:</label>
                  <select id="shipper_location_code" name="shipper_location_code" required>
                      <option value="" disabled>Select Location Code</option>
                      <?php
                      $result = $connection->query("SELECT * FROM location_card");
                      while ($row = $result->fetch_assoc()) {
                          $selected = ($row['location_code'] == $quotation['shipper_location_code']) ? 'selected' : '';
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
                  <input type="text" id="shipper_company_name" name="shipper_company_name" value="<?php echo $quotation['shipper_company_name']; ?>" readonly>
                  <label>Address:</label>
                  <input type="text" id="shipper_address" name="shipper_address" value="<?php echo $quotation['shipper_address']; ?>" readonly>
                  <label>City:</label>
                  <input type="text" id="shipper_city" name="shipper_city" value="<?php echo $quotation['shipper_city']; ?>" readonly>
                  <label>State:</label>
                  <input type="text" id="shipper_state" name="shipper_state" value="<?php echo $quotation['shipper_state']; ?>" readonly>
                  <label>Country:</label>
                  <input type="text" id="shipper_country" name="shipper_country" value="<?php echo $quotation['shipper_country']; ?>" readonly>
                  <label>Pincode:</label>
                  <input type="text" id="shipper_pincode" name="shipper_pincode" value="<?php echo $quotation['shipper_pincode']; ?>" readonly>
                  <label>Phone:</label>
                  <input type="text" id="shipper_phone" name="shipper_phone" value="<?php echo $quotation['shipper_phone']; ?>" readonly>
                  <label>GST No.:</label>
                  <input type="text" id="shipper_gstno" name="shipper_gstno" value="<?php echo $quotation['shipper_gstno']; ?>" readonly>
              </div>
          </div>

          <h3>Product Details</h3>
          <table id="invoiceTable" border="1" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
              <thead>
                  <tr>
                      <th>Product</th>
                      <th>Unit</th>
                      <th>Qty</th>
                      <th>Rate</th>
                      <th>GST (%)</th>
                      <th>Stock</th>
                      <th>IGST</th>
                      <th>CGST</th>
                      <th>SGST</th>
                      <th>Amount</th>
                      <th>Action</th>
                  </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <select name="product_name[]" onchange="fetchProductDetails(this)" required>
                            <option value="" disabled>Select Product</option>
                            <?php
                            $product_result = $connection->query("SELECT * FROM item");
                            while ($row = $product_result->fetch_assoc()) {
                                $selected = ($row['item_code'] == $item['product_id']) ? 'selected' : '';
                                echo "<option value='" . $row['item_code'] . "'
                                    data-rate='" . $row['sales_price'] . "'
                                    data-unit='" . $row['unit_of_measurement_code'] . "'
                                    data-gst='" . $row['gst_code'] . "'
                                    data-name='" . $row['item_name'] . "' $selected>" . $row['item_name'] . "</option>";
                            }
                            ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="unit[]" class="unit-field" value="<?php echo $item['unit']; ?>" readonly required
                            onclick="loadUnitOptions(this)" placeholder="Click to select">
                        <div class="unit-dropdown" style="display:none; position:absolute; background:#fff; border:1px solid #ccc; z-index:1000;">
                            <ul></ul>
                        </div>
                    </td>
                    <td><input type="number" name="quantity[]" value="<?php echo $item['quantity']; ?>" oninput="calculateRow(this)" required></td>
                    <td><input type="number" name="rate[]" value="<?php echo $item['rate']; ?>" oninput="calculateRow(this)" required></td>
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
                    <td><input type="text" name="stock[]" value="<?php echo $item['stock']; ?>" readonly></td>
                    <input type="hidden" name="product_name_actual[]" value="<?php echo $item['product_name']; ?>">
                    <input type="hidden" name="unit_value[]" value="<?php echo $item['value']; ?>" id="unitValueField">
                    <td><input type="text" name="igst[]" value="<?php echo $item['igst']; ?>" readonly></td>
                    <td><input type="text" name="cgst[]" value="<?php echo $item['cgst']; ?>" readonly></td>
                    <td><input type="text" name="sgst[]" value="<?php echo $item['sgst']; ?>" readonly></td>
                    <td><input type="text" name="amount[]" value="<?php echo $item['amount']; ?>" readonly></td>
                    <td><button type="button" onclick="removeRow(this)" class="remove-button">Remove</button></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
          </table>
          <button type="button" onclick="addRow()">+ Add Product</button>

          <h2>Summary</h2>

          <div class="summary-section">
              <div class="summary-row">
                  <input type="hidden" name="base_amount" id="baseAmountInput" value="<?php echo $quotation['base_amount']; ?>">
                  <div class="input-group">
                      <label for="baseAmount">Base Value:</label>
                      <span id="baseAmount">₹<?php echo $quotation['base_amount']; ?></span>
                  </div>
              </div>
              <div class="summary-row">
                  <input type="hidden" name="total_cgst" id="totalCGSTInput" value="<?php echo $quotation['total_cgst']; ?>">
                  <div class="input-group">
                      <label for="totalCGST">Total CGST:</label>
                      <span id="totalCGST">₹<?php echo $quotation['total_cgst']; ?></span>
                  </div>
              </div>
              <div class="summary-row">
                  <input type="hidden" name="total_sgst" id="totalSGSTInput" value="<?php echo $quotation['total_sgst']; ?>">
                  <div class="input-group">
                      <label for="totalSGST">Total SGST:</label>
                      <span id="totalSGST">₹<?php echo $quotation['total_sgst']; ?></span>
                  </div>
              </div>
              <div class="summary-row">
                  <input type="hidden" name="total_igst" id="totalIGSTInput" value="<?php echo $quotation['total_igst']; ?>">
                  <div class="input-group">
                      <label for="totalIGST">Total IGST:</label>
                      <span id="totalIGST">₹<?php echo $quotation['total_igst']; ?></span>
                  </div>
              </div>
              <div class="summary-row">
                  <input type="hidden" name="gross_amount" id="grossAmountInput" value="<?php echo $quotation['gross_amount']; ?>">
                  <div class="input-group">
                      <label for="grossAmount">Gross Amount:</label>
                      <span id="grossAmount">₹<?php echo $quotation['gross_amount']; ?></span>
                  </div>
              </div>
              <div class="summary-row">
                  <div class="input-group">
                      <label for="discount">Discount:</label>
                      <input type="text" id="discount" name="discount" value="<?php echo $quotation['discount']; ?>" oninput="calculateTotal()" />
                  </div>
              </div>
              <div class="summary-row">
                  <input type="hidden" name="net_amount" id="netAmountInput" value="<?php echo $quotation['net_amount']; ?>">
                  <div class="input-group ">
                      <label for="netAmount">Net Amount:</label>
                      <span style="font-weight:bold;" id="netAmount">₹<?php echo $quotation['net_amount']; ?></span>
                  </div>
              </div>
          </div>

          <button type="submit">Update Quotation</button>
          <div id="message">
      </form>
  </div>

  <script>
  document.getElementById("client_name").addEventListener("change", function () {
      let selectedOption = this.options[this.selectedIndex];
      document.getElementById("client_address").value = selectedOption.getAttribute("data-address");
      document.getElementById("client_phone").value = selectedOption.getAttribute("data-phone");
      document.getElementById("client_city").value = selectedOption.getAttribute("data-city");
      document.getElementById("client_state").value = selectedOption.getAttribute("data-state");
      document.getElementById("client_country").value = selectedOption.getAttribute("data-country");
      document.getElementById("client_pincode").value = selectedOption.getAttribute("data-pincode");
      document.getElementById("client_gstno").value = selectedOption.getAttribute("data-gstno");
      document.getElementById("client_company_name").value = selectedOption.getAttribute("data-company");
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
              <select name="product_name[]" onchange="fetchProductDetails(this)" required>
                  <option value="" disabled selected>Select Product</option>
                  <?php
                  $product_result = $connection->query("SELECT * FROM item");
                  while ($row = $product_result->fetch_assoc()) {
                      echo "<option value='" . $row['item_code'] . "'
                          data-rate='" . $row['sales_price'] . "'
                          data-unit='" . $row['unit_of_measurement_code'] . "'
                          data-gst='" . $row['gst_code'] . "'
                          data-name='" . $row['item_name'] . "'>" . $row['item_name'] . "</option>";
                  }
                  ?>
              </select>
          </td>
          <td>
              <input type="text" name="unit[]" class="unit-field" readonly required
                  onclick="loadUnitOptions(this)" placeholder="Click to select">
              <div class="unit-dropdown" style="display:none; position:absolute; background:#fff; border:1px solid #ccc; z-index:1000;">
                  <ul></ul>
              </div>
          </td>
          <td><input type="number" name="quantity[]" oninput="calculateRow(this)" required></td>
          <td><input type="number" name="rate[]" oninput="calculateRow(this)" required></td>
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
          <td><input type="text" name="stock[]" readonly></td>
          <input type="hidden" name="product_name_actual[]">
          <input type="hidden" name="unit_value[]" value="1" id="unitValueField">
          <td><input type="text" name="igst[]" readonly></td>
          <td><input type="text" name="cgst[]" readonly></td>
          <td><input type="text" name="sgst[]" readonly></td>
          <td><input type="text" name="amount[]" readonly></td>
          <td><button type="button" onclick="removeRow(this)" class="remove-button">Remove</button></td>
      `;

      styleRowInputs(row);
      // Add event listener to unit field
      row.querySelector("input[name='unit[]']").addEventListener("change", function() {
          calculateRow(this);
      });
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
                  calculateRow(inputField); // Recalculate row when unit changes
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

      row.querySelector("input[name='quantity[]']").value = "";
      calculateRow(row.querySelector("input[name='quantity[]']"));
  }

  function calculateRow(input) {
      let row = input.parentElement.parentElement;
      let qty = parseFloat(row.cells[2].querySelector("input").value) || 0;
      let unitValue = parseFloat(row.querySelector("input[name='unit_value[]']").value) || 1;
      let stock = qty * unitValue;
      let rate = parseFloat(row.cells[3].querySelector("input").value) || 0;
      let gstPercentage = parseFloat(row.querySelector(".product-gst").value) || 0;

      // Display stock in the stock field
      row.cells[5].querySelector("input").value = stock;

      let amount = stock * rate;
      let gstAmount = (amount * gstPercentage) / 100;

      let clientState = document.getElementById("client_state").value;
      let shipperState = document.getElementById("shipper_state").value;

      let igst = 0, cgst = 0, sgst = 0;
      if (clientState && shipperState) {
          if (clientState === shipperState) {
              cgst = gstAmount / 2;
              sgst = gstAmount / 2;
          } else {
              igst = gstAmount;
          }
      }

      row.cells[6].querySelector("input").value = igst.toFixed(2);
      row.cells[7].querySelector("input").value = cgst.toFixed(2);
      row.cells[8].querySelector("input").value = sgst.toFixed(2);
      row.cells[9].querySelector("input").value = (amount + gstAmount).toFixed(2);

      calculateTotal(); // Now calls the complete calculation
  }

  function calculateTotal() {
      let rows = document.querySelectorAll("#invoiceTable tbody tr");
      let gross = 0;
      let totalIGST = 0;
      let totalCGST = 0;
      let totalSGST = 0;
      let totalBaseAmount = 0;

      rows.forEach(row => {
          let qty = parseFloat(row.cells[2].querySelector("input").value) || 0;
          let unitValue = parseFloat(row.querySelector("input[name='unit_value[]']").value) || 1;
          let stock = qty * unitValue;
          let rate = parseFloat(row.cells[3].querySelector("input").value) || 0;
          let gstPercentage = parseFloat(row.querySelector(".product-gst").value) || 0;

          let amount = stock * rate;
          let gstAmount = (amount * gstPercentage) / 100;

          gross += (amount + gstAmount);
          totalBaseAmount += amount;

          // Calculate GST breakdown
          let clientState = document.getElementById("client_state").value;
          let shipperState = document.getElementById("shipper_state").value;

          if (clientState && shipperState) {
              if (clientState === shipperState) {
                  totalCGST += (gstAmount / 2);
                  totalSGST += (gstAmount / 2);
              } else {
                  totalIGST += gstAmount;
              }
          }
      });

      // Update all summary fields
      document.getElementById("grossAmount").innerText = "₹" + gross.toFixed(2);
      document.getElementById("grossAmountInput").value = gross.toFixed(2);

      let discount = parseFloat(document.getElementById("discount").value) || 0;
      let netAmount = gross - discount;
      document.getElementById("netAmount").innerText = "₹" + netAmount.toFixed(2);
      document.getElementById("netAmountInput").value = netAmount.toFixed(2);

      document.getElementById("baseAmount").innerText = "₹" + totalBaseAmount.toFixed(2);
      document.getElementById("baseAmountInput").value = totalBaseAmount.toFixed(2);

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

  // Initialize calculations when page loads
  window.onload = function() {
      calculateTotal();
  };

  </script>

</body>
</html>
