<?php
include('connection.php'); // Include your database connection

// Get the employee ID, start date, and end date from the POST data
$data = json_decode(file_get_contents('php://input'), true);
$employee_id = isset($data['employee_id']) ? intval($data['employee_id']) : 0;
$start_date = isset($data['start_date']) ? $data['start_date'] : '';
$end_date = isset($data['end_date']) ? $data['end_date'] : '';

// Validate input
if ($employee_id === 0 || empty($start_date) || empty($end_date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

// Fetch the minimum_shift value from the company_card table where id = 1
$min_shift_query = "SELECT minimum_shift FROM company_card WHERE id = 1";
$min_shift_result = mysqli_query($connection, $min_shift_query);
$min_shift = mysqli_fetch_assoc($min_shift_result)['minimum_shift'];

// Query to fetch the attendance entries for the selected employee within the chosen date range
$query = "SELECT session_duration FROM attendance WHERE user_id = ? AND checkin_time BETWEEN ? AND ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, 'iss', $employee_id, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Initialize the working days, shortcoming entries, and shortcoming days counts
$working_days = 0;
$shortcoming_entries = 0;
$shortcoming_days = 0;

// Iterate through the attendance entries and count working days, shortcoming entries, and shortcoming days
while ($row = mysqli_fetch_assoc($result)) {
    $session_duration = $row['session_duration'];
    if ($session_duration >= $min_shift) {
        $working_days += 1;
    } else {
        $working_days += 1; // Count as a working day
        $shortcoming_entries += 1;
        $shortcoming_days += 0.5;
    }
}

// Return the counts as a JSON response
header('Content-Type: application/json');
echo json_encode([
    'working_days' => $working_days,
    'shortcoming_entries' => $shortcoming_entries,
    'shortcoming_days' => $shortcoming_days
]);
?>
