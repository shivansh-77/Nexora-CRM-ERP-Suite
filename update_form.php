<?php
include('connection.php');

// Check if the 'id' is passed in the URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize input to prevent SQL injection

    // Fetch user details from the database
    $query = "SELECT * FROM login_db WHERE id = $id";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result); // Fetch the user data as an associative array
    } else {
        echo "<script>alert('User not found!'); window.location.href = 'display_form.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('No ID provided!'); window.location.href = 'display_form.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <title>Update User</title>
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
        <span>Employee Card</span>
      </div>

      <div class="form-grid">
        <div class="input_field">
          <label>Name <span class="required">*</span></label>
          <input type="text" name="name" class="input" value="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="input_field">
          <label>Department <span class="required">*</span></label>
          <select name="department" class="input" required>
            <option value="">Select</option>
            <?php
            // Fetch departments from the database for the dropdown
            $departments = mysqli_query($connection, "SELECT * FROM department");
            while ($row = mysqli_fetch_assoc($departments)) {
                $selected = ($row['id'] == $user['department']) ? 'selected' : '';
                echo "<option value=\"{$row['id']}\" $selected>{$row['department']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="input_field">
          <label>Password <span class="required">*</span></label>
          <input type="password" name="password" class="input" value="<?= htmlspecialchars($user['password'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="input_field">
          <label>Confirm Password <span class="required">*</span></label>
          <input type="password" name="confirm_password" class="input" value="<?= htmlspecialchars($user['conpassword'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="input_field">
          <label>Gender <span class="required">*</span></label>
          <select name="gender" class="input" required>
            <option value="">Select</option>
            <option value="male" <?= $user['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
            <option value="female" <?= $user['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
          </select>
        </div>
        <div class="input_field">
          <label>Email <span class="required">*</span></label>
          <input type="email" name="email" class="input" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="input_field">
          <label>Phone <span class="required">*</span></label>
          <input type="text" name="phone" class="input" value="<?= htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="input_field">
          <label>Address <span class="required">*</span></label>
          <input type="text" name="address" class="input" value="<?= htmlspecialchars($user['address'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="input_field">
          <label>Designation <span class="required">*</span></label>
          <select name="designation" class="input" required>
            <option value="">Select</option>
            <option value="Web Developer" <?= $user['designation'] === 'Web Developer' ? 'selected' : ''; ?>>Web Developer</option>
            <option value="Software Developer" <?= $user['designation'] === 'Software Developer' ? 'selected' : ''; ?>>Software Developer</option>
            <option value="Video Editor" <?= $user['designation'] === 'Video Editor' ? 'selected' : ''; ?>>Video Editor</option>
            <option value="SEO Management" <?= $user['designation'] === 'SEO Management' ? 'selected' : ''; ?>>SEO Management</option>
            <option value="Android Developer" <?= $user['designation'] === 'Android Developer' ? 'selected' : ''; ?>>Android Developer</option>
            <option value="Content Creator" <?= $user['designation'] === 'Content Creator' ? 'selected' : ''; ?>>Content Creator</option>
          </select>
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
// Handle form submission for updating the user
if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $departmentId = $_POST['department']; // Get the selected department ID
    $password = $_POST['password'];
    $cpassword = $_POST['confirm_password'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $designation = $_POST['designation'];

    // Check if passwords match
    if ($password === $cpassword) {
        // Get the current department of the user
        $currentDepartmentQuery = "SELECT department FROM login_db WHERE id = $id";
        $currentDepartmentResult = mysqli_query($connection, $currentDepartmentQuery);
        $currentDepartment = mysqli_fetch_assoc($currentDepartmentResult)['department'];

        // Update deptcount for the selected department only if it has changed
        if ($currentDepartment != $departmentId) {
            // Decrement the count for the previous department
            $decrementDeptCountQuery = "UPDATE department SET deptcount = deptcount - 1 WHERE id = '$currentDepartment'";
            mysqli_query($connection, $decrementDeptCountQuery);
            // Increment the count for the new department
            $incrementDeptCountQuery = "UPDATE department SET deptcount = deptcount + 1 WHERE id = '$departmentId'";
            mysqli_query($connection, $incrementDeptCountQuery);
        }

        $update_query = "UPDATE login_db SET
                         name = '$name',
                         department = '$departmentId',
                         password = '$password',
                         conpassword = '$cpassword',
                         gender = '$gender',
                         email = '$email',
                         phone = '$phone',
                         address = '$address',
                         designation = '$designation'
                         WHERE id = $id";

        $update_result = mysqli_query($connection, $update_query);

        if ($update_result) {
            echo "<script>alert('Record updated successfully!');</script>";
            // Use a delay before redirecting to ensure the alert is shown
            echo "<script>setTimeout(function() { window.location.href = 'display.php'; }, 1000);</script>";
        } else {
            echo "Error updating record: " . mysqli_error($connection);
        }
    } else {
        echo '<script>alert("Passwords do not match!");</script>';
    }
}
?>
