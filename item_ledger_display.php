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
<link rel="icon" type="image/png" href="favicon.png">
    <title>Item Ledger Display</title>
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
        max-height: calc(100vh - 150px); /* Adjust based on your layout */
        overflow-y: auto; /* Enable vertical scrolling */
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
            width: calc(100% - 290px); /* Adjust width to account for sidebar*/
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

        /* Filter Styles */
        .filter-select {
            padding: 8px;
            border-radius: 10px;
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
            width: 120px;
        }

        .filter-input {
            width: 100%;
            padding: 6px;
            box-sizing: border-box;
            border-radius: 6px;
        }
    </style>
  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">Item Ledger</h2>
      <div class="lead-actions">
        <input type="text" id="globalSearch" class="filter-input" placeholder="Search all records...">
        <input type="date" id="startDateFilter" class="date-filter">
        <input type="date" id="endDateFilter" class="date-filter">
        <button id="downloadExcel" class="btn-primary" title="Export to Excel">
          <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;">
        </button>
      </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table" id="ledgerTable">
    <thead>
        <!-- Filter Row -->
        <tr>
            <th><input type="text" id="idFilter" class="filter-input" placeholder="Search ID"></th>
            <th><input type="text" id="documentNoFilter" class="filter-input" placeholder="Search..."></th>
            <th><select id="documentTypeFilter" class="filter-select">
                <option value="all">All</option>
                <option value="Sales Invoice">Sales Invoice</option>
                <option value="Purchase Invoice">Purchase Invoice</option>
                <option value="Transfer">Transfer</option>
                <option value="Adjustment">Adjustment</option>
            </select></th>
            <th><select id="entryTypeFilter" class="filter-select">
                <option value="all">All</option>
                <option value="Purchase">Purchase</option>
                <option value="Sale">Sale</option>
                <option value="Positive Adjmt.">Positive Adjmt.</option>
                <option value="Negative Adjmt.">Negative Adjmt.</option>
                <option value="Transfer">Transfer</option>
            </select></th>
            <th><input type="text" id="productIdFilter" class="filter-input" placeholder="Search..."></th>
            <th><input type="text" id="productNameFilter" class="filter-input" placeholder="Search..."></th>
            <th><input type="text" id="quantityFilter" class="filter-input" placeholder="Search..."></th>
            <th><input type="text" id="locationFilter" class="filter-input" placeholder="Search..."></th>
            <th><input type="text" id="unitFilter" class="filter-input" placeholder="Search..."></th>
            <th><input type="text" id="dateFilter" class="filter-input" placeholder="Search..."></th>
            <th><input type="text" id="lotIdFilter" class="filter-input" placeholder="Search..."></th>
            <th><input type="text" id="expiryFilter" class="filter-input" placeholder="Search..."></th>
        </tr>

        <!-- Table Headings Row -->
        <tr>
            <th>Id</th>
            <th>Document No</th>
            <th>Document Type</th>
            <th>Entry Type</th>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Quantity</th>
            <th>Location</th>
            <th>Unit</th>
            <th>Date</th>
            <th>Lot ID</th>
            <th>Expiration Date</th>
        </tr>
    </thead>
    <tbody>
    <?php
    // Update the query to match your new table name
    $query = "SELECT * FROM item_ledger_history ORDER BY id DESC";

    $result = mysqli_query($connection, $query);
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Determine the color based on the quantity value
            $quantity_color = ($row['quantity'] < 0) ? 'red' : 'green';

            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['invoice_no']}</td>
                    <td>{$row['document_type']}</td>
                    <td>{$row['entry_type']}</td>
                    <td>{$row['product_id']}</td>
                    <td>{$row['product_name']}</td>
                    <td style='color: $quantity_color; font-weight: bold; font-size: 16px;'>{$row['quantity']}</td>
                    <td>{$row['location']}</td>
                    <td>{$row['unit']}</td>
                    <td>{$row['date']}</td>
                    <td>{$row['lot_trackingid']}</td>
                    <td>{$row['expiration_date']}</td>
                </tr>";
            }
        } else {
             echo "<tr><td colspan='12' style='text-align: center;'>No records found</td></tr>";
        }
        ?>
    </tbody>
