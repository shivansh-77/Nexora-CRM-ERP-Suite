<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // show detailed debug in browser
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'anubhavs777779@gmail.com';     // <-- your Gmail
    $mail->Password   = 'fkrp zhet axsm fzyy';   // <-- 16-char app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('your_email@gmail.com', 'Test Mail');
    $mail->addAddress('any_destination@example.com'); // send to yourself for testing

    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test';
    $mail->Body    = 'If you see this, SMTP works!';

    $mail->send();
    echo "Message sent successfully!";
} catch (Exception $e) {
    echo "Message failed. Error: {$mail->ErrorInfo}";
}
