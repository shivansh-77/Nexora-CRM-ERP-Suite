<?php
include('connection.php');
session_start(); // Start the session

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Save values to the session
    $_SESSION['lead_source'] = $_POST['lead-source'];
    $_SESSION['lead_for'] = $_POST['lead-for'];

    // Prepare the SQL query for inserting into the contact table
    $sql = "INSERT INTO contact (
        lead_source, lead_for, lead_priority, contact_person, company_name,
        mobile_no, whatsapp_no, email_id, address, country, state, city,
        pincode, reference_pname, reference_pname_no, estimate_amnt, followupdate, employee, gstno, remarks
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare the statement
    $stmt = $connection->prepare($sql);

    // Check if the prepare was successful
    if (!$stmt) {
        die("Preparation failed: " . $connection->error);
    }

    // Bind the parameters to the prepared statement
    $stmt->bind_param(
        'ssssssssssssssssssss', // 15 string parameters and 1 double for estimate_amnt
        $_POST['lead-source'],
        $_POST['lead-for'],
        $_POST['lead-priority'],
        $_POST['contact-person'],
        $_POST['company-name'],
        $_POST['mobile-no'],
        $_POST['whatsapp-no'],
        $_POST['email'],
        $_POST['address'],
        $_POST['country'],
        $_POST['state'],
        $_POST['city'],
        $_POST['pincode'],
        $_POST['reference-person-name'],
        $_POST['reference-person-mobile'],
        $_POST['estimate-amount'], // Convert to float for estimate_amnt
        $_POST['next-follow-up-date'],
        $_POST['employee'],
        $_POST['gstno'],
        $_POST['remarks']
    );

    // Execute the statement
    if ($stmt->execute()) {
        // Get the last inserted contact ID
        $last_contact_id = $connection->insert_id;

        // Retrieve fy_code with permission = 1
        $fy_code_sql = "SELECT fy_code FROM emp_fy_permission WHERE permission = 1 LIMIT 1";
        $fy_code_result = $connection->query($fy_code_sql);

        // Check if the query was successful and fetch the fy_code
        $fy_code = null;
        if ($fy_code_result && $fy_code_result->num_rows > 0) {
            $fy_row = $fy_code_result->fetch_assoc();
            $fy_code = $fy_row['fy_code'];
        } else {
            // Handle the case where there is no fy_code found (optional)
            echo "No fy_code found with permission 1.";
            exit(); // Stop further execution if needed
        }

        // Prepare the SQL query for inserting into the followup table
        $followup_sql = "INSERT INTO followup (contact_id, lead_source, lead_for, lead_priority, fy_code) VALUES (?, ?, ?, ?, ?)";

        // Prepare the followup statement
        $followup_stmt = $connection->prepare($followup_sql);
        if (!$followup_stmt) {
            die("Preparation failed: " . $connection->error);
        }

        // Bind the parameters
        $followup_stmt->bind_param(
            'issss',
            $last_contact_id,
            $_POST['lead-source'],
            $_POST['lead-for'],
            $_POST['lead-priority'],
            $fy_code // Use the fy_code retrieved
        );

        // Execute the followup statement
        if ($followup_stmt->execute()) {
            // If both insertions are successful, redirect to contact_display.php
            echo "<script>alert('Record added successfully!'); window.location.href = 'contact_display.php';</script>";
            exit();  // Ensure no further code is executed
        } else {
            // Display error if insertion fails
            echo "Error inserting into followup: " . $followup_stmt->error;
        }

        // Close the followup statement
        $followup_stmt->close();
    } else {
        // Display error if insertion into contact fails
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $connection->close(); // Use $connection for closing
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Form</title>
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
    .cancel-btn{
      text-decoration: none;
    }
    .form-container {
      width: 80%;
      max-width: 1200px; /* To keep the form size manageable */
      background: #fff;
      border-radius: 10px;
      padding: 20px 30px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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
        top: 3px;
        right: 100px;
        font-size: 18px;
        color: #2c3e50;
        cursor: pointer;
        transition: color 0.3s;
    }

  </style>
</head>
<body>
  <div class="form-container">
    <a style="text-decoration:None;"href="contact_display.php" class="close-btn">&times;</a>

    <h2>Add Contact</h2>
    <form method="POST" action="">
      <!-- Row 1 -->
      <div class="form-group">
    <!-- Lead Source Input -->
    <?php
    // Get the leadSource parameter from the URL
    $leadSource = isset($_GET['leadSource']) ? $_GET['leadSource'] : '';

    // Fetch Lead Source names from the lead_source table
    $leadSourceOptions = [];
    $result = $connection->query("SELECT name FROM lead_sourc");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $leadSourceOptions[] = $row['name'];
        }
    }
    ?>
    <div style="position: relative;">
        <label for="lead-source">Lead Source *</label>
        <select id="lead-source" name="lead-source" placeholder="Lead Sources">
            <option value="">Select Lead Source</option>
            <?php foreach ($leadSourceOptions as $option) : ?>
                <option value="<?php echo htmlspecialchars($option); ?>"
                    <?php echo $leadSource === $option ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($option); ?>
                </option>
            <?php endforeach; ?>
        </select>

    </div>

    <!-- Lead For Input -->
    <?php
    // Get the leadFor parameter from the URL
    $leadFor = isset($_GET['leadFor']) ? $_GET['leadFor'] : '';

    // Fetch Lead For names from the lead_for table
    $leadForOptions = [];
    $result = $connection->query("SELECT name FROM lead_for");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $leadForOptions[] = $row['name'];
        }
    }
    ?>
    <div style="position: relative;">
        <label for="lead-for">Lead For *</label>
        <select id="lead-for" name="lead-for">
            <option value="">Select Lead For</option>
            <?php foreach ($leadForOptions as $option) : ?>
                <option value="<?php echo htmlspecialchars($option); ?>"
                    <?php echo $leadFor === $option ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($option); ?>
                </option>
            <?php endforeach; ?>
        </select>

    </div>

        <!-- Lead Priority Dropdown -->
        <div>
          <label for="lead-priority">Lead Priority *</label>
          <select id="lead-priority" name="lead-priority">  <!-- added name attribute for POST -->
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </div>
      </div>

      <!-- Row 2 -->
      <div class="form-group">
        <div>
          <label for="contact-person">Contact Person *</label>
          <input type="text" id="contact-person" name="contact-person" placeholder="Enter Contact Person">  <!-- added name attribute -->
        </div>
        <div>
          <label for="company-name">Company Name</label>
          <input type="text" id="company-name" name="company-name" placeholder="Enter Company Name">  <!-- added name attribute -->
        </div>
      </div>

      <!-- Row 3 -->
      <div class="form-group">
        <div>
          <label for="mobile-no">Mobile No *</label>
          <input type="text" id="mobile-no" name="mobile-no" placeholder="Enter Mobile No">  <!-- added name attribute -->
        </div>
        <div>
          <label for="whatsapp-no">WhatsApp No</label>
          <input type="text" id="whatsapp-no" name="whatsapp-no" placeholder="Enter WhatsApp No">  <!-- added name attribute -->
        </div>
        <div>
          <label for="email">Email ID</label>
          <input type="email" id="email" name="email" placeholder="Enter Email ID">  <!-- added name attribute -->
        </div>
      </div>

      <!-- Row 4 -->
      <div class="form-group full">
        <label for="address">Address</label>
        <input type="text" id="address" name="address" placeholder="Enter Address">  <!-- added name attribute -->
      </div>

      <!-- Row 5 -->
      <div class="form-group">
        <div>
    <label for="country">Country</label>
    <select id="country-select" name="country" onchange="toggleCustomCountry(this.value)">
        <option value="India">India</option>
        <option value="USA">USA</option>
        <option value="UK">UK</option>
        <option value="custom">Other (please specify)</option>
    </select>
    <input type="text" id="custom-country" name="custom-country" placeholder="Enter country" style="display:none;" />
