<?php
session_start();
// Set default timezone to Asia/Kolkata
date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        "status" => "error",
        "message" => "User not logged in."
    ]));
}

include("connection.php");

// Set MySQL connection timezone
$connection->query("SET time_zone = '+05:30'");

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Determine the session status based on the request
$session_status = (isset($_POST['auto']) && $_POST['auto'] === 'true')
    ? 'Auto Checkout'
    : 'ended';

// Get current time in IST
$current_time = date('Y-m-d H:i:s');

// First get the checkin time to calculate duration accurately
$checkin_query = $connection->prepare("
    SELECT checkin_time
    FROM attendance
    WHERE user_id = ? AND session_status = 'active'
    ORDER BY checkin_time DESC
    LIMIT 1
");
$checkin_query->bind_param("i", $user_id);
$checkin_query->execute();
$result = $checkin_query->get_result();

if ($result->num_rows === 0) {
    die(json_encode([
        "status" => "error",
        "message" => "No active session found to check out."
    ]));
}

$row = $result->fetch_assoc();
$checkin_time = $row['checkin_time'];

// Get the company's daily checkout time limit
$company_limit_query = $connection->query("
    SELECT checkout_time FROM company_card WHERE id = 1
");
$company_limit = $company_limit_query->fetch_assoc();
$company_checkout_time = $company_limit['checkout_time'];

// Parse the checkin time and company checkout time
$checkin_datetime = new DateTime($checkin_time);
$checkin_date = $checkin_datetime->format('Y-m-d');

// Create datetime object for company's checkout time on the checkin date
$company_checkout_datetime = new DateTime($checkin_date . ' ' . $company_checkout_time);

// Get current datetime
$current_datetime = new DateTime();

// Determine which checkout time to use
$checkout_datetime = $current_datetime;
$company_limit_applied = false;

// If current time is after company's checkout time on checkin date
if ($current_datetime > $company_checkout_datetime) {
    $checkout_datetime = $company_checkout_datetime;
    $company_limit_applied = true;
}

// Calculate duration
$duration = $checkout_datetime->diff($checkin_datetime);
$duration_str = $duration->format('%H:%I:%S');

// Update the database
$stmt = $connection->prepare("
    UPDATE attendance
    SET
        checkout_time = ?,
        session_status = ?,
        session_duration = ?
    WHERE
        user_id = ?
        AND session_status = 'active'
        AND checkout_time IS NULL
");
if (!$stmt) {
    die(json_encode([
        "status" => "error",
        "message" => "Database error: " . $connection->error
    ]));
}

$checkout_time_str = $checkout_datetime->format('Y-m-d H:i:s');
$stmt->bind_param(
    "sssi",
    $checkout_time_str,
    $session_status,
    $duration_str,
    $user_id
);

// Execute the statement
if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    if ($affected_rows > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Check-out successful!",
            "company_limit_applied" => $company_limit_applied,
            "checkout_time" => $checkout_time_str
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "No records updated. You may have already checked out."
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error: " . $stmt->error
    ]);
}

// Close the statements
$stmt->close();
$checkin_query->close();
$company_limit_query->close();
?>
