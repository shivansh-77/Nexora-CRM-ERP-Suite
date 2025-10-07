<?php
session_start();
include('connection.php');

// Display success/error messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
    unset($_SESSION['error']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['employee_name'])) {
        $_SESSION['error'] = "Employee name is required";
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit();
    }

    // Get all form values
    $employee_id = $_POST['employee_name'];
    $month = $_POST['month'];
    $fy_code = $_POST['fy_code'];
    $month_id = $_GET['month_id']; // From URL parameters

    // Get employee name and salary from login_db
    $employee_query = "SELECT name, salary FROM login_db WHERE id = $employee_id";
    $employee_result = mysqli_query($connection, $employee_query);

    if (!$employee_data = mysqli_fetch_assoc($employee_result)) {
        $_SESSION['error'] = "Employee not found";
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit();
    }

    $employee_name = $employee_data['name'];
    $salary = $employee_data['salary'];

    // Get all calculated values from form
    $working_days = floatval($_POST['working_days'] ?? 0);
    $weekends = floatval($_POST['weekends'] ?? 0);
    $holidays = floatval($_POST['holidays'] ?? 0);
    $sick_leaves = floatval($_POST['sick_leaves'] ?? 0);
    $earned_leaves = floatval($_POST['earned_leaves'] ?? 0);
    $lwp = floatval($_POST['lwp'] ?? 0);
    $payable_days = floatval($_POST['payable_days'] ?? 0);
    $total_days = floatval($_POST['total_days'] ?? 0);
    $salary_per_day = floatval($_POST['salary_per_day'] ?? 0);
    $payable_salary = floatval($_POST['payable_salary'] ?? 0);
    $additional_fund = floatval($_POST['additional_fund'] ?? 0);
    $total_amount = floatval($_POST['total_amount'] ?? 0);

    // New fields
    $shortcoming_entries = floatval($_POST['shortcoming_entries'] ?? 0);
    $shortcoming_days = floatval($_POST['shortcoming_days'] ?? 0);

    // Check if salary record already exists for this employee and month
    $check_query = "SELECT id FROM salary WHERE employee_id = $employee_id AND month = '$month' AND fy_code = '$fy_code'";
    $check_result = mysqli_query($connection, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // Record exists - show JavaScript alert and redirect
        echo "<script>
            alert('Salary record for employee {$employee_name} in {$month} already exists');
            window.location.href = 'salary_sheet1.php?fy_code=".urlencode($fy_code)."&month_id=".$month_id."&month=".urlencode($month)."&start_date=".urlencode($start_date)."&end_date=".urlencode($end_date)."';
        </script>";
        exit();
    }

    // Generate base for salary_sheet_no
$current_month = date('m', strtotime($month)); // Get month as two digits (04 for April)
$current_year_short = date('y', strtotime($month)); // Get year as two digits (25 for 2025)
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
                    '$employee_id', '$employee_name', '$month', '$fy_code', '$month_id',
                    '$working_days', '$weekends', '$holidays', '$sick_leaves', '$earned_leaves',
                    '$lwp', '$payable_days', '$total_days', '$salary_per_day',
                    '$payable_salary', '$additional_fund', '$total_amount', '$salary_sheet_no',
                    '$shortcoming_entries', '$shortcoming_days', NOW(), 'Pending')";

    // Execute the query
    if (mysqli_query($connection, $insert_query)) {
        $_SESSION['success'] = "Salary record created successfully for {$employee_name}";
        header("Location: salary_sheets.php?fy_code=".urlencode($fy_code)."&month_id=".$month_id."&month=".urlencode($month));
        exit();
    } else {
        $_SESSION['error'] = "Error creating salary record: " . mysqli_error($connection);
        header("Location: salary_sheets.php?fy_code=".urlencode($fy_code)."&month_id=".$month_id."&month=".urlencode($month)."&start_date=".urlencode($start_date)."&end_date=".urlencode($end_date));
        exit();
    }
}

