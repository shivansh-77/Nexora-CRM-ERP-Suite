<?php
include('connection.php'); // Include your database connection

$employee_id = $_GET['employee_id'];

// Fetch the latest entry for the employee
$latest_entry_query = "
    SELECT month
    FROM salary
    WHERE employee_id = ?
    ORDER BY month DESC
    LIMIT 1
";

$stmt = mysqli_prepare($connection, $latest_entry_query);
mysqli_stmt_bind_param($stmt, "s", $employee_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$lastRecordedMonth = null;
if ($row = mysqli_fetch_assoc($result)) {
    $lastRecordedMonth = $row['month'];
}

mysqli_stmt_close($stmt);

// Fetch all existing months for the employee
$existing_months_query = "
    SELECT month
    FROM salary
    WHERE employee_id = ?
";

$stmt = mysqli_prepare($connection, $existing_months_query);
mysqli_stmt_bind_param($stmt, "s", $employee_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$existingMonths = [];
while ($row = mysqli_fetch_assoc($result)) {
    $existingMonths[] = $row['month'];
}

mysqli_stmt_close($stmt);

// Prepare the response
$response = [
    'lastRecordedMonth' => $lastRecordedMonth,
    'existingMonths' => $existingMonths
];

header('Content-Type: application/json');
echo json_encode($response);
?>
