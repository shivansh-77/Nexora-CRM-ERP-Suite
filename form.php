<?php
include('connection.php');

// Fetch departments from the database
$departments = mysqli_query($connection, "SELECT * FROM department");
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
  </style>
</head>
<body>
  <div class="container">
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
              <option value="<?php echo $row['id']; ?>"><?php echo $row['department']; ?></option>
            <?php } ?>
          </select>
        </div>
        <?php
                    // Fetch designations from the database
            $sql = "SELECT designation FROM designation"; // Change this if your query needs to be different
            $result = $connection->query($sql);
            ?>

            <div class="input_field">
                <label>Designation <span class="required">*</span></label>
                <select name="designation" class="input" required>
                    <option value="">Select</option>
                    <?php
                    // Check if there are any results
                    if ($result->num_rows > 0) {
                        // Loop through the results and create an option for each designation
                        while ($row = $result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['designation']) . '">' . htmlspecialchars($row['designation']) . '</option>';
                        }
                    } else {
                        echo '<option value="">No designations available</option>';
                    }
                    ?>
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
    $name = $_POST['name'];
    $departmentId = $_POST['department']; // Get the department ID
    $password = $_POST['password'];
    $cpassword = $_POST['confirm_password'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $designation = $_POST['designation'];

    // Check for empty fields
    if (!empty($name) && !empty($departmentId) && !empty($password) && !empty($cpassword) && !empty($gender) && !empty($email) && !empty($phone) && !empty($designation)) {

        // Check if passwords match
        if ($password === $cpassword) {
            // Update deptcount for the selected department
            $updateDeptCountQuery = "UPDATE department SET deptcount = deptcount + 1 WHERE id = '$departmentId'"; // Use id for the update
            $updateDeptCountResult = mysqli_query($connection, $updateDeptCountQuery);

            // Check if the update query was successful
            if (!$updateDeptCountResult) {
                echo "Failed to update department count! Error: " . mysqli_error($connection);
            }

            // Insert user data into login_db
            $query = "INSERT INTO login_db (name, department, password, conpassword, gender, email, phone, address, designation)
                      VALUES ('$name', '$departmentId', '$password', '$cpassword', '$gender', '$email', '$phone', '$address', '$designation')";

            $result = mysqli_query($connection, $query);

            if ($result) {
                // Alert message and redirect after successful registration
                echo "<script>alert('Employee registered successfully!'); window.location.href = 'display.php';</script>";
                exit(); // Make sure to exit after redirecting
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
