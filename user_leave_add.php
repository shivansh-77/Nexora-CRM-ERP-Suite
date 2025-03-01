<?php
session_start(); // Start the session

// Include your database connection file
include('connection.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('User not logged in.'); window.location.href='login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $user_id = $_SESSION['user_id']; // Assuming user ID is stored in session
    $leave_type = mysqli_real_escape_string($connection, $_POST['leave_type']);
    $start_date = mysqli_real_escape_string($connection, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($connection, $_POST['end_date']);
    $approver_id = mysqli_real_escape_string($connection, $_POST['approver_id']);

    // Validate dates
    if (strtotime($start_date) > strtotime($end_date)) {
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
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $total_days = $interval->days + 1; // Include both start and end dates

        // Insert the new leave request into the database using prepared statements
        $query = "INSERT INTO user_leave (user_id, user_name, leave_type, start_date, end_date, approver_id, approver_name, total_days)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("issssisi", $user_id, $user_name, $leave_type, $start_date, $end_date, $approver_id, $approver_name, $total_days);

        if ($stmt->execute()) {
            echo "<script>alert('Leave request submitted successfully'); window.location.href='user_leave_display.php?id=" . $_SESSION['user_id'] . "';</script>";
        } else {
            echo "<script>alert('Error submitting leave request: " . $stmt->error . "'); window.location.href='user_leave_add.php';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Invalid approver selected.'); window.location.href='user_leave_add.php';</script>";
    }
} else {
    // Redirect if the request method is not POST
    echo "<script>alert('Invalid request method.'); window.location.href='user_leave_add.php';</script>";
    exit;
}

// Close the database connection
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Leave</title>
    <style>
        body {
            background: #2c3e50;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
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

<div class="container">
    <a href="user_leave_display.php?id=<?php echo $_SESSION['user_id']; ?>" class="cross-btn">✖</a> <!-- Cross button redirects to user_leave_display.php -->
    <div class="title">
        <span>Apply for Leave</span>
    </div>
    <form action="" method="POST">
        <div class="form-grid">
            <!-- Row 1 -->
            <div class="input_field">
                <label for="leave_type">Leave Type <span class="required">*</span></label>
                <select name="leave_type" id="leave_type" required>
                    <option value="Sick Leave">Sick Leave</option>
                    <option value="Earned Leave">Earned Leave</option>
                </select>
            </div>
            <div class="input_field">
                <label for="start_date">Start Date <span class="required">*</span></label>
                <input type="date" name="start_date" id="start_date" required>
            </div>

            <!-- Row 2 -->
            <div class="input_field">
                <label for="end_date">End Date <span class="required">*</span></label>
                <input type="date" name="end_date" id="end_date" required>
            </div>
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

</body>
</html>
