<?php
session_start();
include("connection.php"); // Include your database connection file
require 'vendor/autoload.php'; // Include PHPMailer's autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $verification_code = bin2hex(random_bytes(16)); // Generate a verification code

    // Store the code and email in the session for later verification
    $_SESSION['verification_code'] = $verification_code;
    $_SESSION['user_email'] = $email; // Store the user's email in the session

    // Send verification email
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'anubhavs777779@gmail.com'; // Your Gmail address
        $mail->Password   = 'fkrp zhet axsm fzyy'; // Your Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipient
        $mail->setFrom('anubhavs777779@gmail.com', 'Your Name'); // Update to your Gmail address
        $mail->addAddress($email); // Add the recipient's email address

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Code';
        $mail->Body    = "Your password reset code is: <b>$verification_code</b>";

        $mail->send();
        echo "<script>alert('Verification code has been sent to your email!'); window.location.href = 'verify_code.php';</script>";
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="login-style.css"> <!-- Link the same stylesheet as reset_password.php -->
    <style>
        .close-btn {
            position: absolute;
            top: 0px;
            right: 0px;
            font-size: 20px;
            color: #333;
            cursor: pointer;
            background: none;
            border: none;
        }

        .close-btn:hover {
            color: #f00; /* Red color on hover */
        }
    </style>
</head>
<body>
    <form action="" method="POST">
        <div class="center">
            <button type="button" class="close-btn" onclick="window.location.href='login.php';">&times;</button>
            <h1>Email Verification</h1>
            <div class="form">

                <div class="input-container">
                    <input type="email" name="email" class="textfield" placeholder="Enter your email or username here" required>
                </div>
                <input type="submit" value="Send Verification Code" class="btn">
            </div>
        </div>
    </form>
</body>
</html>
