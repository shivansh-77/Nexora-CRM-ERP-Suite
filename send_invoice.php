<?php
use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include 'connection.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die('No invoice ID provided.');
}

// Generate the PDF
ob_start();
$_GET['id'] = $id; // Ensure invoice1.php gets the correct ID
include "invoice1.php"; // This should output invoice HTML
$html = ob_get_clean();

// Dompdf setup
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfOutput = $dompdf->output();
$pdfFileName = "invoice_{$id}.pdf";
file_put_contents($pdfFileName, $pdfOutput); // Save PDF

// Fetch email
$query = $connection->prepare("SELECT email_id AS email, client_name FROM contact WHERE id = (SELECT client_id FROM invoices WHERE id = ?)");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$data = $result->fetch_assoc();

$recipientEmail = $data['email'] ?? 'client@example.com';
$clientName = $data['client_name'] ?? 'Customer';

// Send Email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'anubhavs777779@gmail.com';
    $mail->Password   = 'fkrp zhet axsm fzyy';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('anubhavs777779@gmail.com', 'Shivansh - Splendid Infotech');
    $mail->addAddress($recipientEmail, $clientName);

    $mail->isHTML(true);
    $mail->Subject = "Invoice from Splendid Infotech";
    $mail->Body    = "Hi $clientName,<br><br>Thank you for the business. Please find your invoice attached.<br><br>Regards,<br>Splendid Infotech";

    $mail->addAttachment($pdfFileName);

    $mail->send();
    // Delete the temp file
    unlink($pdfFileName);

    echo "<script>alert('Invoice sent to $recipientEmail'); window.location.href='invoice_display.php';</script>";
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
?>
