<?php
session_start();
include('connection.php');
include('topbar.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Invoice Display</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
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

        .btn-primary, .btn-secondary, .btn-danger, .btn-warning, .btn-info {
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
        .btn-info { background-color: #17a2b8; }

        .leadforhead {
            position: fixed;
            width: 79%;
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
      <h2 class="leadfor">Draft Invoice</h2>
      <div class="lead-actions">
        <div class="search-bar">
          <input type="text" id="searchInput" class="search-input" placeholder="Search...">
          <button class="btn-search" id="searchButton">🔍</button>
        </div>
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
                   <th>Invoice No</th>
                   <th>Quotation No</th>
                   <th>Client Name</th>
                   <th>Invoice Date</th>
                   <th>Gross Amount</th>
                   <th>Discount</th>
                   <th>Net Amount</th>
                   <th>Actions</th>
               </tr>
           </thead>
           <tbody>
               <?php
               $query = "SELECT id, invoice_no, quotation_no, client_name, invoice_date, gross_amount, discount, net_amount
             FROM invoices
             WHERE status = 'draft'";
               $result = mysqli_query($connection, $query);
               if (mysqli_num_rows($result) > 0) {
                   while ($row = mysqli_fetch_assoc($result)) {
                       echo "<tr>
                               <td>{$row['id']}</td>
                               <td>{$row['invoice_no']}</td>
                               <td>{$row['quotation_no']}</td>
                               <td>{$row['client_name']}</td>
                               <td>{$row['invoice_date']}</td>
                               <td>{$row['gross_amount']}</td>
                               <td>{$row['discount']}</td>
                               <td>{$row['net_amount']}</td>
                               <td>
                                   <button class='btn-info'  onclick=\"window.location.href='invoice.php?id={$row['id']}'\">📝</button>
                                   <button class='btn-secondary' onclick=\"window.location.href='invoice_cancel.php?id={$row['id']}'\">⛔</button>
                               </td>
                           </tr>";
                   }
               } else {
                   echo "<tr><td colspan='9'>No records found</td></tr>";
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
                      rowText += cell.textContent.toLowerCase() + ' ';
                  });

                  if (rowText.includes(searchTerm)) {
                      row.style.display = '';
                  } else {
                      row.style.display = 'none';
                  }
              });
          });

          // Download Excel
          const downloadExcelButton = document.getElementById('downloadExcel');
          downloadExcelButton.addEventListener('click', function() {
              const table = document.querySelector('.user-table');
              const clonedTable = table.cloneNode(true);
              const actionColumn = clonedTable.querySelectorAll('th:last-child, td:last-child');

              actionColumn.forEach(col => col.remove());

              const ws = XLSX.utils.table_to_sheet(clonedTable, { raw: true });
              const wb = XLSX.utils.book_new();
              XLSX.utils.book_append_sheet(wb, ws, 'Draft Invoices');
              XLSX.writeFile(wb, 'draft_invoices.xlsx');
          });
      });
    </script>
  </body>
</html>
