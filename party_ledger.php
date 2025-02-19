<?php
session_start();
include('connection.php');
include('topbar.php');

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Party Ledger</title>
    <style>
        /* Table Styles */
        /* Prevent the body from scrolling */
html, body {
    overflow: hidden;
    height: 100%;
    margin: 0;
}

/* Table Wrapper with Scroll */
.user-table-wrapper {
    width: calc(100% - 260px);
    margin-left: 260px;
    margin-top: 140px;
    max-height: 475px; /* Fixed height for the table wrapper */
    overflow-y: auto; /* Enable vertical scrolling only inside the table */
    border: 1px solid #ddd;
    background-color: white;
}

/* Ensure the main content fits within the viewport */
.main-content {
    height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

        .user-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        .user-table th, .user-table td {
            padding: 10px;
            border: 1px solid #ddd;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-table th {
            background-color: #2c3e50;
            color: white;
            text-align: left;
            position: sticky; /* Make header sticky */
            z-index: 1; /* Ensure header stays above table rows */
        }

        /* Make the filter row sticky */
        .user-table thead tr:first-child th {
            top: 0; /* Stick to the top of the table wrapper */
            background-color: #2c3e50; /* Match the header background */
        }

        /* Make the table headings row sticky */
        .user-table thead tr:nth-child(2) th {
            top: 50px; /* Stick below the filter row */
            background-color: #2c3e50; /* Match the header background */
        }

        .user-table td {
            text-align: left;
            padding: 6px;
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

        /* Button Styles */
        .btn-primary, .btn-secondary, .btn-danger, .btn-warning, .btn-info {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            margin-right: 5px;
            display: inline-block;
        }

        .btn-primary { background-color: green; }
        .btn-secondary { background-color: #6c757d; }
        .btn-danger { background-color: #dc3545; }
        .btn-warning { background-color: #3498db; color: black; }
        .btn-info { background-color: #17a2b8; }

        /* Header Styles */
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

        /* Search Bar Styles */
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
        <h2 class="leadfor">Party Ledger Records</h2>
        <div class="lead-actions">
            <!-- <select id="sortFilter" class="filter-select">
                <option value="all">All Records</option>
                <option value="monthly">This Month</option>
                <option value="quarterly">Last 3 Months</option>
                <option value="yearly">Last 1 Year</option>
            </select> -->
            <input type="text" id="globalSearch" class="filter-input" placeholder="Search all records...">
            <input type="date" id="startDateFilter" class="date-filter">
            <input type="date" id="endDateFilter" class="date-filter">
            <button id="downloadExcel" class="btn-primary">  <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px; "></button>
        </div>
    </div>

    <div class="user-table-wrapper">
    <table class="user-table" id="ledgerTable">
        <thead>
            <!-- Filter Row -->
            <tr>
                <th><input type="text" id="idFilter" class="filter-input" placeholder="Search ID"></th>
                <th><select id="ledgerTypeFilter" class="filter-select">
                    <option value="all">All</option>
                    <option value="Customer Ledger">Customer Ledger</option>
                    <option value="Vendor Ledger">Vendor Ledger</option>
                </select></th>
                <th><select id="partyTypeFilter" class="filter-select">
                    <option value="all">All</option>
                    <option value="Customer">Customer</option>
                    <option value="Vendor">Vendor</option>
                </select></th>
                <th><input type="text" id="partyNoFilter" class="filter-input" placeholder="Search..."></th>
                <th><input type="text" id="partyNameFilter" class="filter-input" placeholder="Search..."></th>
                <th><select id="documentTypeFilter" class="filter-select">
                    <option value="all">All</option>
                    <option value="Sales Invoice">Sales Invoice</option>
                    <option value="Purchase Invoice">Purchase Invoice</option>
                    <option value="Payment Received">Payment Received</option>
                    <option value="Payment Paid">Payment Paid</option>
                </select></th>
                <th><input type="text" id="documentNoFilter" class="filter-input" placeholder="Search..."></th>
                <th><input type="text" id="amountFilter" class="filter-input" placeholder="Search..."></th>
                <th><input type="text" id="refDocNoFilter" class="filter-input" placeholder="Search..."></th>
                <th></th>
                <th></th> <!-- Empty for actions column -->
            </tr>

            <!-- Table Headings Row -->
            <tr>
                <th>Id</th>
                <th>Ledger Type</th>
                <th>Party Type</th>
                <th>Party No</th>
                <th>Party Name</th>
                <th>Document Type</th>
                <th>Document No</th>
                <th>Amount</th>
                <th>Reference Doc. No</th>
                <th>Transaction Date</th>
                <th>Actions</th>
            </tr>
        </thead>
            <tbody>
              <?php
              include('connection.php');
              $query = "SELECT * FROM party_ledger";
              $result = mysqli_query($connection, $query);

              if (mysqli_num_rows($result) > 0) {
                  while ($row = mysqli_fetch_assoc($result)) {
                      $amount = $row['amount'];
                      $amount_style = ($amount > 0) ? "style='font-weight: bold; color: green;'" : "style='font-weight: bold; color: red;'";

                      // Convert the date format to display properly
                      $transactionDate = date("d-m-Y H:i:s", strtotime($row['date'])); // Format: DD-MM-YYYY HH:MM:SS

                      echo "<tr>
                              <td>{$row['id']}</td>
                              <td>{$row['ledger_type']}</td>
                              <td>{$row['party_type']}</td>
                              <td>{$row['party_no']}</td>
                              <td>{$row['party_name']}</td>
                              <td>{$row['document_type']}</td>
                              <td>{$row['document_no']}</td>
                              <td $amount_style>{$amount}</td>
                              <td>{$row['ref_doc_no']}</td>
                              <td>{$transactionDate}</td>
                              <td>
                                  <button class='btn-secondary' onclick=\"window.location.href='ledger_details.php?id={$row['id']}'\">📜 View</button>
                              </td>
                          </tr>";
                  }
              } else {
                  echo "<tr><td colspan='11'>No records found</td></tr>";
              }
              ?>

            </tbody>
        </table>
    </div>

    <!-- Include SheetJS library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
    document.getElementById("downloadExcel").addEventListener("click", function() {
        let table = document.getElementById("ledgerTable");

        // Clone the table to avoid modifying the original
        let clonedTable = table.cloneNode(true);

        // Remove the first row (filter row)
        clonedTable.deleteRow(0);

        let wb = XLSX.utils.book_new();
        let ws = XLSX.utils.table_to_sheet(clonedTable, { raw: true });

        XLSX.utils.book_append_sheet(wb, ws, "Ledger Records");
        XLSX.writeFile(wb, "Party_Ledger_Records.xlsx");
    });

    </script>

      <script>
      document.addEventListener('DOMContentLoaded', function () {
          function filterTable() {
              const searchQuery = document.getElementById('globalSearch').value.toLowerCase();
              const ledgerType = document.getElementById('ledgerTypeFilter').value;
              const partyType = document.getElementById('partyTypeFilter').value;
              const documentType = document.getElementById('documentTypeFilter').value;
              const partyNo = document.getElementById('partyNoFilter').value.toLowerCase();
              const partyName = document.getElementById('partyNameFilter').value.toLowerCase();
              const documentNo = document.getElementById('documentNoFilter').value.toLowerCase();
              const amount = document.getElementById('amountFilter').value.toLowerCase();
              const refDocNo = document.getElementById('refDocNoFilter').value.toLowerCase();
              const startDate = document.getElementById('startDateFilter').value;
              const endDate = document.getElementById('endDateFilter').value;

              document.querySelectorAll('.user-table tbody tr').forEach(row => {
                  const rowText = row.innerText.toLowerCase(); // Get all text from the row
                  const ledgerTypeText = row.children[1].textContent.trim();
                  const partyTypeText = row.children[2].textContent.trim();
                  const partyNoText = row.children[3].textContent.trim().toLowerCase();
                  const partyNameText = row.children[4].textContent.trim().toLowerCase();
                  const documentTypeText = row.children[5].textContent.trim();
                  const documentNoText = row.children[6].textContent.trim().toLowerCase();
                  const amountText = row.children[7].textContent.trim().toLowerCase();
                  const refDocNoText = row.children[8].textContent.trim().toLowerCase();
                  const rowDateText = row.children[9].textContent.trim();

                  let rowDate = rowDateText ? new Date(rowDateText.split(' ')[0].split('-').reverse().join('-')) : null;
                  let start = startDate ? new Date(startDate) : null;
                  let end = endDate ? new Date(endDate) : null;
                  if (end) end.setHours(23, 59, 59, 999); // Include the full end date

                  let dateMatch = true;
                  if (start && end) {
                      dateMatch = rowDate && rowDate >= start && rowDate <= end;
                  } else if (start) {
                      dateMatch = rowDate && rowDate >= start;
                  } else if (end) {
                      dateMatch = rowDate && rowDate <= end;
                  }

                  let showRow = (ledgerType === 'all' || ledgerTypeText === ledgerType) &&
                                (partyType === 'all' || partyTypeText === partyType) &&
                                (documentType === 'all' || documentTypeText === documentType) &&
                                (partyNo === '' || partyNoText.includes(partyNo)) &&
                                (partyName === '' || partyNameText.includes(partyName)) &&
                                (documentNo === '' || documentNoText.includes(documentNo)) &&
                                (amount === '' || amountText.includes(amount)) &&
                                (refDocNo === '' || refDocNoText.includes(refDocNo)) &&
                                dateMatch &&
                                (searchQuery === '' || rowText.includes(searchQuery)); // Global search check

                  row.style.display = showRow ? '' : 'none';
              });
          }

          document.querySelectorAll('.filter-input, .filter-select').forEach(input => {
              input.addEventListener('input', filterTable);
          });

          document.getElementById('startDateFilter').addEventListener('change', filterTable);
          document.getElementById('endDateFilter').addEventListener('change', filterTable);
      });

      </script>
  </body>


</html>
