<?php
include('connection.php');

// Check if an ID is provided in the URL
if (isset($_GET['id'])) {
    $contactId = $_GET['id'];

    // Fetch the vendor contact data by ID
    $sql = "SELECT * FROM contact_vendor WHERE id = $contactId";
    $result = $connection->query($sql);
    $contact = $result->fetch_assoc();

    // If no record is found, redirect to display.php
    if (!$contact) {
        header("Location: contact_vendor_display.php");
        exit();
    }
} else {
    header("Location: contact_vendor_display.php");
    exit();
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    // Update query
    $sql = "UPDATE contact_vendor SET
        contact_person = '{$_POST['contact-person']}',
        company_name = '{$_POST['company-name']}',
        mobile_no = '{$_POST['mobile-no']}',
        whatsapp_no = '{$_POST['whatsapp-no']}',
        email_id = '{$_POST['email']}',
        address = '{$_POST['address']}',
        country = '{$_POST['country']}',
        state = '{$_POST['state']}',
        city = '{$_POST['city']}',
        pincode = '{$_POST['pincode']}',
        reference_pname = '{$_POST['reference-person-name']}',
        reference_pname_no = '{$_POST['reference-person-mobile']}',
        remarks = '{$_POST['remarks']}',
        gstno = '{$_POST['gstno']}'
        WHERE id = $contactId";

    if ($connection->query($sql)) {
        echo "<script>alert('Record updated successfully!'); window.location.href = 'contact_vendor_display.php';</script>";
        exit();
    } else {
        echo "Error: " . $connection->error;
    }
}

