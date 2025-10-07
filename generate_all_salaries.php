<?php
session_start();
include('connection.php'); // Include your database connection

// Get the data from the request
$data = json_decode(file_get_contents('php://input'), true);
$fy_code = $data['fy_code'];
$month_id = $data['month_id'];
$month = $data['month'];
$start_date = $data['start_date'];
$end_date = $data['end_date'];

// Initialize counters and error tracking
$processed_count = 0;
$error_count = 0;
$errors = [];

// Fetch all employees from login_db
$employee_query = "SELECT id, name, salary FROM login_db";
$employee_result = mysqli_query($connection, $employee_query);
$employees = mysqli_fetch_all($employee_result, MYSQLI_ASSOC);

// Start transaction for data integrity
mysqli_begin_transaction($connection);

try {
    foreach ($employees as $employee) {
        $employee_id = $employee['id'];
        $employee_name = $employee['name'];
        $salary = $employee['salary'];

        try {
            // Calculate total days
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $interval = $start->diff($end);
            $total_days = $interval->days + 1; // Include both start and end dates

            // Calculate weekends
            $weekends = 0;
            for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
                $dayOfWeek = $date->format('N'); // 1 (Monday) to 7 (Sunday)
                if ($dayOfWeek >= 6) { // Saturday or Sunday
                    $weekends++;
                }
            }

            // Fetch working days, shortcoming entries, and shortcoming days
            $working_days_data = fetchWorkingDays($employee_id, $start_date, $end_date);
            $working_days = $working_days_data['working_days'];
            $shortcoming_entries = $working_days_data['shortcoming_entries'];
            $shortcoming_days = $working_days_data['shortcoming_days'];

            // Fetch holidays
            $holidays = fetchHolidays($start_date, $end_date);

            // Fetch leaves
            $leaves = fetchLeaves($employee_id, $start_date, $end_date);
            $sick_leaves = $leaves['sickLeaves'];
            $earned_leaves = $leaves['earnedLeaves'];
            $lwp = $leaves['lwp'];

            // Calculate payable days
            $payable_days = $working_days + $weekends + $holidays + $sick_leaves + $earned_leaves - $shortcoming_days;

            // Calculate salary per day
            $salary_per_day = $salary / $total_days;

            // Calculate payable salary
            $payable_salary = $payable_days * $salary_per_day;

            // Additional fund (assuming it's zero for this example)
            $additional_fund = 0;

            // Calculate total amount
            $total_amount = $payable_salary + $additional_fund;

            // Check if salary record already exists for this employee and month
            $check_query = "SELECT id FROM salary WHERE employee_id = $employee_id AND month = '$month' AND fy_code = '$fy_code'";
            $check_result = mysqli_query($connection, $check_query);

            if (mysqli_num_rows($check_result)) {
                // Record exists, skip this employee
                continue;
            }

            // Generate base for salary_sheet_no
            $current_month = date('m', strtotime($start_date)); // Get month as two digits (04 for April)
            $current_year_short = date('y', strtotime($start_date)); // Get year as two digits (25 for 2025)
            $base_sheet_no = "SAL/{$current_year_short}{$current_month}/";

            // Get the last sequence number used for this month/year
            $last_sequence = 0;
            $last_sheet_query = "SELECT salary_sheet_no FROM salary
                                WHERE salary_sheet_no LIKE '{$base_sheet_no}%'
                                ORDER BY id DESC LIMIT 1";
            $last_sheet_result = mysqli_query($connection, $last_sheet_query);

            if ($last_sheet_data = mysqli_fetch_assoc($last_sheet_result)) {
                preg_match('/\/(\d{4})$/', $last_sheet_data['salary_sheet_no'], $matches);
                if ($matches) {
                    $last_sequence = intval($matches[1]);
                }
            }

            // Increment sequence for new record
            $last_sequence++;
            $new_sequence = str_pad($last_sequence, 4, '0', STR_PAD_LEFT);
            $salary_sheet_no = $base_sheet_no . $new_sequence;

            // Insert new record
            $insert_query = "INSERT INTO salary (
                            employee_id, employee_name, month, fy_code, month_id,
                            working_days, weekends, holidays, sick_leaves, earned_leaves,
                            lwp, payable_days, total_days, salary_per_day,
                            payable_salary, additional_fund, total_amount, salary_sheet_no,
                            shortcoming_entries, shortcoming_days, created_at, status
                            ) VALUES (
                            $employee_id, '$employee_name', '$month', '$fy_code', '$month_id',
                            $working_days, $weekends, $holidays, $sick_leaves, $earned_leaves,
                            $lwp, $payable_days, $total_days, $salary_per_day,
                            $payable_salary, $additional_fund, $total_amount, '$salary_sheet_no',
                            '$shortcoming_entries', '$shortcoming_days', NOW(), 'Pending')";

            if (mysqli_query($connection, $insert_query)) {
                $processed_count++;
            } else {
                $error_count++;
                $errors[] = "Failed to generate salary for $employee_name: " . mysqli_error($connection);
            }
        } catch (Exception $e) {
            $error_count++;
            $errors[] = "Error processing $employee_name: " . $e->getMessage();
        }
    }

    // Commit transaction if no errors
    if ($error_count === 0) {
        mysqli_commit($connection);
    } else {
        mysqli_rollback($connection);
    }
} catch (Exception $e) {
    mysqli_rollback($connection);
    $error_count++;
    $errors[] = "Transaction failed: " . $e->getMessage();
}

