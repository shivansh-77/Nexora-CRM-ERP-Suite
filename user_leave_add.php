<?php
session_start(); // Start the session

// Include your database connection file
include('connection.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('User not logged in.'); window.location.href='login.php';</script>";
    exit;
}

// Fetch the user's leave balance details
$user_id = $_SESSION['user_id'];
$leaveBalanceQuery = "SELECT * FROM user_leave_balance WHERE user_id = ?";
$stmt = $connection->prepare($leaveBalanceQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$leaveBalanceResult = $stmt->get_result();

if ($leaveBalanceResult && mysqli_num_rows($leaveBalanceResult) > 0) {
    $leaveBalance = $leaveBalanceResult->fetch_assoc();

    // Fetch the relevant dates
    $next_update = $leaveBalance['next_update'];
    $today = new DateTime(); // Current date

    // Convert next_update to DateTime object
    $nextUpdateDate = new DateTime($next_update);

    // Check if next_update is today or in the past
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

        // Check if the current date is April 1st or within the month of April
        if ($today->format('m-d') == '04-01' || $today->format('m') == '04') {
            // Reset sick leaves
            $total_sick_leaves = 6;
            $sick_leaves_taken = 0;

            // Update the user_leave_balance table with reset sick leaves
            $updateQuery = "UPDATE user_leave_balance
                            SET total_earned_leaves = ?, last_updated = ?, next_update = ?,
                                total_sick_leaves = ?, sick_leaves_taken = ?
                            WHERE user_id = ?";
            $stmt = $connection->prepare($updateQuery);
            $stmt->bind_param("dssiid", $newTotalEarnedLeaves, $today->format('Y-m-d'), $nextUpdateDateFormatted, $total_sick_leaves, $sick_leaves_taken, $user_id);
        } else {
            // Update the user_leave_balance table without resetting sick leaves
            $updateQuery = "UPDATE user_leave_balance
                            SET total_earned_leaves = ?, last_updated = ?, next_update = ?
                            WHERE user_id = ?";
            $stmt = $connection->prepare($updateQuery);
            $stmt->bind_param("dssi", $newTotalEarnedLeaves, $today->format('Y-m-d'), $nextUpdateDateFormatted, $user_id);
        }

        $stmt->execute();

        // Check if the update was successful
        if ($stmt->affected_rows > 0) {
            echo "Leave balance updated successfully.";
        } else {
            echo "Failed to update leave balance.";
        }
    } else {
        echo "No update needed. Next update is in the future.";
    }
} else {
    // Handle case where leave balance is not found
    echo "<script>alert('Leave balance not found.'); window.location.href='user_leave_display.php?id=" . $_SESSION['user_id'] . "';</script>";
    exit;
}

