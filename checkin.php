<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    die(json_encode(["status" => "error", "message" => "User not logged in or user name not set."]));
}

include("connection.php"); // Include the database connection file

// Function to get location from latitude and longitude using OpenCage's Geocoding API
function getLocationFromCoordinates($latitude, $longitude) {
    $apiKey = "dacd661dda574f7f871fe91d44f9fd98"; // Replace with your actual OpenCage API key
    $url = "https://api.opencagedata.com/geocode/v1/json?q=$latitude,$longitude&key=$apiKey";

    $response = file_get_contents($url);
    $json = json_decode($response, true);

    return $json['results'][0]['formatted'] ?? "Unknown Location";
}

// Get user ID and user name from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Check if latitude and longitude are provided
if (!isset($_POST['checkin_latitude']) || !isset($_POST['checkin_longitude'])) {
    die(json_encode(["status" => "error", "message" => "Latitude and longitude are required."]));
}

$checkin_latitude = $_POST['checkin_latitude'];
$checkin_longitude = $_POST['checkin_longitude'];

// Validate latitude and longitude
if (!is_numeric($checkin_latitude) || !is_numeric($checkin_longitude)) {
    die(json_encode(["status" => "error", "message" => "Invalid latitude or longitude."]));
}

// Check if the user is already checked in
$check_stmt = $connection->prepare("SELECT * FROM attendance WHERE user_id = ? AND session_status = 'active'");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    die(json_encode(["status" => "error", "message" => "You are already checked in. Kindly Refresh the Page!"]));
}

// Get location name from coordinates
$checkin_location = getLocationFromCoordinates($checkin_latitude, $checkin_longitude);

// Prepare and bind
$stmt = $connection->prepare("INSERT INTO attendance (user_id, user_name, checkin_time, checkin_latitude, checkin_longitude, session_status, checkin_location) VALUES (?, ?, NOW(), ?, ?, 'active', ?)");
if (!$stmt) {
    error_log("Database error: " . $connection->error);
    die(json_encode(["status" => "error", "message" => "Database error."]));
}
$stmt->bind_param("isdds", $user_id, $user_name, $checkin_latitude, $checkin_longitude, $checkin_location);

// Execute the statement
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Check-in successful!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
}

// Close the statements
$stmt->close();
$check_stmt->close();
?>
