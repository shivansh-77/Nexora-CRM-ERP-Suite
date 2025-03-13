<?php
session_start();
include('connection.php');
include('topbar.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>AMC Dues</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
    <style>
    html, body {
        overflow: hidden;
        height: 100%;
        margin: 0;
    }

    /* Table Wrapper with Responsive Scroll */
    .user-table-wrapper {
        width: calc(100% - 260px);
        margin-left: 260px;
        margin-top: 140px;
        max-height: calc(100vh - 140px); /* Dynamic height based on viewport */
        min-height: 100vh; /* Ensures it doesn't shrink too much */
        overflow-y: auto; /* Enables vertical scrolling */
        border: 1px solid #ddd;
        background-color: white;
    }

        .user-table {
            width: 100%; /* Full width */
            border-collapse: collapse;
            background-color: white;
            table-layout: auto; /* Allow columns to adjust based on content */
        }

        .user-table th, .user-table td {
            padding: 9px; /* Increased padding for wider columns */
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
        /* Filter Styles */
        .filter-select {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
            margin-right: 10px;
        }

        .date-filter {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
            margin-right: 10px;
        }

        .glow-red {
            color: red;
            font-weight: bold;
            text-shadow: 0px 0px 0px red;
        }

        .date-filter {
            width: 120px; /* Adjust width */
            padding: 7px; /* Smaller padding */
            font-size: 14px; /* Reduce font size */
        }

        .filter-input, .filter-select {
            width: 100%;
            padding: 6px;
            box-sizing: border-box;
        }

        #downloadExcel {
            background-color: green;
        }

    </style>
  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">AMC Dues</h2>
      <div class="lead-actions">
        <!-- Sort Filter -->
  <select id="sortFilter" class="filter-select">
      <option value="all">All Dues</option>
      <option value="upcoming">Upcoming Dues</option>
      <option value="monthly">This Month</option>
      <option value="quarterly">Next 3 Months</option>
      <option value="yearly">Next 1 Year</option>
  </select>

  <!-- Date Filters -->

  <input type="date" id="startDateFilter" class="date-filter">


  <input type="date" id="endDateFilter" class="date-filter">
        <!-- Add Button -->
        <!-- <a href="invoice_generate.php">
          <button class="btn-primary" id="openModal" data-mode="add" title="">➕</button>
        </a> -->
        <button id="downloadExcel" class="btn-primary">
          <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;" title="Download Excel File">
        </button>
      </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table">
      <thead>
          <tr>
              <th>Id</th>
              <th>Invoice No</th>
              <th>Client Name</th>
              <th>Invoice Date</th>
              <th>Net Amount</th>
              <th>AMC Code</th>
              <th>AMC Paid Date</th>
              <th>AMC Due Date</th>
              <th>AMC Amount</th>
              <th>Days Left</th> <!-- New Column -->
              <th>New AMC Invoice No.</th>
              <th>New AMC Inv. Generate Dt.</th>
              <th>Reference Invoice No</th>
              <th>Actions</th>
          </tr>
      </thead>
      <tbody>
          <?php
          $query = "SELECT
                      i.id AS invoice_id,
                      i.invoice_no,
                      i.quotation_no,
                      i.client_name,
                      i.invoice_date,
                      i.gross_amount,
                      i.discount,
                      i.net_amount,
                      ii.id AS invoice_items_id,
                      ii.amc_code,
                      ii.amc_term,
                      ii.amc_paid_date,
                      ii.amc_due_date,
                      ii.amc_amount,
                      ii.new_amc_invoice_no,
                      ii.new_amc_invoice_gen_date,
                      ii.reference_invoice_no
                    FROM invoices i
                    LEFT JOIN invoice_items ii ON i.id = ii.invoice_id
                    WHERE i.status = 'Finalized'
                    AND ii.amc_code REGEXP '^[0-9]+$'";

          $result = mysqli_query($connection, $query);

          if (mysqli_num_rows($result) > 0) {
              while ($row = mysqli_fetch_assoc($result)) {
                  // Calculate Days Left
                  $today = new DateTime();
                  $dueDate = new DateTime($row['amc_due_date']);
                  $daysLeft = $today->diff($dueDate)->days;
                  $isRedGlow = ($daysLeft <= 31) ? "glow-red" : ""; // Apply class if ≤ 31 days

                  echo "<tr>
                          <td>{$row['invoice_id']}</td>
                          <td>{$row['invoice_no']}</td>
                          <td>{$row['client_name']}</td>
                          <td>{$row['invoice_date']}</td>
                          <td>{$row['net_amount']}</td>
                          <td>{$row['amc_code']}</td>
                          <td>{$row['amc_paid_date']}</td>
                          <td>{$row['amc_due_date']}</td>
                          <td>{$row['amc_amount']}</td>
                          <td class='$isRedGlow'>{$daysLeft} days</td> <!-- Days Left Column -->
                          <td>{$row['new_amc_invoice_no']}</td>
                          <td>{$row['new_amc_invoice_gen_date']}</td>
                          <td>{$row['reference_invoice_no']}</td>
                          <td>
                              <button class='btn-secondary' title='Print Invoice for this AMC' onclick=\"window.location.href='invoice1.php?id={$row['invoice_id']}'\">🖨️</button>
                              <button class='btn-secondary' title='Renew this AMC'
                                  onclick=\"if(confirm('Are you sure you want to renew this AMC record ?')) {
                                    window.location.href='amc_due_renew.php?id={$row['invoice_items_id']}';
                                  }\">🔁</button>
                          </td>
                      </tr>";
              }
          } else {
           echo "<tr><td colspan='16' style='text-align: center;'>No records found</td></tr>";
          }
          ?>
      </tbody>
  </table>

    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
          const sortFilter = document.getElementById('sortFilter');
          const startDateFilter = document.getElementById('startDateFilter');
          const endDateFilter = document.getElementById('endDateFilter');
          const tableRows = document.querySelectorAll('.user-table tbody tr');

          function filterTable() {
              const filterValue = sortFilter.value;
              const startDateValue = startDateFilter.value ? new Date(startDateFilter.value) : null;
              const endDateValue = endDateFilter.value ? new Date(endDateFilter.value) : null;

              tableRows.forEach(function (row) {
                  const dueDate = row.querySelector('td:nth-child(8)').textContent.trim();
                  const rowDate = new Date(dueDate);
                  const currentDate = new Date();

                  let shouldDisplay = true;

                  if (filterValue === 'upcoming') {
                      shouldDisplay = rowDate > currentDate;
                  } else if (filterValue === 'monthly') {
                      shouldDisplay = rowDate.getMonth() === currentDate.getMonth() && rowDate.getFullYear() === currentDate.getFullYear();
                  } else if (filterValue === 'quarterly') {
                      const threeMonthsLater = new Date();
                      threeMonthsLater.setMonth(currentDate.getMonth() + 3);
                      shouldDisplay = rowDate >= currentDate && rowDate <= threeMonthsLater;
                  } else if (filterValue === 'yearly') {
                      const oneYearLater = new Date();
                      oneYearLater.setFullYear(currentDate.getFullYear() + 1);
                      shouldDisplay = rowDate >= currentDate && rowDate <= oneYearLater;
                  }

                  if (startDateValue) {
                      shouldDisplay = shouldDisplay && rowDate >= startDateValue;
                  }

                  if (endDateValue) {
                      shouldDisplay = shouldDisplay && rowDate <= endDateValue;
                  }

                  row.style.display = shouldDisplay ? '' : 'none';
              });
          }

          sortFilter.addEventListener('change', filterTable);
          startDateFilter.addEventListener('change', filterTable);
          endDateFilter.addEventListener('change', filterTable);

          // Download Excel
          const downloadExcelButton = document.getElementById('downloadExcel');
          downloadExcelButton.addEventListener('click', function () {
              const table = document.querySelector('.user-table');
              const clonedTable = table.cloneNode(true);
              const actionColumn = clonedTable.querySelectorAll('th:last-child, td:last-child');

              actionColumn.forEach(col => col.remove());

              const ws = XLSX.utils.table_to_sheet(clonedTable, { raw: true });
              const wb = XLSX.utils.book_new();
              XLSX.utils.book_append_sheet(wb, ws, 'AMC Dues');
              XLSX.writeFile(wb, 'amc_dues.xlsx');
          });
      });
  </script>
  </body>
</html>
