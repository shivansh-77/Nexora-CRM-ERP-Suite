<?php
require_once 'connection.php'; // Your database connection file

header('Content-Type: application/json');

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID not provided']);
    exit;
}

$id = (int)$_POST['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM purchase_order_item_lots WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No record found with that ID']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
