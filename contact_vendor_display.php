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
    <title>Vendor Contacts</title>
    <style>
    /* Table Styles */
    /* Table Styles */
    html, body {
        overflow: hidden;
        height: 100%;
        margin: 0;
    }
    .main-content {
        height: 100vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    /* Table Wrapper with Responsive Scroll */
    .user-table-wrapper {
        width: calc(100% - 260px);
        margin-left: 260px;
        margin-top: 140px;
        max-height: calc(100vh - 150px); /* Dynamic height based on viewport */
        min-height: 100vh; /* Ensures it doesn't shrink too much */
        overflow-y: auto; /* Enables vertical scrolling */
        border: 1px solid #ddd;
        background-color: white;
    }

    /* Table Styles */
    .user-table {
      width: 100%;
      border-collapse: collapse;
    }

    /* Fix table header */
    .user-table thead {
      position: sticky;
      top: 0;
      background-color: #2c3e50;
      z-index: 10;
    }

    /* Styling table header */
    .user-table th {
      padding: 12px;
      border: 1px solid #ddd;
      background-color: #2c3e50;
      color: white;
      text-align: left;
    }

    /* Styling table rows */
    .user-table td {
      padding: 10px;
      border: 1px solid #ddd;
      text-align: left;
    }

    /* Zebra striping for rows */
    .user-table tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .user-table tr:hover {
      background-color: #f1f1f1;
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
      width: calc(100% - 290px); /* Adjust width to account for sidebar */
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
    #downloadExcel{
      background-color: green;
    }

    </style>
  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">Vendor Contacts</h2>
      <div class="lead-actions">
        <div class="search-bar">
          <input type="text" id="searchInput" class="search-input" placeholder="Search...">
          <button class="btn-search" id="searchButton">🔍</button>
        </div>
        <a href="contact_vendor_add.php">
          <button class="btn-primary" id="openModal" data-mode="add" title="Add Vendor Contact">➕</button>
        </a>
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
            <th>Remarks</th>
            <th>GST Number</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $query = "SELECT * FROM contact_vendor"; // Update the query to match your contact_vendor table
          $result = mysqli_query($connection, $query);
          if (mysqli_num_rows($result) > 0) {
              while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr ondblclick=\"window.location.href='contact_vendor_update.php?id={$row['id']}'\" style='cursor: pointer;'>

                          <td>{$row['id']}</td>
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
                          <td>{$row['remarks']}</td>
                          <td>{$row['gstno']}</td>
                          <td>
                              <button class='btn-warning edit-btn' title='Update Vendor Contact' onclick=\"window.location.href='contact_vendor_update.php?id={$row['id']}'\">✏️</button>
                                <button title='Payment History' class='btn-warning edit-btn' onclick=\"window.location.href='vendor_payment_history.php?id={$row['id']}'\">💰</button>
                              <button class='btn-danger' title='Delete this Vendor Contact' onclick=\"if(confirm('Are you sure you want to delete this record?')) { window.location.href='delete_contact_vendor.php?id={$row['id']}'; }\">🗑️</button>
                          </td>
                        </tr>";
              }
          } else {
               echo "<tr><td colspan='15' style='text-align: center;'>No records found</td></tr>";
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
              XLSX.utils.book_append_sheet(wb, ws, 'Vendor Contacts');

              // Generate and download the Excel file
              XLSX.writeFile(wb, 'vendor_contacts.xlsx');
          });
      });
    </script>
  </body>
</html>
