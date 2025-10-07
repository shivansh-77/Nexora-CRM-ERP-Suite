<?php
// Include your database connection
include 'connection.php';

// Get parameters from POST request
$temp_invoice_no = $_POST['temp_invoice_no'] ?? '';
$row_id = $_POST['row_id'] ?? '';

// Validate input
if (empty($temp_invoice_no) || empty($row_id)) {
    echo json_encode(['exists' => false, 'error' => 'Missing required parameters']);
    exit;
}

// Prepare and execute query to check if lot tracking entry exists
$query = "SELECT COUNT(*) as count FROM invoice_lot_tracking
          WHERE temp_invoice_no = ? AND row_number = ?";

$stmt = $connection->prepare($query);
$stmt->bind_param("ss", $temp_invoice_no, $row_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Return JSON response
if ($row['count'] > 0) {
    echo json_encode(['exists' => true]);
} else {
    echo json_encode(['exists' => false]);
}

$stmt->close();
$connection->close();
?>
