<?php
session_start();
include('connection.php');

// Check if required parameters are set
if (!isset($_GET['fy_code']) || !isset($_GET['month_id']) || !isset($_GET['month']) ||
    !isset($_GET['start_date']) || !isset($_GET['end_date'])) {
    die("Required parameters are missing.");
}

$fy_code = urldecode($_GET['fy_code']);
$month_id = $_GET['month_id'];
$month = urldecode($_GET['month']);
$start_date = urldecode($_GET['start_date']);
$end_date = urldecode($_GET['end_date']);

// Fetch all employees
$employee_query = "SELECT id, name, salary FROM login_db";
$employee_result = mysqli_query($connection, $employee_query);
$employees = mysqli_fetch_all($employee_result, MYSQLI_ASSOC);

// Process each employee
foreach ($employees as $employee) {
    $employee_id = $employee['id'];
    $employee_name = $employee['name'];
    $salary = $employee['salary'];

    // Calculate all required values
    $working_days = getWorkingDays($connection, $employee_id, $start_date, $end_date);
    $weekends = calculateWeekends($start_date, $end_date);
    $holidays = getHolidaysCount($connection, $start_date, $end_date);
    $leaves = getEmployeeLeaves($connection, $employee_id, $start_date, $end_date);

    $total_days = calculateTotalDays($start_date, $end_date);
    $payable_days = $working_days + $weekends + $holidays + $leaves['sick_leaves'] + $leaves['earned_leaves'];
    $salary_per_day = $salary / $total_days;
    $payable_salary = $payable_days * $salary_per_day;
    $additional_fund = 0; // Default to 0 for batch processing
    $total_amount = $payable_salary + $additional_fund;

    // Check if record exists
    $check_query = "SELECT id FROM salary WHERE employee_id = ? AND month = ? AND fy_code = ?";
    $stmt = mysqli_prepare($connection, $check_query);
    mysqli_stmt_bind_param($stmt, "iss", $employee_id, $month, $fy_code);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($check_result)) {
        // Update existing record
        $update_query = "UPDATE salary SET
                        working_days = ?,
                        weekends = ?,
                        holidays = ?,
                        sick_leaves = ?,
                        earned_leaves = ?,
                        half_days = ?,
                        lwp = ?,
                        payable_days = ?,
                        total_days = ?,
                        salary_per_day = ?,
                        payable_salary = ?,
                        additional_fund = ?,
                        total_amount = ?,
                        created_at = NOW()
                        WHERE employee_id = ? AND month = ? AND fy_code = ?";

        $stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($stmt, "ddddddddddddiss",
            $working_days, $weekends, $holidays, $leaves['sick_leaves'], $leaves['earned_leaves'],
            $leaves['half_day'], $leaves['lwp'], $payable_days, $total_days, $salary_per_day,
            $payable_salary, $additional_fund, $total_amount,
            $employee_id, $month, $fy_code);
    } else {
        // Insert new record
        $insert_query = "INSERT INTO salary (
                        employee_id, employee_name, month, fy_code, month_id,
                        working_days, weekends, holidays, sick_leaves, earned_leaves,
                        half_days, lwp, payable_days, total_days, salary_per_day,
                        payable_salary, additional_fund, total_amount, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = mysqli_prepare($connection, $insert_query);
        mysqli_stmt_bind_param($stmt, "isssiddddddddddddd",
            $employee_id, $employee_name, $month, $fy_code, $month_id,
            $working_days, $weekends, $holidays, $leaves['sick_leaves'], $leaves['earned_leaves'],
            $leaves['half_day'], $leaves['lwp'], $payable_days, $total_days, $salary_per_day,
            $payable_salary, $additional_fund, $total_amount);
    }

    if (!mysqli_stmt_execute($stmt)) {
        // Log error but continue with other employees
        error_log("Failed to process salary for employee ID: $employee_id - " . mysqli_error($connection));
    }
}

$_SESSION['success'] = "Salary records processed for all employees";
header("Location: salary_sheet_display.php");
exit();

/**
 * Calculate total days between two dates (inclusive)
 */
function calculateTotalDays($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    return $end->diff($start)->days + 1;
}

/**
 * Count weekends between two dates
 */
function calculateWeekends($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $weekendCount = 0;

    for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
        $dayOfWeek = $date->format('w'); // 0=Sunday, 6=Saturday
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $weekendCount++;
        }
    }
    return $weekendCount;
}

/**
 * Get working days for an employee (you'll need to implement your actual logic)
 */
function getWorkingDays($connection, $employee_id, $start_date, $end_date) {
    // This should query your attendance records
    $query = "SELECT COUNT(*) as working_days
              FROM attendance
              WHERE user_id = ?
              AND date BETWEEN ? AND ?
              AND status = 'Present'";

    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "iss", $employee_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    return $data['working_days'] ?? 0;
}

/**
 * Get holidays count between dates
 */
function getHolidaysCount($connection, $start_date, $end_date) {
    $query = "SELECT COUNT(*) as holiday_count
              FROM holidays
              WHERE holiday_date BETWEEN ? AND ?";

    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    return $data['holiday_count'] ?? 0;
}

/**
 * Get employee leaves between dates
 */
function getEmployeeLeaves($connection, $employee_id, $start_date, $end_date) {
    $result = [
        'sick_leaves' => 0,
        'earned_leaves' => 0,
        'half_day' => 0,
        'lwp' => 0
    ];

    $query = "SELECT leave_type, SUM(days) as total_days
              FROM leaves
              WHERE user_id = ?
              AND start_date <= ?
              AND end_date >= ?
              AND status = 'Approved'
              GROUP BY leave_type";

    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "iss", $employee_id, $end_date, $start_date);
    mysqli_stmt_execute($stmt);
    $leave_result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($leave_result)) {
        switch ($row['leave_type']) {
            case 'Sick Leave':
                $result['sick_leaves'] = $row['total_days'];
                break;
            case 'Earned Leave':
                $result['earned_leaves'] = $row['total_days'];
                break;
            case 'Half Day':
                $result['half_day'] = $row['total_days'];
                break;
            case 'Leave Without Pay':
                $result['lwp'] = $row['total_days'];
                break;
        }
    }

    return $result;
}
