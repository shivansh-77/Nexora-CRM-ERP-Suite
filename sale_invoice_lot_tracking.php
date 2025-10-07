<?php
// Database connection
require_once 'connection.php';

// Get all parameters from URL
$item_code = $_GET['item_code'] ?? '';
$row_number = $_GET['row_number'] ?? 0;
$stock = (float)($_GET['stock'] ?? 0);
$location_code = $_GET['location_code'] ?? '';
$unit_value = (float)($_GET['unit_value'] ?? 1);
$temp_invoice_no = $_GET['temp_invoice_no'] ?? '';

// Validate required fields
if (empty($item_code)) die("Error: Item code not specified.");
if ($stock <= 0) die("Error: Stock quantity must be greater than 0.");
if (empty($location_code)) die("Error: Location code not specified.");
if (empty($temp_invoice_no)) die("Error: Temporary invoice number not provided.");

// Fetch item details
$stmt = $connection->prepare("SELECT item_name, unit_of_measurement_code, sales_price FROM item WHERE item_code = ?");
$stmt->bind_param("s", $item_code);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Error: Item not found.");
$item = $result->fetch_assoc();
$item_name = $item['item_name'];
$unit = $item['unit_of_measurement_code'];
$rate = $item['sales_price'];

// Fetch available lot quantities from item_ledger_history
$availableLots = [];
$stmt = $connection->prepare("
    SELECT
        lot_trackingid,
        expiration_date,
        SUM(quantity) as available_quantity
    FROM item_ledger_history
    WHERE product_id = ? AND location = ?
    GROUP BY lot_trackingid, expiration_date
    HAVING available_quantity > 0
    ORDER BY expiration_date ASC, lot_trackingid ASC
");
$stmt->bind_param("ss", $item_code, $location_code);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $availableLots[] = $row;
}
$stmt->close();

