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

// Step 2: Fetch Invoice Records
if (!empty($fy_codes)) {
    $fy_codes_string = implode("','", $fy_codes);
    $query = "SELECT id, invoice_no, quotation_no, client_name, invoice_date, gross_amount, discount, net_amount, pending_amount
              FROM invoices_cancel
              WHERE status = 'Finalized' AND fy_code IN ('$fy_codes_string')
              ORDER BY
                  CAST(SUBSTRING_INDEX(invoice_no, '/', -1) AS UNSIGNED) DESC,
                  invoice_date DESC";
} else {
    $query = "SELECT * FROM invoices_cancel WHERE 0";
}
$result = mysqli_query($connection, $query);
?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <title>Invoice Display</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
    <style>
        /* Your existing CSS styles */
        html, body {
            overflow: hidden;
            height: 100%;
            margin: 0;
        }

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
            top: 40%;
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

        /* Overlay */
.overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: none;
}

/* Popup Container */
.popup {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 400px;
  max-width: 90%;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  z-index: 1001;
  display: none;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Popup Header */
.popup-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #eee;
}

.popup-header h3 {
  margin: 0;
  font-size: 18px;
  color: #333;
  font-weight: 600;
}

.close-btn {
  font-size: 24px;
  cursor: pointer;
  color: #777;
  transition: color 0.2s;
}

.close-btn:hover {
  color: #333;
}

/* Popup Body */
.popup-body {
  padding: 20px;
}

.amount-display {
  margin-bottom: 20px;
  background-color: #f9f9f9;
  padding: 15px;
  border-radius: 6px;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.amount-row:last-child {
  margin-bottom: 0;
}

.amount-label {
  color: #666;
  font-weight: 500;
}

.amount-value {
  color: #333;
  font-weight: 600;
}

/* Form Elements */
.form-group {
  margin-bottom: 18px;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  color: #555;
  font-size: 14px;
  font-weight: 500;
}

.form-input, .form-select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  transition: border-color 0.3s;
  box-sizing: border-box;
}

.form-input:focus, .form-select:focus {
  outline: none;
  border-color: #4a90e2;
  box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
}

/* Popup Footer */
.popup-footer {
  padding: 0 20px 20px;
  text-align: right;
}

.submit-btn {
  background-color: #4a90e2;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.submit-btn:hover {
  background-color: #3a7bc8;
}

.submit-btn:active {
  background-color: #2c6bb7;
}
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2 class="leadfor">Sale Cancel Invoices</h2>
        <div class="lead-actions">
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search...">
                <button class="btn-search" id="searchButton">🔍</button>
            </div>
            <a href="invoice_cancel_generate.php">
    <button class="btn-primary" id="openModal" data-mode="add" title="Add New Invoice">➕</button>
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
            <!-- <th>Id</th> -->
            <th>Invoice No</th>
            <th>Quotation No</th>
            <th>Client Name</th>
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

                echo "<tr ondblclick=\"window.location.href='sale_invoice_cancel_display.php?id={$row['id']}'\" style='cursor: pointer;'>


                        <td>{$row['invoice_no']}</td>
                        <td>{$row['quotation_no']}</td>
                        <td>{$row['client_name']}</td>
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
                            <button class='btn-secondary' title='Print this Invoice' onclick=\"window.location.href='sale_invoice_cancel_display.php?id={$row['id']}'\">🖨️</button>
                            <button class='btn-secondary' title='Return this Invoice' onclick=\"window.location.href='invoice_cancel.php?id={$row['id']}'\">⛔</button>
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
  <div class="popup-header">
    <h3>Update Pending Amount</h3>
    <div class="close-btn" id="close-popup">&times;</div>
  </div>

  <div class="popup-body">
    <div class="amount-display">
      <div class="amount-row">
        <span class="amount-label">Net Amount:</span>
        <span class="amount-value" id="popup-net"></span>
      </div>
      <div class="amount-row">
        <span class="amount-label">Pending Amount:</span>
        <span class="amount-value" id="popup-pending"></span>
      </div>
    </div>

    <div class="form-group">
      <input type="number" id="amount-paid" class="form-input" placeholder="Enter Amount Paid" min="0" step="0.01">
    </div>

    <div class="form-group">
      <label for="payment-method" class="form-label">Payment Method</label>
      <select id="payment-method" class="form-select">
        <option value="Cash">Cash</option>
        <option value="Cheque">Cheque</option>
        <option value="UPI">UPI</option>
        <option value="Draft">Draft</option>
        <option value="Others">Others</option>
      </select>
    </div>

    <div class="form-group">
      <label for="payment-date" class="form-label">Payment Date</label>
      <input type="date" id="payment-date" class="form-input" value="<?php echo date('Y-m-d'); ?>">
    </div>

    <div class="form-group">
      <label for="payment-details" class="form-label">Payment Details</label>
      <input type="text" id="payment-details" class="form-input" placeholder="Enter details (Cheque No., UPI ID, etc.)">
    </div>
  </div>

  <div class="popup-footer">
    <button id="submit-payment" class="submit-btn">Submit Payment</button>
  </div>
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
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Invoices');

        // Export the workbook as an Excel file
        XLSX.writeFile(workbook, 'Finalized_Invoices.xlsx');
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
    // Submit Payment
    submitPaymentButton.addEventListener('click', function () {
        const amountPaid = parseFloat(amountPaidInput.value);
        const pendingAmount = parseFloat(popupPending.textContent);
        const paymentMethod = document.getElementById('payment-method').value;
        const paymentDate = document.getElementById('payment-date').value;
        const paymentDetails = document.getElementById('payment-details').value;

        if (isNaN(amountPaid) || amountPaid <= 0 || amountPaid > pendingAmount) {
            alert('Please enter a valid amount.');
            return;
        }

        const newPendingAmount = pendingAmount - amountPaid;

        // Log data being sent for debugging
        console.log('Sending data:', {
            id: currentId,
            amount_paid: amountPaid,
            new_pending_amount: newPendingAmount,
            payment_method: paymentMethod,
            payment_date: paymentDate,
            payment_details: paymentDetails
        });

        // Update database via AJAX
        fetch('update_invoice_cancel_pending_amount.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: currentId,
                amount_paid: amountPaid,
                new_pending_amount: newPendingAmount,
                payment_method: paymentMethod,
                payment_date: paymentDate,
                payment_details: paymentDetails
            }),
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data); // Log the response for debugging

            if (data.success) {
                // Update the button text and style
                const button = document.querySelector(`.pending-button[data-id='${currentId}']`);
                if (newPendingAmount === 0) {
                    button.textContent = 'PAID';
                    button.classList.remove('btn-danger');
                    button.classList.add('paid-button');
                    button.disabled = true;
                } else {
                    button.textContent = newPendingAmount;
                    button.setAttribute('data-pending', newPendingAmount);
                }

                // Close popup
                popup.style.display = 'none';
                overlay.style.display = 'none';
            } else {
                alert('Failed to update pending amount: ' + (data.error || 'Unknown error'));
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

// Add this with your other event listeners
document.getElementById('close-popup').addEventListener('click', function() {
  popup.style.display = 'none';
  overlay.style.display = 'none';
});

    </script>
  </body>
</html>
