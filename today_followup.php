<?php
session_start();
include('connection.php');
include('topbar.php');
// Check if user is logged in
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <link rel="icon" type="image/png" href="favicon.png">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
  <title>Today Follow Ups</title>
  <style>
  html, body {
  height: 100%;
  margin: 0;
  overflow: hidden;
}

    .main-content {
        height: 100vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .user-table-wrapper {
  width: calc(100% - 260px);
  margin-left: 260px;
  margin-top: 140px;
  height: calc(100vh - 150px);
  overflow: auto;               /* allow both axes, show scrollbars only when necessary */
  border: 1px solid #ddd;
  background-color: white;
  -webkit-overflow-scrolling: touch;
  scrollbar-gutter: stable;     /* keeps layout from jumping when scrollbar appears (modern browsers) */
}

.user-table {
  width: 100%;
  border-collapse: collapse;
  display: table;               /* let the table act like a normal table */
  white-space: nowrap;          /* prevent cell wrap if you want single-line cells */
  /* remove overflow from the table itself — wrapper controls scrolling */
}


    .user-table thead {
      position: sticky;
      top: 0;
      background-color: #2c3e50;
      z-index: 10;
    }

    .user-table th, .user-table td {
      padding: 10px;
      border: 1px solid #ddd;
      text-align: left;
    }

    .user-table th {
      background-color: #2c3e50;
      color: white;
    }

    .user-table tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .user-table tr:hover {
      background-color: #f1f1f1;
      cursor: pointer;
    }

    .user-table td:last-child {
      text-align: right;
      padding: 5px 8px;
    }

    .btn-primary, .btn-secondary, .btn-danger, .btn-warning {
      padding: 5px 10px;
      border: none;
      border-radius: 4px;
      color: white;
      cursor: pointer;
    }

    .btn-primary { background-color: #a5f3fc; }
    .btn-secondary { background-color: #6c757d; }
    .btn-danger { background-color: #dc3545; }
    .btn-warning { background-color: #3498db; color: black; }

    .leadforhead {
      position: fixed;
      width: calc(100% - 290px);
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

    #downloadExcel {
      background-color: green;
    }

    .truncate-tooltip {
      max-width: 200px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="leadforhead">
    <h2 class="leadfor">Today Follow Ups</h2>
    <div class="lead-actions">
      <div class="search-bar">
        <input type="text" id="searchInput" class="search-input" placeholder="Search...">
        <button class="btn-search" id="searchButton">🔍</button>
      </div>
      <button id="downloadExcel" class="btn-primary" title="Download Excel File">
        <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;">
      </button>
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
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit();
        }

        $user_id = $_SESSION['user_id'];

        $fy_codes = [];
        $fy_query = "SELECT fy_code FROM emp_fy_permission WHERE emp_id = ? AND permission = 1";
        $stmt = $connection->prepare($fy_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $fy_codes[] = $row['fy_code'];
        }

        if (!empty($fy_codes)) {
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
                        DATE(f.followup_date_nxt) = CURDATE() AND f.lead_status != 'Close' AND f.fy_code IN ('$fy_codes_string')";
        } else {
            $query = "SELECT * FROM followup WHERE 0";
        }

        $result = mysqli_query($connection, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr data-id='{$row['followup_id']}'>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['followup_id']}\">{$row['followup_id']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['lead_source']}\">{$row['lead_source']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['lead_for']}\">{$row['lead_for']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['lead_priority']}\">{$row['lead_priority']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['contact_person']}\">{$row['contact_person']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['company_name']}\">{$row['company_name']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['mobile_no']}\">{$row['mobile_no']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['whatsapp_no']}\">{$row['whatsapp_no']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['email_id']}\">{$row['email_id']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['followupdate']}\">{$row['followupdate']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['status']}\">{$row['status']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['lead_status']}\">{$row['lead_status']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['next_followup_date']}\">{$row['next_followup_date']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['followup_time_nxt']}\">{$row['followup_time_nxt']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['lead_followup']}\">{$row['lead_followup']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['estimate_amount']}\">{$row['estimate_amount']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['closed_amount']}\">{$row['closed_amount']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['employee']}\">{$row['employee']}</div></td>";
                echo "<td><div class='truncate-tooltip' title=\"{$row['reporting_details']}\">{$row['reporting_details']}</div></td>";
                echo "<td>
                        <button class='btn-warning edit-btn' title='Update Followup'
                            onclick=\"window.location.href='update_today_followup.php?id={$row['followup_id']}'\">✏️</button>
                        <button class='btn-danger' title='Delete this Followup'
                            onclick=\"if(confirm('Are you sure you want to delete this record?')) {
                                window.location.href='delete_followup.php?id={$row['followup_id']}';
                            }\">🗑️</button>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='20' style='text-align: center;'>No records found</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('searchInput');
      const tableRows = document.querySelectorAll('.user-table tbody tr');

      // Search functionality
      searchInput.addEventListener('keyup', function() {
        const searchTerm = searchInput.value.toLowerCase();
        tableRows.forEach(function(row) {
          const cells = row.querySelectorAll('td');
          let rowText = '';
          cells.forEach(function(cell) {
            rowText += cell.textContent.toLowerCase() + ' ';
          });
          row.style.display = rowText.includes(searchTerm) ? '' : 'none';
        });
      });

      // Excel Export
      document.getElementById('downloadExcel').addEventListener('click', function() {
        const table = document.querySelector('.user-table');
        const rows = table.querySelectorAll('tr');
        const data = [];

        const headers = Array.from(rows[0].querySelectorAll('th')).map(th => th.textContent);
        headers.pop(); // remove Actions
        data.push(headers);

        rows.forEach(row => {
          const cells = row.querySelectorAll('td');
          if (cells.length > 0 && row.style.display !== 'none') {
            const rowData = Array.from(cells).map(cell => cell.textContent);
            rowData.pop();
            data.push(rowData);
          }
        });

        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Today Followups');
        XLSX.writeFile(wb, 'today_followups.xlsx');
      });

      // Double click to open update page
      document.querySelectorAll('.user-table tbody tr').forEach(row => {
        row.addEventListener('dblclick', function(e) {
          // Don't trigger if clicking on buttons
          if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
            return;
          }
          const followupId = this.getAttribute('data-id');
          window.location.href = `update_today_followup.php?id=${followupId}`;
        });
      });

      // Single click to show pointer cursor (visual feedback)
      document.querySelectorAll('.user-table tbody tr').forEach(row => {
        row.style.cursor = 'pointer';
      });
    });
  </script>
</body>
</html>
