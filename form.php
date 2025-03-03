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
  <meta charset="UTF-8">
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
    $password = mysqli_real_escape_string($connection, $_POST['password']);
    $cpassword = mysqli_real_escape_string($connection, $_POST['confirm_password']);
    $gender = mysqli_real_escape_string($connection, $_POST['gender']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $address = mysqli_real_escape_string($connection, $_POST['address']);
    $designation = mysqli_real_escape_string($connection, $_POST['designation']);
    $role = mysqli_real_escape_string($connection, $_POST['role']);

    // Check for empty fields
    if (!empty($name) && !empty($department) && !empty($password) && !empty($cpassword) && !empty($gender) && !empty($email) && !empty($phone) && !empty($designation) && !empty($role)) {

        // Check if passwords match
        if ($password === $cpassword) {
            // Update deptcount for the selected department
            $updateDeptCountQuery = "UPDATE department SET deptcount = deptcount + 1 WHERE department = '$department'";
            $updateDeptCountResult = mysqli_query($connection, $updateDeptCountQuery);

            if (!$updateDeptCountResult) {
                echo "Failed to update department count! Error: " . mysqli_error($connection);
            }

            // Insert user data into login_db
            $query = "INSERT INTO login_db (name, department, password, conpassword, gender, email, phone, address, designation, role)
                      VALUES ('$name', '$department', '$password', '$cpassword', '$gender', '$email', '$phone', '$address', '$designation', '$role')";

            $result = mysqli_query($connection, $query);

            if ($result) {
                // Get the last inserted user_id
                $user_id = mysqli_insert_id($connection);

                // Insert a record into user_leave_balance with default values
                $leaveBalanceQuery = "INSERT INTO user_leave_balance (user_id, name, `D.O.J`, total_sick_leaves, total_earned_leaves, sick_leaves_taken, earned_leaves_taken, half_day_leaves_taken, last_updated, next_update)
                          VALUES ($user_id, '$name', CURDATE(), 6.00, 0.00, 0.00, 0.00, 0.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH))";
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
