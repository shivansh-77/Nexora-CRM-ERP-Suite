<?php
session_start(); // Start the session

// Include your database connection file
include('connection.php');

// Check if the user is logged in (optional, depending on your use case)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

// Get today's date
$today = new DateTime();
$todayFormatted = $today->format('Y-m-d');

// Fetch all users from the user_leave_balance table
$leaveBalanceQuery = "SELECT * FROM user_leave_balance";
$stmt = $connection->prepare($leaveBalanceQuery);
$stmt->execute();
$leaveBalanceResult = $stmt->get_result();

if ($leaveBalanceResult && mysqli_num_rows($leaveBalanceResult) > 0) {
    // Prepare the update statement
    $updateQuery = "UPDATE user_leave_balance
                    SET total_earned_leaves = ?, last_updated = ?, next_update = ?
                    WHERE user_id = ?";
    $updateStmt = $connection->prepare($updateQuery);

    // Loop through each user's leave balance
    while ($leaveBalance = $leaveBalanceResult->fetch_assoc()) {
        $user_id = $leaveBalance['user_id'];
        $next_update = $leaveBalance['next_update'];

        // Convert next_update to DateTime object
        $nextUpdateDate = new DateTime($next_update);

        // Check if today's date matches or exceeds the next_update date
        if ($today >= $nextUpdateDate) {
            // Calculate the number of months passed
            $interval = $today->diff($nextUpdateDate);
            $months_passed = $interval->y * 12 + $interval->m;

            // If the day of the month has passed, count it as an additional month
            if ($today->format('d') >= $nextUpdateDate->format('d')) {
                $months_passed++;
            }

            // Calculate earned leaves to add
            $earnedLeavesToAdd = 1.5 * $months_passed;
            $newTotalEarnedLeaves = $leaveBalance['total_earned_leaves'] + $earnedLeavesToAdd;

            // Update next_update to the next month
            $nextUpdateDate->modify("+$months_passed months");
            $nextUpdateDateFormatted = $nextUpdateDate->format('Y-m-d');

            // Update the user_leave_balance table for this user
            $updateStmt->bind_param("dssi", $newTotalEarnedLeaves, $todayFormatted, $nextUpdateDateFormatted, $user_id);
            $updateStmt->execute();
        }
    }

    // Close the update statement
    $updateStmt->close();

    // Return a success response
    echo json_encode(['status' => 'success', 'message' => 'Leave balances updated successfully.']);
} else {
    // Return an error response
    echo json_encode(['status' => 'error', 'message' => 'No users found in leave balance table.']);
}

// Close the database connection
$stmt->close();
$connection->close();
?>
