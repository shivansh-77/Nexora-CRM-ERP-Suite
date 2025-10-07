<?php
// Include your database connection file
include 'connection.php';

// Get the item ID from the query parameter
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

if ($item_id == 0) {
    echo "<script>alert('Invalid item ID.');</script>";
    exit();
}

// Fetch the product_id from purchase_invoice_items
$product_id_query = "SELECT product_id FROM purchase_order_items WHERE id = ?";
$product_id_stmt = $connection->prepare($product_id_query);
if (!$product_id_stmt) {
    echo "<script>alert('Database preparation error.');</script>";
    exit();
}

$product_id_stmt->bind_param('i', $item_id);
if (!$product_id_stmt->execute()) {
    echo "<script>alert('Error executing query.');</script>";
    $product_id_stmt->close();
    exit();
}

$product_id_result = $product_id_stmt->get_result();
if ($product_id_result->num_rows == 0) {
    echo "<script>alert('Item not found in purchase invoice items.');</script>";
    $product_id_stmt->close();
    exit();
}

$product_id_data = $product_id_result->fetch_assoc();
$product_id = $product_id_data['product_id'];
$product_id_stmt->close();

// Fetch the lot_tracking status from the item table using product_id as item_code
$lot_tracking_query = "SELECT lot_tracking FROM item WHERE item_code = ?";
$lot_tracking_stmt = $connection->prepare($lot_tracking_query);
if (!$lot_tracking_stmt) {
    echo "<script>alert('Database preparation error.');</script>";
    exit();
}

$lot_tracking_stmt->bind_param('s', $product_id);
if (!$lot_tracking_stmt->execute()) {
    echo "<script>alert('Error executing query.');</script>";
    $lot_tracking_stmt->close();
    exit();
}

$lot_tracking_result = $lot_tracking_stmt->get_result();
if ($lot_tracking_result->num_rows == 0) {
    echo "<script>alert('Product not found in item table.');</script>";
    $lot_tracking_stmt->close();
    exit();
}

$lot_tracking_data = $lot_tracking_result->fetch_assoc();
$lot_tracking = $lot_tracking_data['lot_tracking'];
$lot_tracking_stmt->close();

// Check if lot_tracking is enabled (1) or disabled (0)
if ($lot_tracking == 0) {
    echo "<script>
        alert('Lot tracking and expiration not needed for this item.');
        window.history.back();
    </script>";
    exit();
}
// Fetch data from the database
$query = "SELECT product_name, product_id, quantity, rate, gst, amount, igst, cgst, sgst, unit, value, stock, qytc, po_invoice, purchase_order_id
          FROM purchase_order_items
          WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('i', $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

// Calculate the adjusted po_invoice by multiplying with the unit value
$item['po_invoice'] = $item['po_invoice'] * $item['value'];

// Close the database connection
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantities = $_POST['quantity'];
    $lot_tracking_ids = $_POST['lot_tracking_id'];
    $expiration_dates = $_POST['expiration_date'];
    $item_id = $_POST['item_id'];
    $po_invoice = $_POST['po_invoice'];

    // Check if the sum of quantities equals po_invoice
    $total_quantity = array_sum($quantities);

    // Fetch the total quantity of lots with invoice_registered = 0
    $lot_query = "SELECT SUM(quantity) AS total_unregistered_quantity FROM purchase_order_item_lot_tracking WHERE invoice_itemid = ? AND invoice_registered = 0";
    $lot_stmt = $connection->prepare($lot_query);
    $lot_stmt->bind_param('i', $item_id);
    $lot_stmt->execute();
    $lot_result = $lot_stmt->get_result();
    $unregistered_quantity_result = $lot_result->fetch_assoc();
    $total_unregistered_quantity = $unregistered_quantity_result['total_unregistered_quantity'] ?: 0;

    $lot_stmt->close();

    // Include the unregistered quantity in the total quantity check
    if (($total_quantity + $total_unregistered_quantity) != $po_invoice) {
        echo "<script>alert('The sum of quantities must equal the PO Invoice value.');</script>";
    } elseif (count(array_unique($lot_tracking_ids)) != count($lot_tracking_ids)) {
        echo "<script>alert('All lot tracking IDs must be unique.');</script>";
    } else {
        // Insert each row into the database with additional columns
        $insert_query = "INSERT INTO purchase_order_item_lot_tracking (
      quantity, lot_trackingid, expiration_date, invoice_itemid,
      document_type, entry_type, product_id, product_name, unit, value, rate, invoice_id
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $connection->prepare($insert_query);

        // Assign string literals to variables
        $document_type = 'Purchase';
        $entry_type = 'Purchase Invoice';

        foreach ($quantities as $index => $quantity) {
            $lot_tracking_id = $lot_tracking_ids[$index];
            $expiration_date = $expiration_dates[$index];
            $insert_stmt->bind_param(
      'ississsssdsi', // 12 parameters now
      $quantity,
      $lot_tracking_id,
      $expiration_date,
      $item_id,
      $document_type,
      $entry_type,
      $item['product_id'],
      $item['product_name'],
      $item['unit'],
      $item['value'],
      $item['rate'],
      $item['purchase_order_id']
  );
            $insert_stmt->execute();
        }

        $insert_stmt->close();

        // Redirect to the same page to avoid resubmission
        header("Location: purchase_lot_tracking.php?item_id=$item_id");
        exit();
    }
}

