<?php
include('connection.php'); // Include your database connection

// Get the start date and end date from the POST data
$data = json_decode(file_get_contents('php://input'), true);
$start_date = isset($data['start_date']) ? $data['start_date'] : '';
$end_date = isset($data['end_date']) ? $data['end_date'] : '';

// Validate input
if (empty($start_date) || empty($end_date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

// Fetch holidays within the selected date range
$holiday_query = "SELECT COUNT(*) AS holidayCount FROM holidays WHERE start_date BETWEEN ? AND ?";
$stmt = mysqli_prepare($connection, $holiday_query);
mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
mysqli_stmt_execute($stmt);
$holiday_result = mysqli_stmt_get_result($stmt);
$holiday_data = mysqli_fetch_assoc($holiday_result);

// Return the count of holidays as a JSON response
header('Content-Type: application/json');
echo json_encode(['holidayCount' => $holiday_data['holidayCount']]);
?>
