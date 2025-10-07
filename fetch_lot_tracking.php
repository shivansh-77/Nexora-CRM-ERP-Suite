<?php
// Create a new file to fetch lot tracking data for a specific row
require_once 'connection.php';

// Get parameters
$temp_invoice_no = $_GET['temp_invoice_no'] ?? '';
$row_number = $_GET['row_number'] ?? '';

// Validate input
if (empty($temp_invoice_no) || empty($row_number)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Prepare and execute query
$query = "SELECT lot_trackingid, expiration_date FROM invoice_lot_tracking
          WHERE temp_invoice_no = ? AND row_number = ?";

$stmt = $connection->prepare($query);
$stmt->bind_param("si", $temp_invoice_no, $row_number);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'lot_tracking_id' => $row['lot_trackingid'],
        'expiration_date' => $row['expiration_date']
    ];
}

// Return JSON response
echo json_encode(['success' => true, 'data' => $data]);

$stmt->close();
$connection->close();
?>