// Fetch lot tracking data
$lot_query = "SELECT id, quantity, lot_trackingid, expiration_date FROM purchase_order_item_lot_tracking WHERE invoice_itemid = ? AND invoice_registered = 0";
$lot_stmt = $connection->prepare($lot_query);
$lot_stmt->bind_param('i', $item_id);
$lot_stmt->execute();
$lot_result = $lot_stmt->get_result();
$lots = $lot_result->fetch_all(MYSQLI_ASSOC);

// Calculate the total quantity of lots with invoice_registered = 0
$total_unregistered_quantity = 0;
foreach ($lots as $lot) {
    $total_unregistered_quantity += $lot['quantity'];
}

$lot_stmt->close();
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Lot Tracking | Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background-color: #2c3e50;
            padding: 20px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 25px;
            font-weight: 500;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h1 i {
            font-size: 1.2em;
        }

        h2 {
            color: var(--secondary-color);
            margin: 25px 0 15px;
            font-weight: 500;
            font-size: 1.4em;
        }

        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            border-left: 4px solid var(--primary-color);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #eee;
        }

        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 500;
            color: #555;
        }

        .info-value {
            font-weight: 400;
            color: var(--dark-color);
        }

        .lot-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            margin-top: 20px;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
        }

        .lot-form {
            margin-bottom: 30px;
        }

        .lot-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 80px;
            gap: 15px;
            margin-bottom: 15px;
            align-items: center;
        }

        .lot-list .lot-row {
            padding: 12px 15px;
            background-color: #f9f9f9;
            border-radius: var(--border-radius);
            margin-bottom: 10px;
            transition: var(--transition);
        }

        .lot-list .lot-row:hover {
            background-color: #f1f1f1;
        }

        input, button {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 14px;
            transition: var(--transition);
        }

        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
        }

        button {
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
            border: none;
        }

        .btn-success:hover {
            background-color: #3a9e34;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background-color: #d32f2f;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .status-indicator {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background-color: #e0e0e0;
            color: #555;
        }

        .status-indicator.pending {
            background-color: #fff3e0;
            color: #e65100;
        }

        .status-indicator.completed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .progress-container {
            margin: 20px 0;
        }

        .progress-bar {
            height: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 5px;
            transition: width 0.5s ease;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #666;
        }

        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-warning {
            background-color: #fff3e0;
            color: #e65100;
            border-left: 4px solid #ff9800;
        }

        .alert i {
            font-size: 1.2em;
        }

        @media (max-width: 768px) {
            .lot-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .container {
                grid-template-columns: 1fr;
            }
        }
        .back-button {
    background-color: var(--danger-color);
    color: white;
    padding: 8px 16px;
    border-radius: var(--border-radius);
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: var(--transition);
    border: none;
    cursor: pointer;
}