// Fetch existing lot entries for this invoice row
$existingEntries = [];
$stmt = $connection->prepare("
    SELECT quantity, lot_trackingid, expiration_date
    FROM invoice_lot_tracking
    WHERE temp_invoice_no = ? AND row_number = ?
");
$stmt->bind_param("si", $temp_invoice_no, $row_number);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $existingEntries[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lot Tracking - <?= htmlspecialchars($item_name) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { display: flex; justify-content: space-between; max-width: 1200px; }
        .column { width: 48%; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .field { margin-bottom: 15px; }
        label { display: inline-block; width: 150px; font-weight: bold; }
        .value { display: inline-block; padding: 5px 10px; background: #f5f5f5; border-radius: 3px; }
        h2 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .action-buttons { margin-top: 20px; display: flex; justify-content: space-between; }
        .error { color: red; margin-top: 10px; }
        input[type="number"], input[type="text"], input[type="date"] { width: 100%; padding: 5px; }
        .expiring-soon { background-color: #fff3cd; } /* Light yellow for near expiry */
        .expired { background-color: #f8d7da; } /* Light red for expired */
        .refresh-btn { margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Lot Tracking Information</h1>

    <div class="container">
        <div class="column">
            <h2>Invoice Information</h2>
            <div class="field"><label>Item Code:</label><span class="value"><?= htmlspecialchars($item_code) ?></span></div>
            <div class="field"><label>Location:</label><span class="value"><?= htmlspecialchars($location_code) ?></span></div>
            <div class="field"><label>Row Number:</label><span class="value"><?= htmlspecialchars($row_number) ?></span></div>
            <div class="field"><label>Stock Quantity:</label><span class="value"><?= htmlspecialchars($stock) ?> <?= htmlspecialchars($unit) ?></span></div>
        </div>

        <div class="column">
            <h2>Product Information</h2>
            <div class="field"><label>Item Name:</label><span class="value"><?= htmlspecialchars($item_name) ?></span></div>
            <div class="field"><label>Unit:</label><span class="value"><?= htmlspecialchars($unit) ?></span></div>
            <div class="field"><label>Unit Value:</label><span class="value"><?= htmlspecialchars($unit_value) ?></span></div>
            <div class="field"><label>Rate:</label><span class="value"><?= htmlspecialchars($rate) ?></span></div>
        </div>
    </div>

    <div style="margin-top: 30px; clear: both;">
        <h2>Available Stock by Lot</h2>
        <button class="refresh-btn" onclick="window.location.reload()">Refresh Available Lots</button>

        <?php if (!empty($availableLots)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Lot Tracking ID</th>
                        <th>Expiration Date</th>
                        <th>Available Quantity (<?= htmlspecialchars($unit) ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($availableLots as $lot):
                        $expiryClass = '';
                        if (!empty($lot['expiration_date'])) {
                            $expiryDate = new DateTime($lot['expiration_date']);
                            $today = new DateTime();
                            $interval = $today->diff($expiryDate);

                            if ($expiryDate < $today) {
                                $expiryClass = 'expired';
                            } elseif ($interval->days <= 30) {
                                $expiryClass = 'expiring-soon';
                            }
                        }
                    ?>
                        <tr class="<?= $expiryClass ?>">
                            <td><?= htmlspecialchars($lot['lot_trackingid']) ?></td>
                            <td><?= !empty($lot['expiration_date']) ? htmlspecialchars($lot['expiration_date']) : 'N/A' ?></td>
                            <td><?= htmlspecialchars(number_format($lot['available_quantity'], 3)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No available stock found for this item at the selected location.</p>
        <?php endif; ?>
    </div>

    <div style="margin-top: 30px;">
        <h2>Lot Tracking Details</h2>
        <form id="lotTrackingForm">
            <input type="hidden" name="item_code" value="<?= htmlspecialchars($item_code) ?>">
            <input type="hidden" name="row_number" value="<?= htmlspecialchars($row_number) ?>">
            <input type="hidden" name="location_code" value="<?= htmlspecialchars($location_code) ?>">
            <input type="hidden" name="unit_value" value="<?= htmlspecialchars($unit_value) ?>">
            <input type="hidden" name="stock" value="<?= htmlspecialchars($stock) ?>">
            <input type="hidden" name="temp_invoice_no" value="<?= htmlspecialchars($temp_invoice_no) ?>">

            <table id="lotEntries">
                <thead>
                    <tr>
                        <th>Quantity (<?= htmlspecialchars($unit) ?>)</th>
                        <th>Lot Number</th>
                        <th>Expiry Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($existingEntries)): ?>
                        <tr>
                            <td><input type="number" name="quantity[]" step="0.001" min="0.001" required></td>
                            <td><input type="text" name="lot_number[]" required></td>
                            <td><input type="date" name="expiry_date[]"></td>
                            <td><button type="button" class="remove-row">Remove</button></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($existingEntries as $entry): ?>
                            <tr>
                                <td><input type="number" name="quantity[]" step="0.001" min="0.001" value="<?= htmlspecialchars($entry['quantity']) ?>" required></td>
                                <td><input type="text" name="lot_number[]" value="<?= htmlspecialchars($entry['lot_trackingid']) ?>" required></td>
                                <td><input type="date" name="expiry_date[]" value="<?= htmlspecialchars($entry['expiration_date']) ?>"></td>
                                <td><button type="button" class="remove-row">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="action-buttons">
                <button type="button" id="addRow">Add Row</button>
                <button type="button" id="saveEntries">Save All Entries</button>
            </div>

            <div id="errorMessage" class="error"></div>
        </form>
    </div>

    <script>
        document.getElementById('addRow').addEventListener('click', function() {
            const tbody = document.querySelector('#lotEntries tbody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td><input type="number" name="quantity[]" step="0.001" min="0.001" required></td>
                <td><input type="text" name="lot_number[]" required></td>
                <td><input type="date" name="expiry_date[]"></td>
                <td><button type="button" class="remove-row">Remove</button></td>
            `;
            tbody.appendChild(newRow);
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                const row = e.target.closest('tr');
                if (document.querySelectorAll('#lotEntries tbody tr').length > 1) {
                    row.remove();
                } else {
                    alert("You must have at least one lot entry.");
                }
            }
        });

        document.getElementById('saveEntries').addEventListener('click', function() {
            const form = document.getElementById('lotTrackingForm');
            const formData = new FormData(form);
            const errorElement = document.getElementById('errorMessage');
            errorElement.textContent = '';

            // Validate quantities sum matches stock
            const quantityInputs = document.querySelectorAll('input[name="quantity[]"]');
            let totalQuantity = 0;
            quantityInputs.forEach(input => {
                totalQuantity += parseFloat(input.value) || 0;
            });

            if (Math.abs(totalQuantity - <?= $stock ?>) > 0.001) {
                errorElement.textContent = `Total quantity (${totalQuantity.toFixed(3)}) must match stock quantity (<?= $stock ?>)`;
                return;
            }

            // Validate unique lot numbers
            const lotNumbers = [];
            const lotInputs = document.querySelectorAll('input[name="lot_number[]"]');
            let hasDuplicates = false;

            lotInputs.forEach(input => {
                const lotNumber = input.value.trim();
                if (lotNumber && lotNumbers.includes(lotNumber)) {
                    hasDuplicates = true;
                    input.style.border = '1px solid red';
                } else {
                    lotNumbers.push(lotNumber);
                    input.style.border = '';
                }
            });

            if (hasDuplicates) {
                errorElement.textContent = "All lot numbers must be unique. Please correct duplicate entries.";
                return;
            }

            // Validate quantities don't exceed available stock
            const availableLots = <?= json_encode($availableLots) ?>;
            const lotAllocations = {};

            // Sum up allocations per lot
            lotInputs.forEach((input, index) => {
                const lotNumber = input.value.trim();
                const quantity = parseFloat(quantityInputs[index].value) || 0;

                if (lotNumber) {
                    if (!lotAllocations[lotNumber]) {
                        lotAllocations[lotNumber] = 0;
                    }
                    lotAllocations[lotNumber] += quantity;
                }
            });

            // Check against available quantities
            for (const lotNumber in lotAllocations) {
                const allocatedQty = lotAllocations[lotNumber];
                const availableLot = availableLots.find(lot => lot.lot_trackingid === lotNumber);

                if (availableLot) {
                    if (allocatedQty > parseFloat(availableLot.available_quantity)) {
                        errorElement.textContent = `Allocated quantity (${allocatedQty}) exceeds available quantity (${availableLot.available_quantity}) for lot ${lotNumber}`;
                        return;
                    }
                } else {
                    errorElement.textContent = `Lot ${lotNumber} not found in available stock`;
                    return;
                }
            }

            // Send AJAX request to save data
            fetch('save_sale_invoice_lot_tracking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.history.back(); // Go back to the previous page
                } else {
                    errorElement.textContent = data.message || 'Error saving data';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorElement.textContent = 'An error occurred while saving the data.';
            });
        });
    </script>
</body>
</html>
