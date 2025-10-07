<?php
include('connection.php'); // Include your database connection

// Handle form submission for single employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_name'])) {
    // ... [your existing single employee processing code] ...
}

// Handle form submission for all employees
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_all'])) {
    $month = $_POST['month'];
    $working_days = $_POST['working_days'];
    $weekends = $_POST['weekends'];
    $holidays = $_POST['holidays'];
    $sick_leaves = $_POST['sick_leaves'];
    $earned_leaves = $_POST['earned_leaves'];
    $half_day = $_POST['half_day'];
    $lwp = $_POST['lwp'];
    $total_days = $_POST['total_days'];
    $payable_days = $_POST['payable_days'];
    $salary_per_day = $_POST['salary_per_day'];
    $payable_salary = $_POST['payable_salary'];
    $additional_fund = $_POST['additional_fund'];
    $total_amount = $_POST['total_amount'];

    // Get all employees
    $employee_query = "SELECT id, name, salary FROM login_db";
    $employee_result = mysqli_query($connection, $employee_query);
    $employees = mysqli_fetch_all($employee_result, MYSQLI_ASSOC);

    $success_count = 0;
    $error_count = 0;

    foreach ($employees as $employee) {
        // Check if salary record already exists for this employee and month
        $check_query = "SELECT id FROM salary WHERE employee_id = ? AND month = ?";
        $check_stmt = mysqli_prepare($connection, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ss", $employee['id'], $month);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error_count++;
            mysqli_stmt_close($check_stmt);
            continue; // Skip this employee as record already exists
        }
        mysqli_stmt_close($check_stmt);

        // Insert data into salary table
        $insert_query = "INSERT INTO salary (
            employee_id,
            month,
            working_days,
            weekends,
            holidays,
            sick_leaves,
            earned_leaves,
            half_days,
            lwp,
            total_days,
            payable_days,
            salary_per_day,
            payable_salary,
            additional_fund,
            total_amount
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";

        $stmt = mysqli_prepare($connection, $insert_query);
        mysqli_stmt_bind_param($stmt, "ssiiiiiiiiidddd",
            $employee['id'],
            $month,
            $working_days,
            $weekends,
            $holidays,
            $sick_leaves,
            $earned_leaves,
            $half_day,
            $lwp,
            $total_days,
            $payable_days,
            $salary_per_day,
            $payable_salary,
            $additional_fund,
            $total_amount
        );

        if (mysqli_stmt_execute($stmt)) {
            $success_count++;
        } else {
            $error_count++;
            echo "<script>alert('Error for employee {$employee['id']}: " . mysqli_error($connection) . "');</script>";
        }

        mysqli_stmt_close($stmt);
    }

    echo "<script>alert('Processed all employees: $success_count successful, $error_count failed');</script>";
}

$company_query = "SELECT working, working_days FROM company_card LIMIT 1";
$company_result = mysqli_query($connection, $company_query);
$company_data = mysqli_fetch_assoc($company_result);

$working_hours = $company_data['working'];
$working_days_per_week = $company_data['working_days'];

// Fetch employee names and salaries from login_db
$employee_query = "SELECT id, name, salary FROM login_db";
$employee_result = mysqli_query($connection, $employee_query);
$employees = mysqli_fetch_all($employee_result, MYSQLI_ASSOC);
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
    </style>