// Determine response based on results
if ($processed_count > 0) {
    $message = "Successfully generated salary sheets for $processed_count employees";
    if ($error_count > 0) {
        $message .= ", with $error_count errors";
    }
    echo json_encode([
        'success' => true,
        'message' => $message,
        'processed' => $processed_count,
        'errors' => $error_count > 0 ? $errors : null
    ]);
} else if ($error_count > 0) {
    // We had errors while trying to process
    echo json_encode([
        'success' => false,
        'message' => "Failed to generate any salary sheets",
        'errors' => $errors
    ]);
} else {
    // No records processed but no errors means all employees already had salary sheets
    echo json_encode([
        'success' => true,
        'message' => "All employees already have salary sheets for this month. No new sheets were created.",
        'processed' => 0,
        'errors' => null
    ]);
}

// Helper functions to fetch data
function fetchWorkingDays($employee_id, $start_date, $end_date) {
    global $connection;
    $min_shift_query = "SELECT minimum_shift FROM company_card WHERE id = 1";
    $min_shift_result = mysqli_query($connection, $min_shift_query);
    $min_shift = mysqli_fetch_assoc($min_shift_result)['minimum_shift'];

    $query = "SELECT session_duration FROM attendance WHERE user_id = $employee_id AND checkin_time BETWEEN '$start_date' AND '$end_date'";
    $result = mysqli_query($connection, $query);

    $working_days = 0;
    $shortcoming_entries = 0;
    $shortcoming_days = 0;

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

    return [
        'working_days' => $working_days,
        'shortcoming_entries' => $shortcoming_entries,
        'shortcoming_days' => $shortcoming_days
    ];
}

function fetchHolidays($start_date, $end_date) {
    global $connection;
    $holiday_query = "SELECT COUNT(*) AS holidayCount FROM holidays WHERE start_date BETWEEN '$start_date' AND '$end_date'";
    $holiday_result = mysqli_query($connection, $holiday_query);
    $holiday_data = mysqli_fetch_assoc($holiday_result);
    return $holiday_data['holidayCount'];
}

function fetchLeaves($employee_id, $start_date, $end_date) {
    global $connection;
    $leave_query = "
        SELECT leave_type, SUM(total_days) AS totalLeaveDays
        FROM user_leave
        WHERE user_id = $employee_id AND start_date BETWEEN '$start_date' AND '$end_date' AND status = 'Approved'
        GROUP BY leave_type
    ";
    $leave_result = mysqli_query($connection, $leave_query);

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
    return $leaves;
}
