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
    <title>Quotation Display</title>
    <style>
    /* Table Styles */
    html, body {
        overflow: hidden;
        height: 100%;
        margin: 0;
    }

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
        padding: 7px;
        border: 1px solid #ddd;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .user-table th {
        background-color: #2c3e50;
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
        text-align: center;
        width: auto;
        white-space: nowrap;
    }

    .user-table td:hover {
        white-space: normal;
        overflow: visible;
        position: relative;
        z-index: 1;
    }

    .btn-primary, .btn-secondary, .btn-danger, .btn-warning, .btn-info {
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        color: white;
        cursor: pointer;
        margin-right: 5px;
        display: inline-block;
    }

    .btn-primary { background-color: #e74c3c; }
    .btn-secondary { background-color: #6c757d; }
    .btn-danger { background-color: #dc3545; }
    .btn-warning { background-color: #3498db; color: black; }
    .btn-info { background-color: #17a2b8; }

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
        overflow: visible;
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

    /* Popup Styles */
    .popup {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        padding: 20px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        z-index: 1001;
        width: 300px;
        text-align: center;
    }

    .popup input {
        width: 100%;
        padding: 8px;
        margin: 10px 0;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .popup button {
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        background-color: #3498db;
        color: white;
        cursor: pointer;
    }

    .popup button:hover {
        background-color: #2980b9;
    }

    .overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .paid-button {
        background-color: green;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: default; /* Disable pointer events */
    }

    table tr td:nth-last-child(2) {
        text-align: center;
    }
</style>
  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">Quotation Display</h2>
      <div class="lead-actions">
        <div class="search-bar">
          <input type="text" id="searchInput" class="search-input" placeholder="Search...">
          <button class="btn-search" id="searchButton">🔍</button>
        </div>
        <a href="quotation.php">
          <button class="btn-primary" id="openModal" data-mode="add">➕</button>
        </a>
      </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table">
           <thead>
               <tr>
                   <th>Id</th>
                   <th>Quotation No</th>
                   <th>Client ID</th>
                   <th>Shipper ID</th>
                   <th>Gross Amount</th>
                   <th>GST Charge</th>
                   <th>Discount</th>
                   <th>Net Amount</th>
                   <th>Quotation Date</th>
                   <th>Actions</th>
               </tr>
           </thead>
           <tbody>
               <?php
               $query = "SELECT * FROM quotations"; // Update the query to match your new table name
               $result = mysqli_query($connection, $query);
               if (mysqli_num_rows($result) > 0) {
                   while ($row = mysqli_fetch_assoc($result)) {
                       echo "<tr>
                               <td>{$row['id']}</td>
                               <td>{$row['quotation_no']}</td>
                               <td>{$row['client_id']}</td>
                               <td>{$row['shipper_id']}</td>
                               <td>{$row['gross_amount']}</td>
                               <td>{$row['gst_charge']}</td>
                               <td>{$row['discount']}</td>
                               <td>{$row['net_amount']}</td>
                               <td>{$row['quotation_date']}</td>
                               <td>
                                <button class='btn-warning edit-btn  info' onclick=\"window.location.href='quotation_form_display.php?id={$row['id']}'\">📋</button>
                                   <button class='btn-warning edit-btn' onclick=\"window.location.href='quotation_edit2.php?id={$row['id']}'\">✏️</button>
                                   <button class='btn-warning edit-btn' onclick=\"if(confirm('Do you want to make an invoice for this quotation ?')) window.location.href='register_invoice.php?id={$row['id']}'\">📄</button>
                                   <button class='btn-danger' onclick=\"if(confirm('Are you sure you want to delete this record?')) { window.location.href='delete_quotation.php?id={$row['id']}'; }\">🗑️</button>
                                </td>
                           </tr>";
                   }
               } else {
                   echo "<tr><td colspan='10'>No records found</td></tr>";
               }
               ?>
           </tbody>
       </table>

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
