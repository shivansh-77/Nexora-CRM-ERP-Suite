<?php
include "connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $document_type = mysqli_real_escape_string($connection, $_POST['document_type']);
    $entry_type = mysqli_real_escape_string($connection, $_POST['entry_type']);
    $product_id = mysqli_real_escape_string($connection, $_POST['product_id']);
    $product_name = mysqli_real_escape_string($connection, $_POST['product_name']);
    $quantity = mysqli_real_escape_string($connection, $_POST['quantity']);
    $location = mysqli_real_escape_string($connection, $_POST['location']);
    $unit = mysqli_real_escape_string($connection, $_POST['unit']);
    $date = mysqli_real_escape_string($connection, $_POST['date']);
    $value = mysqli_real_escape_string($connection, $_POST['value']);

    $query = "INSERT INTO item_ledger_history (document_type, entry_type, product_id, product_name, quantity, location, unit, date, value)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ssssssssd", $document_type, $entry_type, $product_id, $product_name, $quantity, $location, $unit, $date, $value);

    if (mysqli_stmt_execute($stmt)) {
        echo "<p class='success'>Record added successfully.</p>";
    } else {
        echo "<p class='error'>Error: " . mysqli_error($connection) . "</p>";
    }

    mysqli_stmt_close($stmt);
}

// Fetch products for dropdown
if (isset($_GET['fetch_products'])) {
    $query = "SELECT item_code, item_name FROM item";
    $result = mysqli_query($connection, $query);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<div class='dropdown-item product-item' data-item_code='" . htmlspecialchars($row['item_code'], ENT_QUOTES, 'UTF-8') . "' data-item_name='" . htmlspecialchars($row['item_name'], ENT_QUOTES, 'UTF-8') . "'>
                    " . htmlspecialchars($row['item_code'], ENT_QUOTES, 'UTF-8') . " - " . htmlspecialchars($row['item_name'], ENT_QUOTES, 'UTF-8') . "
                  </div>";
        }
    } else {
        echo "<div class='dropdown-item'>No products found</div>";
    }
    exit;
}

// Fetch units for dropdown
if (isset($_GET['fetch_units'])) {
    $product_id = mysqli_real_escape_string($connection, $_GET['product_id']);
    $query = "SELECT unit, value FROM item_add WHERE item_code = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<div class='dropdown-item unit-item' data-unit='" . htmlspecialchars($row['unit'], ENT_QUOTES, 'UTF-8') . "' data-value='" . htmlspecialchars($row['value'], ENT_QUOTES, 'UTF-8') . "'>
                    " . htmlspecialchars($row['unit'], ENT_QUOTES, 'UTF-8') . " (Value: " . htmlspecialchars($row['value'], ENT_QUOTES, 'UTF-8') . ")
                  </div>";
        }
    } else {
        echo "<div class='dropdown-item'>No units found</div>";
    }
    mysqli_stmt_close($stmt);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Item Ledger History</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        h2 {
            color: #333;
        }
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="number"], input[type="date"], select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        #product-dropdown, #unit-dropdown {
            position: absolute;
            border: 1px solid #ccc;
            background: white;
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            width: calc(100% - 22px);
        }
        .dropdown-item {
            padding: 8px;
            cursor: pointer;
        }
        .dropdown-item:hover {
            background-color: #f0f0f0;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>Create Item Ledger History</h2>
    <form id="ledgerForm" action="create_item_ledger_history.php" method="POST">
        <label for="document_type">Document Type:</label>
        <select id="document_type" name="document_type" required>
            <option value="Sale">Sale</option>
            <option value="Purchase">Purchase</option>
            <option value="Adjustment">Adjustment</option>
        </select>

        <label for="entry_type">Entry Type:</label>
        <select id="entry_type" name="entry_type" required>
            <option value="Sales Invoice">Sales Invoice</option>
            <option value="Purchase Invoice">Purchase Invoice</option>
            <option value="Negative Adjustment">Negative Adjustment</option>
            <option value="Positive Adjustment">Positive Adjustment</option>
        </select>

        <label for="product_id">Product ID:</label>
        <input type="text" id="product_id" name="product_id" required readonly>
        <div id="product-dropdown"></div>

        <label for="product_name">Product Name:</label>
        <input type="text" id="product_name" name="product_name" required readonly>

        <label for="quantity">Quantity:</label>
        <input type="number" id="quantity" name="quantity" required min="1">

        <label for="location">Location:</label>
        <input type="text" id="location" name="location" required>

        <label for="unit">Unit:</label>
        <input type="text" id="unit" name="unit" required readonly>
        <div id="unit-dropdown"></div>

        <label for="date">Date:</label>
        <input type="date" id="date" name="date" required>

        <label for="value">Value:</label>
        <input type="number" id="value" name="value" step="0.01" required>

        <input type="submit" value="Submit">
    </form>

    <script>
        $(document).ready(function() {
            // Set default date to today
            $('#date').val(new Date().toISOString().split('T')[0]);

            // Product dropdown
            $('#product_id').on('focus', function() {
                if ($('#product-dropdown').is(':empty')) {
                    $.ajax({
                        url: 'create_item_ledger_history.php',
                        type: 'GET',
                        data: { fetch_products: true },
                        success: function(data) {
                            $('#product-dropdown').html(data).show();
                        }
                    });
                } else {
                    $('#product-dropdown').show();
                }
            });

            // Select product
            $(document).on('click', '.product-item', function() {
                var itemCode = $(this).data('item_code');
                var itemName = $(this).data('item_name');
                $('#product_id').val(itemCode);
                $('#product_name').val(itemName);
                $('#product-dropdown').hide();
                $('#unit').val(''); // Clear unit when product changes
                $('#value').val(''); // Clear value when product changes
            });

            // Filter products
            $('#product_id').on('input', function() {
                var filter = $(this).val().toLowerCase();
                $('.product-item').each(function() {
                    var text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(filter) > -1);
                });
            });

            // Unit dropdown
            $('#unit').on('focus', function() {
                var product_id = $('#product_id').val();
                if (product_id) {
                    $.ajax({
                        url: 'create_item_ledger_history.php',
                        type: 'GET',
                        data: { fetch_units: true, product_id: product_id },
                        success: function(data) {
                            $('#unit-dropdown').html(data).show();
                        }
                    });
                }
            });

            // Select unit
            $(document).on('click', '.unit-item', function() {
                $('#unit').val($(this).data('unit'));
                $('#value').val($(this).data('value'));
                $('#unit-dropdown').hide();
            });

            // Hide dropdowns when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#product_id, #product-dropdown, #unit, #unit-dropdown').length) {
                    $('#product-dropdown, #unit-dropdown').hide();
                }
            });

            // Calculate total value (This section was already present and remains unchanged)
            $('#quantity, #value').on('input', function() {
                var quantity = $('#quantity').val();
                var unitValue = $('#value').val();
                if (quantity && unitValue) {
                    var totalValue = (quantity * unitValue).toFixed(2);
                    $('#value').val(totalValue);
                }
            });

            // Form submission (This section was already present and remains unchanged)
            $('#ledgerForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'create_item_ledger_history.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        alert(response);
                        $('#ledgerForm')[0].reset();
                        $('#date').val(new Date().toISOString().split('T')[0]);
                    }
                });
            });
        });
    </script>
</body>
</html>
