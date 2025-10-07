<?php
include 'connection.php';

// Initialize success message variable
$success_message = '';

// Get parameters from URL
$invoice_item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch main item details
$item_query = "SELECT poi.*, po.po_invoice
               FROM purchase_order_items poi
               JOIN purchase_order po ON poi.purchase_order_id = po.id
               WHERE poi.id = ?";
$stmt_item = $connection->prepare($item_query);
$stmt_item->bind_param("i", $invoice_item_id);
$stmt_item->execute();
$item_result = $stmt_item->get_result();
$item = $item_result->fetch_assoc();

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

// Fetch existing lot entries
$existing_lots = [];
$lots_query = "SELECT * FROM purchase_order_item_lots
               WHERE invoice_itemid = ? AND document_type = 'Purchase'
               ORDER BY id";
$stmt_lots = $connection->prepare($lots_query);
$stmt_lots->bind_param("i", $invoice_item_id);
$stmt_lots->execute();
$lots_result = $stmt_lots->get_result();
while ($row = $lots_result->fetch_assoc()) {
    $existing_lots[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['delete_lot'])) {
    $quantities = $_POST['quantity'];
    $lot_numbers = $_POST['lot_number'];
    $expiry_dates = $_POST['expiry_date'];
    $lot_ids = isset($_POST['lot_id']) ? $_POST['lot_id'] : [];

    // Validate total quantity matches po_invoice
    $total_quantity = array_sum($quantities);
    if ($total_quantity != $item['po_invoice']) {
        $error = "Error: Total quantity ($total_quantity) must match po_invoice ({$item['po_invoice']})";
    }
    // Check for duplicate lot numbers
    else if (count($lot_numbers) != count(array_unique($lot_numbers))) {
        $error = "Error: All lot numbers must be unique";
    }
    else {
        // Delete old entries (if any)
        $delete_query = "DELETE FROM purchase_order_item_lots WHERE invoice_itemid = ?";
        $stmt_delete = $connection->prepare($delete_query);
        $stmt_delete->bind_param("i", $invoice_item_id);
        $stmt_delete->execute();

        // Insert new entries
        $success = true;
        for ($i = 0; $i < count($quantities); $i++) {
            $document_type = 'Purchase';
            $entry_type = 'Purchase Invoice';
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
                                {$item['purchase_order_id']}
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
                    window.location.href = "purchase_lot_tracking_form_display.php?id=' . $item['purchase_order_id'] . '";
                  </script>';
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Lot Details</title>
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
    </style>
</head>
<body>
    <h2>Add Lot Details</h2>

    <div class="info-container">
        <h3>Product Information</h3>
        <p><strong>Product Name:</strong> <?= htmlspecialchars($item['product_name']) ?></p>
        <p><strong>Product ID:</strong> <?= htmlspecialchars($item['product_id']) ?></p>
        <p><strong>Unit:</strong> <?= htmlspecialchars($item['unit']) ?></p>
        <p><strong>Rate:</strong> <?= htmlspecialchars($item['rate']) ?></p>
        <p><strong>PO Invoice:</strong> <?= htmlspecialchars($item['po_invoice']) ?></p>
        <p><strong>Location Code:</strong> <?= htmlspecialchars($item['shipper_location_code']) ?></p>
        <p><strong>Value:</strong> <?= htmlspecialchars($item['value']) ?></p>
    </div>

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
                            <button type="button" class="remove-btn" data-lot-id="<?= htmlspecialchars($lot['id']) ?>">Remove</button>
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
            newRow.querySelector('.remove-btn').addEventListener('click', function() {
                tbody.removeChild(newRow);
            });
        });

        // Add event listeners to existing remove buttons
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                const lotId = row.dataset.lotId;

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
                }
            });
        });

        // Form validation
        document.getElementById('lotForm').addEventListener('submit', function(e) {
            const po_invoice = <?= $item['po_invoice'] ?>;
            const quantityInputs = document.querySelectorAll('input[name="quantity[]"]');
            let total = 0;

            quantityInputs.forEach(input => {
                total += parseFloat(input.value) || 0;
            });

            if (total !== po_invoice) {
                e.preventDefault();
                alert(`Total quantity (${total}) must match po_invoice (${po_invoice})`);
                return false;
            }

            // Check for duplicate lot numbers
            const lotNumbers = [];
            document.querySelectorAll('input[name="lot_number[]"]').forEach(input => {
                if (lotNumbers.includes(input.value)) {
                    e.preventDefault();
                    alert('All lot numbers must be unique');
                    return false;
                }
                lotNumbers.push(input.value);
            });
        });
    </script>
</body>
</html>
