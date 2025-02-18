<?php
include('connection.php');

// Check if contact_id is provided in the URL
if (!isset($_GET['contact_id'])) {
    // Redirect to the same page with a default contact_id, e.g., contact_id=1
    header("Location: followup.php?contact_id=1");
    exit();
}

$contact_id = $_GET['contact_id']; // Get contact_id from the URL

// Prepare the SQL query to fetch necessary fields from the contact table
$sql_contact = "SELECT company_name, contact_person, mobile_no, whatsapp_no, email_id, lead_source, lead_for, lead_priority FROM contact WHERE id = ?";
$stmt_contact = $connection->prepare($sql_contact);

if (!$stmt_contact) {
    die("Preparation failed: " . $connection->error);
}

$stmt_contact->bind_param("i", $contact_id); // Bind contact_id as integer
$stmt_contact->execute();
$result_contact = $stmt_contact->get_result();

// Fetch the data from the contact table
if ($result_contact->num_rows > 0) {
    $contact_data = $result_contact->fetch_assoc();
} else {
    die("No contact found with the given ID.");
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if contact_id is provided in the URL
if (isset($_GET['contact_id'])) {
    $contact_id = $_GET['contact_id']; // Get contact_id from the URL

    // Prepare the SQL query to fetch necessary fields from the contact table
    $sql_contact = "SELECT company_name, contact_person, mobile_no, whatsapp_no, email_id, lead_source, lead_for, lead_priority FROM contact WHERE id = ?";
    $stmt_contact = $connection->prepare($sql_contact);

    if (!$stmt_contact) {
        die("Preparation failed: " . $connection->error);
    }

    $stmt_contact->bind_param("i", $contact_id); // Bind contact_id as integer
    $stmt_contact->execute();
    $result_contact = $stmt_contact->get_result();

    // Fetch the data from the contact table
    if ($result_contact->num_rows > 0) {
        $contact_data = $result_contact->fetch_assoc();
    } else {
        die("No contact found with the given ID.");
    }

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get the posted values
        $status = $_POST['status'];
        $lead_status = $_POST['lead_status'];
        $followup_date_nxt = $_POST['followup_date_nxt'];
        $followup_time_nxt = $_POST['followup_time_nxt'];
        $lead_followup = $_POST['lead_followup'];
        $estimate_amount = $_POST['estimate_amount'];
        $closed_amount = $_POST['closed_amount'];
        $reporting_details = $_POST['reporting_details'];

        // Prepare the SQL query to insert the followup data
        $sql_insert = "INSERT INTO followup (contact_id, lead_status, status, followup_date_nxt, followup_time_nxt, lead_followup, estimate_amount, closed_amount, reporting_details)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $connection->prepare($sql_insert);

        if (!$stmt_insert) {
            die("Preparation failed: " . $connection->error);
        }

        // Bind parameters
        $stmt_insert->bind_param("issssisss", $contact_id, $lead_status, $status, $followup_date_nxt, $followup_time_nxt, $lead_followup, $estimate_amount, $closed_amount, $reporting_details);

        if ($stmt_insert->execute()) {
            // Get the last inserted followup ID
            $followup_id = $stmt_insert->insert_id;

            // Insert into followup_history
            $sql_history_insert = "INSERT INTO followup_history (followup_id, contact_id, followup_date_nxt, followup_time_nxt, status, lead_status, lead_sub_status, estimate_amount, closed_amount, lead_followup, reporting_details)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_history_insert = $connection->prepare($sql_history_insert);

            if (!$stmt_history_insert) {
                die("Preparation failed: " . $connection->error);
            }

            // Bind parameters for history
            $stmt_history_insert->bind_param("iisssssssss", $followup_id, $contact_id, $followup_date_nxt, $followup_time_nxt, $status, $lead_status, $lead_followup, $estimate_amount, $closed_amount, $reporting_details);

            if ($stmt_history_insert->execute()) {
                echo "<script>alert('Record Added Successfully');</script>";
            } else {
                echo "Error adding to followup_history: " . $stmt_history_insert->error;
            }
        } else {
            echo "Error adding follow-up entry: " . $stmt_insert->error;
        }
    }

    // Prepare the SQL query to fetch all necessary fields from the followup table
    $sql_followup = "SELECT lead_status, followup_date_nxt, followup_time_nxt, lead_followup, estimate_amount, closed_amount, reporting_details FROM followup WHERE contact_id = ?";
    $stmt_followup = $connection->prepare($sql_followup);

    if (!$stmt_followup) {
        die("Preparation failed: " . $connection->error);
    }

    $stmt_followup->bind_param("i", $contact_id); // Bind contact_id as integer
    $stmt_followup->execute();
    $result_followup = $stmt_followup->get_result();

    // Fetch the lead_status and other details if they exist
    if ($result_followup->num_rows > 0) {
        $followup_data = $result_followup->fetch_assoc();
    } else {
        // Default values if no followup data found
        $followup_data = [
            'lead_status' => "N/A",
            'followup_date_nxt' => '',
            'followup_time_nxt' => '',
            'lead_followup' => '',
            'estimate_amount' => '',
            'closed_amount' => '',
            'reporting_details' => ''
        ];
    }
} else {
    die("Contact ID not provided.");
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Management Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #2c3e50;
        }

        .container {
            width: 100%;
            max-width: 900px;
            margin: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            background-color: white;
        }

        .header {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }



        .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .row label {
            font-weight: bold;
            margin-right: 10px;
            display: inline-block;
            width: 40%;
        }

        .row div {
            width: 48%;
        }

        .input-field, select, textarea {
            width: 100%;
            max-width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }

        textarea {
            height: 100px;
            resize: none;
        }

        .button-container {
            text-align: center;
            margin-top: 20px;
            color: #2c3e50;
        }

        .btn {
            background-color: #2c3e50;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #2c3e60;
        }

        .row-inline {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .row-inline label {
            margin-right: 10px;
            font-weight: bold;
        }

        .row-inline textarea {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="container">
      <div class="header">
    <div>
        <span>Lead Source: <?php echo htmlspecialchars($contact_data['lead_source']); ?></span> |
        <span>Lead For: <?php echo htmlspecialchars($contact_data['lead_for']); ?></span>
    </div>
    <div class="lead-priority" style="color: green;">
        Lead Priority: <?php echo htmlspecialchars($contact_data['lead_priority']); ?>
    </div>
</div>

<div class="row">
    <div>
        <label>Company Name:</label>
        <span><?php echo htmlspecialchars($contact_data['company_name']); ?></span>
    </div>
    <div>
        <label>Person Name:</label>
        <span><?php echo htmlspecialchars($contact_data['contact_person']); ?></span>
    </div>
</div>
<div class="row">
    <div>
        <label>Mobile No:</label>
        <span><?php echo htmlspecialchars($contact_data['mobile_no']); ?></span>
    </div>
    <div>
        <label>Whatsapp No:</label>
        <span><?php echo htmlspecialchars($contact_data['whatsapp_no']); ?></span>
    </div>
</div>
<div class="row">
    <div>
        <label>Lead Status:</label>
        <span><?php echo htmlspecialchars($followup_data['lead_status']); ?></span>
    </div>
    <div>
        <label>Email Id:</label>
        <span><?php echo htmlspecialchars($contact_data['email_id']); ?></span>
    </div>
</div>
<form method="POST" action="">
    <div class="row">
      <div>
    <label>Status:</label>
    <select class="input-field" name="status">
        <option value="" disabled>Select Status</option>
        <?php
        // Define the status options
        $status_options = [
            'Received',
            'Switch Off',
            'Out of Coverage',
            'Wrong No',
            'Not Received',
            'Wait',
            'Whatsapp Sent',
            'Email Sent',
            'Whatsapp and Email Sent'
        ];

        // Loop through the status options and create the dropdown
        foreach ($status_options as $option): ?>
            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo (isset($followup_data['status']) && $followup_data['status'] === $option) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($option); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


        <div>
    <label>Lead Status:</label>
    <select class="input-field" name="lead_status">
        <option value="" disabled>Select Lead Status</option>
        <?php
        // Define the lead status options
        $lead_status_options = ['Open', 'Close', 'Cancel', 'Dormant'];

        // Loop through the lead status options and create the dropdown
        foreach ($lead_status_options as $option): ?>
            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($followup_data['lead_status'] === $option) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($option); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

    </div>
    <div class="row">
        <div>
            <label>Next Follow-ups Date:</label>
            <input type="date" class="input-field" name="followup_date_nxt" value="<?php echo htmlspecialchars($followup_data['followup_date_nxt']); ?>">
        </div>
        <div>
            <label>Next Follow-ups Time:</label>
            <input type="time" class="input-field" name="followup_time_nxt" value="<?php echo htmlspecialchars($followup_data['followup_time_nxt']); ?>">
        </div>
    </div>
    <div class="row">
      <div>
      <label>Lead Activity Status:</label>
      <select class="input-field" name="lead_followup">
          <option value="" disabled>Select Lead Activity Status</option>
          <?php
          // Define the known options for lead_followup
          $known_lead_followup_options = ['Fresh', 'Repeat'];

          // Generate dropdown options
          foreach ($known_lead_followup_options as $option): ?>
              <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($followup_data['lead_followup'] === $option) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($option); ?>
              </option>
          <?php endforeach; ?>
      </select>
  </div>


        <div>
            <label>Whatsapp Template:</label>
            <select class="input-field" name="whatsapp_template">
                <option>--- Select Whatsapp Template ---</option>
                <!-- Add Whatsapp template options if needed -->
            </select>
        </div>
    </div>
    <div class="row">
    <div>
        <label>Estimate Amount:</label>
        <input type="number" class="input-field" name="estimate_amount" placeholder="Estimate Amount" value="<?php echo htmlspecialchars($followup_data['estimate_amount'] ?? ''); ?>">
    </div>
    <div>
        <label>Closed Amount:</label>
        <input type="number" class="input-field" name="closed_amount" placeholder="Closed Amount" value="<?php echo htmlspecialchars($followup_data['closed_amount'] ?? ''); ?>">
    </div>
</div>

    <div class="row-inline">
    <label for="reportingDetails">Reporting Details:</label>
    <textarea id="reportingDetails" class="textarea-field" name="reporting_details"><?php echo htmlspecialchars($followup_data['reporting_details'] ?? ''); ?></textarea>
</div>

    <div class="button-container">
        <button class="btn" type="submit">Add Follow Up</button>
    </div>
</form>


</body>
</html>
