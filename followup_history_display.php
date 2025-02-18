<?php
include('connection.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$id = isset($_GET['id']) ? $_GET['id'] : null;
$selected_followup_id = isset($_POST['followup_id']) ? $_POST['followup_id'] : null;

// Date filter variables
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : null;
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : null;

// Check if ID is provided in the URL
if ($id) {
    // Prepare the SQL query to fetch the contact details
    $sql_contact = "SELECT * FROM contact WHERE id = ?";
    $stmt_contact = $connection->prepare($sql_contact);

    if (!$stmt_contact) {
        die("Preparation failed: " . $connection->error);
    }

    $stmt_contact->bind_param("i", $id);
    $stmt_contact->execute();
    $result_contact = $stmt_contact->get_result();

    if ($result_contact->num_rows > 0) {
        $contact_data = $result_contact->fetch_assoc();
        $contact_id = $contact_data['id'];

        // Fetch unique followup_id for the contact
        $sql_followup_ids = "SELECT DISTINCT followup_id FROM followup_history WHERE contact_id = ?";
        $stmt_followup_ids = $connection->prepare($sql_followup_ids);
        $stmt_followup_ids->bind_param("i", $contact_id);
        $stmt_followup_ids->execute();
        $result_followup_ids = $stmt_followup_ids->get_result();

        // Fetch lead_source, lead_for, and lead_priority based on the selected followup_id or contact_id
        if ($selected_followup_id) {
            // Fetch based on followup_id
            $sql_lead_details = "SELECT lead_source, lead_for, lead_priority
                                 FROM followup
                                 WHERE contact_id = ? AND id = ?";
            $stmt_lead_details = $connection->prepare($sql_lead_details);
            $stmt_lead_details->bind_param("ii", $contact_id, $selected_followup_id);
        } else {
            // Fetch based on contact_id
            $sql_lead_details = "SELECT lead_source, lead_for, lead_priority
                                 FROM followup
                                 WHERE contact_id = ?";
            $stmt_lead_details = $connection->prepare($sql_lead_details);
            $stmt_lead_details->bind_param("i", $contact_id);
        }

        $stmt_lead_details->execute();
        $result_lead_details = $stmt_lead_details->get_result();

        if ($result_lead_details->num_rows > 0) {
            $lead_data = $result_lead_details->fetch_assoc();
            $lead_source = $lead_data['lead_source'];
            $lead_for = $lead_data['lead_for'];
            $lead_priority = $lead_data['lead_priority'];
        } else {
            $lead_source = "N/A";
            $lead_for = "N/A";
            $lead_priority = "N/A";
        }

        // Fetch follow-up history using the contact_id with optional filtering
$sql_history = "SELECT * FROM followup_history WHERE contact_id = ?";
$params = [$contact_id];

if ($selected_followup_id) {
    $sql_history .= " AND followup_id = ?";
    $params[] = $selected_followup_id;
}

// Date filter logic
if ($startDate && $endDate) {
    $sql_history .= " AND followup_date_nxt BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

$stmt_history = $connection->prepare($sql_history);
$types = str_repeat("s", count($params)); // Update for string parameters if using MySQL DATE type
$stmt_history->bind_param($types, ...$params);
$stmt_history->execute();
$result_history = $stmt_history->get_result();


        // Fetch follow-up history using the contact_id with optional filtering
        $sql_history = "SELECT * FROM followup_history WHERE contact_id = ?";
        $params = [$contact_id];

        if ($selected_followup_id) {
            $sql_history .= " AND followup_id = ?";
            $params[] = $selected_followup_id;
        }

        // Date filter logic
        if ($startDate && $endDate) {
            $sql_history .= " AND followup_date_nxt BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $stmt_history = $connection->prepare($sql_history);
        $stmt_history->bind_param(str_repeat("i", count($params)), ...$params);
        $stmt_history->execute();
        $result_history = $stmt_history->get_result();
    } else {
        die("No contact found with the given ID.");
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
    <title>Contact Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #2c3e50;
        }

        .container {
            width: 100%;
            max-width: 1200px;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
        label, span {
    font-weight: bold;
}
form {
    max-width: 400px; /* Adjust the width as needed */
    margin: auto;
    background: #fff;
    padding: 4px;
    border-radius: 8px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);

}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

select {
    width: auto; /* Set to auto for smaller width */
    padding: 5px; /* Reduced padding for a smaller dropdown */
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px; /* Smaller font size */
}


    </style>
</head>
<body>
    <div class="container">
      <!-- Filter Form -->

      <form method="POST" action="" style="position: relative; display: flex; flex-direction: column; align-items: center; margin-bottom: 30px; margin-right: 990px; padding: 10px; border: 1px solid #ccc; border-radius: 8px; background-color: #fff; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
      <!-- Follow-Up ID Filter -->
      <label for="followup_id" style="margin-bottom: 5px; font-weight: bold;">Choose Follow-Up ID:</label>
      <select name="followup_id" id="followup_id" style="width: 150px; margin-bottom: 10px; padding: 5px; font-size: 14px; border: 1px solid #ccc; border-radius: 4px;">
          <option value="">All Follow-Ups</option>
          <?php while ($row = $result_followup_ids->fetch_assoc()): ?>
              <option value="<?php echo htmlspecialchars($row['followup_id']); ?>" <?php echo ($selected_followup_id == $row['followup_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($row['followup_id']); ?>
              </option>
          <?php endwhile; ?>
      </select>

      <!-- Date Range Filters -->
      <label for="start_date" style="margin-bottom: 5px; font-weight: bold;">Start Date:</label>
      <input type="date" name="start_date" id="start_date" style="width: 150px; margin-bottom: 10px; padding: 5px; font-size: 14px; border: 1px solid #ccc; border-radius: 4px;" value="<?php echo htmlspecialchars($startDate); ?>">

      <label for="end_date" style="margin-bottom: 5px; font-weight: bold;">End Date:</label>
      <input type="date" name="end_date" id="end_date" style="width: 150px; margin-bottom: 10px; padding: 5px; font-size: 14px; border: 1px solid #ccc; border-radius: 4px;" value="<?php echo htmlspecialchars($endDate); ?>">

      <input type="submit" value="Filter" style="padding: 5px 12px; cursor: pointer; font-size: 14px; border: none; border-radius: 4px; background-color: #2c3e50; color: white; transition: background-color 0.3s;">
  </form>


      <!-- Cross Button -->
      <a href="contact_display.php" style="position: absolute; top: 25px; right: 25px; text-decoration: none; font-size: 16px; color: #555; font-weight: bold; cursor: pointer;">&#x2716;</a>




      <!--
      <div class="header">
          <div>
              <span style="color:
                  <?php
                      if ($lead_priority === 'High') {
                          echo 'red';
                      } elseif ($lead_priority === 'Medium') {
                          echo 'orange';
                      } else {
                          echo 'green';
                      }
                  ?>;">
                  Lead Source: <?php echo htmlspecialchars($lead_source); ?>
              </span> |
              <span style="color:
                  <?php
                      if ($lead_priority === 'High') {
                          echo 'red';
                      } elseif ($lead_priority === 'Medium') {
                          echo 'orange';
                      } else {
                          echo 'green';
                      }
                  ?>;">
                  Lead For: <?php echo htmlspecialchars($lead_for); ?>
              </span>
          </div>
          <div class="lead-priority" style="font-weight: bold; color:
              <?php
                  if ($lead_priority === 'High') {
                      echo 'red';
                  } elseif ($lead_priority === 'Medium') {
                      echo 'orange';
                  } else {
                      echo 'green';
                  }
              ?>;">
              Lead Priority: <?php echo htmlspecialchars($lead_priority); ?>
          </div>
      </div>
      -->



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
                <label>Email Id:</label>
                <span><?php echo htmlspecialchars($contact_data['email_id']); ?></span>
            </div>
            <div>
                <label>Lead Status:</label>
                <span><?php echo htmlspecialchars($lead_status ?? 'N/A'); ?></span>
            </div>
        </div>

        <!-- Follow-Up History Table -->
        <h2 style="text-align: center;">Follow-Up History</h2>
        <table>
            <thead>
                <tr>
                  <!--
<th>ID</th>
<th>Follow-Up ID</th>
<th>Contact ID</th>
-->                 <th>Followup Id</th>
                    <th>Updation Time</th>
                    <th>Lead Source</th>
                    <th>Lead For</th>
                    <th>Lead Priority</th>
                    <th>Follow-Up Date</th>
                    <th>Follow-Up Time</th>
                    <th>Status</th>
                    <th>Lead Status</th>
                    <th>Estimate Amount</th>
                    <th>Closed Amount</th>
                    <th>Lead Follow-Up</th>
                    <th>Reporting Details</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result_history->fetch_assoc()): ?>
                    <tr>
                      <?php
// echo '<td>' . htmlspecialchars($row['history_id']) . '</td>';
// echo '<td>' . htmlspecialchars($row['followup_id']) . '</td>';
// echo '<td>' . htmlspecialchars($row['contact_id']) . '</td>';
?>
                        <td><?php echo htmlspecialchars($row['followup_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['updation_time']); ?></td>
                        <td style="font-weight: bold; color: <?php echo ($row['lead_priority'] === 'High' ? 'red' : ($row['lead_priority'] === 'Medium' ? 'orange' : 'green')); ?>;">
    <?php echo htmlspecialchars($row['lead_source']); ?>
</td>
<td style="font-weight: bold; color: <?php echo ($row['lead_priority'] === 'High' ? 'red' : ($row['lead_priority'] === 'Medium' ? 'orange' : 'green')); ?>;">
    <?php echo htmlspecialchars($row['lead_for']); ?>
</td>
<td style="font-weight: bold; color: <?php echo ($row['lead_priority'] === 'High' ? 'red' : ($row['lead_priority'] === 'Medium' ? 'orange' : 'green')); ?>;">
    <?php echo htmlspecialchars($row['lead_priority']); ?>
</td>

                        <td><?php echo htmlspecialchars($row['followup_date_nxt']); ?></td>
                        <td><?php echo htmlspecialchars($row['followup_time_nxt']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo htmlspecialchars($row['lead_status']); ?></td>

                        <td><?php echo htmlspecialchars($row['estimate_amount']); ?></td>
                        <td><?php echo htmlspecialchars($row['closed_amount']); ?></td>
                        <td><?php echo htmlspecialchars($row['lead_followup']); ?></td>
                        <td><?php echo htmlspecialchars($row['reporting_details']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
