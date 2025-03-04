<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "User not logged in."]));
}

include("connection.php");

$user_id = $_SESSION['user_id'];

$stmt = $connection->prepare("
    SELECT session_status, checkin_time
    FROM attendance
    WHERE user_id = ?
    ORDER BY checkin_time DESC
    LIMIT 1
");
if (!$stmt) {
    die(json_encode(["status" => "error", "message" => "Database error: " . $connection->error]));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        "status" => "success",
        "session_status" => $row['session_status'],
        "start_time" => $row['checkin_time']
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "No session found."]);
}

$stmt->close();
?>