.back-button:hover {
    background-color: #d32f2f;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    border-bottom: 2px solid #eee;
    padding-bottom: 15px;
}
    </style>
</head>
<body>
    <div class="main-container">
      <div class="header-container">
    <h1><i class="fas fa-clipboard-list"></i> Purchase Lot Tracking</h1>
    <a href="purchase_order_form_display.php?id=<?php echo $item['purchase_order_id']; ?>" class="back-button">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

        <div class="container">
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">Product Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($item['product_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Product ID:</span>
                    <span class="info-value"><?php echo htmlspecialchars($item['product_id']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Quantity:</span>
                    <span class="info-value"><?php echo htmlspecialchars($item['quantity']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Rate:</span>
                    <span class="info-value"><?php echo htmlspecialchars($item['rate']); ?></span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">GST:</span>
                    <span class="info-value"><?php echo htmlspecialchars($item['gst']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Value:</span>
                    <span class="info-value"><?php echo htmlspecialchars($item['value']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Stock:</span>
                    <span class="info-value"><?php echo htmlspecialchars($item['stock']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">PO Invoice:</span>
                    <span class="info-value"><?php echo htmlspecialchars($item['po_invoice']); ?></span>
                </div>
            </div>
        </div>

        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min(100, ($total_unregistered_quantity / $item['po_invoice']) * 100); ?>%"></div>
            </div>
            <div class="progress-text">
                <span>Registered: <?php echo $total_unregistered_quantity; ?> / <?php echo $item['po_invoice']; ?></span>
                <span><?php echo round(($total_unregistered_quantity / $item['po_invoice']) * 100, 2); ?>%</span>
            </div>
        </div>

        <div class="lot-container">
            <div class="form-header">
                <h2><i class="fas fa-barcode"></i> Lot Tracking Details</h2>
                <div class="form-actions">
                    <button type="button" class="btn-outline" onclick="addLotRow()">
                        <i class="fas fa-plus"></i> Add Lot
                    </button>
                </div>
            </div>

            <?php if (($total_unregistered_quantity < $item['po_invoice'])): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>You need to register <?php echo ($item['po_invoice'] - $total_unregistered_quantity); ?> more units to complete this PO.</span>
                </div>
            <?php endif; ?>

            <form id="lotForm" method="POST" action="" class="lot-form">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                <input type="hidden" name="po_invoice" value="<?php echo $item['po_invoice']; ?>">

                <div id="lotRows">
                    <div class="lot-row">
                        <input type="number" name="quantity[]" placeholder="Quantity" required min="1" step="1">
                        <input type="text" name="lot_tracking_id[]" placeholder="Lot Tracking ID" required>
                        <input type="date" name="expiration_date[]" required>
                        <button type="button" class="btn-danger" onclick="removeRow(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="form-actions" style="margin-top: 20px;">
                    <button type="submit" class="btn-success">
                        <i class="fas fa-save"></i> Save Lots
                    </button>
                </div>
            </form>

            <?php if (!empty($lots)): ?>
                <h2><i class="fas fa-list"></i> Registered Lots</h2>
                <div class="lot-list">
                    <?php foreach ($lots as $lot): ?>
                        <div class="lot-row">
                            <span><strong>Qty:</strong> <?php echo htmlspecialchars($lot['quantity']); ?></span>
                            <span><strong>Lot ID:</strong> <?php echo htmlspecialchars($lot['lot_trackingid']); ?></span>
                            <span><strong>Expires:</strong> <?php echo htmlspecialchars($lot['expiration_date']); ?></span>
                            <button type="button" class="btn-danger" onclick="removeLotRow(<?php echo $lot['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert">
                    <i class="fas fa-info-circle"></i>
                    <span>No lots have been registered yet for this item.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let totalUnregisteredQuantity = <?php echo $total_unregistered_quantity; ?>;
        const poInvoice = <?php echo $item['po_invoice']; ?>;
        const remainingQuantity = poInvoice - totalUnregisteredQuantity;

        function updateTotalUnregisteredQuantity() {
            const quantities = document.querySelectorAll('input[name="quantity[]"]');
            totalUnregisteredQuantity = <?php echo $total_unregistered_quantity; ?>;
            quantities.forEach(input => {
                totalUnregisteredQuantity += parseInt(input.value) || 0;
            });
            updateProgressBar();
        }

        function updateProgressBar() {
            const progressFill = document.querySelector('.progress-fill');
            const progressText = document.querySelectorAll('.progress-text span');
            const percentage = Math.min(100, (totalUnregisteredQuantity / poInvoice) * 100);

            progressFill.style.width = `${percentage}%`;
            progressText[0].textContent = `Registered: ${totalUnregisteredQuantity} / ${poInvoice}`;
            progressText[1].textContent = `${percentage.toFixed(2)}%`;
        }

        function addLotRow() {
            updateTotalUnregisteredQuantity();

            if (totalUnregisteredQuantity >= poInvoice) {
                alert('Cannot add more rows. The total unregistered quantity has reached the PO Invoice value.');
                return;
            }

            const lotRow = document.createElement('div');
            lotRow.className = 'lot-row';
            lotRow.innerHTML = `
                <input type="number" name="quantity[]" placeholder="Quantity" required min="1" max="${remainingQuantity}" step="1">
                <input type="text" name="lot_tracking_id[]" placeholder="Lot Tracking ID" required>
                <input type="date" name="expiration_date[]" required>
                <button type="button" class="btn-danger" onclick="removeRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            document.getElementById('lotRows').appendChild(lotRow);
        }

        function removeRow(button) {
            const row = button.closest('.lot-row');
            row.remove();
            updateTotalUnregisteredQuantity();
        }

        function removeLotRow(id) {
            if (confirm('Are you sure you want to remove this lot? This action cannot be undone.')) {
                fetch(`remove_lot.php?id=${id}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Failed to remove lot: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while removing the lot.');
                    });
            }
        }

        document.getElementById('lotForm').addEventListener('submit', function(event) {
            updateTotalUnregisteredQuantity();
            const quantities = document.querySelectorAll('input[name="quantity[]"]');
            const lotTrackingIds = document.querySelectorAll('input[name="lot_tracking_id[]"]');

            let totalQuantity = 0;
            const trackingIdSet = new Set();

            // Validate quantities
            quantities.forEach(input => {
                const value = parseInt(input.value);
                if (isNaN(value) || value <= 0) {
                    alert('Please enter valid quantities (greater than 0) for all lots.');
                    event.preventDefault();
                    return;
                }
                totalQuantity += value;
            });

            // Validate unique tracking IDs
            lotTrackingIds.forEach(input => {
                if (input.value.trim() === '') {
                    alert('Please enter lot tracking IDs for all lots.');
                    event.preventDefault();
                    return;
                }

                if (trackingIdSet.has(input.value)) {
                    alert('All lot tracking IDs must be unique.');
                    event.preventDefault();
                    return;
                }
                trackingIdSet.add(input.value);
            });

            // Validate total quantity
            if (totalUnregisteredQuantity !== poInvoice) {
                alert(`The sum of quantities (${totalUnregisteredQuantity}) must equal the PO Invoice value (${poInvoice}).`);
                event.preventDefault();
            }
        });

        // Initialize date inputs with today's date as default
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[type="date"]').forEach(input => {
                if (!input.value) {
                    input.value = today;
                }
            });
        });
    </script>
</body>
</html>
