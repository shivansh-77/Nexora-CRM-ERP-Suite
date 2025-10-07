<?php
include 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $temp_invoice_no = $_POST['temp_invoice_no'];
    $row_numbers = json_decode($_POST['row_numbers']);

    // Convert row numbers to integers and create placeholders for the query
    $row_numbers = array_map('intval', $row_numbers);
    $placeholders = implode(',', array_fill(0, count($row_numbers), '?'));

    // Prepare and execute the query
    $stmt = $connection->prepare("SELECT DISTINCT row_number FROM invoice_lot_tracking
                                WHERE temp_invoice_no = ? AND row_number IN ($placeholders)");

    // Bind parameters
    $params = array_merge([$temp_invoice_no], $row_numbers);
    $types = str_repeat('i', count($row_numbers));
    $stmt->bind_param('s' . $types, ...$params);

    $stmt->execute();
    $result = $stmt->get_result();

    $found_rows = [];
    while ($row = $result->fetch_assoc()) {
        $found_rows[] = $row['row_number'];
    }

    echo json_encode([
        'status' => 'success',
        'found_rows' => $found_rows
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?>
