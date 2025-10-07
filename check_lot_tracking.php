<?php
// Include your database connection
require_once 'connection.php';

// Get the item ID from query parameter
$itemId = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

// Prepare the response
$response = ['exists' => false];

if ($itemId > 0) {
    // Query to check if the item exists in sales_item_lots table
    $query = "SELECT COUNT(*) as count FROM sales_item_lots WHERE invoice_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        $response['exists'] = true;
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