// Check if the required parameters are set in the URL
if (isset($_GET['fy_code']) && isset($_GET['month_id']) && isset($_GET['month']) && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    // Retrieve the parameters from the URL
    $fy_code = urldecode($_GET['fy_code']);
    $month_id = $_GET['month_id'];
    $month = urldecode($_GET['month']);
    $start_date = urldecode($_GET['start_date']);
    $end_date = urldecode($_GET['end_date']);

    // Fetch company working hours and working days per week
    $company_query = "SELECT working, working_days FROM company_card LIMIT 1";
    $company_result = mysqli_query($connection, $company_query);
    $company_data = mysqli_fetch_assoc($company_result);

    $working_hours = $company_data['working'];
    $working_days_per_week = $company_data['working_days'];

    // Fetch employee names and salaries from login_db
    $employee_query = "SELECT id, name, salary FROM login_db";
    $employee_result = mysqli_query($connection, $employee_query);
    $employees = mysqli_fetch_all($employee_result, MYSQLI_ASSOC);
} else {
    // Handle the case where the required parameters are not set
    $_SESSION['error'] = "Required parameters are missing";
    header("Location: salary_sheets.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href=""> <!-- Link your external CSS file -->
    <title>Salary Sheet</title>
    <style>
        body {
            background: #2c3e50;
            font-family: arial, sans-serif;
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
        .button-group {
            display: flex;
            gap: 8px;
            margin: 10px 0;
        }

        .button-group button,
        .button-group a.btn-primary {
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-unlock {
            background-color: #4CAF50;
            color: white;
            border: none;
        }

        .btn-lock {
            background-color: #f44336;
            color: white;
            border: none;
        }

        .btn-primary {
            background-color: #2196F3;
            color: white;
            border: none;
        }
    </style>
</head>
<body>

  <div class="container">
    <a href="salary_sheets.php?fy_code=<?php echo urlencode($fy_code); ?>&month_id=<?php echo $month_id; ?>&month=<?php echo urlencode($month); ?>" class="cross-btn">✖</a>
    <div class="title">
        <span>Salary Sheet</span>
    </div>
    <form action="" method="POST">
        <div class="form-grid">
            <!-- Employee Name -->
            <div class="input_field">
                <label for="employee_name">Employee Name <span class="required">*</span></label>
                <select name="employee_name" id="employee_name" required>
                    <option value="">Select Employee</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?php echo $employee['id']; ?>" data-salary="<?php echo $employee['salary']; ?>">
                            <?php echo $employee['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Month -->
            <div class="input_field">
                <label for="month">Month <span class="required">*</span></label>
                <input type="text" name="month" id="month" value="<?php echo htmlspecialchars($month); ?>" readonly>
                <span id="month-warning" style="color: red; display: none;">Salary sheet already created for this month.</span>
            </div>

            <!-- Hidden FY Code -->
            <input type="hidden" name="fy_code" value="<?php echo htmlspecialchars($fy_code); ?>">

            <!-- Working Days -->
            <div class="input_field">
                <label for="working_days">Working Days</label>
                <input type="number" name="working_days" id="working_days" readonly>
            </div>

            <!-- Weekends -->
            <div class="input_field">
                <label for="weekends">Weekends</label>
                <input type="number" name="weekends" id="weekends" readonly>
            </div>

            <!-- Holidays -->
            <div class="input_field">
                <label for="holidays">Holidays</label>
                <input type="number" name="holidays" id="holidays" readonly>
            </div>

            <!-- Sick Leaves -->
            <div class="input_field">
                <label for="sick_leaves">Sick Leaves</label>
                <input type="number" name="sick_leaves" id="sick_leaves" readonly>
            </div>

            <!-- Earned Leaves -->
            <div class="input_field">
                <label for="earned_leaves">Earned Leaves</label>
                <input type="number" name="earned_leaves" id="earned_leaves" readonly>
            </div>

            <!-- Shortcoming Entries -->
            <div class="input_field">
                <label for="shortcoming_entries">Shortcoming Entries</label>
                <input type="number" name="shortcoming_entries" id="shortcoming_entries" readonly>
            </div>

            <!-- Shortcoming Days -->
            <div class="input_field">
                <label for="shortcoming_days">Shortcoming Days</label>
                <input type="number" name="shortcoming_days" id="shortcoming_days" readonly>
            </div>

            <!-- Leave Without Pay -->
            <div class="input_field">
                <label for="lwp">Leave Without Pay</label>
                <input type="number" name="lwp" id="lwp" readonly>
            </div>

            <!-- Total Days -->
            <div class="input_field">
                <label for="total_days">Total Days</label>
                <input type="number" name="total_days" id="total_days" readonly>
            </div>

            <!-- Payable Days -->
            <div class="input_field">
                <label for="payable_days">Payable Days</label>
                <input type="number" name="payable_days" id="payable_days" readonly>
            </div>

            <!-- Salary Per Day -->
            <div class="input_field">
                <label for="salary_per_day">Salary Per Day</label>
                <input type="number" name="salary_per_day" id="salary_per_day" readonly>
            </div>

            <!-- Payable Salary -->
            <div class="input_field">
                <label for="payable_salary">Payable Salary</label>
                <input type="number" name="payable_salary" id="payable_salary" readonly>
            </div>

            <!-- Additional Fund -->
            <div class="input_field">
                <label for="additional_fund">Additional Fund</label>
                <input type="number" name="additional_fund" id="additional_fund" value="0" min="0">
            </div>

            <!-- Total Amount -->
            <div class="input_field">
                <label for="total_amount">Total Amount</label>
                <input type="number" name="total_amount" id="total_amount" readonly>
            </div>
        </div>

        <div class="btn-container">
            <!-- <button type="button" id="generateAllSalaries" class="btn-primary" style="position: absolute; top: 20px; left: 20px;">Generate All Salaries</button> -->

            <button type="submit" class="btn-register">Register</button>
            <button type="button" class="btn-cancel" onclick="window.history.back();">Cancel</button>
        </div>
    </form>
</div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize event listeners
    document.getElementById('employee_name').addEventListener('change', handleEmployeeChange);
    document.getElementById('additional_fund').addEventListener('input', calculateTotalAmount);

    // If you want to trigger the calculation immediately when page loads for a preselected employee
    const initialEmployee = document.getElementById('employee_name').value;
    if (initialEmployee) {
        handleEmployeeChange.call(document.getElementById('employee_name'));
    }
});

async function handleEmployeeChange() {
    const employeeId = this.value;
    const startDate = "<?php echo htmlspecialchars($start_date); ?>";
    const endDate = "<?php echo htmlspecialchars($end_date); ?>";

    if (!employeeId) {
        resetAllFields();
        return;
    }

    try {
        // First calculate synchronous values
        calculateTotalDays(startDate, endDate);
        calculateWeekends(startDate, endDate);

        // Then fetch async values and wait for all to complete
        await Promise.all([
            fetchWorkingDays(employeeId, startDate, endDate),
            fetchHolidays(startDate, endDate),
            fetchLeaves(employeeId, startDate, endDate)
        ]);

        // Now all data is loaded, calculate dependent values
        calculatePayableDays();
        calculateSalaryPerDay(employeeId);
        calculateTotalAmount();
    } catch (error) {
        console.error('Error calculating salary data:', error);
    }
}

function resetAllFields() {
    const fieldsToReset = [
        'working_days', 'weekends', 'holidays', 'sick_leaves',
        'earned_leaves', 'lwp', 'total_days',
        'payable_days', 'salary_per_day', 'payable_salary', 'total_amount',
        'shortcoming_entries', 'shortcoming_days' // Add new fields here
    ];

    fieldsToReset.forEach(id => {
        document.getElementById(id).value = '';
    });
}

function calculateTotalDays(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const timeDiff = end - start;
    const totalDays = Math.ceil(timeDiff / (1000 * 60 * 60 * 24)) + 1; // Include both dates
    document.getElementById('total_days').value = totalDays;
}

function calculateWeekends(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    let weekendCount = 0;

    for (let date = new Date(start); date <= end; date.setDate(date.getDate() + 1)) {
        const dayOfWeek = date.getDay();
        if (dayOfWeek === 0 || dayOfWeek === 6) { // Sunday or Saturday
            weekendCount++;
        }
    }

    document.getElementById('weekends').value = weekendCount;
}

async function fetchWorkingDays(employeeId, startDate, endDate) {
    try {
        const response = await fetch('fetch_working_days.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                employee_id: employeeId,
                start_date: startDate,
                end_date: endDate
            })
        });

        const data = await response.json();

        if (data.working_days !== undefined) {
            document.getElementById('working_days').value = data.working_days;
        } else {
            console.error('Error fetching working days:', data.error);
            document.getElementById('working_days').value = 0;
        }

        if (data.shortcoming_entries !== undefined) {
            document.getElementById('shortcoming_entries').value = data.shortcoming_entries;
        } else {
            document.getElementById('shortcoming_entries').value = 0;
        }

        if (data.shortcoming_days !== undefined) {
            document.getElementById('shortcoming_days').value = data.shortcoming_days;
        } else {
            document.getElementById('shortcoming_days').value = 0;
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('working_days').value = 0;
        document.getElementById('shortcoming_entries').value = 0;
        document.getElementById('shortcoming_days').value = 0;
    }
}

function fetchHolidays(startDate, endDate) {
    return fetch('fetch_holidays.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            start_date: startDate,
            end_date: endDate
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.holidayCount !== undefined) {
            document.getElementById('holidays').value = data.holidayCount;
        } else {
            console.error('Error fetching holidays:', data.error);
            document.getElementById('holidays').value = 0;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('holidays').value = 0;
    });
}

