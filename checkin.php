<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    die(json_encode(["status" => "error", "message" => "User not logged in"]));
}

include("connection.php");

// Set MySQL connection timezone
$connection->query("SET time_zone = '+05:30'");

function getLocationFromCoordinates($latitude, $longitude) {
    $apiKey = "dacd661dda574f7f871fe91d44f9fd98";
    $url = "https://api.opencagedata.com/geocode/v1/json?q=$latitude,$longitude&key=$apiKey";
    $response = file_get_contents($url);
    $json = json_decode($response, true);
    return $json['results'][0]['formatted'] ?? "Unknown Location";
}

// First check company checkout time
$company_checkout = $connection->query("SELECT checkout_time FROM company_card WHERE id = 1");
if ($company_checkout && $company_checkout->num_rows > 0) {
    $company_time = $company_checkout->fetch_assoc()['checkout_time'];
    $current_time = date('H:i:s');

    // If current time is past company checkout time, deny check-in
    if ($current_time >= $company_time) {
        die(json_encode([
            "status" => "error",
            "message" => "Check-in not allowed after company checkout time ($company_time)"
        ]));
    }
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

if (!isset($_POST['checkin_latitude']) || !isset($_POST['checkin_longitude'])) {
    die(json_encode(["status" => "error", "message" => "Coordinates required"]));
}

$checkin_latitude = $_POST['checkin_latitude'];
$checkin_longitude = $_POST['checkin_longitude'];

if (!is_numeric($checkin_latitude) || !is_numeric($checkin_longitude)) {
    die(json_encode(["status" => "error", "message" => "Invalid coordinates"]));
}

// Get current time in IST
$current_time = date('Y-m-d H:i:s');
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

// Check existing check-in
$check_stmt = $connection->prepare("SELECT * FROM attendance WHERE user_id = ? AND checkin_time BETWEEN ? AND ?");
$check_stmt->bind_param("iss", $user_id, $today_start, $today_end);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows > 0) {
    die(json_encode(["status" => "error", "message" => "Already checked in today"]));
}

$checkin_location = getLocationFromCoordinates($checkin_latitude, $checkin_longitude);

$stmt = $connection->prepare("INSERT INTO attendance (user_id, user_name, checkin_time, checkin_latitude, checkin_longitude, session_status, checkin_location) VALUES (?, ?, ?, ?, ?, 'active', ?)");
$stmt->bind_param("issdds", $user_id, $user_name, $current_time, $checkin_latitude, $checkin_longitude, $checkin_location);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Check-in successful!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}

$stmt->close();
$check_stmt->close();
?>
