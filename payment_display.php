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
    <title>Payment Display</title>
    <style>
        /* Table Styles */
        html, body {
            overflow: hidden;
            height: 100%;
            margin: 0;
        }
        .user-table-wrapper {
            width: calc(100% - 260px);
            margin-left: 260px;
            margin-top: 140px;
            max-height: calc(100vh - 150px);
            overflow-y: auto;
            border: 1px solid #ddd;
            background-color: white;
        }
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
            border: 1px solid #ddd;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .user-table th {
          padding: 10px;
            background-color: #2c3e50;
            color: white;
            text-align: left;
            position: sticky;
            z-index: 1;
        }
        .user-table thead tr:first-child th {
            top: 0;
            background-color: #2c3e50;
        }
        .user-table thead tr:nth-child(2) th {
            top: 50px;
            background-color: #2c3e50;
        }
        .user-table td {
            text-align: left;
            padding: 7px;
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
        }
        .glow-red {
            color: red;
            font-weight: bold;
            text-shadow: 0px 0px 0px red;
        }
        .date-filter {
            width: 120px;
            padding: 7px;
            font-size: 14px;
        }
        .filter-input, .filter-select {
            width: 100%;
            padding: 6px;
            box-sizing: border-box;
              border-radius: 6px;
        }
        #downloadExcel {
            background-color: green;
        }
        /* Payment Details Tooltip */
        .user-table td:nth-child(10) { /* Payment Details column */
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            position: relative;
        }
        .user-table td:nth-child(10):hover::after {
            content: attr(data-full-text);
            position: absolute;
            left: 0;
            top: 100%;
            background: #333;
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            white-space: normal;
            max-width: 300px;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
    </style>
  </head>
  <body>
    <div class="leadforhead">
        <h2 class="leadfor">Payment Records</h2>
        <div class="lead-actions">
            <input type="text" id="globalSearch" class="filter-input" placeholder="Search all records...">
            <input type="date" id="startDateFilter" class="date-filter">
            <input type="date" id="endDateFilter" class="date-filter">
            <button id="downloadExcel" title="Download Excel File" class="btn-primary">
                <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px; ">
            </button>
        </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table" id="ledgerTable">
          <thead>
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
                  <th><input type="text" id="companyNameFilter" class="filter-input" placeholder="Search..."></th>
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
                  <th><input type="text" id="paymentMethodFilter" class="filter-input" placeholder="Search..."></th>
                  <th><input type="text" id="paymentDetailsFilter" class="filter-input" placeholder="Search..."></th>
                  <th><input type="text" id="paymentDateFilter" class="filter-input" placeholder="Search..."></th>
                  <th></th> <!-- Empty for transaction date column -->
              </tr>
              <tr>
                  <th>Id</th>
                  <th>Ledger Type</th>
                  <th>Party Type</th>
                  <th>Party No</th>
                  <th>Party Name</th>
                  <th>Company Name</th>
                  <th>Document Type</th>
                  <th>Document No</th>
                  <th>Amount</th>
                  <th>Advance Doc. No</th>
                  <th>Payment Method</th>
                  <th>Payment Details</th>
                  <th>Payment Date</th>
                  <th>Transaction Date</th>
              </tr>
          </thead>
          <tbody>
      <?php
      $query = "
          SELECT
              ap.*,
              c.company_name
          FROM
              advance_payments ap
          LEFT JOIN
              contact c ON ap.party_no = c.id
          ORDER BY
              ap.id DESC
      ";
      $result = mysqli_query($connection, $query);
      if (mysqli_num_rows($result) > 0) {
          while ($row = mysqli_fetch_assoc($result)) {
              $amount = $row['amount'];
              $displayAmount = $amount;
              $color = ($amount >= 0) ? 'green' : 'red';
              $amount_style = "style='font-weight: bold; color: $color;'";
              $paymentDate = !empty($row['payment_date']) ? date("d-m-Y", strtotime($row['payment_date'])) : '';
              $transactionDate = date("d-m-Y H:i:s", strtotime($row['date']));
              $document_no = $row['document_no'];
              $party_no = $row['party_no'];
              if (strpos($document_no, 'PVR') === 0) {
                  $redirect_url = 'advance_ledger_details.php';
                  $redirectUrlWithParams = "{$redirect_url}?advance_doc_no=" . urlencode($document_no) . "&party_no=" . urlencode($party_no);
              } elseif (strpos($document_no, 'PVP') === 0 && $row['party_type'] === 'Vendor') {
                  $redirect_url = 'advance_vendor_ledger_details.php';
                  $redirectUrlWithParams = "{$redirect_url}?advance_doc_no=" . urlencode($document_no) . "&party_no=" . urlencode($party_no);
              } else {
                  $redirect_url = ($row['party_type'] === 'Vendor') ? 'vendor_ledger_details.php' : 'ledger_details.php';
                  $redirectUrlWithParams = "{$redirect_url}?document_no=" . urlencode($document_no) . "&party_no=" . urlencode($party_no);
              }
              echo "<tr ondblclick=\"window.location.href='{$redirectUrlWithParams}'\" style='cursor: pointer;'>
                      <td>{$row['id']}</td>
                      <td>{$row['ledger_type']}</td>
                      <td>{$row['party_type']}</td>
                      <td>{$row['party_no']}</td>
                      <td>{$row['party_name']}</td>
                      <td>{$row['company_name']}</td>
                      <td>{$row['document_type']}</td>
                      <td>{$document_no}</td>
                      <td $amount_style>" . number_format($displayAmount, 2) . "</td>
                      <td>{$row['advance_doc_no']}</td>
                      <td>{$row['payment_method']}</td>
                      <td data-full-text='" . htmlspecialchars($row['payment_details']) . "' title='" . htmlspecialchars($row['payment_details']) . "'>
                          " . (strlen($row['payment_details']) > 20 ? substr(htmlspecialchars($row['payment_details']), 0, 20) . '...' : htmlspecialchars($row['payment_details'])) . "
                      </td>
                      <td>{$paymentDate}</td>
                      <td>{$transactionDate}</td>
                    </tr>";
          }
      } else {
          echo "<tr><td colspan='14' style='text-align: center;'>No records found</td></tr>";
      }
      ?>
          </tbody>
      </table>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.getElementById("downloadExcel").addEventListener("click", function() {
            let table = document.getElementById("ledgerTable");
            // Clone the table
            let clonedTable = table.cloneNode(true);
            // Remove the filter row
            clonedTable.deleteRow(0);

            // Replace truncated text with full text for Payment Details column
            let paymentDetailsCells = clonedTable.querySelectorAll('td[data-full-text]');
            paymentDetailsCells.forEach(cell => {
                cell.textContent = cell.getAttribute('data-full-text');
            });

            // Extract data from the table
            let data = [];
            let rows = clonedTable.querySelectorAll('tr');
            rows.forEach(row => {
                let rowData = [];
                let cells = row.querySelectorAll('td');
                cells.forEach((cell, index) => {
                    let cellText = cell.textContent.trim();

                    // Format Amount column (index 8) as number
                    if (index === 8) {
                        rowData.push(parseFloat(cellText.replace(/,/g, ''))); // Remove commas and parse as float
                    }
                    // Format Payment Date (index 12) and Transaction Date (index 13) as Excel dates
                    else if (index === 12 || index === 13) {
                        if (cellText) {
                            // Parse date string (e.g., "25-09-2025" or "25-09-2025 14:30:00")
                            let dateParts = cellText.split(' ')[0].split('-');
                            if (dateParts.length === 3) {
                                // Excel dates are serial numbers starting from 1/1/1900
                                let excelDate = new Date(`${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`);
                                rowData.push(excelDate);
                            } else {
                                rowData.push(cellText);
                            }
                        } else {
                            rowData.push(null);
                        }
                    }
                    // All other columns as text
                    else {
                        rowData.push(cellText);
                    }
                });
                data.push(rowData);
            });

            // Create worksheet with explicit column types
            let ws = XLSX.utils.aoa_to_sheet(data);

            // Set column types (1=text, 2=number, 3=date)
            ws['!cols'] = [
                { wch: 10 }, // ID
                { wch: 20 }, // Ledger Type
                { wch: 15 }, // Party Type
                { wch: 15 }, // Party No
                { wch: 25 }, // Party Name
                { wch: 25 }, // Company Name
                { wch: 20 }, // Document Type
                { wch: 20 }, // Document No
                { wch: 15, type: 'n' }, // Amount (number)
                { wch: 20 }, // Advance Doc. No
                { wch: 20 }, // Payment Method
                { wch: 30 }, // Payment Details
                { type: 'd', wch: 15 }, // Payment Date (date)
                { type: 'd', wch: 25 }  // Transaction Date (date)
            ];

            // Create workbook and export
            let wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Payment Records");
            XLSX.writeFile(wb, "Advance_Payment_Records.xlsx");
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
                const companyName = document.getElementById('companyNameFilter').value.toLowerCase();
                const documentNo = document.getElementById('documentNoFilter').value.toLowerCase();
                const amount = document.getElementById('amountFilter').value.toLowerCase();
                const refDocNo = document.getElementById('refDocNoFilter').value.toLowerCase();
                const startDate = document.getElementById('startDateFilter').value;
                const endDate = document.getElementById('endDateFilter').value;
                document.querySelectorAll('.user-table tbody tr').forEach(row => {
                    const rowText = row.innerText.toLowerCase();
                    const ledgerTypeText = row.children[1].textContent.trim();
                    const partyTypeText = row.children[2].textContent.trim();
                    const partyNoText = row.children[3].textContent.trim().toLowerCase();
                    const partyNameText = row.children[4].textContent.trim().toLowerCase();
                    const companyNameText = row.children[5].textContent.trim().toLowerCase();
                    const documentTypeText = row.children[6].textContent.trim();
                    const documentNoText = row.children[7].textContent.trim().toLowerCase();
                    const amountText = row.children[8].textContent.trim().toLowerCase();
                    const refDocNoText = row.children[9].textContent.trim().toLowerCase();
                    const paymentDateText = row.children[12].textContent.trim(); // Payment Date is now column 12
                    // Parse payment date for filtering
                    let rowDate = null;
                    if (paymentDateText) {
                        // Convert dd-mm-yyyy to yyyy-mm-dd for Date parsing
                        const dateParts = paymentDateText.split('-');
                        if (dateParts.length === 3) {
                            rowDate = new Date(dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0]);
                        }
                    }
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
                                  (companyName === '' || companyNameText.includes(companyName)) &&
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
