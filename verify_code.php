<?php
session_start();

if (isset($_POST['verify'])) {
    $entered_code = $_POST['verification_code'];

    // Use the same session variable as in forgot_password.php
    if ($entered_code == $_SESSION['verification_code']) {
        // Code is valid, allow the user to reset the password
        header('Location: reset_password.php'); // Redirect to reset password page
        exit();
    } else {
        echo "<script>alert('Invalid verification code!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Code</title>
    <link rel="stylesheet" href="login-style.css">
</head>
<body>
    <form action="" method="POST">
        <div class="center">
            <h1>Verify Code</h1>
            <div class="form">
                <div class="input-container">
                    <input type="text" name="verification_code" class="textfield" placeholder="Enter Verification Code" required>
                </div>
                <input type="submit" name="verify" value="Verify" class="btn">
            </div>
        </div>
    </form>
</body>
</html>
