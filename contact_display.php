<?php
session_start();
include('connection.php');
include('topbar.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <link rel="icon" type="image/png" href="favicon.png">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
  <title>Contacts</title>
  <style>
  html, body {
  height: 100%;
  margin: 0;
  overflow: hidden; /* Keep page fixed (no scrolling) */
}

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
    .btn-primary { background-color: red; }
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

    /* Modal styles */
    #contactCardModal { display: none; }
    #contactCardBackdrop {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 2000;
    }
    #contactCardContent {
      position: fixed;
      top: 54%; left: 50%;
      transform: translate(-50%, -50%);
      width: 600px;
      max-height: 85vh;
      overflow-y: auto;
      background: white;
      padding: 20px;
      border-radius: 12px;
      z-index: 3000;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
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
    <h2 class="leadfor">Contacts</h2>
    <div class="lead-actions">
      <div class="search-bar">
        <input type="text" id="searchInput" class="search-input" placeholder="Search...">
        <button class="btn-search" id="searchButton">🔍</button>
      </div>
      <a href="contact.php"><button class="btn-primary" title="Add Contact">➕</button></a>
      <button id="downloadExcel" class="btn-primary" title="Download Excel File">
        <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;">
      </button>
    </div>
  </div>

  <div class="user-table-wrapper">
    <table class="user-table">
      <thead>
        <tr>
          <th>Id</th><th>Lead Generated Date</th><th>Lead Source</th><th>Lead For</th><th>Lead Priority</th>
          <th>Contact Person</th><th>Company Name</th><th>Mobile No</th><th>WhatsApp No</th><th>Email ID</th>
          <th>Address</th><th>Country</th><th>State</th><th>City</th><th>Pincode</th><th>Reference Name</th>
          <th>Reference Mobile</th><th>Estimate Amount</th><th>Employee Allocation</th><th>Remarks</th>
          <th>Balance</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
  <?php
  // Fetch contacts into array first
  $contacts = [];
  $query = "SELECT * FROM contact ORDER BY id DESC";
  $result = mysqli_query($connection, $query);
  if ($result && mysqli_num_rows($result) > 0) {
      while ($r = mysqli_fetch_assoc($result)) {
          $contacts[] = $r;
      }
  }

  // Build list of contact ids
  $ids = array_map(function($c) { return intval($c['id']); }, $contacts);
  $balances = [];

  if (!empty($ids)) {
      // Prepare the IN list safely (integers only)
      $id_list = implode(',', $ids);

      // Aggregate balances for only these contacts
      $bal_q = "
        SELECT pl.party_no AS contact_id, COALESCE(SUM(pl.amount), 0) AS balance
        FROM party_ledger pl
        WHERE pl.party_type = 'Customer' AND pl.party_no IN ($id_list)
        GROUP BY pl.party_no
      ";
      $bal_res = mysqli_query($connection, $bal_q);
      if ($bal_res) {
          while ($b = mysqli_fetch_assoc($bal_res)) {
              $balances[intval($b['contact_id'])] = (float)$b['balance'];
          }
      }
  }

  // Render rows
  if (!empty($contacts)) {
      foreach ($contacts as $row) {
          $id = intval($row['id']);
          $balance = $balances[$id] ?? 0.0;
          // Format balance
          $formatted_balance = number_format($balance, 2);
          $balance_color = ($balance < 0) ? 'red' : 'green';

          echo "<tr>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['id'] . "\">" . $row['id'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['followupdate'] . "\">" . $row['followupdate'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['lead_source'] . "\">" . $row['lead_source'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['lead_for'] . "\">" . $row['lead_for'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['lead_priority'] . "\">" . $row['lead_priority'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['contact_person'] . "\">" . $row['contact_person'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['company_name'] . "\">" . $row['company_name'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['mobile_no'] . "\">" . $row['mobile_no'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['whatsapp_no'] . "\">" . $row['whatsapp_no'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['email_id'] . "\">" . $row['email_id'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['address'] . "\">" . $row['address'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['country'] . "\">" . $row['country'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['state'] . "\">" . $row['state'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['city'] . "\">" . $row['city'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['pincode'] . "\">" . $row['pincode'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['reference_pname'] . "\">" . $row['reference_pname'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['reference_pname_no'] . "\">" . $row['reference_pname_no'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['estimate_amnt'] . "\">" . $row['estimate_amnt'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['employee'] . "\">" . $row['employee'] . "</div></td>";
          echo "<td><div class='truncate-tooltip' title=\"" . $row['remarks'] . "\">" . $row['remarks'] . "</div></td>";

          // Balance column
          echo "<td><div class='truncate-tooltip' title=\"{$formatted_balance}\"><span style=\"color: {$balance_color};\">{$formatted_balance}</span></div></td>";

          // Actions
          echo "<td>
                  <button title='followup History' class='btn-warning edit-btn' onclick=\"window.location.href='followup_filter.php?id={$row['id']}'\">ℹ️</button>
                  <button title='Update Contact info' class='btn-warning edit-btn' onclick=\"window.location.href='update_contact.php?id={$row['id']}'\">✏️</button>
                  <button title='Generate Followup' class='btn-warning edit-btn' onclick=\"window.location.href='followup_add.php?id={$row['id']}'\">📩</button>
                  <button title='Payment History' class='btn-warning edit-btn' onclick=\"window.location.href='payment_history.php?id={$row['id']}'\">💰</button>
                  <button title='Delete Contact' class='btn-danger' onclick=\"if(confirm('Are you sure you want to delete this record?')) { window.location.href='delete_contact.php?id={$row['id']}'; }\">🗑️</button>
                </td>";
          echo "</tr>";
      }
  } else {
      // colspan updated to 22 (added Balance column)
      echo "<tr><td colspan='22' style='text-align: center;'>No records found</td></tr>";
  }
  ?>
  </tbody>

    </table>
  </div>

  <!-- MODAL HTML -->
  <div id="contactCardModal">
    <div id="contactCardBackdrop" onclick="closeContactCard()"></div>
    <div id="contactCardContent">
      <div style="display: flex; justify-content: space-between; align-items: start;">
        <h3 style="margin: 0 0 20px 0;">Contact Details</h3>
        <div id="contactCardActions"></div>
      </div>
      <div id="contactCardDetails"></div>
      <div style="text-align: right; margin-top: 20px;">
        <button onclick="closeContactCard()" style="padding: 8px 16px; background-color: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer;">Close</button>
      </div>
    </div>
  </div>

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
        XLSX.utils.book_append_sheet(wb, ws, 'Contacts');
        XLSX.writeFile(wb, 'contacts.xlsx');
      });

      // Card Modal Logic
      document.querySelectorAll('.user-table tbody tr').forEach(row => {
        row.addEventListener('click', function(e) {
          if (e.target.tagName === 'BUTTON' || e.target.closest('button')) return;
          const cells = this.querySelectorAll('td');
          const headers = document.querySelectorAll('.user-table thead th');
          let detailHTML = '<table style="width: 100%; border-collapse: collapse;">';
          for (let i = 0; i < cells.length - 1; i++) {
            detailHTML += `
              <tr>
                <td style="font-weight: bold; padding: 6px 10px; width: 40%;">${headers[i].textContent}</td>
                <td style="padding: 6px 10px;">${cells[i].textContent}</td>
              </tr>`;
          }
          detailHTML += '</table>';
          document.getElementById('contactCardDetails').innerHTML = detailHTML;
          document.getElementById('contactCardActions').innerHTML = cells[cells.length - 1].innerHTML;
          document.getElementById('contactCardModal').style.display = 'block';
        });
      });
    });

    function closeContactCard() {
      document.getElementById('contactCardModal').style.display = 'none';
    }
  </script>
</body>
</html>