// Close the connection
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Vendor Contact</title>
  <style>
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #2c3e50;
  }

  .cancel-btn {
    text-decoration: none;
  }

  .form-container {
    width: 80%;
    max-width: 1200px; /* To keep the form size manageable */
    background: #fff;
    border-radius: 10px;
    padding: 20px 30px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    position: relative; /* Add this line */
  }

  .form-container h2 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 24px;
    color: #2c3e50;
  }

  .form-group {
    display: flex;
    gap: 10px; /* Space between input fields */
    margin-bottom: 15px;
    flex-wrap: wrap; /* Ensure fields wrap properly on smaller screens */
  }

  .form-group.full {
    display: flex;
  }

  .form-group label {
    display: block;
    font-size: 14px;
    color: #555;
    margin-bottom: 5px;
  }

  .form-group input,
  .form-group select {
    flex: 1; /* Each input takes equal space */
    padding: 12px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    outline: none;
  }

  .form-group input:focus,
  .form-group select:focus {
    border-color: #007bff;
  }

  .form-group.full input {
    width: 100%;
  }

  .form-actions {
    text-align: center;
    margin-top: 20px;
  }

  .form-actions button,
  .cancel-btn {
    padding: 10px 20px;
    font-size: 16px;
    background: #2c3e50;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }

  .form-actions button:hover,
  .cancel-btn:hover {
    background: #2c3e50;
  }

  /* Ensure all fields align perfectly */
  .form-group > div {
    flex: 1;
    display: flex;
    flex-direction: column; /* Ensure label and input are stacked */
  }

  .form-group > div:last-child {
    margin-right: 0; /* Prevent last column from adding unnecessary margin */
  }

  .form-group.full {
    display: flex; /* Keep it flex like other rows */
    flex-wrap: wrap; /* Allow wrapping for responsiveness */
    gap: 10px;
  }

  .form-group.full label {
    width: 100%; /* Ensure the label takes full width */
    margin-bottom: 5px;
  }

  .form-group.full input {
    flex: 1; /* Ensure the input spans the remaining width */
    padding: 12px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    outline: none;
  }

  .form-group.full textarea {
    width: 100%;
    padding: 12px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    outline: none;
  }

  .close-btn {
    position: absolute;
    top: 10px; /* Adjust this value as needed */
    right: 10px; /* Adjust this value as needed */
    font-size: 24px; /* Increase size for better visibility */
    color: #2c3e50;
    cursor: pointer;
    transition: color 0.3s;
  }

  .close-btn:hover {
    color: #e74c3c; /* Change color on hover for better UX */
  }
  </style>
</head>
<body>
<div class="form-container">
  <a style="text-decoration:None;"href="contact_vendor_display.php" class="close-btn">&times;</a>
  <h2>Update Vendor Contact</h2>
  <form method="POST" action="">
      <!-- Row 1 -->
      <div class="form-group">
        <div>
          <label for="contact-person">Contact Person *</label>
          <input type="text" id="contact-person" name="contact-person" placeholder="Enter Contact Person" value="<?php echo htmlspecialchars($contact['contact_person']); ?>">
        </div>
        <div>
          <label for="company-name">Company Name</label>
          <input type="text" id="company-name" name="company-name" placeholder="Enter Company Name" value="<?php echo htmlspecialchars($contact['company_name']); ?>">
        </div>
      </div>

      <!-- Row 2 -->
      <div class="form-group">
        <div>
          <label for="mobile-no">Mobile No *</label>
          <input type="text" id="mobile-no" name="mobile-no" placeholder="Enter Mobile No" value="<?php echo htmlspecialchars($contact['mobile_no']); ?>">
        </div>
        <div>
          <label for="whatsapp-no">WhatsApp No</label>
          <input type="text" id="whatsapp-no" name="whatsapp-no" placeholder="Enter WhatsApp No" value="<?php echo htmlspecialchars($contact['whatsapp_no']); ?>">
        </div>
        <div>
          <label for="email">Email ID</label>
          <input type="email" id="email" name="email" placeholder="Enter Email ID" value="<?php echo htmlspecialchars($contact['email_id']); ?>">
        </div>
      </div>

      <!-- Row 3 -->
      <div class="form-group full">
        <label for="address">Address</label>
        <input type="text" id="address" name="address" placeholder="Enter Address" value="<?php echo htmlspecialchars($contact['address']); ?>">
      </div>

      <!-- Row 4 -->
      <div class="form-group">
        <div>
          <label for="country">Country</label>
          <select id="country" name="country">
            <option value="india" <?php echo ($contact['country'] == 'india') ? 'selected' : ''; ?>>India</option>
            <option value="usa" <?php echo ($contact['country'] == 'usa') ? 'selected' : ''; ?>>USA</option>
            <option value="uk" <?php echo ($contact['country'] == 'uk') ? 'selected' : ''; ?>>UK</option>
          </select>
        </div>
        <div>
    <label for="state">State</label>
    <select id="state" name="state">
        <option value="">Select State</option>
        <?php
        // List of all Indian states
        $states = [
            "Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", "Goa",
            "Gujarat", "Haryana", "Himachal Pradesh", "Jharkhand", "Karnataka", "Kerala",
            "Madhya Pradesh", "Maharashtra", "Manipur", "Meghalaya", "Mizoram", "Nagaland",
            "Odisha", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu", "Telangana", "Tripura",
            "Uttar Pradesh", "Uttarakhand", "West Bengal", "Andaman and Nicobar Islands",
            "Chandigarh", "Dadra and Nagar Haveli", "Daman and Diu", "Lakshadweep", "Delhi",
            "Puducherry"
        ];

        // Loop through the states array to create options
        foreach ($states as $state) {
            // Check if the current state is the one stored in the database
            $selected = ($state == htmlspecialchars($contact['state'])) ? 'selected' : '';
            echo "<option value='$state' $selected>$state</option>";
        }
        ?>
    </select>
</div>

        <div>
          <label for="city">City</label>
          <input type="text" id="city" name="city" placeholder="Enter City" value="<?php echo htmlspecialchars($contact['city']); ?>">
        </div>
        <div>
          <label for="pincode">Pincode</label>
          <input type="text" id="pincode" name="pincode" placeholder="Enter Pincode" value="<?php echo htmlspecialchars($contact['pincode']); ?>">
        </div>
      </div>

      <!-- Row 5 -->
      <div class="form-group">
        <div>
          <label for="reference-person-name">Reference Person Name</label>
          <input type="text" id="reference-person-name" name="reference-person-name" placeholder="Enter Reference Person Name" value="<?php echo htmlspecialchars($contact['reference_pname']); ?>">
        </div>
        <div>
          <label for="reference-person-mobile">Reference Person Mobile No.</label>
          <input type="text" id="reference-person-mobile" name="reference-person-mobile" placeholder="Enter Reference Person Mobile No." value="<?php echo htmlspecialchars($contact['reference_pname_no']); ?>">
        </div>
      </div>

      <!-- Row 6 (Remarks taking full width) -->
      <div class="form-group">
      <div style="grid-column: span 2;">
        <label for="remarks">Remarks</label>
        <textarea id="remarks" name="remarks" placeholder="Enter Remarks" rows="2" class="input-field"><?php echo htmlspecialchars($contact['remarks']); ?></textarea>
    </div>
      </div>

      <!-- Row 7 -->
      <div class="form-group">
        <div>
          <label for="gst-no">GST No.</label>
          <input type="text" id="gst-no" name="gstno" placeholder="Enter GST Number" class="input-field" value="<?php echo htmlspecialchars($contact['gstno']); ?>">
        </div>
      </div>

      <!-- Row 8 (Actions) -->
      <div class="form-actions">
        <button type="submit" name="update" class="button">Update</button>
        <a href="contact_vendor_display.php" class="cancel-btn">Cancel</a>
      </div>
  </form>
</div>
</body>
</html>
