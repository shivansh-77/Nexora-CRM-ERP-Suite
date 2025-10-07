<?php
// Start the session to store the previous page URL
session_start();

// Database connection
include('connection.php');

// Function to fetch the main invoice ID from invoice_items
function fetchMainInvoiceId($connection, $invoiceItemId) {
    $stmt = $connection->prepare("SELECT invoice_id FROM invoice_items WHERE id = ?");
    $stmt->bind_param("i", $invoiceItemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoiceData = $result->fetch_assoc();
    $stmt->close();
    return $invoiceData ? $invoiceData['invoice_id'] : null;
}

// Function to fetch the shipper location code from invoices using the main invoice ID
function fetchShipperLocationCode($connection, $mainInvoiceId) {
    $stmt = $connection->prepare("SELECT shipper_location_code FROM invoices WHERE id = ?");
    $stmt->bind_param("i", $mainInvoiceId);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoiceData = $result->fetch_assoc();
    $stmt->close();
    return $invoiceData ? $invoiceData['shipper_location_code'] : null;
}

// Function to insert data into purchase_order_item_lots table
function insertLedgerHistory($connection, $quantity, $lotNumber, $expirationDate, $invoiceItemData, $mainInvoiceId, $invoiceId) {
    $documentType = 'Sale';
    $entryType = 'Sales Invoice';
    $productId = (string)$invoiceItemData['product_id'];
    $productName = $invoiceItemData['product_name'];
    $unit = $invoiceItemData['unit'];
    $value = $invoiceItemData['value'];
    $rate = $invoiceItemData['rate'];
    $date = date('Y-m-d H:i:s');
    $negativeQuantity = -$quantity;

    // Fetch the shipper location code using the main invoice ID
    $shipperLocationCode = fetchShipperLocationCode($connection, $mainInvoiceId);

    if ($shipperLocationCode === null) {
        echo "Error: Shipper location code cannot be null.";
        exit;
    }

    $stmt = $connection->prepare("INSERT INTO purchase_order_item_lots
        (document_type, entry_type, product_id, product_name, quantity, unit, date, value, invoice_itemid, lot_trackingid, expiration_date, rate, invoice_id_main, location)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Corrected bind_param format string and parameters
    $stmt->bind_param(
        "ssssisssisssis", // 14 characters for 14 parameters
        $documentType,     // s
        $entryType,        // s
        $productId,        // s
        $productName,     // s
        $negativeQuantity, // i (integer)
        $unit,             // s
        $date,             // s
        $value,            // s
        $invoiceId,        // i (integer)
        $lotNumber,        // s
        $expirationDate,  // s
        $rate,            // s (decimal is treated as string)
        $mainInvoiceId,    // i (integer)
        $shipperLocationCode // s
    );

    if (!$stmt->execute()) {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Function to fetch invoice item data
function fetchInvoiceItemData($connection, $invoiceId) {
    $stmt = $connection->prepare("SELECT product_name, product_id, unit, value, quantity, rate, stock, gst, igst, cgst, sgst, amount FROM invoice_items WHERE id = ?");
    $stmt->bind_param("i", $invoiceId);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoiceItemData = $result->fetch_assoc();
    $stmt->close();

    // Debugging output
    if (!$invoiceItemData) {
        echo "Error: Invoice item data not found for invoice ID $invoiceId.<br>";
    }

    return $invoiceItemData;
}

// Function to fetch invoice data
function fetchInvoiceData($connection, $invoiceId) {
    $stmt = $connection->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->bind_param("i", $invoiceId);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoiceData = $result->fetch_assoc();
    $stmt->close();
    return $invoiceData;
}

// Function to fetch existing entries from purchase_order_item_lots
function fetchExistingEntries($connection, $invoiceItemId) {
    $stmt = $connection->prepare("SELECT quantity, lot_trackingid, expiration_date FROM purchase_order_item_lots WHERE invoice_itemid = ?");
    $stmt->bind_param("i", $invoiceItemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingEntries = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $existingEntries;
}

// Function to calculate the sum of quantities of existing entries
function calculateTotalQuantity($existingEntries) {
    $totalQuantity = 0;
    foreach ($existingEntries as $entry) {
        $totalQuantity += $entry['quantity'];
    }
    return $totalQuantity;
}

// Function to fetch the total sum of quantities for each distinct lot number
function fetchTotalQuantitiesByLot($connection, $productId, $shipperLocationCode) {
    $stmt = $connection->prepare("
        SELECT expiration_date , lot_trackingid, SUM(quantity) as total_quantity
        FROM item_ledger_history
        WHERE product_id = ? AND location = ?
        GROUP BY lot_trackingid
        HAVING total_quantity > 0
    ");
    $stmt->bind_param("ss", $productId, $shipperLocationCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $lotQuantities = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $lotQuantities;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoiceId = $_POST['id'];
    $stock = $_POST['stock'];
    $quantities = $_POST['quantity'];
    $lotNumbers = $_POST['lot_number'];
    $expirationDates = $_POST['expiration_date'];

    $totalQuantity = 0;
    $lotNumbersArray = [];

    // Fetch invoice item data
    $invoiceItemData = fetchInvoiceItemData($connection, $invoiceId);

    // Fetch the main invoice ID
    $mainInvoiceId = fetchMainInvoiceId($connection, $invoiceId);

    // Check if mainInvoiceId is null
    if ($mainInvoiceId === null) {
        echo "Error: Main invoice ID cannot be null.";
        exit;
    }

    foreach ($quantities as $index => $quantity) {
        $totalQuantity += $quantity;
        $lotNumber = $lotNumbers[$index];
        $expirationDate = $expirationDates[$index];

        // Check for unique lot numbers
        if (in_array($lotNumber, $lotNumbersArray)) {
            echo "Error: Lot numbers must be unique.";
            exit;
        }

        $lotNumbersArray[] = $lotNumber;
    }

    // Check if total quantity matches stock
    if ($totalQuantity != $stock) {
        echo "<script>alert('Error: Total quantity must be equal to the stock.');</script>";
        exit;
    }

    foreach ($quantities as $index => $quantity) {
        $lotNumber = $lotNumbers[$index];
        $expirationDate = $expirationDates[$index];

        // Insert data into the purchase_order_item_lots table
        insertLedgerHistory($connection, $quantity, $lotNumber, $expirationDate, $invoiceItemData, $mainInvoiceId, $invoiceId);
    }

    // Redirect back to the previous page with a success parameter
    $previousPage = isset($_SESSION['previous_page']) ? $_SESSION['previous_page'] : 'default_page.php';
    header("Location: $previousPage?success=true");
    exit;
}

// Store the current page as the previous page
$_SESSION['previous_page'] = $_SERVER['HTTP_REFERER'];

// Fetch product details from the URL parameters
$id = $_GET['id'];
$name = $_GET['name'];
$unit = $_GET['unit'];
$stock = $_GET['stock'];

// Fetch invoice data
$invoiceId = fetchMainInvoiceId($connection, $id);
$invoiceData = fetchInvoiceData($connection, $invoiceId);
$invoiceItemData = fetchInvoiceItemData($connection, $id);

// Fetch existing entries
$existingEntries = fetchExistingEntries($connection, $id);
$existingLots = $existingEntries; // Assuming $existingLots is used in the HTML part

// Calculate the total quantity of existing entries
$totalExistingQuantity = calculateTotalQuantity($existingEntries);

// Fetch the shipper location code using the main invoice ID
$shipperLocationCode = fetchShipperLocationCode($connection, $invoiceId);

// Fetch the total sum of quantities for each distinct lot number
$lotQuantities = fetchTotalQuantitiesByLot($connection, $invoiceItemData['product_id'], $shipperLocationCode);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Lot Tracking</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #2c3e50;
        }
        .container {
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 80%;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header img {
            max-width: 150px;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-info p {
            margin: 5px 0;
        }
        .product-details {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .details-section {
            width: 50%;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .details-section div {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .table-container {
            margin-bottom: 20px;
        }
        .table-section {
            width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .input-row {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .input-row input {
            margin-right: 10px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .input-row button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            background-color: #007BFF;
            color: #fff;
            cursor: pointer;
        }
        .input-row button:hover {
            background-color: #0056b3;
        }
        .close-button {
      position: absolute;
      top: 20px;
      right: 121px;
      font-size: 14px;
      font-style: bold;
      cursor: pointer;
      color: #fff;
      background-color: #333;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
  }

  .close-button:hover {
      background-color: #555;
  }
    </style>
</head>
<body>
    <div class="container">
      <div class="header">
    <?php
    // Fetch company logo from database
    $query = "SELECT company_logo FROM company_card WHERE id = 1"; // Change `1` to the correct company ID
    $result = mysqli_query($connection, $query);
    $company = mysqli_fetch_assoc($result);

    // Set logo path (fallback to default if not available)
    $company_logo = !empty($company['company_logo']) ? $company['company_logo'] : 'uploads/default_logo.png';

    // Fetch the invoice_id from invoice_items using the id parameter from the URL
    $id = $_GET['id'];
    $invoiceIdQuery = "SELECT invoice_id FROM invoice_items WHERE id = ?";
    $stmt = $connection->prepare($invoiceIdQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $invoiceIdResult = $stmt->get_result();
    $invoiceIdData = $invoiceIdResult->fetch_assoc();
    $invoiceId = $invoiceIdData ? $invoiceIdData['invoice_id'] : null;
    $stmt->close();
    ?>
    <img src="<?php echo $company_logo; ?>" alt="Logo">
    <div class="invoice-info">
        <p><strong>Invoice No:</strong> <?php echo htmlspecialchars($invoiceData['invoice_no']); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($invoiceData['invoice_date']); ?></p>
    </div>
    <!-- Cross symbol to go back -->
    <div class="close-button" onclick="goBack(<?php echo htmlspecialchars(json_encode($invoiceId)); ?>)">✕</div>
</div>
        <div class="product-details">
            <div class="details-section">
                <div><strong>Product Name:</strong> <?php echo htmlspecialchars($invoiceItemData['product_name']); ?></div>
                <div><strong>Product ID:</strong> <?php echo htmlspecialchars($invoiceItemData['product_id']); ?></div>
                <div><strong>Unit:</strong> <?php echo htmlspecialchars($invoiceItemData['unit']); ?></div>
                <div><strong>Value:</strong> <?php echo htmlspecialchars($invoiceItemData['value']); ?></div>
                <div><strong>Quantity:</strong> <?php echo htmlspecialchars($invoiceItemData['quantity']); ?></div>
                <div><strong>Rate:</strong> <?php echo htmlspecialchars($invoiceItemData['rate']); ?></div>
            </div>
            <div class="details-section">
                <div><strong>Stock:</strong> <?php echo htmlspecialchars($invoiceItemData['stock']); ?></div>
                <div><strong>GST (%):</strong> <?php echo htmlspecialchars($invoiceItemData['gst']); ?></div>
                <div><strong>IGST:</strong> <?php echo htmlspecialchars($invoiceItemData['igst']); ?></div>
                <div><strong>CGST:</strong> <?php echo htmlspecialchars($invoiceItemData['cgst']); ?></div>
                <div><strong>SGST:</strong> <?php echo htmlspecialchars($invoiceItemData['sgst']); ?></div>
                <div><strong>Amount:</strong> <?php echo htmlspecialchars($invoiceItemData['amount']); ?></div>
            </div>
        </div>

        <!-- Display the total sum of quantities for each distinct lot number -->
        <?php if (!empty($lotQuantities)): ?>
            <div class="table-container">
                <h3>Total Quantities by Lot Number:</h3>
                <div style='border: 1px solid #000; padding: 10px; margin-top: 20px;'>
                    <table border='1'>
                        <tr><th>Lot Number</th><th>Total Quantity</th><th>Expiration Date</th></tr>
                        <?php foreach ($lotQuantities as $lot): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lot['lot_trackingid']); ?></td>
                                <td><?php echo htmlspecialchars($lot['total_quantity']); ?></td>
                                <td><?php echo htmlspecialchars($lot['expiration_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <p>No lot quantities found.</p>
        <?php endif; ?>

        <div class="container">
            <h2>Lot Tracking Information</h2>
            <form id="lotForm" method="post">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                <input type="hidden" name="stock" value="<?php echo htmlspecialchars($stock); ?>">
                <div id="inputRows">
                    <?php if (!empty($existingLots)): ?>
                        <?php foreach ($existingLots as $lot): ?>
                            <div class="input-row">
                                <input type="number" name="quantity[]" value="<?php echo htmlspecialchars($lot['quantity']); ?>" placeholder="Quantity" required>
                                <input type="text" name="lot_number[]" value="<?php echo htmlspecialchars($lot['lot_trackingid']); ?>" placeholder="Lot Tracking ID" required>
                                <input type="date" name="expiration_date[]" value="<?php echo htmlspecialchars($lot['expiration_date']); ?>" required>
                                <button type="button" onclick="removeRow(this)">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="input-row">
                            <input type="number" name="quantity[]" placeholder="Quantity" required>
                            <input type="text" name="lot_number[]" placeholder="Lot Tracking ID" required>
                            <input type="date" name="expiration_date[]" required>
                            <button type="button" onclick="removeRow(this)">Remove</button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" id="addRowButton" onclick="addRow()">Add Row</button>
                <button type="submit">Save</button>
            </form>
        </div>
    </div>

    <script>
        const stock = <?php echo htmlspecialchars($stock); ?>;
        const totalExistingQuantity = <?php echo htmlspecialchars($totalExistingQuantity); ?>;

        function addRow() {
            if (totalExistingQuantity >= stock) {
                alert("Stock is already completely allocated with lot tracking.");
                return;
            }

            const inputRows = document.getElementById('inputRows');
            const newRow = document.createElement('div');
            newRow.className = 'input-row';
            newRow.innerHTML = `
                <input type="number" name="quantity[]" placeholder="Quantity" required>
                <input type="text" name="lot_number[]" placeholder="Lot Tracking ID" required>
                <input type="date" name="expiration_date[]" required>
                <button type="button" onclick="removeRow(this, false)">Remove</button>
            `;
            inputRows.appendChild(newRow);
        }

        function removeRow(button, isFetchedEntry = true) {
            const row = button.parentNode;
            const lotNumber = row.querySelector('input[name="lot_number[]"]').value;

            // Ask for confirmation before removing
            const userConfirmed = confirm("Are you sure you want to delete this entry?");
            if (!userConfirmed) {
                return;
            }

            if (isFetchedEntry) {
                // Send AJAX request to remove_sales_lot.php
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "remove_sales_lot.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        if (xhr.responseText === "success") {
                            row.parentNode.removeChild(row);
                            alert("Entry removed successfully.");
                            // Optionally, update the totalExistingQuantity here
                        } else {
                            alert("Error removing entry: " + xhr.responseText);
                        }
                    }
                };
                xhr.send("lot_number=" + encodeURIComponent(lotNumber) + "&invoice_item_id=" + <?php echo htmlspecialchars($id); ?>);
            } else {
                row.parentNode.removeChild(row);
            }
        }

        document.getElementById('lotForm').onsubmit = function(event) {
            const quantities = document.getElementsByName('quantity[]');
            const lotNumbers = document.getElementsByName('lot_number[]');
            let totalQuantity = totalExistingQuantity;
            const lotNumbersArray = [];

            for (let i = 0; i < quantities.length; i++) {
                totalQuantity += parseInt(quantities[i].value);
                if (lotNumbersArray.includes(lotNumbers[i].value)) {
                    alert("Lot numbers must be unique.");
                    event.preventDefault();
                    return false;
                }
                lotNumbersArray.push(lotNumbers[i].value);
            }

            if (totalQuantity !== stock) {
                alert("Total quantity must be equal to the stock.");
                event.preventDefault();
                return false;
            }
        }

        // Disable the "Add Row" button if the stock is reached or exceeded
        if (totalExistingQuantity == -stock) {
            document.getElementById('addRowButton').disabled = true;
            alert("Cannot add more rows. Total quantity has reached or exceeded the stock.");
        }

        function goBack(invoiceId) {
     if (invoiceId !== null) {
         // Redirect to invoice.php with the invoice_id
         window.location.href = 'invoice.php?id=' + invoiceId;
     } else {
         alert("Error: Unable to retrieve invoice ID.");
     }
 }
    </script>

</body>
</html>

<?php
$connection->close();
?>
