<?php
include 'connection.php';

// Initialize success message variable
$success_message = '';

// Get parameters from URL
$invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;
$invoice_item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';

// Handle lot deletion via AJAX
if(isset($_POST['delete_lot']) && isset($_POST['lot_id'])) {
    $lot_id = intval($_POST['lot_id']);
    $delete_query = "DELETE FROM purchase_order_item_lots WHERE id = ?";
    $stmt_delete = $connection->prepare($delete_query);
    $stmt_delete->bind_param("i", $lot_id);
    $result = $stmt_delete->execute();

    if($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $connection->error]);
    }
    exit;
}

// Fetch main item details
$item_query = "SELECT ii.*, i.invoice_no, i.shipper_location_code
               FROM invoice_items ii
               JOIN invoices i ON ii.invoice_id = i.id
               WHERE ii.id = ?";
$stmt_item = $connection->prepare($item_query);
$stmt_item->bind_param("i", $invoice_item_id);
$stmt_item->execute();
$item_result = $stmt_item->get_result();
$item = $item_result->fetch_assoc();

// Fetch existing lot entries
$existing_lots = [];
$lots_query = "SELECT * FROM purchase_order_item_lots
               WHERE invoice_itemid = ? AND document_type = 'Sale' ORDER BY id";
$stmt_lots = $connection->prepare($lots_query);
$stmt_lots->bind_param("i", $invoice_item_id);
$stmt_lots->execute();
$lots_result = $stmt_lots->get_result();
while ($row = $lots_result->fetch_assoc()) {
    $existing_lots[] = $row;
}