function fetchLeaves(employeeId, startDate, endDate) {
    return fetch('fetch_leaves.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            employee_id: employeeId,
            start_date: startDate,
            end_date: endDate
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('sick_leaves').value = data.sickLeaves || 0;
        document.getElementById('earned_leaves').value = data.earnedLeaves || 0;
        document.getElementById('lwp').value = data.lwp || 0;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('sick_leaves').value = 0;
        document.getElementById('earned_leaves').value = 0;
        document.getElementById('lwp').value = 0;
    });
}

function calculatePayableDays() {
    const workingDays = parseFloat(document.getElementById('working_days').value) || 0;
    const weekends = parseFloat(document.getElementById('weekends').value) || 0;
    const holidays = parseFloat(document.getElementById('holidays').value) || 0;
    const sickLeaves = parseFloat(document.getElementById('sick_leaves').value) || 0;
    const earnedLeaves = parseFloat(document.getElementById('earned_leaves').value) || 0;
    const shortcomingDays = parseFloat(document.getElementById('shortcoming_days').value) || 0;

    const payableDays = workingDays + weekends + holidays + sickLeaves + earnedLeaves - shortcomingDays;
    document.getElementById('payable_days').value = payableDays;
}

function calculateSalaryPerDay(employeeId) {
    const selectedOption = document.querySelector(`#employee_name option[value="${employeeId}"]`);
    const salary = selectedOption ? parseFloat(selectedOption.getAttribute('data-salary')) : 0;
    const totalDays = parseInt(document.getElementById('total_days').value) || 1;

    const salaryPerDay = salary / totalDays;
    document.getElementById('salary_per_day').value = salaryPerDay.toFixed(2);

    calculatePayableSalary();
}

