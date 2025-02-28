<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "User not logged in."]));
}

include("connection.php"); // Include the database connection file

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Update the database to set checkout time, session status, and session duration
$stmt = $connection->prepare("
    UPDATE attendance
    SET checkout_time = NOW(),
        session_status = 'ended',
        session_duration = TIMEDIFF(NOW(), checkin_time)
    WHERE user_id = ? AND session_status = 'active'
");
if (!$stmt) {
    die(json_encode(["status" => "error", "message" => "Database error: " . $connection->error]));
}
$stmt->bind_param("i", $user_id);

// Execute the statement
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Check-out successful!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
}

// Close the statement
$stmt->close();
?>