</div>

        <div>
      <label for="state">State</label>
      <select id="state" name="state" required>
          <option value="">Select State</option>
          <option value="Andhra Pradesh">Andhra Pradesh</option>
          <option value="Arunachal Pradesh">Arunachal Pradesh</option>
          <option value="Assam">Assam</option>
          <option value="Bihar">Bihar</option>
          <option value="Chhattisgarh">Chhattisgarh</option>
          <option value="Goa">Goa</option>
          <option value="Gujarat">Gujarat</option>
          <option value="Haryana">Haryana</option>
          <option value="Himachal Pradesh">Himachal Pradesh</option>
          <option value="Jharkhand">Jharkhand</option>
          <option value="Karnataka">Karnataka</option>
          <option value="Kashmir">Kashmir</option>
          <option value="Kerala">Kerala</option>
          <option value="Madhya Pradesh">Madhya Pradesh</option>
          <option value="Maharashtra">Maharashtra</option>
          <option value="Manipur">Manipur</option>
          <option value="Meghalaya">Meghalaya</option>
          <option value="Mizoram">Mizoram</option>
          <option value="Nagaland">Nagaland</option>
          <option value="Odisha">Odisha</option>
          <option value="Punjab">Punjab</option>
          <option value="Rajasthan">Rajasthan</option>
          <option value="Sikkim">Sikkim</option>
          <option value="Tamil Nadu">Tamil Nadu</option>
          <option value="Telangana">Telangana</option>
          <option value="Tripura">Tripura</option>
          <option value="Uttar Pradesh">Uttar Pradesh</option>
          <option value="Uttarakhand">Uttarakhand</option>
          <option value="West Bengal">West Bengal</option>
          <option value="Andaman and Nicobar Islands">Andaman and Nicobar Islands</option>
          <option value="Chandigarh">Chandigarh</option>
          <option value="Dadra and Nagar Haveli and Daman and Diu">Dadra and Nagar Haveli and Daman and Diu</option>
          <option value="Lakshadweep">Lakshadweep</option>
          <option value="Delhi">Delhi</option>
          <option value="Puducherry">Puducherry</option>
      </select>
  </div>
        <div>
          <label for="city">City</label>
          <input type="text" id="city" name="city" placeholder="Enter City">  <!-- added name attribute -->
        </div>
        <div>
          <label for="pincode">Pincode</label>
          <input type="text" id="pincode" name="pincode" placeholder="Enter Pincode">  <!-- added name attribute -->
        </div>
      </div>

      <!-- Row 6 -->
      <div class="form-group">
        <div>
          <label for="reference-person-name">Reference Person Name</label>
          <input type="text" id="reference-person-name" name="reference-person-name" placeholder="Enter Reference Person Name">  <!-- added name attribute -->
        </div>
        <div>
          <label for="reference-person-mobile">Reference Person Mobile No.</label>
          <input type="text" id="reference-person-mobile" name="reference-person-mobile" placeholder="Enter Reference Person Mobile No.">  <!-- added name attribute -->
        </div>
      </div>

      <!-- Row 7 -->
      <div class="form-group">
        <div>
          <label for="estimate-amount">Estimate Amount</label>
          <input type="text" id="estimate-amount" name="estimate-amount" placeholder="Enter Estimate Amount">  <!-- added name attribute -->
        </div>
        <div>
          <label for="next-follow-up-date">Lead Generation Date</label>
          <input type="date" id="next-follow-up-date" name="next-follow-up-date">  <!-- added name attribute -->
        </div>
      </div>

      <!-- Row 8 -->
      <div class="form-group" >
          <?php
          // Fetch employee names from login_db
          $employeeNames = [];
          $query = "SELECT name FROM login_db";
          $result = $connection->query($query);

          if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                  $employeeNames[] = $row['name'];
              }
          }
          ?>

    <div>
        <label for="employee-name">Employee</label>
        <select id="employee-name" name="employee" class="input-field">
            <option value="">Select Employee</option>
            <?php foreach ($employeeNames as $name): ?>
                <option value="<?php echo htmlspecialchars($name); ?>">
                    <?php echo htmlspecialchars($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
      <label for="gstno">GST Number</label>
      <input id="gstno" name="gstno" type="text" placeholder="Enter GST Number" class="input-field">
  </div>

   </div> <!--Row 8 ends -->

  <!-- Row 9 (Actions) -->
  <div class="form-group">

  <div style="grid-column: span 2;">
    <label for="remarks">Remarks</label>
    <textarea id="remarks" name="remarks" placeholder="Enter Remarks" rows="2" class="input-field"></textarea>
</div>
  </div>
  <!-- Row 10 (Actions) -->
<div class="form-actions">
  <button type="submit" name="register" class="button">Submit</button>
  <a href="contact_display.php" class="cancel-btn">Cancel</a>
</div>

    </form>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const dateInput = document.getElementById('next-follow-up-date');
      const today = new Date().toISOString().split('T')[0]; // Get today's date in 'YYYY-MM-DD' format
      dateInput.value = today; // Set the default value to today's date
    });
    // Country
      function toggleCustomCountry(value) {
          const customCountryInput = document.getElementById('custom-country');

          if (value === 'custom') {
              customCountryInput.style.display = 'block'; // Show the input field
              customCountryInput.required = true; // Make it required if selecting custom
          } else {
              customCountryInput.style.display = 'none'; // Hide the input field
              customCountryInput.required = false; // Not required if not custom
          }
      }
  </script>
</body>
</html>