// Process form submission only when POST request is made
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $leave_type = mysqli_real_escape_string($connection, $_POST['leave_type']);
    $leave_duration = mysqli_real_escape_string($connection, $_POST['leave_duration']);
    $start_date = mysqli_real_escape_string($connection, $_POST['start_date']);
    $end_date = ($leave_duration === 'Half Day') ? mysqli_real_escape_string($connection, $_POST['start_date']) : mysqli_real_escape_string($connection, $_POST['end_date']);
    $approver_id = mysqli_real_escape_string($connection, $_POST['approver_id']);

    // Validate dates for other leave types
    if ($leave_duration !== 'Half Day' && strtotime($start_date) > strtotime($end_date)) {
        echo "<script>alert('Invalid date: Start date cannot be greater than end date.'); window.location.href='user_leave_add.php';</script>";
        exit;
    }

    // Fetch the user's name based on the user_id
    $user_query = "SELECT name FROM login_db WHERE id = ?";
    $stmt = $connection->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result && mysqli_num_rows($user_result) > 0) {
        $user_row = $user_result->fetch_assoc();
        $user_name = $user_row['name']; // Fetch the user's name
    } else {
        echo "<script>alert('Invalid user.'); window.location.href='user_leave_add.php';</script>";
        exit;
    }

    // Fetch the approver's name based on the approver_id
    $approver_query = "SELECT name FROM login_db WHERE id = ?";
    $stmt = $connection->prepare($approver_query);
    $stmt->bind_param("i", $approver_id);
    $stmt->execute();
    $approver_result = $stmt->get_result();

    if ($approver_result && mysqli_num_rows($approver_result) > 0) {
        $approver_row = $approver_result->fetch_assoc();
        $approver_name = $approver_row['name'];

        // Calculate the total number of leave days
        if ($leave_duration === 'Half Day') {
            $total_days = 0.5; // Set total_days to 0.5 for Half Day
        } else {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $interval = $start->diff($end);
            $total_days = $interval->days + 1; // Include both start and end dates
        }

        // Check leave balance before applying for leave
        if ($leave_type === 'Earned Leave' || $leave_duration === 'Half Day') {
            $available_leaves = $leaveBalance['total_earned_leaves'] - ($leaveBalance['earned_leaves_taken'] + $leaveBalance['half_day_leaves_taken']);
            if ($total_days > $available_leaves) {
                echo "<script>alert('You do not have enough earned leave balance.'); window.location.href='user_leave_add.php';</script>";
                exit;
            }
        } elseif ($leave_type === 'Sick Leave') {
            $available_sick_leaves = 6.00 - $leaveBalance['sick_leaves_taken'];
            if ($total_days > $available_sick_leaves) {
                echo "<script>alert('You do not have enough sick leave balance.'); window.location.href='user_leave_add.php';</script>";
                exit;
            }
        }

        // Begin transaction
        $connection->begin_transaction();
        $success = true;

        try {
            if ($leave_duration === 'Half Day') {
                // For Half Day, just insert one record as before
                $query = "INSERT INTO user_leave (user_id, user_name, leave_type, leave_duration, start_date, end_date, approver_id, approver_name, total_days)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($query);
                $stmt->bind_param("isssssisd", $user_id, $user_name, $leave_type, $leave_duration, $start_date, $end_date, $approver_id, $approver_name, $total_days);
                $stmt->execute();
            } else {
                // For other leave types, create one record per day
                $start = new DateTime($start_date);
                $end = new DateTime($end_date);
                $end->modify('+1 day'); // To include the end date in the period
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($start, $interval, $end);

                foreach ($period as $date) {
    $current_date = $date->format('Y-m-d');
    $query = "INSERT INTO user_leave (user_id, user_name, leave_type, leave_duration, start_date, end_date, approver_id, approver_name, total_days)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    $total_days_for_day = 1.0; // Each day counts as 1 day
    $stmt->bind_param("isssssisd", $user_id, $user_name, $leave_type, $leave_duration, $current_date, $current_date, $approver_id, $approver_name, $total_days_for_day);
    $stmt->execute();
}
            }

            // Update leave balance after successful leave application
            if ($leave_type === 'Earned Leave') {
                $leaveBalance['earned_leaves_taken'] += $total_days;
            } elseif ($leave_duration === 'Half Day') {
                $leaveBalance['half_day_leaves_taken'] += $total_days;
            } elseif ($leave_type === 'Sick Leave') {
                $leaveBalance['sick_leaves_taken'] += $total_days;
            } elseif ($leave_type === 'Leave Without Pay') {
                $leaveBalance['total_lwp'] += $total_days;
            }

            // Update the leave balance in the database
            $updateBalanceQuery = "UPDATE user_leave_balance
                                   SET earned_leaves_taken = ?, half_day_leaves_taken = ?, sick_leaves_taken = ?, total_lwp = ?
                                   WHERE user_id = ?";
            $stmt = $connection->prepare($updateBalanceQuery);
            $stmt->bind_param("ddddi", $leaveBalance['earned_leaves_taken'], $leaveBalance['half_day_leaves_taken'], $leaveBalance['sick_leaves_taken'], $leaveBalance['total_lwp'], $user_id);
            $stmt->execute();

            // Commit the transaction if all queries succeeded
            $connection->commit();

            echo "<script>
                alert('Leave request submitted successfully');
                window.location.href='user_leave_display.php?id=" . $_SESSION['user_id'] . "&name=" . urlencode($_SESSION['user_name']) . "';
            </script>";
        } catch (Exception $e) {
            // Rollback the transaction on error
            $connection->rollback();
            echo "<script>alert('Error submitting leave request: " . $e->getMessage() . "'); window.location.href='user_leave_add.php';</script>";
        }
    } else {
        echo "<script>alert('Invalid approver selected.'); window.location.href='user_leave_add.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Leave</title>
    <style>
        body {
            background: #2c3e50;
            font-family: Arial, sans-serif;
        }
        .leave-balance {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            text-align: center;
        }
        .leave-balance h2 {
            margin-bottom: 15px;
            font-size: 20px;
            font-weight: bold;
        }
        .leave-balance p {
            margin: 5px 0;
            font-size: 16px;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            position: relative;
        }
        .title {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .input_field {
            display: flex;
            flex-direction: column;
        }
        .input_field label {
            margin-bottom: 5px;
        }
        .input_field input,
        .input_field select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .required {
            color: red;
        }
        .btn-container {
            display: flex;
            justify-content: center; /* Center the buttons */
            margin-top: 20px;
        }
        .btn-register,
        .btn-cancel {
            padding: 10px 15px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-cancel {
            margin-left: 10px;
        }
        .cross-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 14px;
            cursor: pointer;
            color: #2c3e50;
            text-decoration: none;
        }
    </style>
</head>
<body>

  <!-- Leave Balance Section -->
  <div class="leave-balance">
      <h2>Leave Balance</h2>
      <div style="display: flex; justify-content: space-between;">
          <!-- Left Side: Sick Leaves -->
          <div>
              <p>Total Sick Leaves: 6.00</p>
              <p>Sick Leaves Taken: <?php echo $leaveBalance['sick_leaves_taken']; ?></p>
              <p>Sick Leaves Left: <?php echo 6.00 - $leaveBalance['sick_leaves_taken']; ?></p>
          </div>
          <!-- Right Side: Earned Leaves and Half Days -->
          <div>
              <p>Total Earned Leaves: <?php echo $leaveBalance['total_earned_leaves']; ?></p>
              <p>Leaves Taken: <?php echo $leaveBalance['earned_leaves_taken'] + $leaveBalance['half_day_leaves_taken']; ?></p>
              <p>Earned Leaves Left: <?php echo $leaveBalance['total_earned_leaves'] - ($leaveBalance['earned_leaves_taken'] + $leaveBalance['half_day_leaves_taken']); ?></p>
          </div>
      </div>
  </div>

<!-- Leave Application Form -->
<div class="container">
  <a href="user_leave_display.php?id=<?php echo $_SESSION['user_id']; ?>&name=<?php echo urlencode($_SESSION['user_name']); ?>" class="cross-btn">✖</a>

    <div class="title">
        <span>Apply for Leave</span>
    </div>
    <form action="" method="POST">
        <div class="form-grid">
            <!-- Row 1 -->
            <div class="input_field">
                <label for="leave_type">Leave Type <span class="required">*</span></label>
                <select name="leave_type" id="leave_type" required onchange="handleLeaveTypeChange()">
                    <option value="Sick Leave">Sick Leave</option>
                    <option value="Earned Leave">Earned Leave</option>
                    <option value="Leave Without Pay">Leave Without Pay</option>
                </select>
            </div>
            <div class="input_field">
                <label for="leave_duration">Leave Duration <span class="required">*</span></label>
                <select name="leave_duration" id="leave_duration" required onchange="handleLeaveDurationChange()">
                    <option value="Full Day" selected>Full Day</option>
                    <option value="Half Day">Half Day</option>
                </select>
            </div>

            <!-- Row 2 -->
            <div class="input_field">
                <label for="start_date">Start Date <span class="required">*</span></label>
                <input type="date" name="start_date" id="start_date" required onchange="handleStartDateChange()">
            </div>
            <div class="input_field">
                <label for="end_date">End Date <span class="required">*</span></label>
                <input type="date" name="end_date" id="end_date" required>
            </div>

            <!-- Row 3 -->
            <div class="input_field">
                <label for="approver_id">Approver <span class="required">*</span></label>
                <select name="approver_id" id="approver_id" required>
                    <?php
                    $query = "SELECT id, name FROM login_db WHERE role = 'Admin'";
                    $result = mysqli_query($connection, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<option value='{$row['id']}'>{$row['name']}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="btn-container">
            <button type="submit" class="btn-register">Submit</button>
            <button type="button" class="btn-cancel" onclick="window.location.href='user_leave_display.php?id=<?php echo $_SESSION['user_id']; ?>';">Cancel</button>
        </div>
    </form>
</div>

<script>
    function handleLeaveDurationChange() {
        const leaveDuration = document.getElementById('leave_duration').value;
        const endDateInput = document.getElementById('end_date');

        if (leaveDuration === 'Half Day') {
            // Disable the end_date field and set it to the same as start_date
            endDateInput.disabled = true;
            const startDate = document.getElementById('start_date').value;
            endDateInput.value = startDate;
        } else {
            // Enable the end_date field
            endDateInput.disabled = false;
        }
    }

    function handleStartDateChange() {
        const leaveDuration = document.getElementById('leave_duration').value;
        const endDateInput = document.getElementById('end_date');

        if (leaveDuration === 'Half Day') {
            // Set end_date to the same as start_date
            const startDate = document.getElementById('start_date').value;
            endDateInput.value = startDate;
        }
    }
</script>

</body>
</html>
