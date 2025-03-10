<?php
include('connection.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if ID is provided in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id']; // Get ID from the URL

    // Prepare the SQL query to fetch the row with the given ID
    $sql = "SELECT contact_id, lead_status, status, followup_date_nxt, followup_time_nxt, lead_followup, estimate_amount, closed_amount, reporting_details, lead_source, lead_for, lead_priority FROM followup WHERE id = ?";
    $stmt = $connection->prepare($sql);

    if (!$stmt) {
        die("Preparation failed: " . $connection->error);
    }

    $stmt->bind_param("i", $id); // Bind ID as integer
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the data from the followup table
    if ($result->num_rows > 0) {
        $followup_data = $result->fetch_assoc();
    } else {
        die("No follow-up found with the given ID.");
    }

    // Fetch contact details using contact_id
    $contact_id = $followup_data['contact_id'];
    $sql_contact = "SELECT company_name, contact_person, mobile_no, whatsapp_no, email_id FROM contact WHERE id = ?";
    $stmt_contact = $connection->prepare($sql_contact);

    if (!$stmt_contact) {
        die("Preparation failed: " . $connection->error);
    }

    $stmt_contact->bind_param("i", $contact_id);
    $stmt_contact->execute();
    $result_contact = $stmt_contact->get_result();

    if ($result_contact->num_rows > 0) {
        $contact_data = $result_contact->fetch_assoc();
    } else {
        die("No contact found with the given contact ID.");
    }

    // Handle form submission for updating
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get the posted values
        $status = $_POST['status'] ?? '';
        $lead_status = $_POST['lead_status'] ?? '';
        $followup_date_nxt = $_POST['followup_date_nxt'] ?? '';
        $followup_time_nxt = $_POST['followup_time_nxt'] ?? '';
        $lead_followup = $_POST['lead_followup'] ?? '';
        $estimate_amount = $_POST['estimate_amount'] ?? '';
        $closed_amount = $_POST['closed_amount'] ?? '';
        $reporting_details = $_POST['reporting_details'] ?? '';

        // Prepare the SQL query to update the followup data
        $sql_update = "UPDATE followup SET lead_status = ?, status = ?, followup_date_nxt = ?, followup_time_nxt = ?, lead_followup = ?, estimate_amount = ?, closed_amount = ?, reporting_details = ? WHERE id = ?";
        $stmt_update = $connection->prepare($sql_update);

        if (!$stmt_update) {
            die("Preparation failed: " . $connection->error);
        }

        // Bind parameters
        $stmt_update->bind_param("ssssssssi", $lead_status, $status, $followup_date_nxt, $followup_time_nxt, $lead_followup, $estimate_amount, $closed_amount, $reporting_details, $id);

        if ($stmt_update->execute()) {
            // Insert the updated data into followup_history
            $sql_history_insert = "INSERT INTO followup_history (followup_id, contact_id, followup_date_nxt, followup_time_nxt, status, lead_status, lead_sub_status, estimate_amount, closed_amount, lead_followup, reporting_details, lead_source, lead_for, lead_priority)
                                   VALUES (?, ?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, ?)";
            $stmt_history_insert = $connection->prepare($sql_history_insert);

            if (!$stmt_history_insert) {
                die("Preparation failed: " . $connection->error);
            }

            // Bind parameters for history including the new fields
            $stmt_history_insert->bind_param("iisssssssssss", $id, $contact_id, $followup_date_nxt, $followup_time_nxt, $status, $lead_status, $estimate_amount, $closed_amount, $lead_followup, $reporting_details, $followup_data['lead_source'], $followup_data['lead_for'], $followup_data['lead_priority']);

            if ($stmt_history_insert->execute()) {
                echo "<script>alert('Record Updated and History Added Successfully'); window.location.href='update_closed_followup.php';</script>";
            } else {
                echo "Error adding to followup_history: " . $stmt_history_insert->error;
            }
        } else {
            echo "Error updating follow-up entry: " . $stmt_update->error;
        }
    }
} else {
    die("ID not provided.");
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Repeat Follow-Up</title>
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
            position: relative; /* For positioning the cross button */
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

        .cross-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #2c3e50;
        }

        .cross-button:hover {
            color: #e74c3c; /* Change color on hover */
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
            font-size: 14px;
            margin: 0 5px; /* Add margin between buttons */
            text-decoration: none; /* Remove underline from Cancel link */
            display: inline-block; /* Ensure buttons are aligned */
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
        .close-btn {
            position: absolute;
            top: 3px;
            right: 6px;
            font-size: 18px;
            color: #2c3e50;
            cursor: pointer;
            transition: color 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <a style="text-decoration:None;"href="repeat_followups.php" class="close-btn">&times;</a>
      <div class="header">
  <div>
      <span>Lead Source: <?php echo htmlspecialchars($followup_data['lead_source']); ?></span> |
      <span>Lead For: <?php echo htmlspecialchars($followup_data['lead_for']); ?></span>
  </div>
  <div class="lead-priority" style="color: green;">
      Lead Priority: <?php echo htmlspecialchars($followup_data['lead_priority']); ?>
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
                        foreach ($status_options as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($followup_data['status'] === $option) ? 'selected' : ''; ?>>
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
                        $lead_status_options = ['Open', 'Close', 'Cancel', 'Dormant'];
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
                        $known_lead_followup_options = ['Fresh', 'Repeat'];
                        foreach ($known_lead_followup_options as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($followup_data['lead_followup'] === $option) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Estimate Amount:</label>
                    <input type="number" class="input-field" name="estimate_amount" placeholder="Estimate Amount" value="<?php echo htmlspecialchars($followup_data['estimate_amount']); ?>">
                </div>
            </div>
            <div class="row">

                <div>
                    <label>Closed Amount:</label>
                    <input type="number" class="input-field" name="closed_amount" placeholder="Closed Amount" value="<?php echo htmlspecialchars($followup_data['closed_amount']); ?>">
                </div>
            </div>
            <textarea id="reportingDetails" class="textarea-field" name="reporting_details" placeholder="REPORTING DETAILS"><?php echo htmlspecialchars($followup_data['reporting_details'] ?? ''); ?></textarea>

            <div class="button-container">
                <button class="btn" type="submit">Update</button>
                <a href="closed_followup.php" class="btn" role="button" style="text-decoration: none;">Cancel</a>

            </div>
        </form>
    </div>
</body>
</html>
