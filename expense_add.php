<?php
session_start(); // Start the session to access user data
include('connection.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process the form submission
    $voucher_no = mysqli_real_escape_string($connection, $_POST['voucher_no']);
    $expense_type = mysqli_real_escape_string($connection, $_POST['expense_type']);
    $amount = mysqli_real_escape_string($connection, $_POST['amount']);
    $date = mysqli_real_escape_string($connection, $_POST['date']);
    $remark = mysqli_real_escape_string($connection, $_POST['remark']);

    // Get user info from session
    $user_id = isset($_SESSION['user_id']) ? mysqli_real_escape_string($connection, $_SESSION['user_id']) : 0;
    $user_name = isset($_SESSION['user_name']) ? mysqli_real_escape_string($connection, $_SESSION['user_name']) : 'Unknown';

    // Insert the new expense into the database with user info
    $query = "INSERT INTO expense (voucher_no, expense_type, amount, date, remark, user_id, user_name)
              VALUES ('$voucher_no', '$expense_type', '$amount', '$date', '$remark', '$user_id', '$user_name')";

    if (mysqli_query($connection, $query)) {
        echo "<script>alert('Expense added successfully'); window.location.href='expense_display.php';</script>";
    } else {
        echo "<script>alert('Error adding expense: " . mysqli_error($connection) . "');</script>";
    }
}

// Function to generate the next voucher number
function generateVoucherNumber($connection) {
    $currentYear = date('y'); // Get the last two digits of the current year
    $prefix = "EXP/{$currentYear}/";

    // Fetch the last voucher number from the database
    $query = "SELECT voucher_no FROM expense ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($connection, $query);
    $lastVoucherNo = '';

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastVoucherNo = $row['voucher_no'];
    }

    // Extract the series number from the last voucher number
    if (strpos($lastVoucherNo, $prefix) === 0) {
        $lastSeriesNumber = (int)substr($lastVoucherNo, strlen($prefix));
        $nextSeriesNumber = $lastSeriesNumber + 1;
    } else {
        $nextSeriesNumber = 1; // Start from 1 if no matching prefix is found
    }

    // Format the series number with leading zeros
    $nextVoucherNo = $prefix . sprintf('%04d', $nextSeriesNumber);

    return $nextVoucherNo;
}

$nextVoucherNo = generateVoucherNumber($connection);

// Fetch distinct expense types from the database
$expense_types_query = "SELECT DISTINCT expense FROM expense_tracker";
$expense_types_result = mysqli_query($connection, $expense_types_query);
$expense_types = [];

while ($row = mysqli_fetch_assoc($expense_types_result)) {
    $expense_types[] = $row['expense'];
}

// Get today's date
$todayDate = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense</title>
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
            position: relative; /* For positioning the cross button */
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
        .input_field select,
        .input_field textarea {
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
    <a href="expense_display.php" class="cross-btn">✖</a> <!-- Cross button -->
    <div class="title">
        <span>Add New Expense</span>
    </div>
    <form action="" method="POST">
        <div class="form-grid">
            <!-- Row 1 -->
            <div class="input_field">
                <label for="voucher_no">Voucher No. <span class="required">*</span></label>
                <input type="text" name="voucher_no" id="voucher_no" value="<?php echo $nextVoucherNo; ?>" readonly required>
            </div>
            <div class="input_field">
                <label for="expense_type">Expense Type <span class="required">*</span></label>
                <select name="expense_type" id="expense_type" required>
                    <option value="" disabled selected>Select Expense Type</option>
                    <?php foreach ($expense_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Row 2 -->
            <div class="input_field">
                <label for="amount">Amount <span class="required">*</span></label>
                <input type="number" name="amount" id="amount" required>
            </div>
            <div class="input_field">
                <label for="date">Date <span class="required">*</span></label>
                <input type="date" name="date" id="date" value="<?php echo $todayDate; ?>" required>
            </div>

            <!-- Row 3 -->
            <div class="input_field" style="grid-column: span 2;">
                <label for="remark">Remark</label>
                <textarea name="remark" id="remark" rows="4"></textarea>
            </div>
        </div>

        <div class="btn-container">
            <button type="submit" class="btn-register">Register</button>
            <button type="button" class="btn-cancel" onclick="window.location.href='expense_display.php';">Cancel</button>
        </div>
    </form>
</div>

</body>
</html>
