<?php
session_start();
include('connection.php');

// Check authorization
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

// Get parameters
$month_id = isset($_POST['month_id']) ? intval($_POST['month_id']) : 0;
$fy_code = isset($_POST['fy_code']) ? mysqli_real_escape_string($connection, $_POST['fy_code']) : '';

// Validate parameters
if ($month_id == 0 || empty($fy_code)) {
    die(json_encode(['error' => 'Invalid parameters']));
}

// Start transaction
$connection->begin_transaction();

try {
    // Update all records for this month
    $query = "UPDATE salary SET status = 'Approved'
              WHERE month_id = ? AND fy_code = ? AND status = 'Pending'";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("is", $month_id, $fy_code);
    $stmt->execute();

    $affected_rows = $stmt->affected_rows;
    $connection->commit();

    echo "$affected_rows salary records approved successfully";

} catch (Exception $e) {
    $connection->rollback();
    echo "Error: " . $e->getMessage();
}

$stmt->close();
$connection->close();
?>
