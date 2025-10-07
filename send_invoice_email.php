<?php
session_start();
require_once 'connection.php';
require_once 'mail_config.php';

// Get invoice details
$invoice_id = $_GET['id'] ?? 0;
// Using a JOIN to get both invoice and email in one query
$invoice_query = "SELECT i.client_id, i.invoice_no, i.client_name, c.email_id
                  FROM invoices i
                  LEFT JOIN contact c ON i.client_id = c.id
                  WHERE i.id = ?";
$stmt = $connection->prepare($invoice_query);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
if (!$invoice) {
    die("Invoice not found");
}
$recipient_email = $invoice['email_id'] ?? '';

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $recipient = isset($_POST['recipient']) ? filter_var(trim($_POST['recipient']), FILTER_VALIDATE_EMAIL) : false;
    $subject = trim($_POST['subject'] ?? '');
    $body = $_POST['body'] ?? '';

    if (!$recipient) {
        $errors[] = "Invalid email address";
    }
    if ($subject === '') {
        $errors[] = "Subject cannot be empty.";
    }
    if ($body === '') {
        $errors[] = "Message body cannot be empty.";
    }

    // File validation
    if (!isset($_FILES['invoice_pdf']) || $_FILES['invoice_pdf']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Please upload the invoice PDF.";
    } else {
        $file_tmp = $_FILES['invoice_pdf']['tmp_name'];
        $file_type = mime_content_type($file_tmp) ?: $_FILES['invoice_pdf']['type'];
        $file_size = $_FILES['invoice_pdf']['size'];

        if ($file_type !== 'application/pdf') {
            $errors[] = "Only PDF files are allowed.";
        }
        if ($file_size > 5 * 1024 * 1024) { // 5MB limit
            $errors[] = "File size must be less than 5MB.";
        }
    }

    // Send email if no errors
    if (empty($errors)) {
        require 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(SMTP_USER, 'Splendid Infotech');
            $mail->addAddress($recipient);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            $mail->addAttachment($file_tmp, 'Invoice_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $invoice['invoice_no']) . '.pdf');

            if ($mail->send()) {
                $_SESSION['email_success'] = "Invoice sent successfully to " . $recipient;
                header("Location: invoice_display.php");
                exit();
            }
        } catch (Exception $e) {
            $errors[] = "Failed to send email: " . $mail->ErrorInfo;
        }
    }
}

// Default email body
$default_body = "Dear " . htmlspecialchars($invoice['client_name'] ?? 'Valued Customer') . ",<br><br>"
    . "Please find attached your invoice (No. " . htmlspecialchars($invoice['invoice_no'] ?? '') . ").<br><br>"
    . "Thank you for your business with Splendid Infotech.<br><br>"
    . "Best regards,<br>Splendid Infotech Team";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Invoice Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
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
        .btn-send {
            background-color: #4a90e2;
            color: white;
        }
        .btn-send:hover {
            background-color: #3a7bc8;
        }
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
        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            text-decoration: none;
            color: black;
        }
        .ql-container {
            min-height: 140px;
            border-radius: 6px;
        }
    </style>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
</head>
<body>
<div id="loadingOverlay">
    <img src="uploads/Iphone-spinner-2.gif" alt="Loading...">
</div>
<div class="container">
    <div class="email-container">
        <a href="invoice_display.php" class="close-btn">&times;</a>
        <h2 class="text-center mb-4">Send Invoice Email</h2>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" id="emailForm">
            <div class="mb-3">
                <label for="recipient" class="form-label">Recipient Email</label>
                <input type="email" class="form-control" id="recipient" name="recipient"
                       value="<?php echo htmlspecialchars($recipient_email); ?>" required>
            </div>
            <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <input type="text" class="form-control" id="subject" name="subject"
                       value="Invoice No. <?php echo htmlspecialchars($invoice['invoice_no'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="body" class="form-label">Message</label>
                <div id="editor"></div>
                <textarea style="display:none;" id="body" name="body"></textarea>
            </div>
            <div class="mb-3">
                <label for="invoice_pdf" class="form-label">Invoice PDF</label>
                <input type="file" class="form-control" id="invoice_pdf" name="invoice_pdf" accept=".pdf" required>
                <small class="text-muted">Only PDF files are allowed (max 5MB)</small>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="button" class="btn btn-secondary me-md-2"
                        onclick="if(confirm('Cancel sending invoice?')) window.location.href='invoice_display.php';">Cancel</button>
                <button type="submit" name="send_email" class="btn btn-send">Send Email</button>
            </div>
        </form>
    </div>
</div>

<script>
  var quill = new Quill('#editor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ header: [1, 2, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['link', 'image'],
        ['clean']
      ]
    }
  });

  // Load default body (from PHP)
  document.addEventListener('DOMContentLoaded', function() {
    var defaultHtml = <?php echo json_encode(isset($_POST['body']) ? $_POST['body'] : $default_body); ?>;
    quill.root.innerHTML = defaultHtml;
  });

  document.getElementById('emailForm').addEventListener('submit', function(e) {
    document.getElementById('body').value = quill.root.innerHTML;
    document.getElementById('loadingOverlay').style.display = 'flex';
    return true;
  });
</script>
</body>
</html>
