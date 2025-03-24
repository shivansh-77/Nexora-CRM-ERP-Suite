<?php
session_start();
include('connection.php');
// include('topbar.php');

// Check if ID is passed via GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the data for the given ID
    $query = "SELECT * FROM expense WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        echo "<script>alert('Record not found!'); window.location.href='expense_display.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('Invalid request!'); window.location.href='expense_display.php';</script>";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $voucher_no = mysqli_real_escape_string($connection, $_POST['voucher_no']);
    $expense_type = mysqli_real_escape_string($connection, $_POST['expense_type']);
    $amount = mysqli_real_escape_string($connection, $_POST['amount']);
    $date = mysqli_real_escape_string($connection, $_POST['date']);
    $remark = mysqli_real_escape_string($connection, $_POST['remark']);

    // Update the record in the database
    $updateQuery = "UPDATE expense SET voucher_no = ?, expense_type = ?, amount = ?, date = ?, remark = ? WHERE id = ?";
    $stmt = $connection->prepare($updateQuery);
    $stmt->bind_param("ssdssi", $voucher_no, $expense_type, $amount, $date, $remark, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Expense record updated successfully!'); window.location.href='expense_display.php';</script>";
    } else {
        echo "<script>alert('Error updating expense record.');</script>";
    }

    $stmt->close();
}

// Fetch distinct expense types from the database
$expense_types_query = "SELECT DISTINCT expense FROM expense_tracker";
$expense_types_result = mysqli_query($connection, $expense_types_query);
$expense_types = [];

while ($row_type = mysqli_fetch_assoc($expense_types_result)) {
    $expense_types[] = $row_type['expense'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Expense</title>
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
            position: relative;
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
        <span>Edit Expense</span>
    </div>
    <form action="" method="POST">
        <div class="form-grid">
            <!-- Row 1 -->
            <div class="input_field" >
                <label for="voucher_no">Voucher No. <span class="required">*</span></label>
                <input type="text" name="voucher_no" id="voucher_no" value="<?php echo htmlspecialchars($row['voucher_no']); ?>" readonly>
            </div>
            <div class="input_field">
                <label for="expense_type">Expense Type <span class="required">*</span></label>
                <select name="expense_type" id="expense_type" required>
                    <option value="" disabled>Select Expense Type</option>
                    <?php foreach ($expense_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($row['expense_type'] == $type) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Row 2 -->
            <div class="input_field">
                <label for="amount">Amount <span class="required">*</span></label>
                <input type="number" name="amount" id="amount" value="<?php echo htmlspecialchars($row['amount']); ?>" required>
            </div>
            <div class="input_field">
                <label for="date">Date <span class="required">*</span></label>
                <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($row['date']); ?>" required>
            </div>

            <!-- Row 3 -->
            <div class="input_field" style="grid-column: span 2;">
                <label for="remark">Remark</label>
                <textarea name="remark" id="remark" rows="4"><?php echo htmlspecialchars($row['remark']); ?></textarea>
            </div>
        </div>

        <div class="btn-container">
            <button type="submit" class="btn-register">Update</button>
            <button type="button" class="btn-cancel" onclick="window.location.href='expense_display.php';">Cancel</button>
        </div>
    </form>
</div>

</body>
</html>
