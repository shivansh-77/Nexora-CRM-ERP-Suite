<?php
session_start();
include('connection.php');
include('topbar.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <title>Contacts</title>
    <style>
    html, body {
        overflow: hidden;
        height: 100%;
        margin: 0;
    }

        /* Table Styles */
        .user-table-wrapper {
            width: calc(100% - 260px); /* Adjust width to account for sidebar */
            margin-left: 260px; /* Align with sidebar */
            margin-top: 140px; /* Adjust for topbar */
            overflow: auto; /* Enable scrolling for the table */
            max-height: 475px; /* Set max height for vertical scrolling */
        }

        .user-table {
            width: 100%; /* Full width */
            border-collapse: collapse;
            background-color: white;
            table-layout: auto; /* Allow columns to adjust based on content */
        }

        .user-table th, .user-table td {
            padding: 10px; /* Increased padding for wider columns */
            border: 1px solid #ddd;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-table th {
            background-color: #2c3e50; /* Header color */
            color: white;
            text-align: left;
            position: sticky; /* Make headers sticky */
            top: 0; /* Stick to the top */
            z-index: 1; /* Ensure headers are above the body */
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

        .user-table td:last-child {
            text-align: right; /* Align buttons to the right */
            width: auto; /* Further reduce the width of the action column */
            padding: 5px 8px; /* Reduce padding further for action column */
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
            overflow: visible; /* Ensure child elements are visible */
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
        #downloadExcel{
          background-color: green;
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
        <a href="contact.php">
          <button class="btn-primary" id="openModal" data-mode="add">➕</button>
        </a>
        <button id="downloadExcel" class="btn-primary">
          <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;">
        </button>
      </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table">
        <thead>
          <tr>
            <th>Id</th>
            <th>Lead Generated Date</th>
            <th>Lead Source</th>
            <th>Lead For</th>
            <th>Lead Priority</th>
            <th>Contact Person</th>
            <th>Company Name</th>
            <th>Mobile No</th>
            <th>WhatsApp No</th>
            <th>Email ID</th>
            <th>Address</th>
            <th>Country</th>
            <th>State</th>
            <th>City</th>
            <th>Pincode</th>
            <th>Reference Name</th>
            <th>Reference Mobile</th>
            <th>Estimate Amount</th>
            <th>Employee Allocation</th>
            <th>Remarks</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $query = "SELECT * FROM contact"; // Update the query to match your contact table
          $result = mysqli_query($connection, $query);
          if (mysqli_num_rows($result) > 0) {
              while ($row = mysqli_fetch_assoc($result)) {
                  echo "<tr>
                          <td>{$row['id']}</td>
                          <td>{$row['followupdate']}</td>
                          <td>{$row['lead_source']}</td>
                          <td>{$row['lead_for']}</td>
                          <td>{$row['lead_priority']}</td>
                          <td>{$row['contact_person']}</td>
                          <td>{$row['company_name']}</td>
                          <td>{$row['mobile_no']}</td>
                          <td>{$row['whatsapp_no']}</td>
                          <td>{$row['email_id']}</td>
                          <td>{$row['address']}</td>
                          <td>{$row['country']}</td>
                          <td>{$row['state']}</td>
                          <td>{$row['city']}</td>
                          <td>{$row['pincode']}</td>
                          <td>{$row['reference_pname']}</td>
                          <td>{$row['reference_pname_no']}</td>
                          <td>{$row['estimate_amnt']}</td>
                          <td>{$row['employee']}</td>
                          <td>{$row['remarks']}</td>
                          <td>
                            <button class='btn-warning edit-btn followup-btn' onclick=\"window.location.href='followup_filter.php?id={$row['id']}'\">ℹ️</button>
                            <button class='btn-warning edit-btn' onclick=\"window.location.href='update_contact.php?id={$row['id']}'\">✏️</button>
                            <button class='btn-warning edit-btn followup-btn-add' onclick=\"window.location.href='followup_add.php?id={$row['id']}'\">📩</button>
                            <button class='btn-warning edit-btn followup-btn payment' onclick=\"window.location.href='payment_history.php?id={$row['id']}'\">💰</button>
                            <button class='btn-danger' onclick=\"if(confirm('Are you sure you want to delete this record?')) { window.location.href='delete_contact.php?id={$row['id']}'; }\">🗑️</button>
                          </td>
                        </tr>";
              }
          } else {
              echo "<tr><td colspan='18'>No users found</td></tr>";
          }
          ?>
        </tbody>
      </table>
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

          const downloadExcel = document.getElementById('downloadExcel');
          downloadExcel.addEventListener('click', function() {
              const table = document.querySelector('.user-table');
              const rows = table.querySelectorAll('tr');
              const data = [];

              // Extract headers
              const headers = Array.from(rows[0].querySelectorAll('th')).map(th => th.textContent);
              headers.pop(); // Remove the "Actions" header
              data.push(headers);

              // Extract rows
              rows.forEach(row => {
                  const cells = row.querySelectorAll('td');
                  if (cells.length > 0 && row.style.display !== 'none') {
                      const rowData = Array.from(cells).map(cell => cell.textContent);
                      rowData.pop(); // Remove the "Actions" cell
                      data.push(rowData);
                  }
              });

              // Create a worksheet and workbook
              const ws = XLSX.utils.aoa_to_sheet(data);
              const wb = XLSX.utils.book_new();
              XLSX.utils.book_append_sheet(wb, ws, 'Contacts');

              // Generate and download the Excel file
              XLSX.writeFile(wb, 'contacts.xlsx');
          });
      });
    </script>
  </body>
</html>