</head>
<body>

  <div class="container">
      <a href="salary_sheet_display.php" class="cross-btn">✖</a>
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
                  <select name="month" id="month" required>
                      <option value="">Select Month</option>
                      <!-- Options will be populated dynamically using JavaScript -->
                  </select>
                  <span id="month-warning" style="color: red; display: none;">Salary sheet already created for this month.</span>
              </div>

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

              <!-- Half Day -->
              <div class="input_field">
                  <label for="half_day">Half Day</label>
                  <input type="number" name="half_day" id="half_day" readonly>
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

          <!-- Add this button near the existing buttons -->
  <div class="btn-container">
      <button type="submit" class="btn-register">Register</button>
      <button type="submit" name="generate_all" class="btn-generate-all" onclick="return confirm('Are you sure you want to generate salary sheets for ALL employees for this month?')">Generate for All</button>
      <button type="button" class="btn-cancel" onclick="window.history.back();">Cancel</button>
  </div>
      </form>
  </div>

  <script>
  document.getElementById('employee_name').addEventListener('change', function() {
      const employeeId = this.value;
      const monthSelect = document.getElementById('month');
      const monthWarning = document.getElementById('month-warning');

      // Clear previous options
      monthSelect.innerHTML = '<option value="">Select Month</option>';
      monthWarning.style.display = 'none';

      if (!employeeId) return;

      // Fetch existing months for the selected employee
      fetch('fetch_salary_months.php?employee_id=' + employeeId)
          .then(response => response.json())
          .then(data => {
              const currentDate = new Date();
              const currentYear = currentDate.getFullYear();
              const currentMonth = currentDate.getMonth() + 1; // Months are zero-based
              const today = currentDate.getDate();

              // Determine if current month is completed (only show if we're in a new month)
              const isCurrentMonthCompleted = today > 1 || currentMonth > 1;

              let startYear, startMonth;
              let endYear = currentYear;
              let endMonth = currentMonth - 1; // Default to previous month

              // If there are existing months, start from the month after the last recorded month
              if (data.lastRecordedMonth) {
                  const [lastYear, lastMonth] = data.lastRecordedMonth.split('-').map(Number);
                  startYear = lastYear;
                  startMonth = lastMonth + 1;

                  // Handle year transition (December to January)
                  if (startMonth > 12) {
                      startYear++;
                      startMonth = 1;
                  }
              }
              // For new employees (no existing records), start from January of current year
              else {
                  startYear = currentYear;
                  startMonth = 1;
              }

              // Adjust end month if current month isn't completed yet
              if (!isCurrentMonthCompleted) {
                  endMonth = currentMonth - 2; // Go back one more month
                  // Handle January case
                  if (endMonth < 1) {
                      endYear--;
                      endMonth = 12;
                  }
              }

              const months = [];

              // Generate months from start month/year to end month/year
              for (let year = startYear; year <= endYear; year++) {
                  const monthStart = (year === startYear) ? startMonth : 1;
                  const monthEnd = (year === endYear) ? endMonth : 12;

                  for (let month = monthStart; month <= monthEnd; month++) {
                      const monthStr = `${year}-${String(month).padStart(2, '0')}`;
                      months.push(monthStr);
                  }
              }

              // Populate the month dropdown
              months.forEach(month => {
                  const option = document.createElement('option');
                  option.value = month;
                  option.textContent = new Date(month + '-01').toLocaleString('default', {
                      month: 'long',
                      year: 'numeric'
                  });

                  // Disable months that already have records
                  if (data.existingMonths.includes(month)) {
                      option.disabled = true;
                  }

                  monthSelect.appendChild(option);
              });

              // Show warning if current month is already recorded
              const currentMonthStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}`;
              if (data.existingMonths.includes(currentMonthStr)) {
                  monthWarning.style.display = 'block';
              }
          })
          .catch(error => {
              console.error('Error fetching months:', error);
          });
  });
      document.getElementById('month').addEventListener('change', function() {
          const employeeId = document.getElementById('employee_name').value;
          const selectedMonth = this.value;
          const [year, month] = selectedMonth.split('-').map(Number);

          // Fetch working days from checkin_time
          fetch('fetch_working_days.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json'
              },
              body: JSON.stringify({ employee_id: employeeId, year: year, month: month })
          })
          .then(response => response.json())
          .then(data => {
              // Convert to number
              const workingDays = Number(data.workingDays);
              document.getElementById('working_days').value = workingDays;

              // Calculate weekends
              const weekends = calculateWeekends(year, month, <?php echo $working_days_per_week; ?>);
              document.getElementById('weekends').value = weekends;

              // Fetch holidays
              return fetch('fetch_holidays.php', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json'
                  },
                  body: JSON.stringify({ year: year, month: month })
              });
          })
          .then(response => response.json())
          .then(holidayData => {
              // Convert to number
              const holidays = Number(holidayData.holidayCount);
              document.getElementById('holidays').value = holidays;

              // Fetch leaves
              return fetch('fetch_leaves.php', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json'
                  },
                  body: JSON.stringify({ employee_id: employeeId, year: year, month: month })
              });
          })
          .then(response => response.json())
          .then(leaveData => {
              // Convert all to numbers with fallback to 0 if undefined
              const sickLeaves = Number(leaveData.sickLeaves) || 0;
              const earnedLeaves = Number(leaveData.earnedLeaves) || 0;
              const halfDay = Number(leaveData.halfDay) || 0;
              const lwp = Number(leaveData.lwp) || 0;

              document.getElementById('sick_leaves').value = sickLeaves;
              document.getElementById('earned_leaves').value = earnedLeaves;
              document.getElementById('half_day').value = halfDay;
              document.getElementById('lwp').value = lwp;

              // Calculate total days in the selected month
              const totalDays = new Date(year, month, 0).getDate();
              document.getElementById('total_days').value = totalDays;

              // Get all values as numbers
              const workingDays = Number(document.getElementById('working_days').value) || 0;
              const weekends = Number(document.getElementById('weekends').value) || 0;
              const holidays = Number(document.getElementById('holidays').value) || 0;

              // Calculate payable days by adding all components
              const payableDays = workingDays + weekends + holidays + sickLeaves + earnedLeaves;
              document.getElementById('payable_days').value = payableDays;

              // Get employee salary
              const salaryOption = document.querySelector(`#employee_name option[value="${employeeId}"]`);
              const salary = Number(salaryOption.dataset.salary);

              // Calculate salary per day and payable salary
              const salaryPerDay = salary / totalDays;
              const payableSalary = salaryPerDay * payableDays;

              document.getElementById('salary_per_day').value = salaryPerDay.toFixed(2);
              document.getElementById('payable_salary').value = payableSalary.toFixed(2);

              // Calculate total amount (payable salary + additional fund)
              calculateTotalAmount();
          })
          .catch(error => {
              console.error('Error:', error);
              alert('An error occurred while calculating salary. Please try again.');
          });
      });

      // Add event listener for additional fund changes
      document.getElementById('additional_fund').addEventListener('input', calculateTotalAmount);

      function calculateTotalAmount() {
          const payableSalary = Number(document.getElementById('payable_salary').value) || 0;
          const additionalFund = Number(document.getElementById('additional_fund').value) || 0;
          const totalAmount = payableSalary + additionalFund;

          document.getElementById('total_amount').value = totalAmount.toFixed(2);
      }

      function calculateWeekends(year, month, workingDaysPerWeek) {
          const startDate = new Date(year, month - 1, 1);
          const endDate = new Date(year, month, 0);
          let weekendCount = 0;

          for (let date = startDate; date <= endDate; date.setDate(date.getDate() + 1)) {
              const day = date.getDay();
              if ((workingDaysPerWeek === 5 && (day === 0 || day === 6)) ||
                  (workingDaysPerWeek === 6 && day === 0)) {
                  weekendCount++;
              }
          }

          return weekendCount;
      }

      // Add this to your existing JavaScript
document.querySelector('button[name="generate_all"]').addEventListener('click', function(e) {
    // Validate that a month is selected
    const month = document.getElementById('month').value;
    if (!month) {
        alert('Please select a month first');
        e.preventDefault();
        return false;
    }

    // Validate that all required fields are filled
    const requiredFields = ['working_days', 'weekends', 'holidays', 'total_days', 'payable_days', 'salary_per_day', 'payable_salary'];
    for (const field of requiredFields) {
        if (!document.getElementById(field).value) {
            alert('Please calculate the salary details first by selecting an employee and month');
            e.preventDefault();
            return false;
        }
    }

    return confirm('This will generate salary sheets for ALL employees. Are you sure?');
});
  </script>

  </body>
  </html>
