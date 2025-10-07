<?php
session_start();
require_once 'connection.php';
require_once 'mail_config.php';

// Get invoice details
$invoice_id = $_GET['id'] ?? 0;
$invoice_query = "SELECT vendor_id, invoice_no, vendor_name FROM purchase_invoice WHERE id = ?";
$stmt = $connection->prepare($invoice_query);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();

if (!$invoice) {
    die("Invoice not found");
}

// Get recipient email
$client_id = $invoice['vendor_id'];
$email_query = "SELECT email_id FROM contact_vendor WHERE id = ?";
$stmt2 = $connection->prepare($email_query);
$stmt2->bind_param("i", $client_id);
$stmt2->execute();
$email_data = $stmt2->get_result()->fetch_assoc();
$recipient_email = $email_data['email_id'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $errors = [];

    $recipient = filter_var($_POST['recipient'], FILTER_VALIDATE_EMAIL);
    $subject = htmlspecialchars($_POST['subject']);
    $body = htmlspecialchars($_POST['body']);

    if (!$recipient) {
        $errors[] = "Invalid email address";
    }

    if ($_FILES['invoice_pdf']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['invoice_pdf']['tmp_name'];
        $file_type = $_FILES['invoice_pdf']['type'];

        if ($file_type != 'application/pdf') {
            $errors[] = "Only PDF files are allowed";
        }
    } else {
        $errors[] = "Please upload the invoice PDF";
    }

    if (empty($errors)) {
        require 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer();

        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        $mail->setFrom(SMTP_USER, 'Splendid Infotech');
        $mail->addAddress($recipient);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->addAttachment($file_tmp, 'Invoice_' . $invoice['invoice_no'] . '.pdf');

        if ($mail->send()) {
            $_SESSION['email_success'] = "Mail sent successfully to " . $recipient;
            echo "<script>

                    window.location.href = 'purchase_invoice_display.php';
                  </script>";
            exit();
        } else {
            $errors[] = "Failed to send email: " . $mail->ErrorInfo;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Invoice Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #2c3e50;
            padding-top: 50px;
        }
        .email-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
        }
        .form-label {
            font-weight: 500;
        }
        .btn-send {
            background-color: #4a90e2;
            color: white;
        }
        .btn-send:hover {
            background-color: #3a7bc8;
        }
        .alert {
            margin-top: 20px;
        }
        /* Loading overlay */
        #loadingOverlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(255,255,255,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        #loadingOverlay img {
            width: 100px;
        }
        /* Close button */
        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            text-decoration: none;
            color: black;
        }
    </style>
</head>
<body>

<!-- Loading GIF -->
<div id="loadingOverlay">
    <img src="uploads/Iphone-spinner-2.gif" alt="Loading...">
</div>

<div class="container">
    <div class="email-container">
        <a href="purchase_invoice_display.php" class="close-btn">&times;</a>
        <h2 class="text-center mb-4">Send Invoice Email</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" onsubmit="showLoading()">
            <div class="mb-3">
                <label for="recipient" class="form-label">Recipient Email</label>
                <input type="email" class="form-control" id="recipient" name="recipient"
                       value="<?php echo htmlspecialchars($recipient_email); ?>" required>
                <small class="text-muted">You can edit this email address before sending.</small>
            </div>

            <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <input type="text" class="form-control" id="subject" name="subject"
                       value="Invoice No. <?php echo htmlspecialchars($invoice['invoice_no'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="body" class="form-label">Message</label>
                <textarea class="form-control" id="body" name="body" rows="5" required>Hello <?php echo htmlspecialchars($invoice['vendor_name'] ?? ''); ?>,

This is your invoice generated <?php echo htmlspecialchars($invoice['invoice_no'] ?? ''); ?>.

Thank you for your business with Splendid Infotech.</textarea>
            </div>

            <div class="mb-3">
                <label for="invoice_pdf" class="form-label">Invoice PDF</label>
                <input type="file" class="form-control" id="invoice_pdf" name="invoice_pdf" accept=".pdf" required>
                <small class="text-muted">Only PDF files are allowed</small>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="button" class="btn btn-secondary me-md-2"
                        onclick="if(confirm('Are you sure you want to cancel?')) window.location.href='purchase_invoice_display.php';">Cancel</button>
                <button type="submit" name="send_email" class="btn btn-send">Send Email</button>
            </div>
        </form>
    </div>
</div>

<script>
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}
</script>

</body>
</html>
