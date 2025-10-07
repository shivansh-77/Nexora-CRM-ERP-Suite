<?php
require_once 'connection.php';

// Get all POST data
$item_code = $_POST['item_code'] ?? '';
$row_number = $_POST['row_number'] ?? 0;
$location_code = $_POST['location_code'] ?? '';
$unit_value = (float)($_POST['unit_value'] ?? 1);
$stock = (float)($_POST['stock'] ?? 0);
$temp_invoice_no = $_POST['temp_invoice_no'] ?? '';

// Validate required fields
if (empty($temp_invoice_no)) {
    echo json_encode(['success' => false, 'message' => 'Error: Temporary invoice number not provided.']);
    exit();
}

// Get all lot entries
$quantities = $_POST['quantity'] ?? [];
$lot_numbers = $_POST['lot_number'] ?? [];
$expiry_dates = $_POST['expiry_date'] ?? [];

// Validate we have matching counts
if (count($quantities) !== count($lot_numbers) || count($quantities) !== count($expiry_dates)) {
    echo json_encode(['success' => false, 'message' => 'Error: Invalid data submitted.']);
    exit();
}

// Start transaction
$connection->begin_transaction();

try {
    // Fetch product details
    $stmt = $connection->prepare("SELECT item_name, unit_of_measurement_code, sales_price FROM item WHERE item_code = ?");
    $stmt->bind_param("s", $item_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Item not found");
    }

    $item = $result->fetch_assoc();

    // Prepare insert statement
    $insert_stmt = $connection->prepare("
        INSERT INTO invoice_lot_tracking (
            document_type, entry_type, product_id, product_name, quantity,
            location, unit, value, lot_trackingid, expiration_date, rate, row_number, temp_invoice_no
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $document_type = "Sale";
    $entry_type = "Sales Invoice";

    // Insert each lot entry
    foreach ($quantities as $i => $quantity) {
        $quantity = (float)$quantity;
        $lot_number = trim($lot_numbers[$i]);
        $expiry_date = !empty($expiry_dates[$i]) ? $expiry_dates[$i] : null;

        $insert_stmt->bind_param(
            "ssssdssdssdis",
            $document_type,
            $entry_type,
            $item_code,
            $item['item_name'],
            $quantity,
            $location_code,
            $item['unit_of_measurement_code'],
            $unit_value,
            $lot_number,
            $expiry_date,
            $item['sales_price'],
            $row_number,
            $temp_invoice_no
        );

        if (!$insert_stmt->execute()) {
            throw new Exception("Failed to save lot entry: " . $connection->error);
        }
    }

    $connection->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $connection->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
