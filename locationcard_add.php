<?php
include('connection.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process the form submission
    $company_name = mysqli_real_escape_string($connection, $_POST['company_name']);
    $location = mysqli_real_escape_string($connection, $_POST['location']);
    $location_code = mysqli_real_escape_string($connection, $_POST['location_code']);
    $pincode = mysqli_real_escape_string($connection, $_POST['pincode']);
    $city = mysqli_real_escape_string($connection, $_POST['city']);
    $state = mysqli_real_escape_string($connection, $_POST['state']);
    $country = mysqli_real_escape_string($connection, $_POST['country']);
    $contact_no = mysqli_real_escape_string($connection, $_POST['contact_no']);
    $whatsapp_no = mysqli_real_escape_string($connection, $_POST['whatsapp_no']);
    $email_id = mysqli_real_escape_string($connection, $_POST['email_id']);
    $gstno = mysqli_real_escape_string($connection, $_POST['gstno']);
    $registration_no = mysqli_real_escape_string($connection, $_POST['registration_no']);

    // Insert the new location into the database
    $query = "INSERT INTO location_card (company_name, location, location_code, pincode, city, state, country, contact_no, whatsapp_no, email_id, gstno, registration_no)
              VALUES ('$company_name', '$location', '$location_code', '$pincode', '$city', '$state', '$country', '$contact_no', '$whatsapp_no', '$email_id', '$gstno', '$registration_no')";

    if (mysqli_query($connection, $query)) {
        echo "<script>alert('Location added successfully'); window.location.href='locationcard_display.php';</script>";
    } else {
        echo "<script>alert('Error adding location: " . mysqli_error($connection) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Location</title>
    <style>
        body {
            background: #2c3e50;
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
        .btn-register,
        .btn-cancel {
            padding: 10px 15px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-cancel {
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
    <a href="locationcard_display.php" class="cross-btn">✖</a> <!-- Cross button -->
    <div class="title">
        <span>Add New Location</span>
    </div>
    <form action="" method="POST">
        <div class="form-grid">
            <!-- Row 1 -->
            <div class="input_field">
                <label for="company_name">Company name <span class="required">*</span></label>
                <input type="text" name="company_name" id="company_name" required>
            </div>
            <div class="input_field">
                <label for="location">Location <span class="required">*</span></label>
                <input type="text" name="location" id="location" required>
            </div>

            <!-- Row 2 -->
            <div class="input_field">
                <label for="location_code">Location Code <span class="required">*</span></label>
                <input type="text" name="location_code" id="location_code" required>
            </div>
            <div class="input_field">
                <label for="pincode">Pincode</label>
                <input type="text" name="pincode" id="pincode">
            </div>

            <!-- Row 3 -->
            <div class="input_field">
                <label for="city">City</label>
                <input type="text" name="city" id="city">
            </div>
            <div class="input_field">
                <label for="state">State</label>
                <input type="text" name="state" id="state">
            </div>

            <!-- Row 4 -->
            <div class="input_field">
                <label for="country">Country</label>
                <input type="text" name="country" id="country">
            </div>
            <div class="input_field">
                <label for="contact_no">Contact No.</label>
                <input type="text" name="contact_no" id="contact_no">
            </div>

            <!-- Row 5 -->
            <div class="input_field">
                <label for="whatsapp_no">WhatsApp No.</label>
                <input type="text" name="whatsapp_no" id="whatsapp_no">
            </div>
            <div class="input_field">
                <label for="email_id">Email ID <span class="required">*</span></label>
                <input type="email" name="email_id" id="email_id" required>
            </div>

            <!-- Row 6 -->
            <div class="input_field">
                <label for="gstno">GST No.</label>
                <input type="text" name="gstno" id="gstno">
            </div>
            <div class="input_field">
                <label for="registration_no">Registration No.</label>
                <input type="text" name="registration_no" id="registration_no">
            </div>
        </div>

        <div class="btn-container">
            <button type="submit" class="btn-register">Register</button>
            <button type="button" class="btn-cancel" onclick="window.location.href='locationcard_display.php';">Cancel</button>
        </div>
    </form>
</div>

</body>
</html>
