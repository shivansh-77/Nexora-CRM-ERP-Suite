<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="login-style.css">
    <title>Login Form</title>
  </head>
  <body>
    <form action="" method="POST">
      <div class="center">
        <div class="logo-container">
               <img src="Splendid.png" alt="Logo" class="logo">

        </div>
        <h1>Login</h1>
        <div class="form">
          <div class="input-container">
            <i class="icon user-icon"></i>
            <input type="text" name="username" class="textfield" placeholder="Username" required>
          </div>
          <div class="input-container">
            <i class="icon password-icon"></i>
            <input type="password" name="password" class="textfield" placeholder="Password" required>
          </div>

          <div class="forgetpass">
            <a href="forgot_password.php" class="link" onclick="message(event)">Forgot Password?</a>
          </div>

          <input type="submit" name="login" value="Login" class="btn">

        </div>
      </div>
    </form>

    <script type="text/javascript">
    function message(event) {
        // Display the confirmation dialog
        var result = confirm("Can't Remember your password, Reset your password with your Email Id !?");

        // If the user clicks "Cancel", prevent the default action (navigation)
        if (!result) {
            event.preventDefault();  // This prevents the link from navigating to the forgot_password.php page
        }
    }
</script>
  </body>
</html>


<?php
session_start(); // Start the session
include("connection.php"); // Include the database connection file

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $pwd = $_POST['password'];

    // Step 1: Authenticate User
    $stmt = $connection->prepare("SELECT id, email, name FROM login_db WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $username, $pwd);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id']; // Store user ID in session
        $_SESSION['user_email'] = $row['email'];
        $_SESSION['user_name'] = $row['name'];

        // Step 2: Retrieve FY Codes with Permission = 1
        $user_id = $row['id'];
        $permission_stmt = $connection->prepare("SELECT fy_code FROM emp_fy_permission WHERE emp_id = ? AND permission = 1");
        $permission_stmt->bind_param("i", $user_id);
        $permission_stmt->execute();
        $permission_result = $permission_stmt->get_result();

        // Fetch all allowed fy_code into an array
        $allowed_fy_codes = [];
        while ($perm_row = $permission_result->fetch_assoc()) {
            $allowed_fy_codes[] = $perm_row['fy_code'];
        }

        // Store allowed fy_codes in session
        $_SESSION['allowed_fy_codes'] = $allowed_fy_codes;

        // Redirect to index.php
        header('Location: index.php');
        exit();
    } else {
        echo "<script>alert('Login Failed! Please check your credentials.');</script>";
    }
}
?>
