<?php
// Include your database connection file
include 'connection.php';

// Variables to retain form inputs
$contact_id = $_GET['id'] ?? ''; // Use the contact_id from the URL
$followup_id = $_POST['followup_id'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$lead_for = $_POST['lead_for'] ?? '';

// Fetch distinct follow-up IDs for the selected contact ID
$followup_ids = [];
if (!empty($contact_id)) {
    $stmt = $connection->prepare("SELECT DISTINCT followup_id FROM followup_history WHERE contact_id = ?");
    $stmt->bind_param("i", $contact_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $followup_ids[] = $row['followup_id'];
    }
    $stmt->close();
}

// Fetch distinct Lead For values for the selected contact ID
$lead_for_options = [];
if (!empty($contact_id)) {
    $stmt = $connection->prepare("SELECT DISTINCT lead_for FROM followup_history WHERE contact_id = ?");
    $stmt->bind_param("i", $contact_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $lead_for_options[] = $row['lead_for'];
    }
    $stmt->close();
}

// Fetch contact details from the contact table based on contact_id
$contact_details = [];
if (!empty($contact_id)) {
    $stmt = $connection->prepare("SELECT contact_person, company_name, mobile_no, whatsapp_no, email_id ,followupdate FROM contact WHERE id = ?");
    $stmt->bind_param("i", $contact_id);
    $stmt->execute();
    $contact_result = $stmt->get_result();
    $contact_details = $contact_result->fetch_assoc();
    $stmt->close();
}

// Initialize the filtered data
$filtered_data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Filter data based on submitted filters
    $filter_query = "SELECT * FROM followup_history WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($contact_id)) {
        $filter_query .= " AND contact_id = ?";
        $params[] = $contact_id;
        $types .= "i";
    }
    if (!empty($followup_id)) {
        $filter_query .= " AND followup_id = ?";
        $params[] = $followup_id;
        $types .= "i";
    }
    if (!empty($start_date) && !empty($end_date)) {
        $filter_query .= " AND followup_date_nxt BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }
    if (!empty($lead_for)) {
        $filter_query .= " AND lead_for = ?";
        $params[] = $lead_for;
        $types .= "s";
    }

    // Prepare the statement dynamically
    $stmt = $connection->prepare($filter_query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $filtered_data = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Follow-up History</title>
    <link rel="stylesheet" href="style.css"> <!-- Link to your CSS file -->
    <style>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
            margin: 0; /* Remove margin */
    padding: 0; /* Remove padding */
    height: 100vh; /* Ensure body takes full viewport height */
    overflow: hidden; /* Disable scroll unless necessary */
}

        .wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin: 0; /* Remove margin */
  height: 100%; /* Ensure wrapper takes full height */
}

  .container {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 20px;
      width: 120%; /* Ensures the container spans full width */
      max-width: 1200px;
      max-height: 5500px;
      height: 100%;
  }

        .card, .table-container {
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            width: 100%; /* Makes cards wide enough to cover the screen */
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .filter-item {
            flex: 1 1 calc(25% - 20px); /* Ensure all fields are in a single row */
            display: flex;
            flex-direction: column;
        }

        .filter-item label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .filter-item select,
        .filter-item input {
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .filter-button-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .filter-button-container button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .filter-button-container button:hover {
            background-color: #2980b9;
        }
        .filter-form {
            display: flex;
            gap: 20px;
            flex-wrap: nowrap; /* Keep all items in a single row */
            align-items: center; /* Align button with form fields */
        }

        .filter-button-container {
            display: flex;
            justify-content: flex-start; /* Keep the button aligned beside the last input */
            gap: 20px;
        }

        .filter-button-container button {
            margin-left: 20px; /* Space between the button and the last filter item */
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table-container table th,
        .table-container table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
            font-size: 14px;
        }

        .table-container table th {
            background-color: #f4f4f4;
            color: #333;
        }

        .table-container table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table-container table tr:hover {
            background-color: #f1f1f1;
        }

        .contact-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Two columns */
            gap: 15px; /* Space between items */
            justify-content: right; /* Aligns the grid to the right */
            text-align: left; /* Keeps the text aligned to the left within each cell */

        }

        .contact-details p {
            margin: 0;
            font-size: 16px;
        }

        .contact-details strong {
            color: #2c3e50;
        }
    </style>

    <div class="wrapper">
        <div class="container">
            <!-- Filter Follow-up History Card -->
            <div class="card">
                <h2>Filter Follow-up History</h2>
                <form id="filter-form" action="followup_filter.php?id=<?= $contact_id ?>" method="POST">
                    <input type="hidden" name="contact_id" value="<?= $contact_id ?>">

                    <div class="filter-form">
                        <div class="filter-item">
                            <label for="followup_id">Follow-up ID:</label>
                            <select id="followup_id" name="followup_id">
                                <option value=""><?= !empty($followup_ids) ? "All Followups" : "No Follow-up IDs available" ?></option>
                                <?php foreach ($followup_ids as $followup_id_option): ?>
                                    <option value="<?= $followup_id_option ?>" <?= $followup_id == $followup_id_option ? 'selected' : '' ?>>
                                        <?= $followup_id_option ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label for="lead_for">Lead For:</label>
                            <select id="lead_for" name="lead_for">
                                <option value=""><?= !empty($lead_for_options) ? "All Lead For" : "No Lead For options available" ?></option>
                                <?php foreach ($lead_for_options as $lead_for_option): ?>
                                    <option value="<?= $lead_for_option ?>" <?= $lead_for == $lead_for_option ? 'selected' : '' ?>>
                                        <?= $lead_for_option ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label for="start_date">Start Date:</label>
                            <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>">
                        </div>

                        <div class="filter-item">
                            <label for="end_date">End Date:</label>
                            <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>">
                        </div>
                        <div class="filter-button-container">
                            <button type="submit">Filter</button>
                        </div>
                    </div>


                </form>
            </div>

            <!-- Contact Details Card -->
            <div class="card">
                <h2>Contact Details</h2>
                <div class="contact-details">
                    <p><strong>Contact Person:</strong> <?= htmlspecialchars($contact_details['contact_person']) ?></p>
                    <p><strong>Company Name:</strong> <?= htmlspecialchars($contact_details['company_name']) ?></p>
                    <p><strong>Mobile No:</strong> <?= htmlspecialchars($contact_details['mobile_no']) ?></p>
                    <p><strong>WhatsApp No:</strong> <?= htmlspecialchars($contact_details['whatsapp_no']) ?></p>
                    <p><strong>Email ID:</strong> <?= htmlspecialchars($contact_details['email_id']) ?></p>
                    <p><strong>Generated on:</strong> <?= htmlspecialchars($contact_details['followupdate']) ?></p>

                </div>
            </div>

            <!-- Filtered Follow-up History Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Follow-up ID</th>
                            <th>Lead Priority</th>
                            <th>Lead For</th>


                            <th>Status</th>

                            <th>Estimate Amount</th>
                            <th>Closed Amount</th>
                            <th>Lead Follow-up</th>
                            <th>Reporting Details</th>
                        </tr>
                    </thead>
                    <tbody>
                      <?php if ($filtered_data && $filtered_data->num_rows > 0): ?>
    <?php while ($row = $filtered_data->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['followup_id']) ?></td>
            <td><?= htmlspecialchars($row['lead_priority']) ?></td>
            <td><?= htmlspecialchars($row['lead_for']) ?></td>
            <td><?= htmlspecialchars($row['followup_date_nxt']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['estimate_amount']) ?></td>
            <td><?= htmlspecialchars($row['closed_amount']) ?></td>
            <td><?= htmlspecialchars($row['reporting_details']) ?></td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <?php
        // Query to fetch all follow-up history for the specific contact_id when no filtered data is available
        $query = "SELECT * FROM followup_history WHERE contact_id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $contact_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
    ?>
                <tr>
                    <td><?= htmlspecialchars($row['followup_id']) ?></td>
                    <td><?= htmlspecialchars($row['lead_priority']) ?></td>
                    <td><?= htmlspecialchars($row['lead_for']) ?></td>
                    <td><?= htmlspecialchars($row['followup_date_nxt']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['estimate_amount']) ?></td>
                    <td><?= htmlspecialchars($row['closed_amount']) ?></td>
                    <td><?= htmlspecialchars($row['reporting_details']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">No follow-up history available for contact ID: <?= htmlspecialchars($contact_id) ?></td>
            </tr>
        <?php endif; ?>
<?php endif; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

</html>
