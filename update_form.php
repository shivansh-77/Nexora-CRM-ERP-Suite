<?php
include('connection.php');

// Start the session to get the logged-in user's ID
session_start();
$loggedInUserId = $_SESSION['user_id']; // Assuming the logged-in user's ID is stored in the session

// Check if an ID is provided in the URL
if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Fetch user details from the database
    $query = "SELECT * FROM login_db WHERE id = '$userId'";
    $result = mysqli_query($connection, $query);
    $user = mysqli_fetch_assoc($result);

    if (!$user) {
        echo "User not found.";
        exit;
    }
} else {
    echo "No user ID provided.";
    exit;
}

// Fetch departments and designations for dropdowns
$departments = mysqli_query($connection, "SELECT * FROM department");
$designations = mysqli_query($connection, "SELECT * FROM designation");

// Handle form submission for updating
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $departmentId = $_POST['department'];
    $password = $_POST['password']; // Raw password input
    $cpassword = $_POST['confirm_password']; // Raw confirm password input
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $designation = $_POST['designation'];
    $role = isset($_POST['role']) ? $_POST['role'] : $user['role']; // Get role if provided, otherwise use existing role

    // Check for empty fields
    if (!empty($name) && !empty($departmentId) && !empty($password) && !empty($cpassword) && !empty($gender) && !empty($email) && !empty($phone) && !empty($designation)) {

        // Check if passwords match
        if ($password === $cpassword) {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Update user data in the database
            $updateQuery = "UPDATE login_db SET
                            name = '$name',
                            department = '$departmentId',
                            password = '$hashed_password',
                            conpassword = '$hashed_password',
                            gender = '$gender',
                            email = '$email',
                            phone = '$phone',
                            address = '$address',
                            designation = '$designation'";

            // Include role in the update query if the logged-in user's ID is 1
            if ($loggedInUserId == 1) {
                $updateQuery .= ", role = '$role'";
            }

            $updateQuery .= " WHERE id = '$userId'";

            $updateResult = mysqli_query($connection, $updateQuery);

            if ($updateResult) {
                echo "<script>alert('User updated successfully!'); window.location.href = 'display.php';</script>";
                exit;
            } else {
                echo "Error updating user: " . mysqli_error($connection);
            }
        } else {
            echo '<script>alert("Passwords do not match!");</script>';
        }
    } else {
        echo '<script>alert("Some entry is missing!");</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <title>Update User Details</title>
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
      cursor: pointer;
    }
    .container {
      position: relative;
    }
  </style>
</head>
<body>
  <div class="container">
    <a onclick="window.history.back();" class="close-btn">×</a> <!-- Cross button -->
    <form class="form" action="" method="post">
      <div class="title">
        <span>Update Employee Details</span>
      </div>

      <div class="form-grid">
        <!-- Name with Email -->
        <div class="input_field">
          <label>Name <span class="required">*</span></label>
          <input type="text" name="name" class="input" value="<?php echo htmlspecialchars($user['name']); ?>" required>
        </div>
        <div class="input_field">
          <label>Email <span class="required">*</span></label>
          <input type="email" name="email" class="input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <!-- Department with Designation -->
        <div class="input_field">
          <label>Department <span class="required">*</span></label>
          <select name="department" class="input" required>
            <option value="">Select</option>
            <?php while ($row = mysqli_fetch_assoc($departments)) { ?>
              <option value="<?php echo $row['department']; ?>" <?php echo ($user['department'] == $row['department']) ? 'selected' : ''; ?>>
                <?php echo $row['department']; ?>
              </option>
            <?php } ?>
          </select>
        </div>
        <div class="input_field">
          <label>Designation <span class="required">*</span></label>
          <select name="designation" class="input" required>
            <option value="">Select</option>
            <?php while ($row = mysqli_fetch_assoc($designations)) { ?>
              <option value="<?php echo $row['designation']; ?>" <?php echo ($user['designation'] == $row['designation']) ? 'selected' : ''; ?>>
                <?php echo $row['designation']; ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <!-- Phone with Address -->
        <div class="input_field">
          <label>Phone <span class="required">*</span></label>
          <input type="text" name="phone" class="input" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
        </div>
        <div class="input_field">
          <label>Address <span class="required">*</span></label>
          <input type="text" name="address" class="input" value="<?php echo htmlspecialchars($user['address']); ?>" required>
        </div>

        <!-- Password with Confirm Password -->
        <div class="input_field">
          <label>Password <span class="required">*</span></label>
          <input type="password" name="password" class="input" value="<?php echo str_repeat('•', strlen($user['password'])); ?>" required>
        </div>
        <div class="input_field">
          <label>Confirm Password <span class="required">*</span></label>
          <input type="password" name="confirm_password" class="input" value="<?php echo str_repeat('•', strlen($user['password'])); ?>" required>
        </div>

        <!-- Gender -->
        <div class="input_field">
          <label>Gender <span class="required">*</span></label>
          <select name="gender" class="input" required>
            <option value="">Select</option>
            <option value="Male" <?php echo ($user['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo ($user['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
          </select>
        </div>

        <!-- Role (only visible to the logged-in user with ID 1) -->
        <?php if ($loggedInUserId == 1): ?>
          <div class="input_field">
            <label>Role <span class="required">*</span></label>
            <select name="role" class="input" required>
              <option value="Employee" <?php echo ($user['role'] === 'Employee') ? 'selected' : ''; ?>>Employee</option>
              <option value="Admin" <?php echo ($user['role'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
            </select>
          </div>
        <?php endif; ?>
      </div>

      <div class="btn-container">
        <button type="submit" name="update" class="btn-register">Update</button>
        <button type="button" class="btn-cancel" onclick="window.history.back();">Cancel</button>
      </div>
    </form>
  </div>
</body>
</html>
