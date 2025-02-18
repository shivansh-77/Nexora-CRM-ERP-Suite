<?php
include('connection.php');
session_start(); // Start the session

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the contact_id from POST data
    $contact_id = $_POST['contact_id'];

    // Save values to the session if needed
    $_SESSION['lead_source'] = $_POST['lead-source'];
    $_SESSION['lead_for'] = $_POST['lead-for'];
    $_SESSION['lead_priority'] = $_POST['lead-priority'];

    // Prepare the SQL query for inserting into the followup table
    $followup_sql = "INSERT INTO followup (
        contact_id, lead_source, lead_for, lead_priority
    ) VALUES (?, ?, ?, ?)";

    // Prepare the followup statement
    $followup_stmt = $connection->prepare($followup_sql);
    if (!$followup_stmt) {
        die("Preparation failed: " . $connection->error);
    }

    // Bind the parameters to the prepared statement
    $followup_stmt->bind_param(
        'isss', // Adjust types accordingly (i for int, s for string)
        $contact_id,
        $_POST['lead-source'],
        $_POST['lead-for'],
        $_POST['lead-priority']
    );

    // Execute the followup statement
    if ($followup_stmt->execute()) {
        // If insertion is successful, redirect to contact_display.php
        echo "<script>alert('Follow-up record added successfully!'); window.location.href = 'contact_display.php';</script>";
        exit();  // Ensure no further code is executed
    } else {
        // Display error if insertion fails
        echo "Error inserting into followup: " . $followup_stmt->error;
    }

    // Close the followup statement and connection
    $followup_stmt->close();
    $connection->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Contact</title>
  <style>
    /* Same styles as your contact form */
  </style>
</head>
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

</style>
</head>
<body>
<div class="form-container">
  <h2>Add Contact</h2>
  <form method="POST" action="">
      <!-- Row 1 -->
      <div class="form-group">
    <!-- Lead Source Input -->
    <?php
    // Fetch Lead Source names from the lead_source table
    $leadSourceOptions = [];
    $conn = new mysqli('localhost', 'root', '', 'lead_management'); // Update with your DB credentials
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $result = $conn->query("SELECT name FROM lead_sourc");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $leadSourceOptions[] = $row['name'];
        }
    }
    ?>
    <div style="position: relative;">
        <label for="lead-source">Lead Source *</label>
        <select id="lead-source" name="lead-source">
            <option value="">Select Lead Source</option>
            <?php foreach ($leadSourceOptions as $option) : ?>
                <option value="<?php echo htmlspecialchars($option); ?>"
                    <?php echo $contact['lead_source'] === $option ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($option); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button
            type="button"
            id="add-lead-source"
            style="position: absolute; right: 0px; top: 73%; transform: translateY(-50%); background-color: #2c3e50; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer;"
            onclick="window.location.href='lead_source_display.php'">
            Add
        </button>
    </div>

    <!-- Lead For Input -->
    <?php
    // Fetch Lead For names from the lead_for table
    $leadForOptions = [];
    $result = $conn->query("SELECT name FROM lead_for");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $leadForOptions[] = $row['name'];
        }
    }
    $conn->close();
    ?>
    <div style="position: relative;">
        <label for="lead-for">Lead For *</label>
        <select id="lead-for" name="lead-for">
            <option value="">Select Lead For</option>
            <?php foreach ($leadForOptions as $option) : ?>
                <option value="<?php echo htmlspecialchars($option); ?>"
                    <?php echo $contact['lead_for'] === $option ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($option); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button
            type="button"
            id="add-lead-for"
            style="position: absolute; right: 0px; top: 73%; transform: translateY(-50%); background-color: #2c3e50; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer;"
            onclick="window.location.href='lead_for_display.php'">
            Add
        </button>
    </div>


        <div>
          <label for="lead-priority">Lead Priority *</label>
          <select id="lead-priority" name="lead-priority">
            <option value="high" <?php echo ($contact['lead_priority'] == 'high') ? 'selected' : ''; ?>>High</option>
            <option value="medium" <?php echo ($contact['lead_priority'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
            <option value="low" <?php echo ($contact['lead_priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
          </select>
        </div>
      </div>

      <!-- Row 2 -->
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

      <!-- Row 3 -->
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

      <!-- Row 4 -->
      <div class="form-group full">
        <label for="address">Address</label>
        <input type="text" id="address" name="address" placeholder="Enter Address" value="<?php echo htmlspecialchars($contact['address']); ?>">
      </div>

      <!-- Row 5 -->
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
          <input type="text" id="state" name="state" placeholder="Enter State" value="<?php echo htmlspecialchars($contact['state']); ?>">
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

      <!-- Row 6 -->
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

      <!-- Row 7 -->
      <div class="form-group">
        <div>
          <label for="estimate-amount">Estimate Amount</label>
          <input type="text" id="estimate-amount" name="estimate-amount" placeholder="Enter Estimate Amount" value="<?php echo htmlspecialchars($contact['estimate_amnt']); ?>">
        </div>
        <div>
          <label for="next-follow-up-date">Next Follow-Up Date</label>
          <input type="date" id="next-follow-up-date" name="next-follow-up-date" value="<?php echo htmlspecialchars($contact['followupdate']); ?>">
        </div>
      </div>

      <!-- Row 8 -->
      <div class="form-group">
        <?php
        // Fetch employee names from login_db
        $conn = new mysqli('localhost', 'root', '', 'lead_management');

        // Check for connection error
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Fetch names from login_db
        $employeeNames = [];
        $query = "SELECT name FROM login_db";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $employeeNames[] = $row['name'];
            }
        }
        $conn->close();
        ?>
        <div>
            <label for="employee-name">Employee</label>
            <select id="employee-name" name="employee" class="input-field">
                <option value="">Select Employee</option>
                <?php foreach ($employeeNames as $name): ?>
                    <option value="<?php echo htmlspecialchars($name); ?>" <?php echo ($name == $contact['employee']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="remarks">Remarks</label>
            <textarea id="remarks" name="remarks" placeholder="Enter Remarks" rows="2.5" class="input-field"><?php echo htmlspecialchars($contact['remarks']); ?></textarea>
        </div>
      </div>

      <!-- Row 9 (Actions) -->
      <div class="form-actions">
          <button type="submit" name="update" class="button">Update</button>
          <a href="contact_display.php" class="cancel-btn">Cancel</a>
      </div>
  </form>

</div>
</body>
</html>
