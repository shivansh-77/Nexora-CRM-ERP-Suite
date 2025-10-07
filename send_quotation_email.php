<?php
session_start();
require_once 'connection.php';
require_once 'mail_config.php';

// Get quotation details
$quotation_id = $_GET['id'] ?? 0;
$quotation_query = "SELECT client_id, quotation_no, client_name FROM quotations WHERE id = ?";
$stmt = $connection->prepare($quotation_query);
$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$quotation = $stmt->get_result()->fetch_assoc();
if (!$quotation) {
    die("Quotation not found");
}

// Get recipient email (if any)
$client_id = $quotation['client_id'];
$email_query = "SELECT email_id FROM contact WHERE id = ?";
$stmt2 = $connection->prepare($email_query);
$stmt2->bind_param("i", $client_id);
$stmt2->execute();
$email_data = $stmt2->get_result()->fetch_assoc();
$recipient_email = $email_data['email_id'] ?? '';

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    // Collect and validate
    $recipient = isset($_POST['recipient']) ? filter_var(trim($_POST['recipient']), FILTER_VALIDATE_EMAIL) : false;
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $body = isset($_POST['body']) ? trim($_POST['body']) : '';

    if (!$recipient) {
        $errors[] = "Invalid or missing recipient email address.";
    }
    if ($subject === '') {
        $errors[] = "Subject cannot be empty.";
    }
    if ($body === '') {
        $errors[] = "Message body cannot be empty.";
    }

    // File checks: make sure upload exists and has no php upload errors
    if (!isset($_FILES['invoice_pdf'])) {
        $errors[] = "Please attach the quotation PDF.";
    } else {
        $fileError = $_FILES['invoice_pdf']['error'];
        if ($fileError !== UPLOAD_ERR_OK) {
            // Provide friendly messages for common error codes
            switch ($fileError) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = "Uploaded file is too large. Check upload_max_filesize and post_max_size settings.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = "File was only partially uploaded. Please try again.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = "No file uploaded. Please attach the quotation PDF.";
                    break;
                default:
                    $errors[] = "File upload error (code: $fileError).";
            }
        } else {
            // Basic type check - trusting client MIME isn't perfect, but it's a reasonable check
            $file_tmp = $_FILES['invoice_pdf']['tmp_name'];
            $file_type = mime_content_type($file_tmp) ?: $_FILES['invoice_pdf']['type'];
            // Accept common PDF mime types
            if ($file_type !== 'application/pdf' && $file_type !== 'application/x-pdf') {
                $errors[] = "Only PDF files are allowed.";
            }
        }
    }

    // If no errors, send email
    if (empty($errors)) {
        require 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // SMTP config from mail_config.php
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;

            // Choose encryption: use STARTTLS by default unless 'ssl' specified
            $secureLower = strtolower(SMTP_SECURE ?? '');
            if ($secureLower === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->Port = (int)SMTP_PORT;

            $mail->setFrom(SMTP_USER, 'Splendid Infotech');
            $mail->addAddress($recipient);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            // Attach PDF
            $mail->addAttachment($file_tmp, 'Quotation_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $quotation['quotation_no']) . '.pdf');

            // Send
            if ($mail->send()) {
                $_SESSION['email_success'] = "Quotation sent successfully to " . $recipient;
                header("Location: quotation_display.php");
                exit();
            } else {
                $errors[] = "Failed to send email. Please try again later.";
            }
        } catch (Exception $e) {
            // Log server-side and show friendly message
            error_log("PHPMailer Exception: " . $mail->ErrorInfo . " / " . $e->getMessage());
            $errors[] = "Failed to send email. Server error occurred (details logged).";
        }
    }
}

// Default email body as HTML
$default_body = "Hello " . htmlspecialchars($quotation['client_name'] ?? '') . ",<br><br>"
    . "This is your quotation (No. " . htmlspecialchars($quotation['quotation_no'] ?? '') . ").<br><br>"
    . "Thank you for your business with Splendid Infotech.<br><br>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Quotation Email</title>
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
        /* Quill content area */
        .ql-container {
            min-height: 140px;
            border-radius: 6px;
        }
    </style>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
</head>
<body>
<!-- Loading GIF -->
<div id="loadingOverlay">
    <img src="uploads/Iphone-spinner-2.gif" alt="Loading...">
</div>
<div class="container">
    <div class="email-container">
        <a href="quotation_display.php" class="close-btn">&times;</a>
        <h2 class="text-center mb-4">Send Quotation Email</h2>

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
                <small class="text-muted">You can edit this email address before sending.</small>
            </div>

            <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <input type="text" class="form-control" id="subject" name="subject"
                       value="Quotation No. <?php echo htmlspecialchars($quotation['quotation_no'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="body" class="form-label">Message</label>
                <!-- Quill Editor -->
                <div id="editor"></div>
                <!-- hidden textarea (do NOT mark required) -->
                <textarea style="display:none;" id="body" name="body"></textarea>
            </div>

            <div class="mb-3">
                <label for="invoice_pdf" class="form-label">Quotation PDF</label>
                <input type="file" class="form-control" id="invoice_pdf" name="invoice_pdf" accept=".pdf" required>
                <small class="text-muted">Only PDF files are allowed</small>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="button" class="btn btn-secondary me-md-2"
                        onclick="if(confirm('Are you sure you want to cancel?')) window.location.href='quotation_display.php';">Cancel</button>
                <button type="submit" name="send_email" class="btn btn-send">Send Email</button>
            </div>
        </form>
    </div>
</div>

<script>
  // Init Quill
  var quill = new Quill('#editor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ header: [1, 2, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        ['link', 'image'],
        ['clean']
      ]
    }
  });

  // Load default HTML body from PHP
  document.addEventListener('DOMContentLoaded', function() {
    var defaultHtml = <?php echo json_encode(isset($_POST['body']) ? $_POST['body'] : $default_body); ?>;
    quill.root.innerHTML = defaultHtml;
  });

  // Robust submit handler
  document.getElementById('emailForm').addEventListener('submit', function(e) {
    // Copy Quill HTML into hidden textarea
    document.getElementById('body').value = quill.root.innerHTML;

    // Basic client-side validation (improves UX)
    var recipient = document.getElementById('recipient').value.trim();
    var fileInput = document.getElementById('invoice_pdf');

    if (!recipient) {
      alert('Please enter recipient email.');
      e.preventDefault();
      return false;
    }

    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(recipient)) {
      alert('Please enter a valid email address.');
      e.preventDefault();
      return false;
    }

    if (!fileInput || fileInput.files.length === 0) {
      alert('Please attach the quotation PDF.');
      e.preventDefault();
      return false;
    }

    var f = fileInput.files[0];
    if (f && f.type !== 'application/pdf') {
      alert('Please attach a PDF file.');
      e.preventDefault();
      return false;
    }

    // Show loading spinner and let form submit
    showLoading();
    return true;
  });

  function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
  }
</script>
</body>
</html>
