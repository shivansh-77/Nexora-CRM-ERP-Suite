<?php
include('connection.php'); // Your database connection file

header('Content-Type: application/json');

$response = ['success' => false, 'data' => [], 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
    $searchTerm = isset($_POST['searchTerm']) ? $_POST['searchTerm'] : '';

    if ($client_id > 0) {
        $query = "SELECT id, advance_doc_no, pending_amount
                  FROM advance_payments
                  WHERE party_no = ?
                  AND party_type = 'Customer'
                  AND document_type = 'Advance Payment'
                  AND pending_amount > 0
                  AND (advance_doc_no LIKE ? OR document_no LIKE ?)
                  ORDER BY date DESC";

        $stmt = $connection->prepare($query);
        $likeSearchTerm = '%' . $searchTerm . '%';
        $stmt->bind_param("iss", $client_id, $likeSearchTerm, $likeSearchTerm);
        $stmt->execute();
        $result = $stmt->get_result();

        $advances = [];
        while ($row = $result->fetch_assoc()) {
            $advances[] = $row;
        }
        $stmt->close();

        $response['success'] = true;
        $response['data'] = $advances;
    } else {
        $response['error'] = 'Invalid client ID.';
    }
} else {
    $response['error'] = 'Invalid request method.';
}

echo json_encode($response);
$connection->close();
?>
