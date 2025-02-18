<?php
include('connection.php'); // Include your database connection

// Check if 'id' is provided in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize the input

    // Fetch the existing record
    $query = "SELECT * FROM location_card WHERE id = $id";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result); // Fetch the record as an associative array
    } else {
        echo "<script>alert('Location not found'); window.location.href='locationcard_display.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('Invalid request'); window.location.href='locationcard_display.php';</script>";
    exit;
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

    // Update the record in the database
    $update_query = "UPDATE location_card
                     SET company_name = '$company_name' ,location = '$location', location_code = '$location_code', pincode = '$pincode', city = '$city',
                         state = '$state', country = '$country', contact_no = '$contact_no', whatsapp_no = '$whatsapp_no',
                         email_id = '$email_id', gstno = '$gstno', registration_no = '$registration_no'
                     WHERE id = $id";

    if (mysqli_query($connection, $update_query)) {
        echo "<script>alert('Location updated successfully'); window.location.href='locationcard_display.php';</script>";
    } else {
        echo "<script>alert('Error updating location: " . mysqli_error($connection) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href=""> <!-- Link your external CSS file -->
    <title>Update Location</title>
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

    </style>

</head>
<body>
<div class="container">
    <div class="title">
        <span>Update Location</span>
    </div>
    <form action="" method="POST">
        <div class="form-grid">
          <div class="input_field">
              <label for="company_name">Location <span class="required">*</span></label>
              <input type="text" name="company_name" id="company_name" value="<?php echo htmlspecialchars($row['company_name']); ?>" required>
          </div>
            <div class="input_field">
                <label for="location">Location <span class="required">*</span></label>
                <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($row['location']); ?>" required>
            </div>
            <div class="input_field">
                <label for="location_code">Location Code <span class="required">*</span></label>
                <input type="text" name="location_code" id="location_code" value="<?php echo htmlspecialchars($row['location_code']); ?>" required>
            </div>
            <div class="input_field">
                <label for="pincode">Pincode</label>
                <input type="text" name="pincode" id="pincode" value="<?php echo htmlspecialchars($row['pincode']); ?>">
            </div>
            <div class="input_field">
                <label for="city">City</label>
                <input type="text" name="city" id="city" value="<?php echo htmlspecialchars($row['city']); ?>">
            </div>
            <div class="input_field">
                <label for="state">State</label>
                <input type="text" name="state" id="state" value="<?php echo htmlspecialchars($row['state']); ?>">
            </div>
            <div class="input_field">
                <label for="country">Country</label>
                <input type="text" name="country" id="country" value="<?php echo htmlspecialchars($row['country']); ?>">
            </div>
            <div class="input_field">
                <label for="contact_no">Contact No.</label>
                <input type="text" name="contact_no" id="contact_no" value="<?php echo htmlspecialchars($row['contact_no']); ?>">
            </div>
            <div class="input_field">
                <label for="whatsapp_no">WhatsApp No.</label>
                <input type="text" name="whatsapp_no" id="whatsapp_no" value="<?php echo htmlspecialchars($row['whatsapp_no']); ?>">
            </div>
            <div class="input_field">
                <label for="email_id">Email ID <span class="required">*</span></label>
                <input type="email" name="email_id" id="email_id" value="<?php echo htmlspecialchars($row['email_id']); ?>" required>
            </div>
            <div class="input_field">
                <label for="gstno">GST No.</label>
                <input type="text" name="gstno" id="gstno" value="<?php echo htmlspecialchars($row['gstno']); ?>">
            </div>
            <div class="input_field">
                <label for="registration_no">Registration No.</label>
                <input type="text" name="registration_no" id="registration_no" value="<?php echo htmlspecialchars($row['registration_no']); ?>">
            </div>
        </div>
        <div class="btn-container">
            <button type="submit" class="btn-register">Update</button>
            <button type="button" class="btn-cancel" onclick="window.location.href='locationcard_display.php';">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>
