<?php
session_start();
include("connection.php"); // Include your database connection

// Check if the user email is set in the session
if (!isset($_SESSION['user_email'])) {
    echo "<script>alert('Session expired. Please try again.');</script>";
    header('Location: forgot_password.php'); // Redirect to forgot password if the session has expired
    exit();
}

if (isset($_POST['reset'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if the passwords match
    if ($new_password === $confirm_password) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the hashed password in the database
        $user_email = $_SESSION['user_email']; // Assuming you stored this during forgot password
        $stmt = $connection->prepare("UPDATE login_db SET password = ?, conpassword = ? WHERE email = ?");
        $stmt->bind_param("sss", $hashed_password, $hashed_password, $user_email);

        if ($stmt->execute()) {
            echo "<script>alert('Password reset successfully!'); window.location.href = 'login.php';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to reset password! Please try again.');</script>";
        }
    } else {
        echo "<script>alert('Passwords do not match!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="login-style.css">
</head>
<body>
    <form action="" method="POST">
        <div class="center">
            <h1>Reset Password</h1>
            <div class="form">
                <div class="input-container">
                    <input type="password" name="new_password" class="textfield" placeholder="New Password" required>
                </div>
                <div class="input-container">
                    <input type="password" name="confirm_password" class="textfield" placeholder="Confirm Password" required>
                </div>
                <input type="submit" name="reset" value="Reset Password" class="btn">
            </div>
        </div>
    </form>
</body>
</html>
