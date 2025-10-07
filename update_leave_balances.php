<?php
session_start();
include('connection.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

$today = new DateTime();
$todayFormatted = $today->format('Y-m-d');
$currentMonth = $today->format('m');
$currentDay = $today->format('d');

$leaveBalanceQuery = "SELECT * FROM user_leave_balance";
$stmt = $connection->prepare($leaveBalanceQuery);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Query preparation failed: ' . $connection->error]);
    exit;
}

$stmt->execute();
$leaveBalanceResult = $stmt->get_result();

$updatedUsers = 0;
$totalUsers = 0;
$debugInfo = [];

if ($leaveBalanceResult && mysqli_num_rows($leaveBalanceResult) > 0) {
    // Prepare update statement outside the loop
    $updateQuery = "UPDATE user_leave_balance
                   SET total_earned_leaves = ?,
                       last_updated = ?,
                       next_update = ?,
                       total_sick_leaves = ?,
                       sick_leaves_taken = ?
                   WHERE user_id = ?";
    $updateStmt = $connection->prepare($updateQuery);

    if (!$updateStmt) {
        echo json_encode(['status' => 'error', 'message' => 'Update preparation failed: ' . $connection->error]);
        exit;
    }

    while ($leaveBalance = $leaveBalanceResult->fetch_assoc()) {
        $totalUsers++;
        $user_id = $leaveBalance['user_id'];
        $user_name = $leaveBalance['name'];
        $next_update = $leaveBalance['next_update'];
        $current_earned_leaves = (float)$leaveBalance['total_earned_leaves'];
        $current_sick_leaves = (float)$leaveBalance['total_sick_leaves'];
        $sick_leaves_taken = (float)$leaveBalance['sick_leaves_taken'];
        $doj = new DateTime($leaveBalance['doj']);
        $dojDay = $doj->format('d');

        $userDebug = [
            'user_id' => $user_id,
            'name' => $user_name,
            'current_leaves' => $current_earned_leaves,
            'next_update' => $next_update,
            'today' => $todayFormatted,
            'update_needed' => false,
            'months_passed' => 0,
            'leaves_added' => 0,
            'new_total' => $current_earned_leaves,
            'new_next_update' => $next_update
        ];

        $nextUpdateDate = new DateTime($next_update);

        // Check if we need to process this user
        if ($today >= $nextUpdateDate) {
            $userDebug['update_needed'] = true;

            // Get the day number from DOJ (e.g., 10)
            $anniversaryDay = $dojDay;

            // Create a date for the next_update's anniversary day in its month
            $currentProcessDate = clone $nextUpdateDate;

            // Count how many monthly anniversary days have passed
            $months_passed = 0;
            $datesProcessed = [];

            while ($currentProcessDate <= $today) {
                $datesProcessed[] = $currentProcessDate->format('Y-m-d');
                $months_passed++;
                $currentProcessDate->modify('+1 month');
            }

            $userDebug['months_passed'] = $months_passed;
            $userDebug['dates_processed'] = $datesProcessed;

            if ($months_passed > 0) {
                $earnedLeavesToAdd = 1.5 * $months_passed;
                $newTotalEarnedLeaves = $current_earned_leaves + $earnedLeavesToAdd;

                // The new next_update is the next anniversary date after today
                $newNextUpdateDate = clone $nextUpdateDate;
                $newNextUpdateDate->modify("+$months_passed months");

                // If we landed on today or before, go one more month
                if ($newNextUpdateDate <= $today) {
                    $newNextUpdateDate->modify('+1 month');
                }

                $nextUpdateDateFormatted = $newNextUpdateDate->format('Y-m-d');

                // Reset sick leaves if it's April 1st
                if ($today->format('m-d') == '04-01') {
                    $current_sick_leaves = 6;
                    $sick_leaves_taken = 0;
                }

                $userDebug['leaves_added'] = $earnedLeavesToAdd;
                $userDebug['new_total'] = $newTotalEarnedLeaves;
                $userDebug['new_next_update'] = $nextUpdateDateFormatted;

                // Execute the update
                $updateStmt->bind_param("dssddi",
                    $newTotalEarnedLeaves,
                    $todayFormatted,
                    $nextUpdateDateFormatted,
                    $current_sick_leaves,
                    $sick_leaves_taken,
                    $user_id
                );

                if ($updateStmt->execute()) {
                    if ($updateStmt->affected_rows > 0) {
                        $updatedUsers++;
                        $userDebug['status'] = 'updated';
                    } else {
                        $userDebug['status'] = 'no_change';
                    }
                } else {
                    $userDebug['status'] = 'error';
                    $userDebug['error'] = $updateStmt->error;
                }
            }
        }

        $debugInfo[] = $userDebug;
    }

    $updateStmt->close();

    echo json_encode([
        'status' => 'success',
        'message' => "Leave balances processed. Updated $updatedUsers out of $totalUsers users.",
        'updated_users' => $updatedUsers,
        'total_users' => $totalUsers,
        'debug_info' => $debugInfo
    ], JSON_PRETTY_PRINT);

} else {
    echo json_encode(['status' => 'error', 'message' => 'No users found in leave balance table.']);
}

$stmt->close();
$connection->close();
?>