// Fetch available lots for this product from item_ledger_history table
$available_lots = [];
if (!empty($product_id)) {
    // Query to get available quantities by lot number AND expiration date from item_ledger_history
    $available_lots_query = "
        SELECT
            lot_trackingid,
            expiration_date,
            SUM(CASE WHEN document_type = 'Purchase' THEN quantity ELSE quantity END) as available_quantity
        FROM
            item_ledger_history
        WHERE
            product_id = ?
        GROUP BY
            lot_trackingid, expiration_date
        HAVING
            SUM(CASE WHEN document_type = 'Purchase' THEN quantity ELSE quantity END) > 0
        ORDER BY
            available_quantity ASC
    ";

    $stmt_available = $connection->prepare($available_lots_query);
    $stmt_available->bind_param("s", $product_id);
    $stmt_available->execute();
    $available_result = $stmt_available->get_result();

    while ($row = $available_result->fetch_assoc()) {
        $available_lots[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['delete_lot'])) {
    $quantities = $_POST['quantity'];
    $lot_numbers = $_POST['lot_number'];
    $expiry_dates = $_POST['expiry_date'];
    $lot_ids = isset($_POST['lot_id']) ? $_POST['lot_id'] : [];

    // Validate total quantity matches item quantity
    $total_quantity = array_sum($quantities);
    if ($total_quantity != $item['stock']) {
        $error = "Error: Total quantity ($total_quantity) must match item quantity ({$item['stock']})";
    }
    // Check for duplicate lot numbers
    else if (count($lot_numbers) != count(array_unique($lot_numbers))) {
        $error = "Error: All lot numbers must be unique";
    }
    else {
        // Validate lot numbers, expiration dates, and quantities
        $validation_error = false;
        for ($i = 0; $i < count($lot_numbers); $i++) {
            $lot_number = $lot_numbers[$i];
            $expiry_date = $expiry_dates[$i];
            $quantity = $quantities[$i];

            // Find matching lot in available lots
            $lot_found = false;
            $valid_quantity = false;
            $valid_expiry = false;

            foreach ($available_lots as $lot) {
                if ($lot['lot_trackingid'] == $lot_number) {
                    $lot_found = true;

                    // If expiry date is provided and not empty, check if it matches
                    if (!empty($expiry_date)) {
                        if ($lot['expiration_date'] == $expiry_date) {
                            $valid_expiry = true;
                            // Check if quantity is valid
                            if ($quantity <= $lot['available_quantity']) {
                                $valid_quantity = true;
                            }
                        }
                    } else {
                        // If no expiry date provided, skip expiry validation
                        $valid_expiry = true;
                        // Check if quantity is valid
                        if ($quantity <= $lot['available_quantity']) {
                            $valid_quantity = true;
                        }
                    }

                    // If we found a valid match, no need to check other lots
                    if ($valid_expiry && $valid_quantity) {
                        break;
                    }
                }
            }

            if (!$lot_found) {
                $error = "Error: Lot number '$lot_number' not found in available lots.";
                $validation_error = true;
                break;
            } else if (!$valid_expiry && !empty($expiry_date)) {
                $error = "Error: Expiration date for lot '$lot_number' does not match available lot.";
                $validation_error = true;
                break;
            } else if (!$valid_quantity) {
                $error = "Error: Quantity for lot '$lot_number' exceeds available quantity.";
                $validation_error = true;
                break;
            }
        }

        if (!$validation_error) {
            // Delete old entries (if any)
            $delete_query = "DELETE FROM purchase_order_item_lots WHERE invoice_itemid = ?";
            $stmt_delete = $connection->prepare($delete_query);
            $stmt_delete->bind_param("i", $invoice_item_id);
            $stmt_delete->execute();

            // Insert new entries
            $success = true;
            for ($i = 0; $i < count($quantities); $i++) {
                $document_type = 'Sale';
                $entry_type = 'Sales Invoice';
                $product_id = $item['product_id'];
                $product_name = $item['product_name'];
                $quantity = $quantities[$i];
                $location = $item['shipper_location_code'];
                $unit = $item['unit'];
                $value = $item['value'];
                $lot_trackingid = $lot_numbers[$i];
                $expiration_date = $expiry_dates[$i];
                $rate = $item['rate'];

                $insert_query = "INSERT INTO purchase_order_item_lots
                                (document_type, entry_type, product_id, product_name, quantity,
                                location, unit, date, value, invoice_itemid, lot_trackingid,
                                expiration_date, rate, invoice_id_main)
                                VALUES (
                                    '$document_type',
                                    '$entry_type',
                                    '$product_id',
                                    '$product_name',
                                    $quantity,
                                    '$location',
                                    '$unit',
                                    NOW(),
                                    $value,
                                    $invoice_item_id,
                                    '$lot_trackingid',
                                    '$expiration_date',
                                    $rate,
                                    $invoice_id
                                )";

                if (!$connection->query($insert_query)) {
                    $error = "Error inserting lot details: " . $connection->error;
                    $success = false;
                    break;
                }
            }

            if ($success) {
    echo '<script type="text/javascript">
            alert("Lot details allocated successfully!");
            window.location.href = "sale_lot_tracking_form_display.php?id=' . $invoice_id . '";
          </script>';
    exit();
}
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Lot Details - Sales</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
            color: #333;
        }
        h2 {
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .info-container {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-container h3 {
            margin-top: 0;
            color: #3498db;
        }
        .info-container p {
            margin: 8px 0;
        }
        .form-container {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th {
            background-color: #f2f2f2;
            padding: 10px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        input[type="text"],
        input[type="number"],
        input[type="date"] {
            padding: 8px;
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .remove-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .remove-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        .add-btn {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .submit-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .button-container {
            margin-top: 20px;
        }
        .success-message {
            color: #2ecc71;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .error-message {
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .available-lots-container {
            background: #f0f8ff;
            border: 1px solid #b0c4de;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .use-lot-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .expiring-soon {
            color: #ff0000;
            font-weight: bold;
            text-shadow: 0 0 1px rgba(255, 0, 0, 0.3);
            animation: glow 1.5s ease-in-out infinite alternate;
        }
        @keyframes glow {
            from {
                text-shadow: 0 0 1px rgba(255, 0, 0, 0.3);
            }
            to {
                text-shadow: 0 0 3px rgba(255, 0, 0, 0.7);
            }
        }
    </style>
</head>

  <body>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h2 style="margin: 0;">Add Lot Details - Sales</h2>
      <button type="button" id="crossBtn" class="submit-btn" style="background: #e74c3c;">Back</button>
  </div>

  <script>
      document.getElementById('crossBtn').addEventListener('click', function() {
          // Assuming you have the invoice_id available in JavaScript
          var invoiceId = '<?php echo isset($invoice_id) ? htmlspecialchars($invoice_id) : ""; ?>';
          window.location.href = 'sale_lot_tracking_form_display.php?id=' + encodeURIComponent(invoiceId);
      });
  </script>

  <?php if (!empty($success_message)): ?>
      <div class="success-message"><?php echo $success_message; ?></div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
      <div class="error-message"><?php echo $error; ?></div>
  <?php endif; ?>

      <div class="info-container">
          <h3>Product Information</h3>
          <p><strong>Product Name:</strong> <?= htmlspecialchars($item['product_name']) ?></p>
          <p><strong>Product ID:</strong> <?= htmlspecialchars($item['product_id']) ?></p>
          <p><strong>Unit:</strong> <?= htmlspecialchars($item['unit']) ?></p>
          <p><strong>Rate:</strong> <?= htmlspecialchars($item['rate']) ?></p>
          <p><strong>Quantity to Handle:</strong> <?= htmlspecialchars($item['stock']) ?></p>
          <p><strong>Location Code:</strong> <?= htmlspecialchars($item['shipper_location_code']) ?></p>
          <p><strong>Value:</strong> <?= htmlspecialchars($item['value']) ?></p>
      </div>

      <?php if (!empty($available_lots)): ?>
      <div class="available-lots-container">
          <h3>Available Lots</h3>
          <p>Select from the following available lots:</p>
          <table id="availableLotsTable">
              <thead>
                  <tr>
                      <th>Lot Number</th>
                      <th>Available Quantity</th>
                      <th>Expiration Date</th>
                      <th>Action</th>
                  </tr>
              </thead>
              <tbody>
                  <?php
                  // Get current date and calculate dates for comparison
                  $currentDate = new DateTime();
                  $nextMonth = clone $currentDate;
                  $nextMonth->modify('+1 month');
                  $nextNextMonth = clone $currentDate;
                  $nextNextMonth->modify('+2 months');

                  foreach ($available_lots as $lot):
                      $expiryDate = !empty($lot['expiration_date']) ? new DateTime($lot['expiration_date']) : null;
                      $isExpiringSoon = ($expiryDate && $expiryDate <= $nextNextMonth);
                      $expiryClass = $isExpiringSoon ? 'expiring-soon' : '';

                      // Create a unique identifier for this lot (lot number + expiry date)
                      $lotKey = $lot['lot_trackingid'] . '-' . ($lot['expiration_date'] ?? 'noexpiry');
                  ?>
                  <tr data-lot="<?= htmlspecialchars($lot['lot_trackingid']) ?>"
                      data-expiry="<?= htmlspecialchars($lot['expiration_date'] ?? '') ?>"
                      data-quantity="<?= htmlspecialchars($lot['available_quantity']) ?>"
                      data-lot-key="<?= htmlspecialchars($lotKey) ?>">
                      <td><?= htmlspecialchars($lot['lot_trackingid']) ?></td>
                      <td><?= htmlspecialchars($lot['available_quantity']) ?></td>
                      <td class="<?= $expiryClass ?>"><?= htmlspecialchars($lot['expiration_date'] ?? 'N/A') ?></td>
                      <td>
                          <button type="button" class="use-lot-btn"
                              data-lot="<?= htmlspecialchars($lot['lot_trackingid']) ?>"
                              data-expiry="<?= htmlspecialchars($lot['expiration_date'] ?? '') ?>"
                              data-quantity="<?= htmlspecialchars($lot['available_quantity']) ?>"
                              data-lot-key="<?= htmlspecialchars($lotKey) ?>">
                              Use This Lot
                          </button>
                      </td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
          </table>
      </div>
      <?php endif; ?>

      <div class="form-container">
          <h3>Lot Tracking Details</h3>
          <form method="post" id="lotForm">
              <table id="lotTable">
                  <thead>
                      <tr>
                          <th>Quantity</th>
                          <th>Lot Number</th>
                          <th>Expiration Date</th>
                          <th>Action</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php foreach ($existing_lots as $lot): ?>
                      <tr data-lot-id="<?= htmlspecialchars($lot['id']) ?>" class="existing-lot">
                          <td><input type="number" name="quantity[]" value="<?= htmlspecialchars($lot['quantity']) ?>" required></td>
                          <td><input type="text" name="lot_number[]" value="<?= htmlspecialchars($lot['lot_trackingid']) ?>" required></td>
                          <td><input type="date" name="expiry_date[]" value="<?= htmlspecialchars($lot['expiration_date']) ?>" required></td>
                          <td>
                              <input type="hidden" name="lot_id[]" value="<?= htmlspecialchars($lot['id']) ?>">
                              <button type="button" class="remove-btn" data-lot-id="<?= htmlspecialchars($lot['id']) ?>"
                                  data-lot="<?= htmlspecialchars($lot['lot_trackingid']) ?>"
                                  data-expiry="<?= htmlspecialchars($lot['expiration_date'] ?? '') ?>"
                                  data-quantity="<?= htmlspecialchars($lot['quantity']) ?>"
                                  data-lot-key="<?= htmlspecialchars($lot['lot_trackingid'] . '-' . ($lot['expiration_date'] ?? 'noexpiry')) ?>">Remove</button>
                          </td>
                      </tr>
                      <?php endforeach; ?>

                      <?php if (empty($existing_lots)): ?>
                      <tr>
                          <td><input type="number" name="quantity[]" step="0.01" required></td>
                          <td><input type="text" name="lot_number[]" required></td>
                          <td><input type="date" name="expiry_date[]" required></td>
                          <td><button type="button" class="remove-btn">Remove</button></td>
                      </tr>
                      <?php endif; ?>
                  </tbody>
              </table>

              <div class="button-container">
                  <button type="button" id="addRowBtn" class="add-btn">Add Row</button>
                  <button type="submit" class="submit-btn">Submit</button>
              </div>
          </form>
      </div>
    <script>
           // Store available lots in JavaScript for easy manipulation
           const availableLots = <?= json_encode($available_lots) ?>;

           // Track allocated quantities for each lot
           const allocatedQuantities = {};

           // Initialize allocated quantities from existing form data
           function initializeAllocatedQuantities() {
               document.querySelectorAll('#lotTable tbody tr').forEach(row => {
                   const lotNumber = row.querySelector('input[name="lot_number[]"]').value;
                   const expiryDate = row.querySelector('input[name="expiry_date[]"]').value || 'noexpiry';
                   const quantity = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;

                   if (lotNumber) {
                       const lotKey = lotNumber + '-' + expiryDate;
                       allocatedQuantities[lotKey] = (allocatedQuantities[lotKey] || 0) + quantity;
                   }
               });
           }

           // Initialize on page load
           initializeAllocatedQuantities();

           // Function to update available lots table
           function updateAvailableLots() {
               // Reset all quantities to original values
               availableLots.forEach(lot => {
                   const lotKey = lot.lot_trackingid + '-' + (lot.expiration_date || 'noexpiry');
                   const row = document.querySelector(`#availableLotsTable tr[data-lot-key="${lotKey}"]`);

                   if (row) {
                       const originalQuantity = parseFloat(lot.available_quantity);
                       const allocatedQuantity = allocatedQuantities[lotKey] || 0;
                       const remainingQuantity = Math.max(0, originalQuantity - allocatedQuantity);

                       // Update the quantity displayed
                       row.querySelector('td:nth-child(2)').textContent = remainingQuantity.toString();

                       // Update data attributes
                       row.dataset.quantity = remainingQuantity.toString();
                       row.querySelector('.use-lot-btn').dataset.quantity = remainingQuantity.toString();

                       // Hide row if no quantity left
                       if (remainingQuantity <= 0) {
                           row.style.display = 'none';
                       } else {
                           row.style.display = '';
                       }
                   }
               });

               // Sort the table by quantity
               sortTableByQuantity();
           }

           // Function to sort the available lots table by quantity
           function sortTableByQuantity() {
               const tbody = document.querySelector('#availableLotsTable tbody');
               const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.style.display !== 'none');

               rows.sort((a, b) => {
                   const quantityA = parseFloat(a.dataset.quantity);
                   const quantityB = parseFloat(b.dataset.quantity);
                   return quantityA - quantityB;
               });

               // Clear the table and add sorted rows
               while (tbody.firstChild) {
                   tbody.removeChild(tbody.firstChild);
               }

               rows.forEach(row => {
                   tbody.appendChild(row);
               });
           }

           // Handler for "Use This Lot" button
           function useLotHandler() {
               const lotNumber = this.dataset.lot;
               const expiryDate = this.dataset.expiry;
               const lotKey = this.dataset.lotKey;
               const availableQuantity = parseFloat(this.dataset.quantity);

               // Get the remaining quantity needed for the sale
              const totalNeeded = <?= $item['stock'] ?>;
               let currentTotal = 0;

               document.querySelectorAll('input[name="quantity[]"]').forEach(input => {
                   currentTotal += parseFloat(input.value) || 0;
               });

               const remaining = totalNeeded - currentTotal;

               // If we still need to allocate quantity
               if (remaining > 0) {
                   // Calculate how much to take from this lot
                   const quantityToUse = Math.min(availableQuantity, remaining);

                   // Check if this lot is already in the table
                   let lotExists = false;
                   let existingRow = null;

                   document.querySelectorAll('input[name="lot_number[]"]').forEach((input, index) => {
                       const row = input.closest('tr');
                       const rowExpiryInput = row.querySelector('input[name="expiry_date[]"]');
                       const rowExpiryDate = rowExpiryInput.value;

                       if (input.value === lotNumber && rowExpiryDate === expiryDate) {
                           lotExists = true;
                           existingRow = row;
                       }
                   });

                   // If lot doesn't exist in the table, add a new row
                   if (!lotExists) {
                       // If there's only one empty row, use it instead of adding a new one
                       const rows = document.querySelectorAll('#lotTable tbody tr');
                       if (rows.length === 1) {
                           const inputs = rows[0].querySelectorAll('input');
                           if (!inputs[0].value && !inputs[1].value && !inputs[2].value) {
                               inputs[0].value = quantityToUse;
                               inputs[1].value = lotNumber;
                               inputs[2].value = expiryDate;

                               // Update the remove button to include lot data
                               const removeBtn = rows[0].querySelector('.remove-btn');
                               removeBtn.dataset.lot = lotNumber;
                               removeBtn.dataset.expiry = expiryDate;
                               removeBtn.dataset.quantity = quantityToUse;
                               removeBtn.dataset.lotKey = lotKey;

                               // Add event listener for quantity change
                               inputs[0].addEventListener('input', quantityChangeHandler);

                               // Update allocated quantities
                               allocatedQuantities[lotKey] = (allocatedQuantities[lotKey] || 0) + quantityToUse;

                               // Update available lots table
                               updateAvailableLots();
                               return;
                           }
                       }

                       // Otherwise add a new row
                       const tbody = document.querySelector('#lotTable tbody');
                       const newRow = document.createElement('tr');
                       newRow.innerHTML = `
                           <td><input type="number" name="quantity[]" value="${quantityToUse}" step="0.01" required></td>
                           <td><input type="text" name="lot_number[]" value="${lotNumber}" required></td>
                           <td><input type="date" name="expiry_date[]" value="${expiryDate}" required></td>
                           <td><button type="button" class="remove-btn" data-lot="${lotNumber}" data-expiry="${expiryDate}" data-quantity="${quantityToUse}" data-lot-key="${lotKey}">Remove</button></td>
                       `;
                       tbody.appendChild(newRow);

                       // Add event listener to new remove button
                       newRow.querySelector('.remove-btn').addEventListener('click', removeRowHandler);

                       // Add event listener for quantity change
                       newRow.querySelector('input[name="quantity[]"]').addEventListener('input', quantityChangeHandler);

                       // Update allocated quantities
                       allocatedQuantities[lotKey] = (allocatedQuantities[lotKey] || 0) + quantityToUse;
                   } else {
                       // Update existing row
                       const quantityInput = existingRow.querySelector('input[name="quantity[]"]');
                       const currentQty = parseFloat(quantityInput.value) || 0;
                       const newQty = currentQty + quantityToUse;
                       quantityInput.value = newQty;

                       // Update allocated quantities
                       allocatedQuantities[lotKey] = (allocatedQuantities[lotKey] || 0) + quantityToUse;
                   }

                   // Update available lots table
                   updateAvailableLots();
               } else {
                   alert('The total quantity has already been allocated. Remove some entries to use this lot.');
               }
           }

           // Handler for quantity change in input fields
           function quantityChangeHandler() {
               const row = this.closest('tr');
               const lotNumber = row.querySelector('input[name="lot_number[]"]').value;
               const expiryDate = row.querySelector('input[name="expiry_date[]"]').value || 'noexpiry';
               const lotKey = lotNumber + '-' + expiryDate;

               // Get the old and new quantities
               const oldQuantity = parseFloat(row.querySelector('.remove-btn').dataset.quantity) || 0;
               const newQuantity = parseFloat(this.value) || 0;

               // Update the button's data attribute
               row.querySelector('.remove-btn').dataset.quantity = newQuantity;

               // Update allocated quantities
               allocatedQuantities[lotKey] = (allocatedQuantities[lotKey] || 0) - oldQuantity + newQuantity;

               // Update available lots table
               updateAvailableLots();
           }

           // Handler for remove button
           function removeRowHandler() {
               const row = this.closest('tr');
               const lotId = row.dataset.lotId;
               const lotNumber = this.dataset.lot;
               const expiryDate = this.dataset.expiry || 'noexpiry';
               const lotKey = lotNumber + '-' + expiryDate;
               const quantity = parseFloat(this.dataset.quantity || row.querySelector('input[name="quantity[]"]').value);

               // Update allocated quantities
               if (lotKey in allocatedQuantities) {
                   allocatedQuantities[lotKey] -= quantity;
                   if (allocatedQuantities[lotKey] <= 0) {
                       delete allocatedQuantities[lotKey];
                   }
               }

               // If this is an existing lot entry
               if (lotId) {
                   if (confirm('This lot entry is already registered. Do you still want to delete it?')) {
                       // Send AJAX request to delete the lot
                       const formData = new FormData();
                       formData.append('delete_lot', '1');
                       formData.append('lot_id', lotId);

                       fetch(window.location.href, {
                           method: 'POST',
                           body: formData
                       })
                       .then(response => response.json())
                       .then(data => {
                           if (data.success) {
                               row.remove();
                               // Update available lots table
                               updateAvailableLots();
                           } else {
                               alert('Error deleting lot: ' + (data.error || 'Unknown error'));
                           }
                       })
                       .catch(error => {
                           console.error('Error:', error);
                           alert('Error deleting lot. Please try again.');
                       });
                   }
               } else {
                   // For new rows, just remove from DOM
                   row.remove();
                   // Update available lots table
                   updateAvailableLots();
               }
           }

           document.getElementById('addRowBtn').addEventListener('click', function() {
               const tbody = document.querySelector('#lotTable tbody');
               const newRow = document.createElement('tr');
               newRow.innerHTML = `
                   <td><input type="number" name="quantity[]" step="0.01" required></td>
                   <td><input type="text" name="lot_number[]" required></td>
                   <td><input type="date" name="expiry_date[]" required></td>
                   <td><button type="button" class="remove-btn">Remove</button></td>
               `;
               tbody.appendChild(newRow);

               // Add event listener to new remove button
               newRow.querySelector('.remove-btn').addEventListener('click', removeRowHandler);

               // Add event listener for quantity change
               newRow.querySelector('input[name="quantity[]"]').addEventListener('input', quantityChangeHandler);
           });

           // Add event listeners to existing remove buttons
           document.querySelectorAll('.remove-btn').forEach(btn => {
               btn.addEventListener('click', removeRowHandler);
           });

           // Add event listeners to "Use This Lot" buttons
           document.querySelectorAll('.use-lot-btn').forEach(btn => {
               btn.addEventListener('click', useLotHandler);
           });

           // Add event listeners to quantity input fields
           document.querySelectorAll('input[name="quantity[]"]').forEach(input => {
               input.addEventListener('input', quantityChangeHandler);
           });

           // Form validation
           document.getElementById('lotForm').addEventListener('submit', function(e) {
              const quantity = <?= $item['stock'] ?>;
               const quantityInputs = document.querySelectorAll('input[name="quantity[]"]');
               let total = 0;

               quantityInputs.forEach(input => {
                   total += parseFloat(input.value) || 0;
               });

               if (total !== quantity) {
                   e.preventDefault();
                   alert(`Total quantity (${total}) must match item quantity (${quantity})`);
                   return false;
               }

               // Check for duplicate lot numbers with same expiry date
               const lotKeys = [];
               let hasDuplicates = false;

               document.querySelectorAll('input[name="lot_number[]"]').forEach((input, index) => {
                   const lotNumber = input.value;
                   const expiryDate = document.querySelectorAll('input[name="expiry_date[]"]')[index].value || 'noexpiry';
                   const lotKey = lotNumber + '-' + expiryDate;

                   if (lotKeys.includes(lotKey)) {
                       hasDuplicates = true;
                   }
                   lotKeys.push(lotKey);
               });

               if (hasDuplicates) {
                   e.preventDefault();
                   alert('Duplicate lot numbers with the same expiration date are not allowed');
                   return false;
               }

               // Validate lot numbers, expiration dates, and quantities
               let validationError = false;

               document.querySelectorAll('input[name="lot_number[]"]').forEach((input, index) => {
                   const lotNumber = input.value;
                   const expiryDate = document.querySelectorAll('input[name="expiry_date[]"]')[index].value;
                   const quantity = parseFloat(document.querySelectorAll('input[name="quantity[]"]')[index].value) || 0;

                   // Find matching lot in available lots
                   let lotFound = false;
                   let validQuantity = false;
                   let validExpiry = false;

                   for (const lot of availableLots) {
                       if (lot.lot_trackingid === lotNumber) {
                           lotFound = true;

                           // If expiry date is provided and not empty, check if it matches
                           if (expiryDate) {
                               if (lot.expiration_date === expiryDate) {
                                   validExpiry = true;
                                   // Check if quantity is valid (considering already allocated)
                                   const lotKey = lotNumber + '-' + expiryDate;
                                   const totalAllocated = allocatedQuantities[lotKey] || 0;
                                   const originalQuantity = parseFloat(lot.available_quantity);

                                   if (totalAllocated <= originalQuantity) {
                                       validQuantity = true;
                                   }
                               }
                           } else {
                               // If no expiry date provided, skip expiry validation
                               validExpiry = true;
                               // Check if quantity is valid
                               const lotKey = lotNumber + '-noexpiry';
                               const totalAllocated = allocatedQuantities[lotKey] || 0;
                               const originalQuantity = parseFloat(lot.available_quantity);

                               if (totalAllocated <= originalQuantity) {
                                   validQuantity = true;
                               }
                           }

                           // If we found a valid match, no need to check other lots
                           if (validExpiry && validQuantity) {
                               break;
                           }
                       }
                   }

                   if (!lotFound) {
                       alert(`Error: Lot number '${lotNumber}' not found in available lots.`);
                       validationError = true;
                       return;
                   } else if (!validExpiry && expiryDate) {
                       alert(`Error: Expiration date for lot '${lotNumber}' does not match available lot.`);
                       validationError = true;
                       return;
                   } else if (!validQuantity) {
                       alert(`Error: Quantity for lot '${lotNumber}' exceeds available quantity.`);
                       validationError = true;
                       return;
                   }
               });

               if (validationError) {
                   e.preventDefault();
                   return false;
               }
           });
       </script>
</body>
</html>
