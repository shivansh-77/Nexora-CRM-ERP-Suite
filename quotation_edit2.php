<?php
$conn = new mysqli("localhost", "root", "", "lead_management");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch quotation data if ID is provided
$quotation = null;
$quotation_items = [];
$client = null;
$shipper = null;

if (isset($_GET['id'])) {
    $quotation_id = $_GET['id'];
    $query = "SELECT * FROM quotations WHERE id = $quotation_id";
    $result = $conn->query($query);
    $quotation = $result->fetch_assoc();

    if ($quotation) {
        // Fetch client details
        $client_query = "SELECT * FROM contact WHERE id = {$quotation['client_id']}";
        $client_result = $conn->query($client_query);
        $client = $client_result->fetch_assoc();

        // Fetch shipper details
        $shipper_query = "SELECT * FROM location_card WHERE id = {$quotation['shipper_id']}";
        $shipper_result = $conn->query($shipper_query);
        $shipper = $shipper_result->fetch_assoc();

        // Fetch quotation items
        $items_query = "SELECT * FROM quotation_items WHERE quotation_id = $quotation_id";
        $items_result = $conn->query($items_query);
        $quotation_items = $items_result->fetch_all(MYSQLI_ASSOC);
    }
}

// Handle form submission for updating
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $quotation_id = $_POST['quotation_id'];
    $quotation_date = $_POST['quotation_date'];
    $client_id = $_POST['client_id'];
    $client_name = $_POST['client_name'];
    $shipper_id = $_POST['shipper_id'];
    $gross_amount = $_POST['gross_amount'];
    $discount = $_POST['discount'];
    $net_amount = $_POST['net_amount'];
    $base_amount = $_POST['base_amount'];
    $total_igst = $_POST['total_igst'];
    $total_cgst = $_POST['total_cgst'];
    $total_sgst = $_POST['total_sgst'];

    // Update quotation in the database
    $update_quotation = "UPDATE quotations SET
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
        client_address = '{$_POST['client_address']}',
        client_phone = '{$_POST['client_phone']}',
        client_city = '{$_POST['client_city']}',
        client_state = '{$_POST['client_state']}',
        client_country = '{$_POST['client_country']}',
        client_pincode = '{$_POST['client_pincode']}',
        client_gstno = '{$_POST['client_gstno']}',
        shipper_company_name = '{$_POST['shipper_company_name']}',
        shipper_address = '{$_POST['shipper_address']}',
        shipper_city = '{$_POST['shipper_city']}',
        shipper_state = '{$_POST['shipper_state']}',
        shipper_country = '{$_POST['shipper_country']}',
        shipper_pincode = '{$_POST['shipper_pincode']}',
        shipper_phone = '{$_POST['shipper_phone']}',
        shipper_gstno = '{$_POST['shipper_gstno']}',
        client_name = '$client_name'
        WHERE id = $quotation_id";

    if ($conn->query($update_quotation)) {
        // Delete existing items for this quotation
        $delete_items = "DELETE FROM quotation_items WHERE quotation_id = $quotation_id";
        $conn->query($delete_items);

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

        for ($i = 0; $i < count($products); $i++) {
            $insert_item = "INSERT INTO quotation_items (quotation_id, product_id, product_name, quantity, rate, gst, amount, unit, igst, cgst, sgst)
                            VALUES ('$quotation_id', '{$products[$i]}', '{$product_names[$i]}', '{$quantities[$i]}',
                            '{$rates[$i]}', '{$gsts[$i]}', '{$amounts[$i]}', '{$units[$i]}', '{$igsts[$i]}',
                            '{$cgsts[$i]}', '{$sgsts[$i]}')";
            $conn->query($insert_item);
        }

        echo "<script>alert('Quotation updated successfully!'); window.location.href='quotation_display.php';</script>";
    } else {
        echo "<p>Error updating quotation: " . $conn->error . "</p>";
    }
}

