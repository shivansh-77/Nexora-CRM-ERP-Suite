<?php
session_start();
include('connection.php');
include('topbar.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

// Success message after redirect
if (isset($_SESSION['email_success'])) {
    echo "<script>alert('" . addslashes($_SESSION['email_success']) . "');</script>";
    unset($_SESSION['email_success']);
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
    $query = "SELECT id, invoice_no, purchase_order_no, vendor_name, invoice_date, gross_amount, discount, net_amount, pending_amount, vendor_id
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
    <link rel="icon" type="image/png" href="favicon.png">
    <title>Purchase Invoice Display</title>
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
            max-height: calc(100vh - 150px);
            overflow-y: auto;
            border: 1px solid #ddd;
            background-color: white;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
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
            position: sticky;
            top: 0;
            z-index: 1;
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
            text-align: right;
            width: auto;
            padding: 5px 8px;
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

        .paid-button {
            background-color: green;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: default;
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

        /* Popup Container - Updated */
        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 700px;
            max-width: 90%;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            display: none;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Popup Header - Updated */
        .popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            position: relative;
        }

        .popup-header h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
            font-weight: 600;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        /* Single toggle button for payment mode */
        .payment-mode-btn {
            background-color: #4a90e2;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            white-space: nowrap;
            user-select: none;
            z-index: 1;
        }
        .payment-mode-btn:hover {
            background-color: #2c6bb7;
        }

        .close-btn {
            font-size: 24px;
            cursor: pointer;
            color: #777;
            transition: color 0.2s;
            z-index: 1;
        }

        .close-btn:hover {
            color: #333;
        }

        /* Popup Body - Updated */
        .popup-body {
            padding: 20px;
        }

        .amount-display {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
        }

        .amount-box {
            width: 48%;
        }

        .amount-label {
            color: #666;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .amount-value {
            color: #333;
            font-weight: 600;
            font-size: 16px;
        }

        /* Form Layout - Updated */
        .form-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .form-group {
            width: 48%;
            margin-bottom: 15px;
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

        .hidden {
    display: none;
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

                      echo "<tr ondblclick=\"window.location.href='purchase_invoice_view.php?id={$row['id']}'\" style='cursor: pointer;'>
                              <td>{$row['id']}</td>
                              <td>{$row['invoice_no']}</td>
                              <td>{$row['purchase_order_no']}</td>
                              <td>{$row['vendor_name']}</td>
                              <td>{$row['invoice_date']}</td>
                              <td>{$row['gross_amount']}</td>
                              <td>{$row['discount']}</td>
                              <td>{$row['net_amount']}</td>
                              <td>
                                  <button title='Pay Amount' class='{$buttonClass} pending-button' data-id='{$row['id']}' data-net='{$row['net_amount']}' data-pending='{$row['pending_amount']}' {$disabled}>
                                      {$buttonText}
                                  </button>
                                  <button class='btn-warning adjust-advance hidden' data-id='{$row['id']}' data-vendor-id='{$row['vendor_id']}' data-invoice-no='{$row['invoice_no']}' data-vendor-name=\"{$row['vendor_name']}\" data-pending=\"{$row['pending_amount']}\" data-net=\"{$row['net_amount']}\">
                                  💵</button>
                              </td>
                              <td>
                                  <button class='btn-secondary' title='Print this Invoice' onclick=\"window.location.href='purchase_invoice_view.php?id={$row['id']}'\">🖨️</button>
                                  <button class='btn-secondary' title='Mail This Invoice'
              onclick=\"window.location.href='send_purchase_invoice_email.php?id={$row['id']}'\">📧</button>

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

    <!-- Combined Popup for Payment and Advance Adjustment -->
    <div class="overlay" id="overlay"></div>
    <div class="popup" id="popup">
        <div class="popup-header">
            <button id="payment-mode-toggle" class="payment-mode-btn">Switch to Advance Payment</button>
            <h3>Payment Details</h3>
            <div class="close-btn" id="close-popup">&times;</div>
        </div>
        <div class="popup-body">
            <div class="amount-display">
                <div class="amount-box">
                    <div class="amount-label">Net Amount:</div>
                    <div class="amount-value" id="popup-net"></div>
                </div>
                <div class="amount-box">
                    <div class="amount-label">Pending Amount:</div>
                    <div class="amount-value" id="popup-pending"></div>
                </div>
            </div>

            <div id="payment-section">
                <div class="form-row">
                    <div class="form-group">
                        <label for="amount-paid" class="form-label">Amount Paid</label>
                        <input type="number" id="amount-paid" class="form-input" placeholder="Enter Amount" min="0" step="0.01">
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
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="payment-date" class="form-label">Payment Date</label>
                        <input type="date" id="payment-date" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="payment-details" class="form-label">Payment Details</label>
                        <input type="text" id="payment-details" class="form-input" placeholder="Enter details (Cheque No., UPI ID, etc.)">
                    </div>
                </div>
            </div>

            <div id="advance-section" style="display: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Advance Doc No</label>
                        <select id="advance-doc-select" class="form-select"></select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Selected Doc No</label>
                        <input type="text" id="advance-doc-input" class="form-input" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Adjustment Amount</label>
                        <input type="number" id="advance-amount" class="form-input" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Date</label>
                        <input type="date" id="advance-date" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Payment Details</label>
                        <input type="text" id="advance-details" class="form-input">
                    </div>
                    <div class="form-group">
                        <!-- Empty column for alignment -->
                    </div>
                </div>
            </div>
        </div>
        <div class="popup-footer">
            <button id="submit-payment" class="submit-btn">Submit Payment</button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const tableRows = document.querySelectorAll('.user-table tbody tr');
        const downloadExcelButton = document.getElementById('downloadExcel');

        searchInput.addEventListener('keyup', function () {
            const searchTerm = searchInput.value.toLowerCase();
            tableRows.forEach(function (row) {
                const cells = row.querySelectorAll('td');
                let rowText = '';
                cells.forEach(function (cell, index) {
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
            const workbook = XLSX.utils.book_new();
            const worksheetData = [];

            const headerRow = [];
            table.querySelectorAll('thead th').forEach((header, index, arr) => {
                if (index !== arr.length - 1) {
                    headerRow.push(header.textContent);
                }
            });
            worksheetData.push(headerRow);

            visibleRows.forEach(row => {
                const rowData = [];
                row.querySelectorAll('td').forEach((cell, index, arr) => {
                    if (index !== arr.length - 1) {
                        rowData.push(cell.textContent);
                    }
                });
                worksheetData.push(rowData);
            });

            const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Purchase Invoices');
            XLSX.writeFile(workbook, 'Finalized_Purchase_Invoices.xlsx');
        });

        // Combined Payment/Advance Functionality
        const pendingButtons = document.querySelectorAll('.pending-button');
        const popup = document.getElementById('popup');
        const overlay = document.getElementById('overlay');
        const popupNet = document.getElementById('popup-net');
        const popupPending = document.getElementById('popup-pending');
        const amountPaidInput = document.getElementById('amount-paid');
        const submitPaymentButton = document.getElementById('submit-payment');
        const paymentModeToggle = document.getElementById('payment-mode-toggle');
        const paymentSection = document.getElementById('payment-section');
        const advanceSection = document.getElementById('advance-section');

        let currentId = null;
        let currentVendorId = null;
        let currentInvoiceNo = null;
        let isAdvanceMode = false; // starts in Regular Payment mode

        // Toggle between Regular and Advance Payment modes
        paymentModeToggle.addEventListener('click', function() {
            if (isAdvanceMode) {
                // Switch to Regular Payment
                paymentSection.style.display = 'block';
                advanceSection.style.display = 'none';
                paymentModeToggle.textContent = 'Switch to Advance Payment';
                isAdvanceMode = false;
            } else {
                // Switch to Advance Payment
                paymentSection.style.display = 'none';
                advanceSection.style.display = 'block';
                paymentModeToggle.textContent = 'Switch to Regular Payment';
                isAdvanceMode = true;
            }
        });

        pendingButtons.forEach(button => {
            button.addEventListener('click', function() {
                currentId = this.getAttribute('data-id');
                currentVendorId = this.closest('tr').querySelector('.adjust-advance').getAttribute('data-vendor-id');
                currentInvoiceNo = this.closest('tr').querySelector('.adjust-advance').getAttribute('data-invoice-no');

                const netAmount = this.getAttribute('data-net');
                const pendingAmount = this.getAttribute('data-pending');

                popupNet.textContent = netAmount;
                popupPending.textContent = pendingAmount;
                amountPaidInput.value = '';

                // Reset to Regular Payment when opening
                paymentSection.style.display = 'block';
                advanceSection.style.display = 'none';
                paymentModeToggle.textContent = 'Switch to Advance Payment';
                isAdvanceMode = false;

                // Load advance options
                fetch('fetch_vendor_advance_entries.php?vendor_id=' + currentVendorId)
                    .then(response => response.json())
                    .then(data => {
                        const select = document.getElementById('advance-doc-select');
                        select.innerHTML = '<option value="">-- Select --</option>';
                        data.forEach(entry => {
                            const option = document.createElement('option');
                            option.value = JSON.stringify(entry);
                            option.textContent = entry.advance_doc_no + ' (₹' + entry.pending_amount + ')';
                            select.appendChild(option);
                        });
                        document.getElementById('advance-doc-input').value = '';
                        document.getElementById('advance-amount').value = '';
                        document.getElementById('advance-details').value = '';
                        document.getElementById('advance-date').value = new Date().toISOString().split('T')[0];
                    });

                popup.style.display = 'block';
                overlay.style.display = 'block';
            });
        });

        document.getElementById('advance-doc-select').addEventListener('change', function() {
            const selected = this.value ? JSON.parse(this.value) : {};
            document.getElementById('advance-doc-input').value = selected.advance_doc_no || '';
            document.getElementById('advance-amount').max = Math.min(
                parseFloat(selected.pending_amount || 0),
                parseFloat(document.getElementById('popup-pending').textContent)
            );
            document.getElementById('advance-amount').value = '';
        });

        // Submit Payment (handles both regular and advance payments)
        submitPaymentButton.addEventListener('click', function() {
            if (isAdvanceMode) {
                // Handle advance payment
                const docNo = document.getElementById('advance-doc-input').value;
                const amount = parseFloat(document.getElementById('advance-amount').value);
                const paymentDetails = document.getElementById('advance-details').value;
                const paymentDate = document.getElementById('advance-date').value;
                const maxAllowed = parseFloat(document.getElementById('advance-amount').max);

                if (!docNo || isNaN(amount) || amount <= 0 || amount > maxAllowed) {
                    alert('Invalid or excess amount.');
                    return;
                }

                fetch('adjust_vendor_advance_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        invoice_id: currentId,
                        invoice_no: currentInvoiceNo,
                        vendor_id: currentVendorId,
                        amount,
                        advance_doc_no: docNo,
                        payment_details: paymentDetails,
                        payment_date: paymentDate
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Advance adjusted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Failed to adjust'));
                    }
                });
            } else {
                // Handle regular payment
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

                fetch('update_purchase_pending_amount.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
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
                    if (data.success) {
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
            }
        });

        // Close popup when clicking outside or on close button
        overlay.addEventListener('click', function() {
            popup.style.display = 'none';
            overlay.style.display = 'none';
        });

        document.getElementById('close-popup').addEventListener('click', function() {
            popup.style.display = 'none';
            overlay.style.display = 'none';
        });
    });
    </script>
</body>
</html>
