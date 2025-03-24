<?php
// Include the database connection
include('connection.php');

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $id = $_POST['id'];
    $checkin_time = $_POST['checkin_time'];
    $checkout_time = $_POST['checkout_time'];
    $location = $_POST['location'];
    $session_status = $_POST['session_status'];
    $session_duration = $_POST['session_duration'];

    // Update the record in the database
    $query = "UPDATE attendance SET
              checkin_time = '$checkin_time',
              checkout_time = '$checkout_time',
              checkin_location = '$location',
              session_status = '$session_status',
              session_duration = '$session_duration'
              WHERE id = $id";

    if (mysqli_query($connection, $query)) {
        // Redirect back to the main page after successful update
        echo "<script>alert('Attendance record updated successfully'); window.location.href='attendance_display.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error updating record: " . mysqli_error($connection) . "');</script>";
    }
}

// Check if the ID is provided in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the record from the database
    $query = "SELECT * FROM attendance WHERE id = $id";
    $result = mysqli_query($connection, $query);

    if (mysqli_num_rows($result)) {
        $row = mysqli_fetch_assoc($result);
    } else {
        echo "<script>alert('Record not found.'); window.location.href='attendance.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('No ID provided.'); window.location.href='attendance.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attendance Record</title>
    <style>
        body {
            background: #2c3e50;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            position: relative; /* Added for positioning the cross button */
        }
        .title {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: bold;
            margin-left: 50px;
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
            justify-content: flex-end;
            margin-top: 20px;
            margin-right: 320px;
        }
        .btn-register {
            padding: 10px 15px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-cancel {
            padding: 10px 15px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            overflow: hidden;
            height: auto;
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
    <a href="attendance.php" class="cross-btn">✖</a> <!-- Cross button -->
    <div class="title">
        <span>Edit Attendance Record for <strong><?php echo $row['user_name']; ?></strong></span>
    </div>
    <form action="" method="POST">
        <!-- Hidden input to store the ID -->
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

        <div class="form-grid">
            <!-- Row 1 -->
            <div class="input_field">
                <label for="checkin_time">Checkin Time <span class="required">*</span></label>
                <input type="datetime-local" name="checkin_time" id="checkin_time" value="<?php echo date('Y-m-d\TH:i', strtotime($row['checkin_time'])); ?>" required>
            </div>
            <div class="input_field">
                <label for="checkout_time">Checkout Time</label>
                <input type="datetime-local" name="checkout_time" id="checkout_time" value="<?php echo $row['checkout_time'] ? date('Y-m-d\TH:i', strtotime($row['checkout_time'])) : ''; ?>">
            </div>

            <!-- Row 2 -->
            <div class="input_field">
                <label for="location">Location <span class="required">*</span></label>
                <input type="text" name="location" id="location" value="<?php echo $row['checkin_location']; ?>" required>
            </div>
            <div class="input_field">
                <label for="session_status">Session Status <span class="required">*</span></label>
                <select name="session_status" id="session_status" required>
                    <option value="active" <?php echo $row['session_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="ended" <?php echo $row['session_status'] === 'ended' ? 'selected' : ''; ?>>Ended</option>
                </select>
            </div>

            <!-- Row 3 -->
            <div class="input_field">
                <label for="session_duration">Session Duration <span class="required">*</span></label>
                <input type="text" name="session_duration" id="session_duration" value="<?php echo $row['session_duration']; ?>" required>
            </div>
        </div>

        <div class="btn-container">
            <button type="submit" class="btn-register">Save Changes</button>
            <button type="button" class="btn-cancel" onclick="window.history.back();">Cancel</button>
        </div>
    </form>
</div>

</body>
</html>