function calculatePayableSalary() {
    const payableDays = parseFloat(document.getElementById('payable_days').value) || 0;
    const salaryPerDay = parseFloat(document.getElementById('salary_per_day').value) || 0;

    const payableSalary = payableDays * salaryPerDay;
    document.getElementById('payable_salary').value = payableSalary.toFixed(2);

    calculateTotalAmount();
}

function calculateTotalAmount() {
    const payableSalary = parseFloat(document.getElementById('payable_salary').value) || 0;
    const additionalFund = parseFloat(document.getElementById('additional_fund').value) || 0;

    const totalAmount = payableSalary + additionalFund;
    document.getElementById('total_amount').value = totalAmount.toFixed(2);
}

document.getElementById('generateAllSalaries').addEventListener('click', function() {
    fetch('generate_all_salaries.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            fy_code: "<?php echo htmlspecialchars($fy_code); ?>",
            month_id: "<?php echo htmlspecialchars($month_id); ?>",
            month: "<?php echo htmlspecialchars($month); ?>",
            start_date: "<?php echo htmlspecialchars($start_date); ?>",
            end_date: "<?php echo htmlspecialchars($end_date); ?>"
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('All salary sheets generated successfully!');
        } else {
            alert('Error generating salary sheets: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while generating salary sheets.');
    });
});


  </script>
</body>
</html>
