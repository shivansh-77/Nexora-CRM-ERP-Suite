<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "User not logged in."]));
}

include("connection.php"); // Include the database connection file

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if the user has an active session
$stmt = $connection->prepare("
    SELECT session_status
    FROM attendance
    WHERE user_id = ? AND session_status = 'active'
    ORDER BY checkin_time DESC
    LIMIT 1
");
if (!$stmt) {
    die(json_encode(["status" => "error", "message" => "Database error: " . $connection->error]));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($session_status);
    $stmt->fetch();
    echo json_encode(["status" => "success", "session_status" => $session_status]);
} else {
    echo json_encode(["status" => "success", "session_status" => "inactive"]);
}

$stmt->close();
?>
