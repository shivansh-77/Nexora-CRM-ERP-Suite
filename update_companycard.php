<?php
include('connection.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM company_card WHERE id = $id";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $company = mysqli_fetch_assoc($result);
    } else {
        echo "<script>alert('Invalid Company ID'); window.location.href='companycard.php';</script>";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
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
        echo "<script>alert('Working days should not exceed 7.'); window.location.href='update_companycard.php?id=$id';</script>";
        exit(); // Stop further execution
    }

    // Fetch existing company details to get the old logo and QR scanner
    $query = "SELECT company_logo, qr_scanner FROM company_card WHERE id = $id";
    $result = mysqli_query($connection, $query);
    $company = mysqli_fetch_assoc($result);

    // Use the existing logo if no new file is uploaded
    $company_logo = $company['company_logo'] ?? '';
    $qr_scanner = $company['qr_scanner'] ?? '';

    // Handle file upload if a new logo is uploaded
    if (!empty($_FILES["company_logo"]["name"])) {
        $target_dir = "uploads/";
        $target_file_logo = $target_dir . basename($_FILES["company_logo"]["name"]);
        $imageFileTypeLogo = strtolower(pathinfo($target_file_logo, PATHINFO_EXTENSION));

        if (in_array($imageFileTypeLogo, ['jpg', 'png', 'jpeg'])) {
            if (move_uploaded_file($_FILES["company_logo"]["tmp_name"], $target_file_logo)) {
                $company_logo = $target_file_logo;
            } else {
                echo "<script>alert('Error uploading logo.');</script>";
            }
        } else {
            echo "<script>alert('Only JPG, JPEG, and PNG files are allowed for company logo.');</script>";
        }
    }

    // Handle file upload if a new QR scanner is uploaded
    if (!empty($_FILES["qr_scanner"]["name"])) {
        $target_dir = "uploads/";
        $target_file_qr = $target_dir . basename($_FILES["qr_scanner"]["name"]);
        $imageFileTypeQr = strtolower(pathinfo($target_file_qr, PATHINFO_EXTENSION));

        if (in_array($imageFileTypeQr, ['jpg', 'png', 'jpeg'])) {
            if (move_uploaded_file($_FILES["qr_scanner"]["tmp_name"], $target_file_qr)) {
                $qr_scanner = $target_file_qr;
            } else {
                echo "<script>alert('Error uploading QR scanner.');</script>";
            }
        } else {
            echo "<script>alert('Only JPG, JPEG, and PNG files are allowed for QR scanner.');</script>";
        }
    }

    // Update query
    $query = "UPDATE company_card SET
        company_name = '$company_name',
        address = '$address',
        pincode = '$pincode',
        city = '$city',
        state = '$state',
        country = '$country',
        contact_no = '$contact_no',
        whatsapp_no = '$whatsapp_no',
        email_id = '$email_id',
        pancard = '$pancard',
        gstno = '$gstno',
        registration_no = '$registration_no',
        company_type = '$company_type',
        company_logo = '$company_logo',
        working = '$working',
        working_days = '$working_days',
        minimum_shift = '$minimum_shift',
        checkout_time = '$checkout_time',
        bank_name = '$bank_name',
        branch_no = '$branch_no',
        account_no = '$account_no',
        ifsc_code = '$ifsc_code',
        swift_code = '$swift_code',
        company_email_id = '$company_email_id',
        company_passkey = '$company_passkey',
        qr_scanner = '$qr_scanner'
    WHERE id = $id";


    if (mysqli_query($connection, $query)) {
        echo "<script>alert('Company updated successfully'); window.location.href='companycard.php';</script>";
    } else {
        echo "<script>alert('Error updating company: " . mysqli_error($connection) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Company</title>
    <style>
        body {
            background: #2c3e50;
            color: #333;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            position: relative; /* For positioning the cross button */
        }
        .title {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: bold;
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
        .input_field input, .input_field select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .btn-container {
            display: flex;
            justify-content: center; /* Center the buttons */
            margin-top: 20px;
        }
        .btn {
            padding: 10px 15px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-update {
            background-color: #2c3e50;
        }
        .btn-cancel {
            background-color: #2c3e50;
            margin-left: 10px;
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
    <div class="title">Update Company Card</div>
    <form action="update_companycard.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $company['id'] ?>">
        <div class="form-grid">
            <!-- Editable Fields -->
            <div class="input_field">
                <label for="company_name">Company Name</label>
                <input type="text" name="company_name" id="company_name" value="<?= $company['company_name'] ?>" required>
            </div>
            <div class="input_field">
                <label for="company_type">Company Type</label>
                <select name="company_type" id="company_type">
                    <option value="Private" <?= $company['company_type'] == 'Private' ? 'selected' : '' ?>>Private</option>
                    <option value="Public" <?= $company['company_type'] == 'Public' ? 'selected' : '' ?>>Public</option>
                    <option value="Partnership" <?= $company['company_type'] == 'Partnership' ? 'selected' : '' ?>>Partnership</option>
                    <option value="LLP" <?= $company['company_type'] == 'LLP' ? 'selected' : '' ?>>LLP</option>
                </select>
            </div>
            <div class="input_field" style="grid-column: span 2;">
                <label for="address">Address</label>
                <input type="text" name="address" id="address" value="<?= $company['address'] ?>">
            </div>
            <div class="input_field">
                <label for="pincode">Pincode</label>
                <input type="text" name="pincode" id="pincode" value="<?= $company['pincode'] ?>">
            </div>
            <div class="input_field">
                <label for="city">City</label>
                <input type="text" name="city" id="city" value="<?= $company['city'] ?>">
            </div>
            <div class="input_field">
                <label for="state">State</label>
                <input type="text" name="state" id="state" value="<?= $company['state'] ?>">
            </div>
            <div class="input_field">
                <label for="country">Country</label>
                <input type="text" name="country" id="country" value="<?= $company['country'] ?>">
            </div>
            <div class="input_field">
                <label for="contact_no">Contact No.</label>
                <input type="text" name="contact_no" id="contact_no" value="<?= $company['contact_no'] ?>">
            </div>
            <div class="input_field">
                <label for="whatsapp_no">WhatsApp No.</label>
                <input type="text" name="whatsapp_no" id="whatsapp_no" value="<?= $company['whatsapp_no'] ?>">
            </div>
            <div class="input_field">
                <label for="email_id">Email ID</label>
                <input type="email" name="email_id" id="email_id" value="<?= $company['email_id'] ?>" required>
            </div>
            <div class="input_field">
                <label for="pancard">PAN Card</label>
                <input type="text" name="pancard" id="pancard" value="<?= $company['pancard'] ?>">
            </div>
            <div class="input_field">
                <label for="gstno">GST No.</label>
                <input type="text" name="gstno" id="gstno" value="<?= $company['gstno'] ?>">
            </div>
            <div class="input_field">
                <label for="registration_no">Registration No.</label>
                <input type="text" name="registration_no" id="registration_no" value="<?= $company['registration_no'] ?>">
            </div>
            <div class="input_field">
                <label for="company_logo">Company Logo</label>
                <input type="file" name="company_logo" id="company_logo" accept="image/png, image/jpeg">
                <?php if (!empty($company['company_logo'])): ?>
                    <p style="margin-top: 10px;"><?= basename($company['company_logo']); ?></p>
                <?php endif; ?>
            </div>
            <div class="input_field">
                <label for="minimum_shift">Minimum Shift</label>
                <input type="time" name="minimum_shift" id="minimum_shift" value="<?= $company['minimum_shift'] ?>">
            </div>
            <div class="input_field">
                <label for="checkout_time">Checkout Time</label>
                <input type="time" name="checkout_time" id="checkout_time" value="<?= $company['checkout_time'] ?>">
            </div>
            <div class="input_field">
                <label for="working">Working Time</label>
                <input type="time" name="working" id="working" value="<?= $company['working'] ?>">
            </div>
            <div class="input_field">
                <label for="working_days">Working Days</label>
                <input type="number" name="working_days" id="working_days" value="<?= $company['working_days'] ?>" min="1" max="7">
            </div>
            <div class="input_field">
                <label for="bank_name">Bank Name</label>
                <input type="text" name="bank_name" id="bank_name" value="<?= $company['bank_name'] ?>">
            </div>
            <div class="input_field">
                <label for="branch_no">Branch No.</label>
                <input type="text" name="branch_no" id="branch_no" value="<?= $company['branch_no'] ?>">
            </div>
            <div class="input_field">
                <label for="account_no">Account No.</label>
                <input type="text" name="account_no" id="account_no" value="<?= $company['account_no'] ?>">
            </div>
            <div class="input_field">
                <label for="ifsc_code">IFSC Code</label>
                <input type="text" name="ifsc_code" id="ifsc_code" value="<?= $company['ifsc_code'] ?>">
            </div>
            <div class="input_field">
                <label for="swift_code">Swift Code</label>
                <input type="text" name="swift_code" id="swift_code" value="<?= $company['swift_code'] ?>">
            </div>
            <div class="input_field">
    <label for="company_email_id">Company Email ID (For Sending Mails)</label>
    <input type="email" name="company_email_id" id="company_email_id" value="<?= $company['company_email_id'] ?>">
</div>
<div class="input_field">
    <label for="company_passkey">Company Email ID Passkey</label>
    <input type="password" name="company_passkey" id="company_passkey" value="<?= $company['company_passkey'] ?>">
</div>

            <div class="input_field">
                <label for="qr_scanner">QR Scanner</label>
                <input type="file" name="qr_scanner" id="qr_scanner" accept="image/png, image/jpeg">
                <?php if (!empty($company['qr_scanner'])): ?>
                    <p style="margin-top: 10px;"><?= basename($company['qr_scanner']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="btn-container">
            <button type="submit" class="btn btn-update">Update</button>
            <a style="text-decoration: none;" href="companycard.php" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
