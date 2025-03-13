<?php
session_start();
include('connection.php');
include('topbar.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];

// Step 1: Fetch Allowed FY Codes
$fy_codes = [];
$fy_query = "SELECT fy_code FROM emp_fy_permission WHERE emp_id = ? AND permission = 1";
$stmt = $connection->prepare($fy_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $fy_codes[] = $row['fy_code'];
}

// Step 2: Fetch Purchase Invoice Records
if (!empty($fy_codes)) {
    // Convert the fy_codes array to a comma-separated string for the SQL IN clause
    $fy_codes_string = implode("','", $fy_codes);
    $query = "SELECT id, invoice_no, purchase_order_no, vendor_name, invoice_date, gross_amount, discount, net_amount, pending_amount
              FROM purchase_invoice
              WHERE status = 'Finalized' AND fy_code IN ('$fy_codes_string')
              ORDER BY id DESC"; // Added ORDER BY id DESC to sort results in descending order
} else {
    // If no fy_codes, set query to an empty result
    $query = "SELECT * FROM purchase_invoice_items WHERE 0"; // Returns no results
}

$result = mysqli_query($connection, $query);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Purchase Invoice Display</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
    <style>
        /* Your existing CSS styles */
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
            min-height: 100%; /* Ensures it doesn't shrink too much */
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
            padding: 7px;
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

        #downloadExcel {
            background-color: green;
        }
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2 class="leadfor">Purchase Invoices</h2>
        <div class="lead-actions">
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search...">
                <button class="btn-search" id="searchButton">🔍</button>
            </div>
            <a href="purchase_invoice.php">
    <button class="btn-primary" id="openModal" data-mode="add" title="Add New Purchase Invoice">➕</button>
