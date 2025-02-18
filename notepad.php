<?php
$conn = new mysqli("localhost", "root", "", "lead_management");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $invoice_date = $_POST['invoice_date'];
    $client_name = $_POST['client_name'];
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
    $client_result = $conn->query("SELECT id FROM contact WHERE contact_person = '$client_name'");
    $client_row = $client_result->fetch_assoc();
    $client_id = $client_row['id'];

    // Fetch shipper_id
    $shipper_result = $conn->query("SELECT id FROM location_card WHERE location_code = '$shipper_location_code'");
    $shipper_row = $shipper_result->fetch_assoc();
    $shipper_id = $shipper_row['id'];

    // Get the next invoice number with 'INV' prefix
  $next_invoice_no_result = $conn->query("SELECT CONCAT('INV', LPAD(COALESCE(MAX(id), 0) + 1, 4, '0')) AS next_invoice_no FROM invoices");
  $next_invoice_no_row = $next_invoice_no_result->fetch_assoc();
  $invoice_no = $next_invoice_no_row['next_invoice_no'];


    // Insert into invoices table
    // Insert into invoices table
  $insert_invoice = "INSERT INTO invoices (invoice_no, client_name, shipper_location_code, client_id, shipper_id, gross_amount, discount,
      net_amount, invoice_date, total_igst, total_cgst, total_sgst, base_amount, client_address, client_phone, client_city,
      client_state, client_country, client_pincode, client_gstno, shipper_company_name, shipper_address, shipper_city,
      shipper_state, shipper_country, shipper_pincode, shipper_phone, shipper_gstno, status)
      VALUES ('$invoice_no', '$client_name', '$shipper_location_code', '$client_id', '$shipper_id', '$gross_amount', '$discount',
      '$net_amount', '$invoice_date', '$total_igst', '$total_cgst', '$total_sgst', '$base_amount', '$client_address', '$client_phone',
      '$client_city', '$client_state', '$client_country', '$client_pincode', '$client_gstno', '$shipper_company_name',
      '$shipper_address', '$shipper_city', '$shipper_state', '$shipper_country', '$shipper_pincode', '$shipper_phone', '$shipper_gstno', 'Finalized')";


    if ($conn->query($insert_invoice) === TRUE) {
        $invoice_id = $conn->insert_id;

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
            $lot_tracking_ids = $_POST['lot_tracking_id'];
            $expiration_dates = $_POST['expiring_date'];

            // New AMC fields
            $amc_codes = $_POST['amc_code'];
            $amc_terms = $_POST['amc_term'];
            $amc_paid_dates = $_POST['amc_paid_date'];
            $amc_due_dates = $_POST['amc_due_date'];
            $amc_amounts = $_POST['amc_amount'];

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

                // New AMC fields
                $amc_code = $amc_codes[$i];
                $amc_term = $amc_terms[$i];
                $amc_paid_date = $amc_paid_dates[$i];
                $amc_due_date = $amc_due_dates[$i];
                $amc_amount = $amc_amounts[$i];

                // Insert into invoice_items with new AMC fields
                $insert_item = "INSERT INTO invoice_items (invoice_id, product_name, product_id, quantity, rate, gst, amount, unit, value, igst, cgst, sgst, lot_trackingid, expiration_date, amc_code, amc_term, amc_paid_date, amc_due_date, amc_amount) VALUES
                    ('$invoice_id', '$product_name', '$product_code', '$quantity', '$rate', '$gst', '$amount', '$unit', '$unit_value', '$igst', '$cgst', '$sgst', '$lot_tracking_id', '$expiration_date', '$amc_code', '$amc_term', '$amc_paid_date', '$amc_due_date', '$amc_amount')";
                $conn->query($insert_item);

                // Fetch the last inserted invoice_item ID
                $invoice_item_id = $conn->insert_id;

      // Fetch unit_of_measurement_code from item table
      // Fetch item type and unit_of_measurement_code from item table
  $item_query = "SELECT item_type, unit_of_measurement_code FROM item WHERE item_code = '$product_code'";
  $item_result = $conn->query($item_query);

  if ($item_result->num_rows > 0) {
      $item_row = $item_result->fetch_assoc();
      $item_type = $item_row['item_type']; // Get the item_type
      $unit = $item_row['unit_of_measurement_code']; // Get the unit from the item table

      // Check item_type
      if ($item_type === 'Inventory') {
          // Now insert into item_ledger_history
          $document_type = 'Sale'; // As it's an invoice for sale
          $entry_type = 'Sales Invoice'; // Entry type
          $item_quantity = -((float)$quantity * (float)$unit_value); // Make quantity negative and calculate value
          $location = $shipper_location_code; // Assuming you want to track where the product is shipped from
          $date = date('Y-m-d'); // Use today's date
          $item_value = $unit_value; // Store the unit value as the value in the item_ledger_history

          // Insert into item_ledger_history with invoice_item_id, lot_trackingid, and expiration_date
          $insert_ledger_history = "INSERT INTO item_ledger_history (invoice_no , document_type, entry_type, product_id, product_name, quantity, location, unit, date, value, invoice_itemid, lot_trackingid, expiration_date)
                                    VALUES ('$invoice_no' ,'$document_type', '$entry_type', '$product_code', '$product_name', '$item_quantity', '$location', '$unit', '$date', '$item_value', '$invoice_item_id', '$lot_tracking_id', '$expiration_date')";
          $conn->query($insert_ledger_history);
      }
  } else {
      // Handle case where item is not found if needed
      // You may want to log or alert the user here
  }

      }

        echo "<script>alert('Invoice Registered Successfully'); window.location.href='invoice_display.php';</script>";
    } else {
        echo "<p>Error saving invoice: " . $conn->error . "</p>";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice System</title>
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
  <a style="text-decoration: none; margin-left: 1217px; padding: 0px; position: relative; top: -34px; transform: translateY(-50%);" href="invoice_display.php" class="close-btn">&times;</a>






    <form id="invoiceForm" method="POST" action="">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: -25px; width: 100%;">
          <div style="flex-grow: 1; text-align: center;">
              <h1 style="margin-left: 150px;">Invoice Generate</h2>
          </div>
          <div style="margin-right: 10px;">
          <label for="invoice_date"><strong>Date:</strong></label>
          <input
              type="date"
              id="invoice_date"
              name="invoice_date"
              value="<?php echo date('Y-m-d'); ?>"
              style="margin-left: 5px;" /> <!-- Corrected comment syntax -->
      </div>

      </div>

        <h3>Client & Shipper Details</h3>

        <div class="form-section">
            <div class="column">
                <h4>Client Details</h4>
                <label>Name:</label>
                <select id="client_name" name="client_name" required>
                    <option value="" disabled selected>Select Client</option>
                    <?php
                    $result = $conn->query("SELECT * FROM contact");
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['contact_person']}'
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
                <input type="text" id="client_address" name="client_address" readonly>
                <label>Phone:</label>
                <input type="text" id="client_phone" name="client_phone" readonly>
                <label>City:</label>
                <input type="text" id="client_city" name="client_city" readonly>
                <label>State:</label>
                <input type="text" id="client_state" name="client_state" readonly>
                <label>Country:</label>
                <input type="text" id="client_country" name="client_country" readonly>
                <label>Pincode:</label>
                <input type="text" id="client_pincode" name="client_pincode" readonly>
                <label>GST No.:</label>
                <input type="text" id="client_gstno" name="client_gstno" readonly>
            </div>

            <div class="column">
                <h4>Shipper Details</h4>
                <label>Location Code:</label>
                <select id="shipper_location_code" name="shipper_location_code" required>
                    <option value="" disabled selected>Select Location Code</option>
                    <?php
                    $result = $conn->query("SELECT * FROM location_card");
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['location_code']}'
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
                <input type="text" id="shipper_company_name" name="shipper_company_name" readonly>
                <label>Address:</label>
                <input type="text" id="shipper_address" name="shipper_address" readonly>
                <label>City:</label>
                <input type="text" id="shipper_city" name="shipper_city" readonly>
                <label>State:</label>
                <input type="text" id="shipper_state" name="shipper_state" readonly>
                <label>Country:</label>
                <input type="text" id="shipper_country" name="shipper_country" readonly>
                <label>Pincode:</label>
                <input type="text" id="shipper_pincode" name="shipper_pincode" readonly>
                <label>Phone:</label>
                <input type="text" id="shipper_phone" name="shipper_phone" readonly>
                <label>GST No.:</label>
                <input type="text" id="shipper_gstno" name="shipper_gstno" readonly>
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
                        <th>AMC Code</th>
                        <th>AMC Term</th>
                        <th>AMC Paid Date</th>
                        <th>AMC Due Date</th>
                        <th>AMC Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <button type="button" onclick="addRow()">+ Add Product</button>

        <h2>Summary</h2>

        <div class="summary-section">
            <div class="summary-row">
                <input type="hidden" name="base_amount" id="baseAmountInput" value="0.00">
                <div class="input-group">
                    <label for="baseAmount">Base Value:</label>
                    <span id="baseAmount">₹0.00</span>
                </div>
            </div>
            <div class="summary-row">
                <input type="hidden" name="total_cgst" id="totalCGSTInput" value="0.00">
                <div class="input-group">
                    <label for="totalCGST">Total CGST:</label>
                    <span id="totalCGST">₹0.00</span>
                </div>
            </div>
            <div class="summary-row">
                <input type="hidden" name="total_sgst" id="totalSGSTInput" value="0.00">
                <div class="input-group">
                    <label for="totalSGST">Total SGST:</label>
                    <span id="totalSGST">₹0.00</span>
                </div>
            </div>
            <div class="summary-row">
                <input type="hidden" name="total_igst" id="totalIGSTInput" value="0.00">
                <div class="input-group">
                    <label for="totalIGST">Total IGST:</label>
                    <span id="totalIGST">₹0.00</span>
                </div>
            </div>
            <div class="summary-row">
                <input type="hidden" name="gross_amount" id="grossAmountInput" value="0.00">
                <div class="input-group">
                    <label for="grossAmount">Gross Amount:</label>
                    <span id="grossAmount">₹0.00</span>
                </div>
            </div>
            <div class="summary-row">
                <div class="input-group">
                    <label for="discount">Discount:</label>
                    <input type="text" id="discount" name="discount" value="0.00" oninput="calculateTotal()" />
                </div>
            </div>
            <div class="summary-row">
                <input type="hidden" name="net_amount" id="netAmountInput" value="0.00">
                <div class="input-group ">
                    <label for="netAmount">Net Amount:</label>
                    <span style="font-weight:bold;" id="netAmount">₹0.00</span>
                </div>
            </div>
        </div>




        <button type="submit">Save Invoice</button>
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

            // Fetch product details (you may need to adjust this based on your data structure)
            const productOption = productSelect.querySelector(`option[value="${productCode}"]`);
            const lotTracking = productOption.getAttribute('data-lot-tracking') === '1';
            const expirationTracking = productOption.getAttribute('data-expiration-tracking') === '1';
            const amcTracking = productOption.getAttribute('data-amc-tracking') === '1';

            const lotInput = row.querySelector('input[name="lot_tracking_id[]"]');
            const expirationInput = row.querySelector('input[name="expiring_date[]"]');
            const amcInputs = row.querySelectorAll('input[name^="amc_"], select[name="amc_term[]"]');

            if (lotTracking && !lotInput.value.trim()) {
                alert(`Please enter Lot Tracking ID for ${productOption.textContent}`);
                isValid = false;
            }

            if (expirationTracking && !expirationInput.value) {
                alert(`Please enter Expiration Date for ${productOption.textContent}`);
                isValid = false;
            }

            if (amcTracking) {
                let allAmcFilled = true; // Flag to check if all AMC inputs are filled
                amcInputs.forEach(input => {
                    if (!input.value.trim()) {
                        allAmcFilled = false; // If any AMC input is empty, set to false
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
    });

    document.getElementById("shipper_location_code").addEventListener("change", function () {
    let selectedOption = this.options[this.selectedIndex]; // Get the selected dropdown option

    // Populate the fields with the data-* attributes
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
                $product_result = $conn->query("SELECT * FROM item");
                while ($row = $product_result->fetch_assoc()) {
                  echo "<option value='" . $row['item_code'] . "'
                    data-rate='" . $row['sales_price'] . "'
                    data-unit='" . $row['unit_of_measurement_code'] . "'
                    data-gst='" . $row['gst_code'] . "'
                    data-name='" . $row['item_name'] . "'
                    data-lot-tracking='" . $row['lot_tracking'] . "'
                    data-expiration-tracking='" . $row['expiration_tracking'] . "'
                    data-amc-tracking='" . $row['amc_tracking'] . "'>" . $row['item_name'] . "</option>";
                }
                ?>
            </select>
        </td>

        <td>
            <input type="text" name="unit[]" readonly required onclick="loadUnitOptions(this)" placeholder="Click to select">
            <div class="unit-dropdown" style="display:none; position:absolute; background:#fff; border:1px solid #ccc; z-index:1000;">
                <ul></ul>
            </div>
        </td>

        <td><input type="number" name="quantity[]" step="any" oninput="calculateRow(this)" placeholder="Quantity" required></td>
        <td><input type="number" name="rate[]" placeholder="Rate"></td>
        <td>
            <select class="product-gst" name="product_gst[]" onchange="calculateRow(this)" required>
                <option value="" disabled selected>Select GST %</option>
                <?php
                $gst_result = $conn->query("SELECT * FROM gst");
                while ($row = $gst_result->fetch_assoc()) {
                    echo "<option value='" . $row['percentage'] . "'>" . $row['percentage'] . "%</option>";
                }
                ?>
            </select>
        </td>
        <input type="hidden" name="product_name_actual[]">
        <input type="hidden" name="unit_value[]" value="" id="unitValueField">
        <td><input type="text" name="igst[]" placeholder="IGST" readonly></td>
        <td><input type="text" name="cgst[]" placeholder="CGST" readonly></td>
        <td><input type="text" name="sgst[]" placeholder="SGST" readonly></td>
        <td><input type="text" name="amount[]" placeholder="Amount" readonly></td>
        <td><input type="text" name="lot_tracking_id[]" placeholder="Enter Lot ID"></td>
        <td><input type="date" name="expiring_date[]" placeholder="Expiry Date"></td>
        <td><input type="text" name="amc_code[]" placeholder="AMC Code"></td>
        <td>
            <select name="amc_term[]">
                <option value="" disabled selected>AMC Term</option>
                <option value="Yearly">Yearly</option>
                <option value="Quarterly">Quarterly</option>
                <option value="Monthly">Monthly</option>
            </select>
        </td>
        <td><input type="date" name="amc_paid_date[]"></td>
        <td><input type="date" name="amc_due_date[]"></td>
        <td><input type="text" name="amc_amount[]" placeholder="AMC Amount"></td>
        <td><button type="button" onclick="removeRow(this)" class="remove-button">Remove</button></td>
    `;

    // Apply styles to the newly created row
    styleRowInputs(row);
}


// Function to load unit options when the unit input is clicked
// Function to load unit options when the unit input is clicked
function loadUnitOptions(inputField) {
    const unitDropdown = inputField.nextElementSibling; // Get the dropdown div
    const productSelect = inputField.closest('tr').querySelector('select[name="product_name[]"]'); // Find the product select element
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const itemCode = selectedOption.value;

    // Clear previous unit options
    const ul = unitDropdown.querySelector('ul');
    ul.innerHTML = '';

    // Fetch units and values based on the selected item code
    fetchUnits(itemCode).then(units => {
        units.forEach(unitObj => {
            const li = document.createElement('li');
            li.textContent = `${unitObj.unit_name} - ${unitObj.value}`;
            li.onclick = function() {
                inputField.value = unitObj.unit_name; // Set unit name
                inputField.dataset.unitValue = unitObj.value; // Store unit value as data attribute

                unitDropdown.style.display = 'none'; // Hide dropdown

                // Update the correct hidden input in the same row
                const hiddenInput = inputField.closest('tr').querySelector('input[name="unit_value[]"]');
                if (hiddenInput) {
                    hiddenInput.value = unitObj.value;
                }
            };
            ul.appendChild(li);
        });

        // Show the dropdown
        unitDropdown.style.display = 'block';
    });
}


// Function to fetch units and values via AJAX
function fetchUnits(itemCode) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'fetch_units.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        resolve(JSON.parse(xhr.responseText)); // Resolve with parsed JSON response
                    } catch (error) {
                        reject("Invalid JSON response");
                    }
                } else {
                    reject("Failed to fetch units");
                }
            }
        };
        xhr.send('item_code=' + encodeURIComponent(itemCode)); // Send item code
    });
}

// Function to apply consistent styles to input fields
function styleRowInputs(row) {
    const inputs = row.querySelectorAll('input[type="text"], input[type="number"], select');
    inputs.forEach(input => {
        input.style.width = "100%"; // Full width
        input.style.padding = "10px"; // Padding for better usability
        input.style.border = "1px solid #ccc"; // Light border
        input.style.borderRadius = "4px"; // Rounded corners
        input.style.boxShadow = "inset 0 1px 3px rgba(0, 0, 0, 0.1)"; // Subtle inset shadow
        input.style.transition = "border-color 0.3s ease"; // Transition for border color
    });

    // Style the remove button
    const removeButton = row.querySelector('.remove-button');
    removeButton.style.padding = "5px 10px";
    removeButton.style.backgroundColor = "#e74c3c"; // Red background
    removeButton.style.color = "white"; // White text
    removeButton.style.border = "none"; // Remove border
    removeButton.style.borderRadius = "4px"; // Rounded corners
    removeButton.style.cursor = "pointer"; // Pointer cursor
}


function fetchProductDetails(selectElement) {
    let selectedOption = selectElement.options[selectElement.selectedIndex];

    // Fetch the details from the selected option's dataset
    let unit = selectedOption.getAttribute("data-unit") || "";
    let rate = selectedOption.getAttribute("data-rate") || "";
    let gst = selectedOption.getAttribute("data-gst") || "";
    let itemName = selectedOption.getAttribute("data-name") || "";
    let itemCode = selectedOption.value;

    // Find the row containing the select element
    let row = selectElement.closest("tr");

    // Assign values to the respective input fields in the same row
    row.querySelector("input[name='unit[]']").value = unit; // Unit column
    row.querySelector("input[name='rate[]']").value = rate; // Rate column
    row.querySelector("input[name='product_name_actual[]']").value = itemName; // Hidden field for product name

    // Set GST dropdown value (if applicable)
    let gstSelect = row.querySelector("select[name='product_gst[]']");
    if (gstSelect) {
        gstSelect.value = gst; // Set GST value in the dropdown
    }

    // Clear quantity for the new product (optional)
    row.querySelector("input[name='quantity[]']").value = "";

    // Recalculate the row with the new data
    calculateRow(row.querySelector("input[name='quantity[]']"));



}

function calculateRow(input) {
    let row = input.parentElement.parentElement;
    let qty = parseFloat(row.cells[2].querySelector("input").value) || 0; // Qty column, allow decimals
    let rate = parseFloat(row.cells[3].querySelector("input").value) || 0; // Rate column
    let gstPercentage = parseFloat(row.querySelector(".product-gst").value) || 0; // GST %

    let amount = qty * rate;
    let gstAmount = (amount * gstPercentage) / 100;

    // Determine IGST, CGST, and SGST based on states
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

    // Set values in the respective columns
    row.cells[5].querySelector("input").value = igst.toFixed(2); // IGST
    row.cells[6].querySelector("input").value = cgst.toFixed(2); // CGST
    row.cells[7].querySelector("input").value = sgst.toFixed(2); // SGST
    row.cells[8].querySelector("input").value = (amount + gstAmount).toFixed(2); // Total amount including GST

    // Update the GST totals
    updateGSTTotals();

    // Also update total values
    calculateTotal();
}

function calculateTotal() {
    let rows = document.querySelectorAll("#invoiceTable tbody tr");
    let gross = 0;

    rows.forEach(row => {
        gross += parseFloat(row.cells[8].querySelector("input").value) || 0; // Amount column
    });

    document.getElementById("grossAmount").innerText = gross.toFixed(2);
    document.getElementById("grossAmountInput").value = gross.toFixed(2); // Update hidden gross amount input

    // Get the discount value from the input field
    let discount = parseFloat(document.getElementById("discount").value) || 0; // Changed from innerText to value

    let netAmount = gross - discount; // Subtract discount from gross
    document.getElementById("netAmount").innerText = netAmount.toFixed(2);
    document.getElementById("netAmountInput").value = netAmount.toFixed(2); // Update hidden Net amount input

    let totalGST = 0;
    rows.forEach(row => {
        let igst = parseFloat(row.cells[5].querySelector("input").value) || 0;
        let cgst = parseFloat(row.cells[6].querySelector("input").value) || 0;
        let sgst = parseFloat(row.cells[7].querySelector("input").value) || 0;
        totalGST += igst + cgst + sgst;
    });

    let baseAmount = gross - totalGST;
    document.getElementById("baseAmount").innerText = baseAmount.toFixed(2);
    document.getElementById("baseAmountInput").value = baseAmount.toFixed(2); // Update hidden base amount input

    updateGSTTotals();
}

function updateGSTTotals() {
    let totalIGST = 0, totalCGST = 0, totalSGST = 0;

    document.querySelectorAll("#invoiceTable tbody tr").forEach(row => {
        totalIGST += parseFloat(row.cells[5].querySelector("input").value) || 0;
        totalCGST += parseFloat(row.cells[6].querySelector("input").value) || 0;
        totalSGST += parseFloat(row.cells[7].querySelector("input").value) || 0;
    });

    document.getElementById("totalIGST").innerText = totalIGST.toFixed(2);
    document.getElementById("totalIGSTInput").value = totalIGST.toFixed(2); // Update hidden IGST input
    document.getElementById("totalCGST").innerText = totalCGST.toFixed(2);
    document.getElementById("totalCGSTInput").value = totalCGST.toFixed(2); // Update hidden CGST input
    document.getElementById("totalSGST").innerText = totalSGST.toFixed(2);
    document.getElementById("totalSGSTInput").value = totalSGST.toFixed(2); // Update hidden SGST input
}


function removeRow(button) {
    button.parentElement.parentElement.remove();
    calculateTotal();
}
</script>

</body>
</html>