// Fetch all clients and shippers for dropdowns
$clients_query = "SELECT * FROM contact";
$clients_result = $conn->query($clients_query);

$shippers_query = "SELECT * FROM location_card";
$shippers_result = $conn->query($shippers_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quotation</title>
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
.submit{
  margin-left: 420px;
}

    </style>
</head>
<body>
    <div class="container">
      <a style="text-decoration: none; margin-left: 1027px; padding: 0px; position: relative; top: -34px; transform: translateY(-50%);" href="quotation_display.php" class="close-btn">&times;</a>
        <form id="editQuotationForm" method="POST" action="">
            <input type="hidden" name="quotation_id" value="<?php echo $quotation['id']; ?>">
            <input type="hidden" name="client_id" id="client_id" value="<?php echo $quotation['client_id']; ?>">
            <input type="hidden" name="shipper_id" id="shipper_id" value="<?php echo $quotation['shipper_id']; ?>">

            <!-- Date input -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: -25px; width: 100%;">
                <div style="flex-grow: 1; text-align: center;">
                    <h1 style="margin-left: 150px;">Edit Sales Quotation</h1>
                </div>
                <div style="margin-right: 10px;">
                    <label for="quotation_date"><strong>Date:</strong></label>
                    <input
                        type="date"
                        id="quotation_date"
                        name="quotation_date"
                        value="<?php echo $quotation ? $quotation['quotation_date'] : date('Y-m-d'); ?>"
                        style="margin-left: 5px;" />
                </div>
            </div>

            <div class="form-section">
                <div class="column">
                    <h4>Client Details</h4>
                    <select id="client_name" name="client_name" required>
                        <?php
                        $result = $conn->query("SELECT * FROM contact");
                        while ($row = $result->fetch_assoc()) {
                            $selected = ($row['id'] == $quotation['client_id']) ? 'selected' : '';
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
                    <input type="text" id="client_address" name="client_address" value="<?php echo htmlspecialchars($client['address']); ?>" readonly>
                    <label>Phone:</label>
                    <input type="text" id="client_phone" name="client_phone" value="<?php echo htmlspecialchars($client['mobile_no']); ?>" readonly>
                    <label>City:</label>
                    <input type="text" id="client_city" name="client_city" value="<?php echo htmlspecialchars($client['city']); ?>" readonly>
                    <label>State:</label>
                    <input type="text" id="client_state" name="client_state" value="<?php echo htmlspecialchars($client['state']); ?>" readonly>
                    <label>Country:</label>
                    <input type="text" id="client_country" name="client_country" value="<?php echo htmlspecialchars($client['country']); ?>" readonly>
                    <label>Pincode:</label>
                    <input type="text" id="client_pincode" name="client_pincode" value="<?php echo htmlspecialchars($client['pincode']); ?>" readonly>
                    <label>GST No.:</label>
                    <input type="text" id="client_gstno" name="client_gstno" value="<?php echo htmlspecialchars($client['gstno']); ?>" readonly>
                </div>

                <div class="column">
                    <h4>Shipper Details</h4>
                    <select id="shipper_location_code" name="shipper_location_code" required>
                        <?php
                        $result = $conn->query("SELECT * FROM location_card");
                        while ($row = $result->fetch_assoc()) {
                            $selected = ($row['id'] == $quotation['shipper_id']) ? 'selected' : '';
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
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quotation_items as $item): ?>
                        <tr>
                            <td>
                                <select name="product_name[]" onchange="fetchProductDetails(this)" required>
                                    <?php
                                    $product_result = $conn->query("SELECT * FROM item");
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
                                <input type="hidden" name="product_name_actual[]" value="<?php echo $item['product_name']; ?>">
                            </td>
                            <td><input type="text" name="unit[]" value="<?php echo $item['unit']; ?>" readonly></td>
                            <td><input type="number" name="quantity[]" value="<?php echo $item['quantity']; ?>" oninput="calculateRow(this)"></td>
                            <td><input type="number" name="rate[]" value="<?php echo $item['rate']; ?>" readonly></td>
                            <td>
                                <select name="product_gst[]" onchange="calculateRow(this)">
                                    <?php
                                    $gst_result = $conn->query("SELECT * FROM gst");
                                    while ($row = $gst_result->fetch_assoc()) {
                                        $selected = ($row['percentage'] == $item['gst']) ? 'selected' : '';
                                        echo "<option value='{$row['percentage']}' {$selected}>{$row['percentage']}%</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td><input type="text" name="igst[]" value="<?php echo $item['igst']; ?>" readonly></td>
                            <td><input type="text" name="cgst[]" value="<?php echo $item['cgst']; ?>" readonly></td>
                            <td><input type="text" name="sgst[]" value="<?php echo $item['sgst']; ?>" readonly></td>
                            <td><input type="text" name="amount[]" value="<?php echo $item['amount']; ?>" readonly></td>
                            <td><button class=".remove-button" type="button" onclick="removeRow(this)">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" onclick="addRow()">Add Product</button>

            <h3>Summary</h3>
            <div class="summary-section">
                <div class="summary-row">
                    <input type="hidden" name="base_amount" id="baseAmountInput" value="<?php echo $quotation['base_amount']; ?>">
                    <div class="input-group">
                        <label for="baseAmount">Base Value:</label>
                        <span id="baseAmount">₹<?php echo number_format($quotation['base_amount'], 2); ?></span>
                    </div>
                </div>
                <div class="summary-row">
                    <input type="hidden" name="total_cgst" id="totalCGSTInput" value="<?php echo $quotation['total_cgst']; ?>">
                    <div class="input-group">
                        <label for="totalCGST">Total CGST:</label>
                        <span id="totalCGST">₹<?php echo number_format($quotation['total_cgst'], 2); ?></span>
                    </div>
                </div>
                <div class="summary-row">
                    <input type="hidden" name="total_sgst" id="totalSGSTInput" value="<?php echo $quotation['total_sgst']; ?>">
                    <div class="input-group">
                        <label for="totalSGST">Total SGST:</label>
                        <span id="totalSGST">₹<?php echo number_format($quotation['total_sgst'], 2); ?></span>
                    </div>
                </div>
                <div class="summary-row">
                    <input type="hidden" name="total_igst" id="totalIGSTInput" value="<?php echo $quotation['total_igst']; ?>">
                    <div class="input-group">
                        <label for="totalIGST">Total IGST:</label>
                        <span id="totalIGST">₹<?php echo number_format($quotation['total_igst'], 2); ?></span>
                    </div>
                </div>
                <div class="summary-row">
                    <input type="hidden" name="gross_amount" id="grossAmountInput" value="<?php echo $quotation['gross_amount']; ?>">
                    <div class="input-group">
                        <label for="grossAmount">Gross Amount:</label>
                        <span id="grossAmount">₹<?php echo number_format($quotation['gross_amount'], 2); ?></span>
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
                    <div class="input-group">
                        <label for="netAmount">Net Amount:</label>
                        <span id="netAmount">₹<?php echo number_format($quotation['net_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <button class="submit" type="submit">Update Quotation</button>
        </form>
    </div>


    <script>
    function addRow() {
      const table = document.getElementById("productTable").getElementsByTagName("tbody")[0]
      const newRow = table.insertRow(table.rows.length)
      newRow.innerHTML = `
            <td>
                <select name="product_name[]" onchange="fetchProductDetails(this)" required>
                    <option value="" disabled selected>Select Product</option>
                    <?php
                    $product_result = $conn->query("SELECT * FROM item");
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
                <select name="product_gst[]" onchange="calculateRow(this)">
                    <option value="" disabled selected>Select GST %</option>
                    <?php
                    $gst_result = $conn->query("SELECT * FROM gst");
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
            <td><button class=".remove-button" type="button" onclick="removeRow(this)">Remove</button></td>
        `
    }

    function removeRow(button) {
      const row = button.parentNode.parentNode
      row.parentNode.removeChild(row)
      calculateTotal()
    }

    function fetchProductDetails(select) {
      const selectedOption = select.options[select.selectedIndex]
      const row = select.closest("tr")
      row.querySelector('input[name="unit[]"]').value = selectedOption.dataset.unit
      row.querySelector('input[name="rate[]"]').value = selectedOption.dataset.rate
      row.querySelector('select[name="product_gst[]"]').value = selectedOption.dataset.gst
      row.querySelector('input[name="product_name_actual[]"]').value = selectedOption.dataset.name
      calculateRow(row.querySelector('input[name="quantity[]"]'))
    }

    function calculateRow(input) {
      const row = input.closest("tr")
      const quantity = Number.parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0
      const rate = Number.parseFloat(row.querySelector('input[name="rate[]"]').value) || 0
      const gst = Number.parseFloat(row.querySelector('select[name="product_gst[]"]').value) || 0

      const amount = quantity * rate
      const gstAmount = (amount * gst) / 100

      const clientState = document.getElementById("client_state").value
      const shipperState = document.getElementById("shipper_state").value

      let igst = 0,
        cgst = 0,
        sgst = 0
      if (clientState !== shipperState) {
        igst = gstAmount
      } else {
        cgst = gstAmount / 2
        sgst = gstAmount / 2
      }

      row.querySelector('input[name="igst[]"]').value = igst.toFixed(2)
      row.querySelector('input[name="cgst[]"]').value = cgst.toFixed(2)
      row.querySelector('input[name="sgst[]"]').value = sgst.toFixed(2)
      row.querySelector('input[name="amount[]"]').value = (amount + gstAmount).toFixed(2)

      calculateTotal()
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

    function updateClientDetails() {
      const selectedOption =
        document.getElementById("client_name").options[document.getElementById("client_name").selectedIndex]
      document.getElementById("client_id").value = selectedOption.value
      document.getElementById("client_address").value = selectedOption.getAttribute("data-address") || ""
      document.getElementById("client_phone").value = selectedOption.getAttribute("data-phone") || ""
      document.getElementById("client_city").value = selectedOption.getAttribute("data-city") || ""
      document.getElementById("client_state").value = selectedOption.getAttribute("data-state") || ""
      document.getElementById("client_country").value = selectedOption.getAttribute("data-country") || ""
      document.getElementById("client_pincode").value = selectedOption.getAttribute("data-pincode") || ""
      document.getElementById("client_gstno").value = selectedOption.getAttribute("data-gstno") || ""
      calculateTotal()
    }

    function updateShipperDetails() {
      const selectedOption =
        document.getElementById("shipper_location_code").options[
          document.getElementById("shipper_location_code").selectedIndex
        ]
      document.getElementById("shipper_id").value = selectedOption.value
      document.getElementById("shipper_company_name").value = selectedOption.getAttribute("data-company") || ""
      document.getElementById("shipper_address").value = selectedOption.getAttribute("data-address") || ""
      document.getElementById("shipper_city").value = selectedOption.getAttribute("data-city") || ""
      document.getElementById("shipper_state").value = selectedOption.getAttribute("data-state") || ""
      document.getElementById("shipper_country").value = selectedOption.getAttribute("data-country") || ""
      document.getElementById("shipper_pincode").value = selectedOption.getAttribute("data-pincode") || ""
      document.getElementById("shipper_phone").value = selectedOption.getAttribute("data-phone") || ""
      document.getElementById("shipper_gstno").value = selectedOption.getAttribute("data-gstno") || ""
      calculateTotal()
    }

    document.addEventListener("DOMContentLoaded", () => {
      updateClientDetails()
      updateShipperDetails()
      calculateTotal()

      document.getElementById("client_name").addEventListener("change", updateClientDetails)
      document.getElementById("shipper_location_code").addEventListener("change", updateShipperDetails)
    })


    </script>
</body>
</html>
