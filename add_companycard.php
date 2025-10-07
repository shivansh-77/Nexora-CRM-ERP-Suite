<?php
include('connection.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if an entry already exists
    $check_query = "SELECT COUNT(*) as count FROM company_card";
    $result = mysqli_query($connection, $check_query);
    $row = mysqli_fetch_assoc($result);
    $count = $row['count'];

    if ($count > 0) {
        echo "<script>alert('An entry is already registered in the company card.'); window.location.href='companycard_display.php';</script>";
        exit(); // Stop further execution
    }

    // Process the form submission
    $company_name = mysqli_real_escape_string($connection, $_POST['company_name']);
    $address = mysqli_real_escape_string($connection, $_POST['address']);
    $pincode = mysqli_real_escape_string($connection, $_POST['pincode']);
    $city = mysqli_real_escape_string($connection, $_POST['city']);
    $state = mysqli_real_escape_string($connection, $_POST['state']);
    $country = mysqli_real_escape_string($connection, $_POST['country']);
    $contact_no = mysqli_real_escape_string($connection, $_POST['contact_no']);
    $whatsapp_no = mysqli_real_escape_string($connection, $_POST['whatsapp_no']);
    $email_id = mysqli_real_escape_string($connection, $_POST['email_id']);
    $pancard = mysqli_real_escape_string($connection, $_POST['pancard']);
    $gstno = mysqli_real_escape_string($connection, $_POST['gstno']);
    $registration_no = mysqli_real_escape_string($connection, $_POST['registration_no']);
    $company_type = mysqli_real_escape_string($connection, $_POST['company_type']);
    $working = mysqli_real_escape_string($connection, $_POST['working']);
    $working_days = mysqli_real_escape_string($connection, $_POST['working_days']);
    $minimum_shift = mysqli_real_escape_string($connection, $_POST['minimum_shift']);
    $checkout_time = mysqli_real_escape_string($connection, $_POST['checkout_time']); // Added checkout_time
    $bank_name = mysqli_real_escape_string($connection, $_POST['bank_name']);
    $branch_no = mysqli_real_escape_string($connection, $_POST['branch_no']);
    $account_no = mysqli_real_escape_string($connection, $_POST['account_no']);
    $ifsc_code = mysqli_real_escape_string($connection, $_POST['ifsc_code']);
    $swift_code = mysqli_real_escape_string($connection, $_POST['swift_code']);
    $company_email_id = mysqli_real_escape_string($connection, $_POST['company_email_id']);
$company_passkey = mysqli_real_escape_string($connection, $_POST['company_passkey']);


    // Validate working days
    if ($working_days > 7) {
        echo "<script>alert('Working days should not exceed 7.'); window.location.href='companycard.php';</script>";
        exit(); // Stop further execution
    }

    // File Upload Handling for Company Logo
    $target_dir = "uploads/"; // Ensure this directory exists with proper permissions
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // Create the directory if it doesn't exist
    }

    $target_file_logo = $target_dir . basename($_FILES["company_logo"]["name"]);
    $uploadOkLogo = 1;

    // Check file type for Company Logo
    $imageFileTypeLogo = strtolower(pathinfo($target_file_logo, PATHINFO_EXTENSION));
    if (!in_array($imageFileTypeLogo, ['jpg', 'png', 'jpeg'])) {
        echo "<script>alert('Only JPG, JPEG, and PNG files are allowed for company logo.');</script>";
        $uploadOkLogo = 0;
    }

    // File Upload Handling for QR Scanner
    $target_file_qr = $target_dir . basename($_FILES["qr_scanner"]["name"]);
    $uploadOkQr = 1;

    // Check file type for QR Scanner
    $imageFileTypeQr = strtolower(pathinfo($target_file_qr, PATHINFO_EXTENSION));
    if (!in_array($imageFileTypeQr, ['jpg', 'png', 'jpeg'])) {
        echo "<script>alert('Only JPG, JPEG, and PNG files are allowed for QR scanner.');</script>";
        $uploadOkQr = 0;
    }

    if ($uploadOkLogo && move_uploaded_file($_FILES["company_logo"]["tmp_name"], $target_file_logo) &&
        $uploadOkQr && move_uploaded_file($_FILES["qr_scanner"]["tmp_name"], $target_file_qr)) {

        // Insert the new company into the database
        $query = "INSERT INTO company_card (
      company_name, address, pincode, city, state, country, contact_no, whatsapp_no, email_id, pancard, gstno, registration_no, company_type, company_logo, working, working_days, minimum_shift, checkout_time, bank_name, branch_no, account_no, ifsc_code, swift_code, company_email_id, company_passkey, qr_scanner
  ) VALUES (
      '$company_name', '$address', '$pincode', '$city', '$state', '$country', '$contact_no', '$whatsapp_no', '$email_id', '$pancard', '$gstno', '$registration_no', '$company_type', '$target_file_logo', '$working', '$working_days', '$minimum_shift', '$checkout_time', '$bank_name', '$branch_no', '$account_no', '$ifsc_code', '$swift_code', '$company_email_id', '$company_passkey', '$target_file_qr'
  )";


        if (mysqli_query($connection, $query)) {
            echo "<script>alert('Company added successfully'); window.location.href='companycard.php';</script>";
        } else {
            echo "<script>alert('Error adding company: " . mysqli_error($connection) . "');</script>";
        }
    } else {
        echo "<script>alert('Error uploading files.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href=""> <!-- Link your external CSS file -->
    <title>Add Company</title>
    <style>
    body{
      background: #2c3e50;
      font-family: arial,sans-serif;
    }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            position: relative; /* Added for positioning the cross button */
        }
        .title {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: bold;
            margin-left: 50px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .input_field {
            display: flex;
            flex-direction: column;
        }
        .input_field label {
            margin-bottom: 5px;
        }
        .input_field input,
        .input_field select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .required {
            color: red;
        }
        .btn-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            margin-right: 320px;
        }
        .btn-register {
            padding: 10px 15px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-cancel {
            padding: 10px 15px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            overflow: hidden;
            height: auto;
        }
        .cross-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 14px;
            cursor: pointer;
            color: #2c3e50;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="companycard.php" class="cross-btn">✖</a> <!-- Cross button -->
    <div class="title">
        <span>Add New Company</span>
    </div>
    <form action="" method="POST" enctype="multipart/form-data">
    <div class="form-grid">
        <!-- Row 1 -->
        <div class="input_field">
            <label for="company_name">Company Name <span class="required">*</span></label>
            <input type="text" name="company_name" id="company_name" required>
        </div>
        <div class="input_field">
            <label for="company_type">Company Type</label>
            <select name="company_type" id="company_type">
                <option value="Private">Private</option>
                <option value="Public">Public</option>
                <option value="Partnership">Partnership</option>
                <option value="LLP">LLP</option>
            </select>
        </div>

        <!-- Row 2 -->
        <div class="input_field" style="grid-column: span 2;">
            <label for="address">Address</label>
            <input type="text" name="address" id="address">
        </div>

        <!-- Row 3 -->
        <div class="input_field">
            <label for="pincode">Pincode</label>
            <input type="text" name="pincode" id="pincode">
        </div>
        <div class="input_field">
            <label for="city">City</label>
            <input type="text" name="city" id="city">
        </div>

        <!-- Row 4 -->
        <div class="input_field">
            <label for="state">State</label>
            <input type="text" name="state" id="state">
        </div>
        <div class="input_field">
            <label for="country">Country</label>
            <input type="text" name="country" id="country">
        </div>

        <!-- Row 5 -->
        <div class="input_field">
            <label for="contact_no">Contact No.</label>
            <input type="text" name="contact_no" id="contact_no">
        </div>
        <div class="input_field">
            <label for="whatsapp_no">WhatsApp No.</label>
            <input type="text" name="whatsapp_no" id="whatsapp_no">
        </div>

        <!-- Row 6 -->
        <div class="input_field">
            <label for="email_id">Email ID <span class="required">*</span></label>
            <input type="email" name="email_id" id="email_id" required>
        </div>
        <div class="input_field">
            <label for="pancard">PAN Card</label>
            <input type="text" name="pancard" id="pancard">
        </div>

        <!-- Row 7 -->
        <div class="input_field">
            <label for="gstno">GST No.</label>
            <input type="text" name="gstno" id="gstno">
        </div>
        <div class="input_field">
            <label for="registration_no">Registration No.</label>
            <input type="text" name="registration_no" id="registration_no">
        </div>

        <!-- Row 8 -->
        <div class="input_field">
            <label for="working">Working Time</label>
            <input type="time" name="working" id="working">
        </div>
        <div class="input_field">
            <label for="working_days">Working Days</label>
            <input type="number" name="working_days" id="working_days" min="1" max="7">
        </div>

        <!-- Row 9 -->
        <div class="input_field">
            <label for="company_logo">Company Logo</label>
            <input type="file" name="company_logo" id="company_logo" accept="image/png, image/jpeg">
        </div>
        <div class="input_field">
            <label for="minimum_shift">Minimum Shift</label>
            <input type="text" name="minimum_shift" id="minimum_shift">
        </div>

        <!-- Row 10 -->
        <div class="input_field">
            <label for="checkout_time">Checkout Time</label>
            <input type="time" name="checkout_time" id="checkout_time">
        </div>
        <div class="input_field">
            <label for="bank_name">Bank Name</label>
            <input type="text" name="bank_name" id="bank_name">
        </div>

        <!-- Row 11 -->
        <div class="input_field">
            <label for="branch_no">Branch No.</label>
            <input type="text" name="branch_no" id="branch_no">
        </div>
        <div class="input_field">
            <label for="account_no">Account No.</label>
            <input type="text" name="account_no" id="account_no">
        </div>

        <!-- Row 12 -->
        <div class="input_field">
            <label for="ifsc_code">IFSC Code</label>
            <input type="text" name="ifsc_code" id="ifsc_code">
        </div>
        <div class="input_field">
            <label for="swift_code">Swift Code</label>
            <input type="text" name="swift_code" id="swift_code">
        </div>

        <!-- Row 13 (New Fields for Email & Passkey) -->
<div class="input_field">
    <label for="company_email_id">Company Email ID (For Sending Mails)</label>
    <input type="email" name="company_email_id" id="company_email_id">
</div>
<div class="input_field">
    <label for="company_passkey">Company Email ID Passkey</label>
    <input type="password" name="company_passkey" id="company_passkey">
</div>


        <!-- Row 14 -->
        <div class="input_field">
            <label for="qr_scanner">QR Scanner</label>
            <input type="file" name="qr_scanner" id="qr_scanner" accept="image/png, image/jpeg">
        </div>
    </div>

    <div class="btn-container">
        <button type="submit" class="btn-register">Register</button>
        <button type="button" class="btn-cancel" onclick="window.history.back();">Cancel</button>
    </div>
</form>

</div>

</body>
</html>
