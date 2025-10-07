<?php
// Include your database connection file
include 'connection.php';

// Set header to return JSON
header('Content-Type: application/json');

// Get the ID from the query parameter
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // First, check if the lot exists and is unregistered
    $check_query = "SELECT invoice_registered FROM purchase_order_item_lots WHERE id = ?";
    $check_stmt = $connection->prepare($check_query);
    $check_stmt->bind_param('i', $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        $response['message'] = 'Lot not found';
    } else {
        $lot = $check_result->fetch_assoc();

        if ($lot['invoice_registered'] == 1) {
            $response['message'] = 'Cannot remove a registered lot';
        } else {
            // Delete the lot
            $delete_query = "DELETE FROM purchase_order_item_lots WHERE id = ?";
            $delete_stmt = $connection->prepare($delete_query);
            $delete_stmt->bind_param('i', $id);

            if ($delete_stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Lot removed successfully';
            } else {
                $response['message'] = 'Failed to remove lot';
            }

            $delete_stmt->close();
        }
    }

    $check_stmt->close();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Close the database connection
$connection->close();

// Return the JSON response
echo json_encode($response);
?>
