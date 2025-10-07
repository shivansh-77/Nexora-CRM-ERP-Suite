<?php
include('connection.php');

// Fetch departments from the database
$departments = mysqli_query($connection, "SELECT * FROM department");

// Fetch designations from the database
$designations = mysqli_query($connection, "SELECT * FROM designation");
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <link rel="icon" type="image/png" href="favicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <title>User Registration Form</title>
  <style>
    .required {
      color: red;
    }
    .close-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 24px;
      text-decoration: none;
      color: #000;
    }
    .container {
      position: relative;
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="display.php" class="close-btn">×</a>
    <form class="form" action="" method="post">
      <div class="title">
        <span>Employee Registration</span>
      </div>

      <div class="form-grid">

        <!-- Name with Email -->
        <div class="input_field">
          <label>Name <span class="required">*</span></label>
          <input type="text" name="name" class="input" required>
        </div>
        <div class="input_field">
          <label>Email <span class="required">*</span></label>
          <input type="email" name="email" class="input" required>
        </div>

        <!-- Department with Designation -->
        <div class="input_field">
          <label>Department <span class="required">*</span></label>
          <select name="department" class="input" required>
            <option value="">Select</option>
            <?php while ($row = mysqli_fetch_assoc($departments)) { ?>
              <option value="<?php echo htmlspecialchars($row['department']); ?>">
                <?php echo htmlspecialchars($row['department']); ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <div class="input_field">
          <label>Designation <span class="required">*</span></label>
          <select name="designation" class="input" required>
            <option value="">Select</option>
            <?php while ($row = mysqli_fetch_assoc($designations)) { ?>
              <option value="<?php echo htmlspecialchars($row['designation']); ?>">
                <?php echo htmlspecialchars($row['designation']); ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <!-- Phone with Address -->
        <div class="input_field">
          <label>Phone <span class="required">*</span></label>
          <input type="text" name="phone" class="input" required>
        </div>
        <div class="input_field">
          <label>Address <span class="required">*</span></label>
          <input type="text" name="address" class="input" required>
        </div>

        <!-- Password with Confirm Password -->
        <div class="input_field">
          <label>Password <span class="required">*</span></label>
          <input type="password" name="password" class="input" required>
        </div>
        <div class="input_field">
          <label>Confirm Password <span class="required">*</span></label>
          <input type="password" name="confirm_password" class="input" required>
        </div>

        <!-- Gender -->
        <div class="input_field">
          <label>Gender <span class="required">*</span></label>
          <select name="gender" class="input" required>
            <option value="">Select</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
          </select>
        </div>
        <!-- Role -->
        <div class="input_field">
          <label>Role <span class="required">*</span></label>
          <select name="role" class="input" required>
            <option value="">Select</option>
            <option value="Admin">Admin</option>
            <option value="Employee">Employee</option>
          </select>
        </div>

        <!-- Status -->
        <div class="input_field">
          <label>Status <span class="required">*</span></label>
          <select name="status" class="input" required>
            <option value="Active" selected>Active</option>
            <option value="Blocked">Blocked</option>
          </select>
        </div>

        <!-- Salary -->
        <div class="input_field">
          <label>Salary <span class="required">*</span></label>
          <input type="number" name="salary" class="input" required>
        </div>

        <!-- Date of Joining -->
        <div class="input_field">
          <label>Date of Joining <span class="required">*</span></label>
          <input type="date" name="date_of_joining" class="input" required>
        </div>

        <!-- Date of Leaving -->
        <div class="input_field">
          <label>Date of Leaving</label>
          <input type="date" name="date_of_leaving" class="input">
        </div>

      </div>

      <div class="btn-container">
        <button type="submit" name="register" class="btn-register">Register</button>
        <button type="button" class="btn-cancel" onclick="window.history.back();">Cancel</button>
      </div>
    </form>
  </div>
</body>
</html>

<?php
if (isset($_POST['register'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $department = mysqli_real_escape_string($connection, $_POST['department']);
    $password = $_POST['password']; // Do not escape passwords before hashing
    $cpassword = $_POST['confirm_password']; // Do not escape passwords before hashing
    $gender = mysqli_real_escape_string($connection, $_POST['gender']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $address = mysqli_real_escape_string($connection, $_POST['address']);
    $designation = mysqli_real_escape_string($connection, $_POST['designation']);
    $role = mysqli_real_escape_string($connection, $_POST['role']);
    $status = mysqli_real_escape_string($connection, $_POST['status']); // Get the status value
    $salary = mysqli_real_escape_string($connection, $_POST['salary']); // Get the salary value
    $date_of_joining = mysqli_real_escape_string($connection, $_POST['date_of_joining']);
    $date_of_leaving = isset($_POST['date_of_leaving']) ? mysqli_real_escape_string($connection, $_POST['date_of_leaving']) : null;

    // Check for empty fields
    if (!empty($name) && !empty($department) && !empty($password) && !empty($cpassword) && !empty($gender) && !empty($email) && !empty($phone) && !empty($designation) && !empty($role) && !empty($status) && !empty($salary) && !empty($date_of_joining)) {

        // Check if passwords match
        if ($password === $cpassword) {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Update deptcount for the selected department
            $updateDeptCountQuery = "UPDATE department SET deptcount = deptcount + 1 WHERE department = '$department'";
            $updateDeptCountResult = mysqli_query($connection, $updateDeptCountQuery);

            if (!$updateDeptCountResult) {
                echo "Failed to update department count! Error: " . mysqli_error($connection);
            }

            // Insert user data into login_db with hashed password, status, and salary
            $query = "INSERT INTO login_db (name, department, password, conpassword, gender, email, phone, address, designation, role, status, salary, doj, dol)
                      VALUES ('$name', '$department', '$hashed_password', '$hashed_password', '$gender', '$email', '$phone', '$address', '$designation', '$role', '$status', '$salary', '$date_of_joining', " . ($date_of_leaving ? "'$date_of_leaving'" : "NULL") . ")";

            $result = mysqli_query($connection, $query);

            if ($result) {
                // Get the last inserted user_id
                $user_id = mysqli_insert_id($connection);

                // Insert a record into user_leave_balance with default values
                $leaveBalanceQuery = "INSERT INTO user_leave_balance (user_id, name, doj, total_sick_leaves, total_earned_leaves, sick_leaves_taken, earned_leaves_taken, half_day_leaves_taken, last_updated, next_update)
                          VALUES ($user_id, '$name', '$date_of_joining', 6.00, 0.00, 0.00, 0.00, 0.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH))";
                $leaveBalanceResult = mysqli_query($connection, $leaveBalanceQuery);

                if ($leaveBalanceResult) {
                    echo "<script>alert('Employee registered successfully!'); window.location.href = 'display.php';</script>";
                    exit();
                } else {
                    echo "Failed to create leave balance record! Error: " . mysqli_error($connection);
                }
            } else {
                echo "Data entry failed! Error: " . mysqli_error($connection);
            }
        } else {
            echo '<script>alert("Passwords do not match!");</script>';
        }
    } else {
        echo '<script>alert("Some entry is missing!");</script>';
    }
}
?>