</table>

<script>
  document.addEventListener('DOMContentLoaded', function() {
      function filterTable() {
          const searchQuery = document.getElementById('globalSearch').value.toLowerCase();
          const idFilter = document.getElementById('idFilter').value.toLowerCase();
          const documentNoFilter = document.getElementById('documentNoFilter').value.toLowerCase();
          const documentTypeFilter = document.getElementById('documentTypeFilter').value;
          const entryTypeFilter = document.getElementById('entryTypeFilter').value;
          const productIdFilter = document.getElementById('productIdFilter').value.toLowerCase();
          const productNameFilter = document.getElementById('productNameFilter').value.toLowerCase();
          const quantityFilter = document.getElementById('quantityFilter').value.toLowerCase();
          const locationFilter = document.getElementById('locationFilter').value.toLowerCase();
          const unitFilter = document.getElementById('unitFilter').value.toLowerCase();
          const lotIdFilter = document.getElementById('lotIdFilter').value.toLowerCase();
          const expiryFilter = document.getElementById('expiryFilter').value.toLowerCase();
          const startDate = document.getElementById('startDateFilter').value;
          const endDate = document.getElementById('endDateFilter').value;

          document.querySelectorAll('.user-table tbody tr').forEach(row => {
              const rowText = row.innerText.toLowerCase();
              const cells = row.querySelectorAll('td');

              const idText = cells[0].textContent.toLowerCase();
              const docNoText = cells[1].textContent.toLowerCase();
              const docTypeText = cells[2].textContent.trim();
              const entryTypeText = cells[3].textContent.trim();
              const productIdText = cells[4].textContent.toLowerCase();
              const productNameText = cells[5].textContent.toLowerCase();
              const quantityText = cells[6].textContent.toLowerCase();
              const locationText = cells[7].textContent.toLowerCase();
              const unitText = cells[8].textContent.toLowerCase();
              const dateText = cells[9].textContent.trim();
              const lotIdText = cells[10].textContent.toLowerCase();
              const expiryText = cells[11].textContent.toLowerCase();

              let rowDate = dateText ? new Date(dateText.split(' ')[0]) : null;
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

              let showRow =
                  (idFilter === '' || idText.includes(idFilter)) &&
                  (documentNoFilter === '' || docNoText.includes(documentNoFilter)) &&
                  (documentTypeFilter === 'all' || docTypeText === documentTypeFilter) &&
                  (entryTypeFilter === 'all' || entryTypeText === entryTypeFilter) &&
                  (productIdFilter === '' || productIdText.includes(productIdFilter)) &&
                  (productNameFilter === '' || productNameText.includes(productNameFilter)) &&
                  (quantityFilter === '' || quantityText.includes(quantityFilter)) &&
                  (locationFilter === '' || locationText.includes(locationFilter)) &&
                  (unitFilter === '' || unitText.includes(unitFilter)) &&
                  (lotIdFilter === '' || lotIdText.includes(lotIdFilter)) &&
                  (expiryFilter === '' || expiryText.includes(expiryFilter)) &&
                  dateMatch &&
                  (searchQuery === '' || rowText.includes(searchQuery));

              row.style.display = showRow ? '' : 'none';
          });
      }

      // Add event listeners to all filter inputs
      document.querySelectorAll('.filter-input, .filter-select').forEach(input => {
          input.addEventListener('input', filterTable);
      });

      document.getElementById('startDateFilter').addEventListener('change', filterTable);
      document.getElementById('endDateFilter').addEventListener('change', filterTable);

      // Download Excel
      const downloadExcelButton = document.getElementById('downloadExcel');
      downloadExcelButton.addEventListener('click', function() {
          let table = document.getElementById('ledgerTable');
          // Clone the table to avoid modifying the original
          let clonedTable = table.cloneNode(true);
          // Remove the first row (filter row)
          clonedTable.deleteRow(0);

          let wb = XLSX.utils.book_new();
          let ws = XLSX.utils.table_to_sheet(clonedTable, { raw: true });
          XLSX.utils.book_append_sheet(wb, ws, "Item Ledger");
          XLSX.writeFile(wb, "Item_Ledger_Records.xlsx");
      });
  });
</script>

</html>
