<?php
session_start();
include('connection.php');
include('topbar.php');
 // Start the session at the beginning of the file
// Check if user is logged in

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>fresh follow ups</title>
    <style>
    /* Table Styles */
    /* Table Styles */
    .user-table {
      width: 100%; /* Full width */
      margin-left: 260px; /* Align with sidebar */
      margin-top: 150px; /* Adjust for topbar */
      border-collapse: collapse;
      background-color: white;
      overflow-x: auto; /* Horizontal scroll */
      overflow-y: auto; /* Vertical scroll */
      max-height: 475px; /* Adjust for vertical scrolling */
    }

    .user-table th, .user-table td {
      padding: 15px; /* Increased padding for wider columns */
      border: 1px solid #ddd;
    }

    .user-table th {
      background-color: #2c3e50; /* Header color */
      color: white;
      text-align: left;
     /* Set a minimum width for columns */
    }

    .user-table td {
      text-align: left;
    }

    .user-table tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .user-table tr:hover {
      background-color: #f1f1f1;
    }

    /* Scrollbar styling */
    .user-table {
      display: block;
      width: 100%;
      overflow: auto;
      white-space: nowrap;
    }

    .user-table td:last-child {
      text-align: right; /* Align buttons to the right */
      width: : 20px; /* Further reduce the width of the action column */
      padding: 5px 8px; /* Reduce padding further for action column */
    }

    .btn-primary, .btn-secondary, .btn-danger, .btn-warning {
      padding: 5px 10px;
      border: none;
      border-radius: 4px;
      color: white;
      cursor: pointer;
    }

    .btn-primary {
      background-color: #a5f3fc;
    }

    .btn-secondary {
      background-color: #6c757d;
    }

    .btn-danger {
      background-color: #dc3545;
    }

    .btn-warning {
      background-color: #3498db;
      color: black;
    }

    .leadforhead {
      position: fixed;
      width: 75%;
      height: 50px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #2c3e50;
      color: white;
      padding: 0 20px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      margin-left: 260px;
      margin-top: 80px;
    }

    .lead-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .btn-primary {
      background-color: #e74c3c;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
    }

    .btn-search {
      background-color: #3498db;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
    }

    .search-bar {
      display: flex;
      align-items: center;
      background-color: white;
      border: 1px solid #ddd;
      border-radius: 5px;
      overflow: hidden;
      margin-right: 40px;
    }

    .search-input {
      border: none;
      padding: 8px;
      outline: none;
      font-size: 14px;
      width: 273px;
    }

    .search-input:focus {
      border: none;
      outline: none;
    }


    </style>
  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">Fresh Follow Ups</h2>
      <div class="lead-actions">
        <div class="search-bar">
          <input type="text" id="searchInput" class="search-input" placeholder="Search...">
          <button class="btn-search" id="searchButton">🔍</button>
        </div>
        <a href="followup.php">
          <button class="btn-primary" id="openModal" data-mode="add">➕</button>
        </a>
      </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table">
        <thead>
          <tr>
            <th>Id</th>
            <th>Lead Source</th>
            <th>Lead For</th>
            <th>Lead Priority</th>
            <th>Contact Person</th>
            <th>Company Name</th>
            <th>Mobile No</th>
            <th>WhatsApp No</th>
            <th>Email ID</th>
            <th>Lead Generated Date</th>
            <th>Status</th>
            <th>Lead Status</th>
            <th>Next Followups Date</th>
            <th>Next Followups Time</th>
            <th>Lead Activity Status</th>
            <th>Estimate Amount</th>
            <th>Closed Amount</th>
            <th>Employee Allocation</th>
            <th>Reporting Details</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>

          <?php
           // Start the session

          if (!isset($_SESSION['user_id'])) {
              header('Location: login.php'); // Redirect to login page if not logged in
              exit();
          }

          // Retrieve User ID from Session
          $user_id = $_SESSION['user_id'];

          // Step 2: Fetch Allowed FY Codes
          $fy_codes = [];
          $fy_query = "SELECT fy_code FROM emp_fy_permission WHERE emp_id = ? AND permission = 1";
          $stmt = $connection->prepare($fy_query);
          $stmt->bind_param("i", $user_id);
          $stmt->execute();
          $result = $stmt->get_result();

          while ($row = $result->fetch_assoc()) {
              $fy_codes[] = $row['fy_code'];
          }

          // Step 3: Fetch Follow-Up Records
          if (!empty($fy_codes)) {
              // Convert the fy_codes array to a comma-separated string for the SQL IN clause
              $fy_codes_string = implode("','", $fy_codes);
              $query = "SELECT
                          f.id AS followup_id, f.lead_source, f.lead_for, f.lead_priority,
                          c.contact_person, c.company_name, c.mobile_no, c.whatsapp_no,
                          c.email_id, c.employee, c.followupdate, f.followup_date_nxt, f.status, f.lead_status,
                          f.followup_date_nxt AS next_followup_date, f.followup_time_nxt,
                          f.lead_followup, f.estimate_amount, f.closed_amount,
                          f.reporting_details
                        FROM
                          contact c
                        LEFT JOIN
                          followup f
                        ON
                          c.id = f.contact_id
                        WHERE
                          f.lead_followup = 'Fresh' AND f.lead_status != 'Close' AND f.fy_code IN ('$fy_codes_string')";
          } else {
              // If no fy_codes, set query to an empty result
              $query = "SELECT * FROM followup WHERE 0"; // Returns no results
          }

          $result = mysqli_query($connection, $query);

          // Step 4: Display the Records
          if (mysqli_num_rows($result) > 0) {
              while ($row = mysqli_fetch_assoc($result)) {
                  echo "<tr>
                          <td>" . ($row['followup_id'] ?? 'N/A') . "</td>
                          <td>" . ($row['lead_source'] ?? 'N/A') . "</td>
                          <td>" . ($row['lead_for'] ?? 'N/A') . "</td>
                          <td>" . ($row['lead_priority'] ?? 'N/A') . "</td>
                          <td>{$row['contact_person']}</td>
                          <td>{$row['company_name']}</td>
                          <td>{$row['mobile_no']}</td>
                          <td>{$row['whatsapp_no']}</td>
                          <td>{$row['email_id']}</td>
                          <td>{$row['followupdate']}</td>
                          <td>{$row['status']}</td>
                          <td>{$row['lead_status']}</td>
                          <td>{$row['next_followup_date']}</td>
                          <td>{$row['followup_time_nxt']}</td>
                          <td>{$row['lead_followup']}</td>
                          <td>{$row['estimate_amount']}</td>
                          <td>{$row['closed_amount']}</td>
                          <td>{$row['employee']}</td>
                          <td>{$row['reporting_details']}</td>
                          <td>
                              <button class='btn-warning edit-btn'
                                  onclick=\"window.location.href='update_followup.php?id={$row['followup_id']}'\">✏️</button>
                              <button class='btn-danger'
                                  onclick=\"if(confirm('Are you sure you want to delete this record?')) {
                                      window.location.href='delete_followup.php?id={$row['followup_id']}';
                                  }\">🗑️</button>
                          </td>
                        </tr>";
              }
          } else {
              echo "<tr><td colspan='19'>No follow-up records found</td></tr>";
          }
          ?>

</tbody>
<script>
  document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('searchInput');
      const tableRows = document.querySelectorAll('.user-table tbody tr');

      searchInput.addEventListener('keyup', function() {
          const searchTerm = searchInput.value.toLowerCase();

          tableRows.forEach(function(row) {
              const cells = row.querySelectorAll('td');
              let rowText = '';

              cells.forEach(function(cell) {
                  rowText += cell.textContent.toLowerCase() + ' '; // Concatenate all cell texts
              });

              // Toggle row visibility based on search term
              if (rowText.includes(searchTerm)) {
                  row.style.display = ''; // Show row
              } else {
                  row.style.display = 'none'; // Hide row
              }
          });
      });
  });
</script>

</html>
