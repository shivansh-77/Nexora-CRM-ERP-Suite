<?php
include('connection.php'); // Include your database connection

// Decode JSON input
$data = json_decode(file_get_contents('php://input'), true);
$employeeId = isset($data['employee_id']) ? intval($data['employee_id']) : 0;
$start_date = isset($data['start_date']) ? $data['start_date'] : '';
$end_date = isset($data['end_date']) ? $data['end_date'] : '';

// Validate input
if ($employeeId === 0 || empty($start_date) || empty($end_date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

// Fetch leaves for the selected employee within the date range
$leave_query = "
    SELECT leave_type, SUM(total_days) AS totalLeaveDays
    FROM user_leave
    WHERE user_id = ? AND start_date BETWEEN ? AND ? AND status = 'Approved'
    GROUP BY leave_type
";

// Use prepared statements to prevent SQL injection
$stmt = mysqli_prepare($connection, $leave_query);
if ($stmt === false) {
    echo json_encode(['error' => 'Failed to prepare statement']);
    exit;
}

mysqli_stmt_bind_param($stmt, 'iss', $employeeId, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$leave_result = mysqli_stmt_get_result($stmt);

$leaves = [
    'sickLeaves' => 0,
    'earnedLeaves' => 0,
    'lwp' => 0
];

while ($row = mysqli_fetch_assoc($leave_result)) {
    switch ($row['leave_type']) {
        case 'Sick Leave':
            $leaves['sickLeaves'] = $row['totalLeaveDays'];
            break;
        case 'Earned Leave':
            $leaves['earnedLeaves'] = $row['totalLeaveDays'];
            break;
        case 'Leave Without Pay':
            $leaves['lwp'] = $row['totalLeaveDays'];
            break;
    }
}

echo json_encode($leaves);
?>