</a>
<button id="downloadExcel" class="btn-primary" title="Export to Excel">
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
                  <th>Purchase Order</th>
                  <th>Vendor Name</th>
                  <th>Invoice Date</th>
                  <th>Gross Amount</th>
                  <th>Discount</th>
                  <th>Net Amount</th>
                  <th>Pending Amount</th>
                  <th>Actions</th>
              </tr>
          </thead>
          <tbody>
              <?php
              if (mysqli_num_rows($result) > 0) {
                  while ($row = mysqli_fetch_assoc($result)) {
                      $pendingAmount = $row['pending_amount'];
                      $buttonClass = ($pendingAmount == 0) ? 'paid-button' : 'btn-danger';
                      $buttonText = ($pendingAmount == 0) ? 'PAID' : $pendingAmount;
                      $disabled = ($pendingAmount == 0) ? 'disabled' : '';

                      echo "<tr>
                              <td>{$row['id']}</td>
                              <td>{$row['invoice_no']}</td>
                              <td>{$row['purchase_order_no']}</td> <!-- Corrected this line -->
                              <td>{$row['vendor_name']}</td>
                              <td>{$row['invoice_date']}</td>
                              <td>{$row['gross_amount']}</td>
                              <td>{$row['discount']}</td>
                              <td>{$row['net_amount']}</td>
                              <td>
                                  <button title='Pay Amount' class='{$buttonClass} pending-button' data-id='{$row['id']}' data-net='{$row['net_amount']}' data-pending='{$row['pending_amount']}' {$disabled}>
                                      {$buttonText}
                                  </button>
                              </td>
                              <td>
                                  <button class='btn-secondary' title='Print this Invoice' onclick=\"window.location.href='purchase_invoice_view.php?id={$row['id']}'\">🖨️</button>
                                  <button class='btn-secondary' title='Return this Invoice' onclick=\"window.location.href='purchase_invoice_close.php?id={$row['id']}'\">⛔</button>
                              </td>
                          </tr>";
                  }
              } else {
                  echo "<tr><td colspan='10' style='text-align: center;'>No records found</td></tr>";
              }
              ?>
          </tbody>
      </table>

    </div>

    <!-- Popup for Pending Amount -->
    <div class="overlay" id="overlay"></div>
    <div class="popup" id="popup">
      <h3>Update Pending Amount</h3>
      <p>Net Amount: <span id="popup-net"></span></p>
      <p>Pending Amount: <span id="popup-pending"></span></p>
      <input type="number" id="amount-paid" placeholder="Enter Amount Paid">
      <button id="submit-payment">Submit</button>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('.user-table tbody tr');
    const downloadExcelButton = document.getElementById('downloadExcel');

    // Search functionality
    searchInput.addEventListener('keyup', function () {
        const searchTerm = searchInput.value.toLowerCase();

        tableRows.forEach(function (row) {
            const cells = row.querySelectorAll('td');
            let rowText = '';

            cells.forEach(function (cell, index) {
                // Skip last column (action buttons)
                if (index !== cells.length - 1) {
                    rowText += cell.textContent.toLowerCase() + ' ';
                }
            });

            row.style.display = rowText.includes(searchTerm) ? '' : 'none';
        });
    });

    // Excel download functionality
    downloadExcelButton.addEventListener('click', function () {
        const table = document.querySelector('.user-table');
        const visibleRows = Array.from(table.querySelectorAll('tbody tr')).filter(row => row.style.display !== 'none');

        // Create a new workbook and worksheet
        const workbook = XLSX.utils.book_new();
        const worksheetData = [];

        // Add header row, excluding last column
        const headerRow = [];
        table.querySelectorAll('thead th').forEach((header, index, arr) => {
            if (index !== arr.length - 1) {
                headerRow.push(header.textContent);
            }
        });
        worksheetData.push(headerRow);

        // Add visible rows, excluding last column
        visibleRows.forEach(row => {
            const rowData = [];
            row.querySelectorAll('td').forEach((cell, index, arr) => {
                if (index !== arr.length - 1) { // Skip last column
                    rowData.push(cell.textContent);
                }
            });
            worksheetData.push(rowData);
        });

        // Convert data to worksheet
        const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Purchase Invoices');

        // Export the workbook as an Excel file
        XLSX.writeFile(workbook, 'Finalized_Purchase_Invoices.xlsx');
    });

    // Pending Amount Button Click
    const pendingButtons = document.querySelectorAll('.pending-button');
    const popup = document.getElementById('popup');
    const overlay = document.getElementById('overlay');
    const popupNet = document.getElementById('popup-net');
    const popupPending = document.getElementById('popup-pending');
    const amountPaidInput = document.getElementById('amount-paid');
    const submitPaymentButton = document.getElementById('submit-payment');

    let currentId = null;

    pendingButtons.forEach(button => {
        button.addEventListener('click', function () {
            currentId = this.getAttribute('data-id');
            const netAmount = this.getAttribute('data-net');
            const pendingAmount = this.getAttribute('data-pending'); // Fetch from data attribute

            popupNet.textContent = netAmount;
            popupPending.textContent = pendingAmount;
            amountPaidInput.value = '';
            popup.style.display = 'block';
            overlay.style.display = 'block';
        });
    });

    // Submit Payment
    submitPaymentButton.addEventListener('click', function () {
        const amountPaid = parseFloat(amountPaidInput.value);
        const pendingAmount = parseFloat(popupPending.textContent);

        if (isNaN(amountPaid) || amountPaid <= 0 || amountPaid > pendingAmount) {
            alert('Please enter a valid amount.');
            return;
        }

        const newPendingAmount = pendingAmount - amountPaid;

        // Update database via AJAX
        fetch('update_purchase_pending_amount.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: currentId,
                amount_paid: amountPaid,
                new_pending_amount: newPendingAmount
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the button text and style
                    const button = document.querySelector(`.pending-button[data-id='${currentId}']`);
                    if (newPendingAmount === 0) {
                        button.textContent = 'PAID';
                        button.classList.remove('btn-danger'); // Remove red class
                        button.classList.add('paid-button'); // Add green paid class
                        button.disabled = true; // Disable button
                    } else {
                        button.textContent = newPendingAmount;
                        button.setAttribute('data-pending', newPendingAmount);
                    }

                    // Close popup
                    popup.style.display = 'none';
                    overlay.style.display = 'none';
                } else {
                    alert('Failed to update pending amount.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the pending amount.');
            });
    });

    // Close popup when clicking outside
    overlay.addEventListener('click', function () {
        popup.style.display = 'none';
        overlay.style.display = 'none';
    });
});

    </script>
  </body>
</html>
