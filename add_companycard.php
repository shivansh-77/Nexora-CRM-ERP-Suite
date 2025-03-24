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

    // File Upload Handling
    $target_dir = "uploads/"; // Ensure this directory exists with proper permissions
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // Create the directory if it doesn't exist
    }

    $target_file = $target_dir . basename($_FILES["company_logo"]["name"]);
    $uploadOk = 1;

    // Check file type
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    if (!in_array($imageFileType, ['jpg', 'png', 'jpeg'])) {
        echo "<script>alert('Only JPG, JPEG, and PNG files are allowed.');</script>";
        $uploadOk = 0;
    }

    if ($uploadOk && move_uploaded_file($_FILES["company_logo"]["tmp_name"], $target_file)) {
        // Insert the new company into the database
        $query = "INSERT INTO company_card (company_name, address, pincode, city, state, country, contact_no, whatsapp_no, email_id, pancard, gstno, registration_no, company_type, company_logo)
                  VALUES ('$company_name', '$address', '$pincode', '$city', '$state', '$country', '$contact_no', '$whatsapp_no', '$email_id', '$pancard', '$gstno', '$registration_no', '$company_type', '$target_file')";

        if (mysqli_query($connection, $query)) {
            echo "<script>alert('Company added successfully'); window.location.href='companycard.php';</script>";
        } else {
            echo "<script>alert('Error adding company: " . mysqli_error($connection) . "');</script>";
        }
    } else {
        echo "<script>alert('Error uploading logo.');</script>";
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
        <div class="input_field" style="grid-column: span 2;">
            <label for="company_logo">Company Logo</label>
            <input type="file" name="company_logo" id="company_logo" accept="image/png, image/jpeg">
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
