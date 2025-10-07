<?php
// Include the connection file
include 'connection.php'; // Ensure this file contains the $connection variable

// Check if invoice ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invoice ID is required'); window.location.href='invoice_display.php';</script>";
    exit();
}

$invoice_id = $_GET['id'];

// Fetch invoice data
$invoice_query = "SELECT * FROM invoices WHERE id = '$invoice_id'";
$invoice_result = $connection->query($invoice_query);

if ($invoice_result->num_rows == 0) {
    echo "<script>alert('Invoice not found'); window.location.href='invoice_display.php';</script>";
    exit();
}

$invoice_data = $invoice_result->fetch_assoc();

// Fetch invoice items
$items_query = "SELECT * FROM invoice_items WHERE invoice_id = '$invoice_id'";
$items_result = $connection->query($items_query);
$invoice_items = [];
while ($item = $items_result->fetch_assoc()) {
    $invoice_items[] = $item;
}

// Handle form submission for updating
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $invoice_date = $_POST['invoice_date'];
    $client_name = $_POST['client_name'];
    $client_company_name = $_POST['client_company_name']; // New field
    $shipper_location_code = $_POST['shipper_location_code'];
    $gross_amount = $_POST['gross_amount'];
    $discount = $_POST['discount'];
    $net_amount = $_POST['net_amount'];
    $base_amount = $_POST['base_amount'];

    // Retrieve IGST, CGST, and SGST values
    $total_igst = $_POST['total_igst'];
    $total_cgst = $_POST['total_cgst'];
    $total_sgst = $_POST['total_sgst'];

    // Client Details
    $client_address = $_POST['client_address'];
    $client_phone = $_POST['client_phone'];
    $client_city = $_POST['client_city'];
    $client_state = $_POST['client_state'];
    $client_country = $_POST['client_country'];
    $client_pincode = $_POST['client_pincode'];
    $client_gstno = $_POST['client_gstno'];

    // Shipper Details
    $shipper_company_name = $_POST['shipper_company_name'];
    $shipper_address = $_POST['shipper_address'];
    $shipper_city = $_POST['shipper_city'];
    $shipper_state = $_POST['shipper_state'];
    $shipper_country = $_POST['shipper_country'];
    $shipper_pincode = $_POST['shipper_pincode'];
    $shipper_phone = $_POST['shipper_phone'];
    $shipper_gstno = $_POST['shipper_gstno'];

    // Fetch client_id
    $client_result = $connection->query("SELECT id FROM contact WHERE contact_person = '$client_name'");
    $client_row = $client_result->fetch_assoc();
    $client_id = $client_row['id'];

    // Fetch shipper_id
    $shipper_result = $connection->query("SELECT id FROM location_card WHERE location_code = '$shipper_location_code'");
    $shipper_row = $shipper_result->fetch_assoc();
    $shipper_id = $shipper_row['id'];

    // Fetch fy_code from financial_years table where is_current = 1
    $fy_result = $connection->query("SELECT fy_code FROM financial_years WHERE is_current = 1 LIMIT 1");
    $fy_row = $fy_result->fetch_assoc();
    $fy_code = $fy_row ? $fy_row['fy_code'] : '';

    // Update the invoice in the database
    $update_invoice = "UPDATE invoices SET
        client_name = '$client_name',
        client_company_name = '$client_company_name',
        shipper_location_code = '$shipper_location_code',
        client_id = '$client_id',
        shipper_id = '$shipper_id',
        gross_amount = '$gross_amount',
        discount = '$discount',
        net_amount = '$net_amount',
        invoice_date = '$invoice_date',
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
        shipper_company_name = '$shipper_company_name',
        shipper_address = '$shipper_address',
        shipper_city = '$shipper_city',
        shipper_state = '$shipper_state',
        shipper_country = '$shipper_country',
        shipper_pincode = '$shipper_pincode',
        shipper_phone = '$shipper_phone',
        shipper_gstno = '$shipper_gstno',
        pending_amount = '$net_amount',
        fy_code = '$fy_code'
    WHERE id = '$invoice_id'";


    if ($connection->query($update_invoice) === TRUE) {
        // Retrieve form data for items
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
        $item_ids = isset($_POST['item_id']) ? $_POST['item_id'] : []; // Get existing item IDs if available

        // New AMC fields - Check if the fields exist in $_POST before accessing them
        $amc_codes = isset($_POST['amc_code']) ? $_POST['amc_code'] : [];
        $amc_terms = isset($_POST['amc_term']) ? $_POST['amc_term'] : [];
        $amc_paid_dates = isset($_POST['amc_paid_date']) ? $_POST['amc_paid_date'] : [];
        $amc_due_dates = isset($_POST['amc_due_date']) ? $_POST['amc_due_date'] : [];
        $amc_amounts = isset($_POST['amc_amount']) ? $_POST['amc_amount'] : [];

        // Get invoice number for reference
        $invoice_no = $invoice_data['invoice_no'];

        // Get all existing item IDs for this invoice
        $existing_items_query = "SELECT id FROM invoice_items WHERE invoice_id = '$invoice_id'";
        $existing_items_result = $connection->query($existing_items_query);
        $existing_item_ids = [];
        while ($row = $existing_items_result->fetch_assoc()) {
            $existing_item_ids[] = $row['id'];
        }

        // Track which items we've processed
        $processed_item_ids = [];

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

            // New AMC fields (use empty values if index does not exist)
            $amc_code = isset($amc_codes[$i]) ? $amc_codes[$i] : '';
            $amc_term = isset($amc_terms[$i]) ? $amc_terms[$i] : '';
            $amc_paid_date = isset($amc_paid_dates[$i]) ? $amc_paid_dates[$i] : '0000-00-00';
            $amc_due_date = isset($amc_due_dates[$i]) ? $amc_due_dates[$i] : '0000-00-00';
            $amc_amount = isset($amc_amounts[$i]) ? $amc_amounts[$i] : 0;

            // Check if this is an existing item or a new one
            $item_id = isset($item_ids[$i]) ? $item_ids[$i] : 0;

            if ($item_id > 0 && in_array($item_id, $existing_item_ids)) {
                // This is an existing item - UPDATE it
                $update_item = "UPDATE invoice_items SET
                    product_name = '$product_name',
                    product_id = '$product_code',
                    quantity = '$quantity',
                    rate = '$rate',
                    gst = '$gst',
                    amount = '$amount',
                    unit = '$unit',
                    value = '$unit_value',
                    igst = '$igst',
                    cgst = '$cgst',
                    sgst = '$sgst',
                    amc_code = '$amc_code',
                    amc_term = '$amc_term',
                    amc_paid_date = '$amc_paid_date',
                    amc_due_date = '$amc_due_date',
                    amc_amount = '$amc_amount'
                WHERE id = '$item_id'";

                $connection->query($update_item);
                $processed_item_ids[] = $item_id;
            } else {
                // This is a new item - INSERT it
                $insert_item = "INSERT INTO invoice_items (
                    invoice_id, product_name, product_id, quantity, rate, gst, amount, unit, value, igst, cgst, sgst,
                    amc_code, amc_term, amc_paid_date, amc_due_date, amc_amount
                ) VALUES (
                    '$invoice_id', '$product_name', '$product_code', '$quantity', '$rate', '$gst', '$amount', '$unit',
                    '$unit_value', '$igst', '$cgst', '$sgst', '$amc_code',
                    '$amc_term', '$amc_paid_date', '$amc_due_date', '$amc_amount'
                )";

                $connection->query($insert_item);
                $invoice_item_id = $connection->insert_id;
                $processed_item_ids[] = $invoice_item_id;
            }
        }

        // Delete items that were not processed (removed items)
        foreach ($existing_item_ids as $existing_id) {
            if (!in_array($existing_id, $processed_item_ids)) {
                $delete_item = "DELETE FROM invoice_items WHERE id = '$existing_id'";
                $connection->query($delete_item);
            }
        }

        echo "<script>alert('Invoice Updated Successfully'); window.location.href='invoice_display.php';</script>";
    } else {
        echo "<p>Error updating invoice: " . $connection->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Invoice</title>
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
    <a style="text-decoration: none; margin-left: 1347px; padding: 0px; position: relative; top: -34px; transform: translateY(-50%);" href="invoice_display.php" class="close-btn">&times;</a>

    <form id="invoiceForm" method="POST" action="">

      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: -25px; width: 100%;">
        <div style="flex-grow: 1; text-align: center;">
          <h1 style="margin-left: 150px;">Edit Invoice</h1>
        </div>
        <div style="margin-right: 10px;">
          <label for="invoice_date"><strong>Date:</strong></label>
          <input type="date" id="invoice_date" name="invoice_date" value="<?php echo $invoice_data['invoice_date']; ?>" style="margin-left: 5px;" />
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
                  $selected = ($row['contact_person'] == $invoice_data['client_name']) ? 'selected' : '';
                  echo "<option value='{$row['contact_person']}'
                        data-phone='{$row['mobile_no']}'
                        data-address='{$row['address']}'
                        data-city='{$row['city']}'
                        data-gstno='{$row['gstno']}'
                        data-state='{$row['state']}'
                        data-country='{$row['country']}'
                        data-pincode='{$row['pincode']}'
                        data-company='{$row['company_name']}' $selected>{$row['contact_person']} ({$row['company_name']})</option>";
              }
              ?>
          </select>
          <label>Company Name:</label>
          <input type="text" id="client_company_name" name="client_company_name" value="<?php echo $invoice_data['client_company_name']; ?>" readonly>
          <label>Address:</label>
          <input type="text" id="client_address" name="client_address" value="<?php echo $invoice_data['client_address']; ?>" readonly>
          <label>Phone:</label>
          <input type="text" id="client_phone" name="client_phone" value="<?php echo $invoice_data['client_phone']; ?>" readonly>
          <label>City:</label>
          <input type="text" id="client_city" name="client_city" value="<?php echo $invoice_data['client_city']; ?>" readonly>
          <label>State:</label>
          <input type="text" id="client_state" name="client_state" value="<?php echo $invoice_data['client_state']; ?>" readonly>
          <label>Country:</label>
          <input type="text" id="client_country" name="client_country" value="<?php echo $invoice_data['client_country']; ?>" readonly>
          <label>Pincode:</label>
          <input type="text" id="client_pincode" name="client_pincode" value="<?php echo $invoice_data['client_pincode']; ?>" readonly>
          <label>GST No.:</label>
          <input type="text" id="client_gstno" name="client_gstno" value="<?php echo $invoice_data['client_gstno']; ?>" readonly>
      </div>

        <div class="column">
          <h4>Shipper Details</h4>
          <label>Location Code:</label>
          <select id="shipper_location_code" name="shipper_location_code" required>
            <option value="" disabled>Select Location Code</option>
            <?php
            $result = $connection->query("SELECT * FROM location_card");
            while ($row = $result->fetch_assoc()) {
              $selected = ($row['location_code'] == $invoice_data['shipper_location_code']) ? 'selected' : '';
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
          <input type="text" id="shipper_company_name" name="shipper_company_name" value="<?php echo $invoice_data['shipper_company_name']; ?>" readonly>
          <label>Address:</label>
          <input type="text" id="shipper_address" name="shipper_address" value="<?php echo $invoice_data['shipper_address']; ?>" readonly>
          <label>City:</label>
          <input type="text" id="shipper_city" name="shipper_city" value="<?php echo $invoice_data['shipper_city']; ?>" readonly>
          <label>State:</label>
          <input type="text" id="shipper_state" name="shipper_state" value="<?php echo $invoice_data['shipper_state']; ?>" readonly>
          <label>Country:</label>
          <input type="text" id="shipper_country" name="shipper_country" value="<?php echo $invoice_data['shipper_country']; ?>" readonly>
          <label>Pincode:</label>
          <input type="text" id="shipper_pincode" name="shipper_pincode" value="<?php echo $invoice_data['shipper_pincode']; ?>" readonly>
          <label>Phone:</label>
          <input type="text" id="shipper_phone" name="shipper_phone" value="<?php echo $invoice_data['shipper_phone']; ?>" readonly>
          <label>GST No.:</label>
          <input type="text" id="shipper_gstno" name="shipper_gstno" value="<?php echo $invoice_data['shipper_gstno']; ?>" readonly>
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
              <th>AMC Code</th>
              <th>AMC Paid Date</th>
              <th>AMC Due Date</th>
              <th>AMC Amount</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($invoice_items as $item): ?>
              <tr>
                <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
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
                            data-amc-tracking='" . $row['amc_tracking'] . "' $selected>" . $row['item_name'] . "</option>";
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
                <td><input type="number" name="rate[]" value="<?php echo $item['rate']; ?>" step="any" oninput="calculateRow(this)" placeholder="Rate" class="small-input"></td>
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
                <input type="hidden" name="unit_value[]" value="<?php echo $item['value']; ?>" id="unitValueField">
                <td><input type="text" name="igst[]" value="<?php echo $item['igst']; ?>" placeholder="IGST" class="small-input" readonly></td>
                <td><input type="text" name="cgst[]" value="<?php echo $item['cgst']; ?>" placeholder="CGST" class="small-input" readonly></td>
                <td><input type="text" name="sgst[]" value="<?php echo $item['sgst']; ?>" placeholder="SGST" class="small-input" readonly></td>
                <td><input type="text" name="amount[]" value="<?php echo $item['amount']; ?>" placeholder="Amount" class="small-input" readonly></td>
                <td>
                  <select name="amc_code[]" onchange="updateDueDateFromAMC(this)" class="small-input">
                    <option value="" disabled <?php echo empty($item['amc_code']) ? 'selected' : ''; ?>>Select AMC</option>
                    <?php
                    $amc_result = $connection->query("SELECT * FROM amc");
                    while ($row = $amc_result->fetch_assoc()) {
                        $selected = (!empty($item['amc_code']) && $row['value'] == $item['amc_code']) ? 'selected' : '';
                        echo "<option value='" . $row['value'] . "' $selected>" . $row['code'] . "</option>";
                    }
                    ?>
                  </select>
                </td>
                <td><input type="date" name="amc_paid_date[]" value="<?php echo $item['amc_paid_date'] != '0000-00-00' ? $item['amc_paid_date'] : ''; ?>" onchange="updateDueDateFromAMC(this)" class="small-input"></td>
                <td><input type="date" name="amc_due_date[]" value="<?php echo $item['amc_due_date'] != '0000-00-00' ? $item['amc_due_date'] : ''; ?>" class="small-input" readonly></td>
                <td><input type="text" name="amc_amount[]" value="<?php echo $item['amc_amount']; ?>" placeholder="AMC Amount" class="small-input"></td>
                <td><button type="button" onclick="removeRow(this)" class="remove-button">Remove</button></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <button type="button" onclick="addRow()">+ Add Product</button>

      <h2>Summary</h2>

      <div class="summary-section">
        <div class="summary-row">
          <input type="hidden" name="base_amount" id="baseAmountInput" value="<?php echo $invoice_data['base_amount']; ?>">
          <div class="input-group">
            <label for="baseAmount">Base Value:</label>
            <span id="baseAmount">₹<?php echo number_format($invoice_data['base_amount'], 2); ?></span>
          </div>
        </div>
        <div class="summary-row">
          <input type="hidden" name="total_cgst" id="totalCGSTInput" value="<?php echo $invoice_data['total_cgst']; ?>">
          <div class="input-group">
            <label for="totalCGST">Total CGST:</label>
            <span id="totalCGST">₹<?php echo number_format($invoice_data['total_cgst'], 2); ?></span>
          </div>
        </div>
        <div class="summary-row">
          <input type="hidden" name="total_sgst" id="totalSGSTInput" value="<?php echo $invoice_data['total_sgst']; ?>">
          <div class="input-group">
            <label for="totalSGST">Total SGST:</label>
            <span id="totalSGST">₹<?php echo number_format($invoice_data['total_sgst'], 2); ?></span>
          </div>
        </div>
        <div class="summary-row">
          <input type="hidden" name="total_igst" id="totalIGSTInput" value="<?php echo $invoice_data['total_igst']; ?>">
          <div class="input-group">
            <label for="totalIGST">Total IGST:</label>
            <span id="totalIGST">₹<?php echo number_format($invoice_data['total_igst'], 2); ?></span>
          </div>
        </div>
        <div class="summary-row">
          <input type="hidden" name="gross_amount" id="grossAmountInput" value="<?php echo $invoice_data['gross_amount']; ?>">
          <div class="input-group">
            <label for="grossAmount">Gross Amount:</label>
            <span id="grossAmount">₹<?php echo number_format($invoice_data['gross_amount'], 2); ?></span>
          </div>
        </div>
        <div class="summary-row">
          <div class="input-group">
            <label for="discount">Discount:</label>
            <input type="text" id="discount" name="discount" value="<?php echo $invoice_data['discount']; ?>" oninput="calculateTotal()" />
          </div>
        </div>
        <div class="summary-row">
          <input type="hidden" name="net_amount" id="netAmountInput" value="<?php echo $invoice_data['net_amount']; ?>">
          <div class="input-group ">
            <label for="netAmount">Net Amount:</label>
            <span style="font-weight:bold;" id="netAmount">₹<?php echo number_format($invoice_data['net_amount'], 2); ?></span>
          </div>
        </div>
      </div>

      <button type="submit">Update Invoice</button>
      <div id="message">

    </form>
  </div>


  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
                const amcTracking = productOption.getAttribute('data-amc-tracking') === '1';

                const amcInputs = row.querySelectorAll('input[name^="amc_"], select[name="amc_code[]"]');

                // Check and clear AMC tracking
                if (!amcTracking) {
                    let hasAmcValue = false;
                    amcInputs.forEach(input => {
                        if (input.value.trim()) {
                            hasAmcValue = true;
                            input.value = '';
                        }
                    });
                    if (hasAmcValue) {
                        alert(`AMC details are not required for ${productOption.textContent}`);
                    }
                } else {
                    let allAmcFilled = true;
                    amcInputs.forEach(input => {
                        if (!input.value.trim()) {
                            allAmcFilled = false;
                        }
                    });
                    if (!allAmcFilled) {
                        alert(`Please fill all AMC fields for ${productOption.textContent}`);
                        isValid = false;
                    }
                }
            });

            if (isValid) {
                form.submit();
            }
        });
    });

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
                          data-amc-tracking='" . $row['amc_tracking'] . "'>" . $row['item_name'] . "</option>";
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
          <td><input type="text" name="stock[]" placeholder="Stock" class="small-input" readonly></td>
          <input type="hidden" name="product_name_actual[]">
          <input type="hidden" name="unit_value[]" value="1" id="unitValueField">
          <td><input type="text" name="igst[]" placeholder="IGST" class="small-input" readonly></td>
          <td><input type="text" name="cgst[]" placeholder="CGST" class="small-input" readonly></td>
          <td><input type="text" name="sgst[]" placeholder="SGST" class="small-input" readonly></td>
          <td><input type="text" name="amount[]" placeholder="Amount" class="small-input" readonly></td>
          <td>
              <select name="amc_code[]" onchange="updateDueDateFromAMC(this)" class="small-input">
                  <option value="" disabled selected>Select AMC</option>
                  <?php
                  $amc_result = $connection->query("SELECT * FROM amc");
                  while ($row = $amc_result->fetch_assoc()) {
                      echo "<option value='" . $row['value'] . "'>" . $row['code'] . "</option>";
                  }
                  ?>
              </select>
          </td>
          <td><input type="date" name="amc_paid_date[]" onchange="updateDueDateFromAMC(this)" class="small-input"></td>
          <td><input type="date" name="amc_due_date[]" class="small-input" readonly></td>
          <td><input type="text" name="amc_amount[]" placeholder="AMC Amount" class="small-input"></td>
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
                // Trigger calculation after unit change
                calculateRow(inputField);
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

    function updateDueDateFromAMC(element) {
        let row = element.closest("tr");
        let amcCodeDropdown = row.querySelector('select[name="amc_code[]"]');
        let paidDateInput = row.querySelector('input[name="amc_paid_date[]"]');
        let dueDateInput = row.querySelector('input[name="amc_due_date[]"]');

        let amcDays = parseInt(amcCodeDropdown.value) || 0;
        let baseDate = paidDateInput.value ? new Date(paidDateInput.value) : new Date();

        baseDate.setDate(baseDate.getDate() + amcDays);

        let formattedDate = baseDate.toISOString().split('T')[0];
        dueDateInput.value = formattedDate;
    }

    function calculateRow(input) {
      let row = input.parentElement.parentElement;
      let qty = parseFloat(row.cells[2].querySelector("input").value) || 0;
      let rate = parseFloat(row.cells[3].querySelector("input").value) || 0;
      let gstPercentage = parseFloat(row.querySelector(".product-gst").value) || 0;
      let unitValue = parseFloat(row.querySelector("input[name='unit_value[]']").value) || 1;

      // Calculate stock (quantity × unit value)
      let stock = qty * unitValue;
      row.querySelector("input[name='stock[]']").value = stock.toFixed(2);

      // Calculate amount (stock × rate)
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

    // Initialize calculations when page loads
    window.onload = function() {
        // Calculate totals for existing rows
        document.querySelectorAll("#invoiceTable tbody tr").forEach(row => {
            const quantityInput = row.querySelector('input[name="quantity[]"]');
            if (quantityInput) {
                calculateRow(quantityInput);
            }
        });
    };
  </script>


</body>
</html>
