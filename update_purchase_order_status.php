<?php
// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header first
header('Content-Type: application/json');

// Include database connection
require_once 'connection.php';

// Initialize response
$response = ['success' => false, 'message' => ''];

try {
    // Verify POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }

    // Validate inputs
    $poId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    if (!$poId || $poId <= 0) {
        throw new Exception('Invalid purchase order ID');
    }

    if (strtolower($status) !== 'completed') {
        throw new Exception('Invalid status value - must be "Completed"');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Check current status
    $checkStmt = $pdo->prepare("SELECT status FROM purchase_order WHERE id = ?");
    $checkStmt->execute([$poId]);
    $current = $checkStmt->fetch();

    if (!$current) {
        throw new Exception('Purchase order not found');
    }

    // Only update if not already completed
    if (strtolower(trim($current['status'])) !== 'completed') {
        $updateStmt = $pdo->prepare("UPDATE purchase_order SET status = ? WHERE id = ?");
        $updateStmt->execute(['Completed', $poId]);

        if ($updateStmt->rowCount() === 0) {
            throw new Exception('No records were updated');
        }
    }

    // Commit transaction
    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Status updated to Completed';

} catch (PDOException $e) {
    $pdo->rollBack();
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    if (isset($pdo) {
        $pdo->rollBack();
    }
    $response['message'] = $e->getMessage();
}

// Ensure no output before this
echo json_encode($response);
exit;
?>
