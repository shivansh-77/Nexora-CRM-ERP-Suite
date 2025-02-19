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

    // Fetch existing company details to get the old logo
    $query = "SELECT company_logo FROM company_card WHERE id = $id";
    $result = mysqli_query($connection, $query);
    $company = mysqli_fetch_assoc($result);

    // Use the existing logo if no new file is uploaded
    $company_logo = $company['company_logo'] ?? '';

    // Handle file upload if a new logo is uploaded
    if (!empty($_FILES["company_logo"]["name"])) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["company_logo"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($imageFileType, ['jpg', 'png', 'jpeg'])) {
            if (move_uploaded_file($_FILES["company_logo"]["tmp_name"], $target_file)) {
                $company_logo = $target_file;
            } else {
                echo "<script>alert('Error uploading logo.');</script>";
            }
        } else {
            echo "<script>alert('Only JPG, JPEG, and PNG files are allowed.');</script>";
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
                company_logo = '$company_logo'
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
    <meta charset="UTF-8">
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
            <div class="input_field" style="grid-column: span 2;">
                <label for="company_logo">Company Logo</label>
                <input type="file" name="company_logo" id="company_logo" accept="image/png, image/jpeg">
                <?php if (!empty($company['company_logo'])): ?>
                    <p style="margin-top: 10px;"><?= basename($company['company_logo']); ?></p>
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
