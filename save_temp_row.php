<?php
include 'connection.php';
session_start();

// Get the posted data
$temp_invoice_no = $_POST['temp_invoice_no'];
$row_number = $_POST['row_number'];
$item_code = $_POST['item_code'];
$product_name = $_POST['product_name'];
$quantity = $_POST['quantity'];
$rate = $_POST['rate'];
$gst = $_POST['gst'];
// Add other fields as needed

// Check if row already exists
$check_sql = "SELECT id FROM invoice_lot_tracking
              WHERE temp_invoice_no = ? AND row_number = ?";
$check_stmt = $connection->prepare($check_sql);
$check_stmt->bind_param("si", $temp_invoice_no, $row_number);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Update existing row
    $update_sql = "UPDATE invoice_lot_tracking SET
                  item_code = ?, product_name = ?, quantity = ?,
                  rate = ?, gst = ?
                  WHERE temp_invoice_no = ? AND row_number = ?";
    $update_stmt = $connection->prepare($update_sql);
    $update_stmt->bind_param("ssddssi",
        $item_code, $product_name, $quantity,
        $rate, $gst, $temp_invoice_no, $row_number);
    $update_stmt->execute();
} else {
    // Insert new row
    $insert_sql = "INSERT INTO invoice_lot_tracking (
                  temp_invoice_no, row_number, item_code,
                  product_name, quantity, rate, gst
                  ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $connection->prepare($insert_sql);
    $insert_stmt->bind_param("sissddd",
        $temp_invoice_no, $row_number, $item_code,
        $product_name, $quantity, $rate, $gst);
    $insert_stmt->execute();
}

echo "Row saved successfully";
?>
