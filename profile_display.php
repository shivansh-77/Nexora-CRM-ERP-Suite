<?php
include('connection.php');

// Start the session
session_start();

// Check if an ID is received
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($connection, $_GET['id']);

    // Fetch the user data from the database
    $query = "SELECT * FROM login_db WHERE id = '$id'";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
    } else {
        echo "<script>alert('User not found!'); window.history.back();</script>";
        exit();
    }
} else {
    echo "<script>alert('No ID received!'); window.history.back();</script>";
    exit();
}

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
  <title>Profile Display</title>
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
    .uneditable {
      pointer-events: none;
      background-color: #f0f0f0;
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="#" class="close-btn" onclick="window.history.back();">×</a>
    <form class="form" action="" method="post">
      <div class="title">
        <span>Profile Display</span>
      </div>

      <div class="form-grid">

        <!-- Name with Email -->
        <div class="input_field">
          <label>Name <span class="required">*</span></label>
          <input type="text" name="name" class="input uneditable" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
        </div>
        <div class="input_field">
          <label>Email <span class="required">*</span></label>
          <input type="email" name="email" class="input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <!-- Department with Designation -->
        <div class="input_field">
          <label>Department <span class="required">*</span></label>
          <select name="department" class="input uneditable" required disabled>
            <option value="">Select</option>
            <?php while ($row = mysqli_fetch_assoc($departments)) { ?>
              <option value="<?php echo htmlspecialchars($row['department']); ?>" <?php if ($row['department'] == $user['department']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($row['department']); ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <div class="input_field">
          <label>Designation <span class="required">*</span></label>
          <select name="designation" class="input uneditable" required disabled>
            <option value="">Select</option>
            <?php while ($row = mysqli_fetch_assoc($designations)) { ?>
              <option value="<?php echo htmlspecialchars($row['designation']); ?>" <?php if ($row['designation'] == $user['designation']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($row['designation']); ?>
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
          <input type="text" name="address" class="input uneditable" value="<?php echo htmlspecialchars($user['address']); ?>" readonly>
        </div>

        <!-- Password with Confirm Password -->
        <div class="input_field">
          <label>Password <span class="required">*</span></label>
          <input type="password" name="password" class="input" placeholder="Leave blank if no change">
        </div>
        <div class="input_field">
          <label>Confirm Password <span class="required">*</span></label>
          <input type="password" name="confirm_password" class="input" placeholder="Leave blank if no change">
        </div>

        <!-- Gender -->
        <div class="input_field">
          <label>Gender <span class="required">*</span></label>
          <select name="gender" class="input uneditable" required disabled>
            <option value="">Select</option>
            <option value="male" <?php if (isset($user['gender']) && $user['gender'] == 'Male') echo 'selected'; ?>>Male</option>
            <option value="female" <?php if (isset($user['gender']) && $user['gender'] == 'Female') echo 'selected'; ?>>Female</option>
          </select>
        </div>

        <!-- Role and Status -->
        <div class="input_field">
          <label>Role <span class="required">*</span></label>
          <select name="role" class="input uneditable" required disabled>
            <option value="">Select</option>
            <option value="Admin" <?php if ($user['role'] == 'Admin') echo 'selected'; ?>>Admin</option>
            <option value="Employee" <?php if ($user['role'] == 'Employee') echo 'selected'; ?>>Employee</option>
          </select>
        </div>
        <div class="input_field">
          <label>Status <span class="required">*</span></label>
          <select name="status" class="input uneditable" required disabled>
            <option value="Active" <?php if ($user['status'] == 'Active') echo 'selected'; ?>>Active</option>
            <option value="Blocked" <?php if ($user['status'] == 'Blocked') echo 'selected'; ?>>Blocked</option>
          </select>
        </div>

        <!-- Date of Joining -->
        <div class="input_field">
          <label>Date of Joining <span class="required">*</span></label>
          <input type="date" name="date_of_joining" class="input uneditable" value="<?php echo htmlspecialchars($user['doj']); ?>" readonly>
        </div>

        <!-- Date of Leaving -->
        <div class="input_field">
          <label>Date of Leaving</label>
          <input type="date" name="date_of_leaving" class="input uneditable" value="<?php echo htmlspecialchars($user['dol']); ?>" readonly>
        </div>
      </div>

      <div class="btn-container">
        <button type="submit" name="update" class="btn-register">Update</button>
        <button type="button" class="btn-cancel" onclick="window.history.back();">Cancel</button>
      </div>
    </form>
  </div>
</body>
</html>

<?php
if (isset($_POST['update'])) {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $password = $_POST['password']; // Do not escape passwords before hashing
    $cpassword = $_POST['confirm_password']; // Do not escape passwords before hashing

    // Check for empty fields
    if (!empty($email) && !empty($phone)) {

        // Check if passwords match and are not empty
        if (!empty($password) && !empty($cpassword)) {
            if ($password === $cpassword) {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $passwordUpdate = ", password = '$hashed_password', conpassword = '$hashed_password'";
            } else {
                echo '<script>alert("Passwords do not match!");</script>';
                exit();
            }
        } else {
            $passwordUpdate = "";
        }

        // Update user data in login_db
        $query = "UPDATE login_db SET email = '$email', phone = '$phone' $passwordUpdate WHERE id = '$id'";

        $result = mysqli_query($connection, $query);

        if ($result) {
            echo "<script>alert('Profile updated successfully!'); window.history.back();</script>";
            exit();
        } else {
            echo "Data update failed! Error: " . mysqli_error($connection);
        }
    } else {
        echo '<script>alert("Some entry is missing!");</script>';
    }
}
?>
