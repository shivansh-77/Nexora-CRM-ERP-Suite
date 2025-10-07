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

// Step 2: Fetch Invoice Records (updated to include base_amount and tax columns)
if (!empty($fy_codes)) {
    $fy_codes_string = implode("','", $fy_codes);
    $query = "SELECT id,
                   invoice_no,
                   quotation_no,
                   client_id,
                   client_name,
                   client_company_name,
                   client_gstno,
                   client_state,
                   invoice_date,
                   base_amount,
                   gross_amount,
                   total_igst,
                   total_cgst,
                   total_sgst,
                   discount,
                   net_amount,
                   pending_amount
            FROM invoices
            WHERE status = 'Finalized' AND fy_code IN ('$fy_codes_string')
            ORDER BY
                CAST(SUBSTRING_INDEX(invoice_no, '/', -1) AS UNSIGNED) DESC,
                invoice_date DESC";

} else {
    $query = "SELECT * FROM invoices WHERE 0";
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

        /* Popup Container - Combined modal */
        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 450px;
            max-width: 90%;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            display: none;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Popup Header - Combined modal */
        .popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
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

        /* Toggle button for payment mode */
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

        /* Popup Body */
        .popup-body {
            padding: 15px 20px 20px;
        }

        .amount-display {
            margin-bottom: 15px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
        }

        .amount-row, .amount-box {
            flex-basis: 48%;
        }

        .amount-label {
            color: #666;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .amount-value {
            color: #333;
            font-weight: 600;
            font-size: 16px;
        }

        /* Form Elements */
        .form-group {
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
            padding: 8px 12px;
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
/* ---- Paste into your existing <style> ---- */

/* Search / filter styles (ledger-like) */
.search-bar { display:flex; align-items:center; gap:8px; background-color:white; border:1px solid #ddd; border-radius:5px; padding:2px; }
.search-input { border: none; padding: 6px; outline: none; font-size: 13px; width: 180px; }
.date-filter, .date-input { border: 1px solid #ddd; padding: 6px 8px; border-radius: 4px; font-size: 13px; width:120px; }
.filter-input, .filter-select { width: 100%; padding: 6px; box-sizing: border-box; border-radius: 6px; border:1px solid #ddd; font-size:13px; }
.filters-row th { background: #2c3e50; } /* light background for filter inputs row */

/* Make the filter row sticky under header (adjust top offset to match your header height) */
.user-table thead tr:first-child th { position: sticky; top: 0; z-index: 3; background: #2c3e50; color:#fff; }
.user-table thead tr:nth-child(2) th { position: sticky; top:50px; z-index: 2; background: #2c3e50; color:#fff; }



    </style>
</head>
<body>
  <div class="leadforhead">
    <h2 class="leadfor">Finalized Invoices</h2>
    <div class="lead-actions">
      <div class="search-bar">
        <input type="text" id="globalSearch" class="search-input" placeholder="Search all...">

      </div>

      <!-- Date range filters -->
      <input type="date" id="startDate" class="date-filter" title="Start date">
      <input type="date" id="endDate" class="date-filter" title="End date">

      <a href="invoice_generate.php">
        <button class="btn-primary" id="openModal" data-mode="add" title="Add New Invoice">➕</button>
      </a>
      <button id="downloadExcel" class="btn-primary" title="Export to Excel">
        <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px;">
      </button>
    </div>
  </div>



    <div class="user-table-wrapper">
      <table class="user-table">
        <thead>
    <!-- Filters Row -->
    <tr class="filters-row">
      <th><input type="text"  id="filter-invoice-no"   class="filter-input col-filter" data-col="0"  placeholder="Filter"></th>
      <th><input type="text"  id="filter-quotation-no" class="filter-input col-filter" data-col="1"  placeholder="Filter"></th>
      <th><input type="text"  id="filter-client-name"  class="filter-input col-filter" data-col="2"  placeholder="Filter"></th>
      <th><input type="text"  id="filter-company-name" class="filter-input col-filter" data-col="3"  placeholder="Filter"></th>
      <th><input type="text"  id="filter-gst"          class="filter-input col-filter" data-col="4"  placeholder="Filter"></th>
      <th><input type="text"  id="filter-state"        class="filter-input col-filter" data-col="5"  placeholder="Filter"></th>
      <th><input type="date"  id="filter-invoice-date" class="filter-input col-filter" data-col="6"></th>
      <th><input type="text"  id="filter-base"         class="filter-input col-filter" data-col="7"  placeholder="Filter"></th>
      <th><input type="text"  id="filter-gross"        class="filter-input col-filter" data-col="8"  placeholder="Filter"></th>
      <th><input type="text"  id="filter-igst"         class="filter-input col-filter" data-col="9"  placeholder="Filter"></th>
      <th><input type="text"  id="filter-cgst"         class="filter-input col-filter" data-col="10" placeholder="Filter"></th>
      <th><input type="text"  id="filter-sgst"         class="filter-input col-filter" data-col="11" placeholder="Filter"></th>
      <th><input type="text"  id="filter-discount"     class="filter-input col-filter" data-col="12" placeholder="Filter"></th>
      <th><input type="text"  id="filter-net"          class="filter-input col-filter" data-col="13" placeholder="Filter"></th>
      <th><input type="text"  id="filter-pending"      class="filter-input col-filter" data-col="14" placeholder="Filter"></th>
      <th></th>
    </tr>

    <!-- Titles Row -->
    <tr style="background-color: #2c3e50; color: #2c3e50;">
      <th>Invoice No</th>
      <th>Quotation No</th>
      <th>Client Name</th>
      <th>Company Name</th>
      <th>GST No.</th>
      <th>State</th>
      <th>Invoice Date</th>
      <th>Base Amount</th>
      <th>Gross Amount</th>
      <th>Total IGST</th>
      <th>Total CGST</th>
      <th>Total SGST</th>
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

    echo "<tr ondblclick=\"window.location.href='invoice1.php?id={$row['id']}'\" style='cursor: pointer;'>
          <td>{$row['invoice_no']}</td>
          <td>{$row['quotation_no']}</td>
          <td>{$row['client_name']}</td>
          <td>{$row['client_company_name']}</td>
          <td>{$row['client_gstno']}</td>     <!-- NEW -->
          <td>{$row['client_state']}</td>     <!-- NEW -->
          <td>{$row['invoice_date']}</td>
          <td>{$row['base_amount']}</td>
          <td>{$row['gross_amount']}</td>
          <td>{$row['total_igst']}</td>
          <td>{$row['total_cgst']}</td>
          <td>{$row['total_sgst']}</td>
          <td>{$row['discount']}</td>
          <td>{$row['net_amount']}</td>
          <td>
            <button title='Pay Amount' class='{$buttonClass} pending-button' data-id='{$row['id']}' data-net='{$row['net_amount']}' data-pending='{$row['pending_amount']}' {$disabled}>
              {$buttonText}
            </button>
            <button class='btn-warning adjust-advance hidden' data-id='{$row['id']}' data-client-id='{$row['client_id']}' data-invoice-no='{$row['invoice_no']}' data-client-name=\"{$row['client_name']}\" data-pending=\"{$row['pending_amount']}\" data-net=\"{$row['net_amount']}\">💵</button>
          </td>
          <td>
            <button class='btn-secondary' title='Print this Invoice' onclick=\"window.location.href='invoice1.php?id={$row['id']}'\">🖨️</button>
            <button class='btn-secondary' title='Mail This Invoice' onclick=\"window.location.href='send_invoice_email.php?id={$row['id']}'\">📧</button>
          </td>
        </tr>";

} // end while
} else {
echo "<tr><td colspan='14' style='text-align: center;'>No records found</td></tr>";
}
?>
</tbody>

      </table>
    </div>

    <!-- Combined Popup for Payment and Advance Adjustment -->
    <!-- Replace the entire popup section in your invoice_display.php with this: -->

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
    const globalSearch = document.getElementById('globalSearch'); // top search
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const colFilters = document.querySelectorAll('.col-filter');
    const table = document.querySelector('.user-table');
    const tbody = table.querySelector('tbody');
    const downloadExcelButton = document.getElementById('downloadExcel');

    function parseDateFromCell(text) {
      if (!text) return null;
      text = text.trim();
      const d = new Date(text); if (!isNaN(d.getTime())) return d;
      const m = text.match(/^(\d{2})[-\/](\d{2})[-\/](\d{4})$/); if (m) return new Date(`${m[3]}-${m[2]}-${m[1]}`);
      return null;
    }

    function norm(t){ return (t||'').toString().trim().toLowerCase(); }

    function applyFilters(){
      const searchTerm = norm(globalSearch.value);
      const sDate = startDate.value ? new Date(startDate.value) : null;
      const eDate = endDate.value ? new Date(endDate.value) : null;
      if (eDate) eDate.setHours(23,59,59,999);

      const perCol = {};
      colFilters.forEach(f => perCol[f.dataset.col] = (f.value||'').toString().trim().toLowerCase());

      Array.from(tbody.querySelectorAll('tr')).forEach(row => {
        const cells = row.querySelectorAll('td');
        if (!cells.length) { row.style.display = 'none'; return; }

        let visible = true;

        // global search across all columns except last action column
        if (searchTerm) {
          let combined = '';
          for (let i=0;i<cells.length-1;i++) combined += ' ' + cells[i].textContent;
          if (!norm(combined).includes(searchTerm)) visible = false;
        }

        // per column filters
        Object.keys(perCol).forEach(ci => {
          if (!visible) return;
          const val = perCol[ci];
          if (!val) return;
          const idx = parseInt(ci,10);
          const cText = norm(cells[idx] ? cells[idx].textContent : '');
          if (idx === 6 && document.getElementById('filter-invoice-date').value) {
            const fDate = new Date(document.getElementById('filter-invoice-date').value);
            const cDate = parseDateFromCell(cells[6].textContent);
            if (!cDate || cDate.toDateString() !== fDate.toDateString()) visible = false;
          } else {
            if (!cText.includes(val)) visible = false;
          }
        });

        // top-level start/end date range on Invoice Date (col idx 4)
        if (visible && (sDate || eDate)) {
          const cDate = parseDateFromCell(cells[4].textContent);
          if (!cDate) visible = false;
          else {
            if (sDate && cDate < sDate) visible = false;
            if (eDate && cDate > eDate) visible = false;
          }
        }

        row.style.display = visible ? '' : 'none';
      });
    }

    // events
    globalSearch.addEventListener('input', applyFilters);
    startDate.addEventListener('change', applyFilters);
    endDate.addEventListener('change', applyFilters);
    colFilters.forEach(f => { f.addEventListener('input', applyFilters); f.addEventListener('change', applyFilters); });

    // --- Excel download functionality (ignore last 2 columns) ---
    downloadExcelButton.addEventListener('click', function () {
    const table = document.querySelector('.user-table');
    const visibleRows = Array.from(table.querySelectorAll('tbody tr'))
      .filter(row => row.style.display !== 'none');

    const workbook = XLSX.utils.book_new();
    const aoa = [];

    // headers (2nd row preferred). Drop last 2 (Pending, Actions) from export.
    let headerCells = table.querySelectorAll('thead tr:nth-child(2) th');
    if (!headerCells || headerCells.length === 0) headerCells = table.querySelectorAll('thead tr:first-child th');
    aoa.push(Array.from(headerCells).slice(0, -2).map(h => h.textContent.trim()));

    // helpers
    const toYMD = (s) => {
      if (!s) return '';
      s = s.trim();
      if (/\s/.test(s)) s = s.split(/\s+/)[0];
      let m = s.match(/^(\d{4})[-\/.](\d{2})[-\/.](\d{2})$/);
      if (m) return `${m[1]}-${m[2]}-${m[3]}`;
      m = s.match(/^(\d{2})[-\/.](\d{2})[-\/.](\d{4})$/);
      if (m) return `${m[3]}-${m[2]}-${m[1]}`;
      const d = new Date(s);
      if (!isNaN(d.getTime())) {
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth()+1).padStart(2,'0');
        const dd = String(d.getDate()).padStart(2,'0');
        return `${yyyy}-${mm}-${dd}`;
      }
      return s;
    };

    const toNumber = (txt) => {
      if (txt == null) return null;
      const t = String(txt).trim();
      if (t.toUpperCase() === 'PAID') return 0; // (we're not exporting Pending anyway)
      const cleaned = t.replace(/[₹$€,\s\u00A0\u2000-\u200B]/g, '').replace(/[^0-9.\-]/g, '');
      const n = parseFloat(cleaned);
      return isNaN(n) ? null : n;
    };

    // Build rows (drop last 2 columns => up to Net Amount)
    visibleRows.forEach(row => {
      const cells = Array.from(row.querySelectorAll('td')).slice(0, -2); // drop Pending + Actions
      const rowVals = cells.map((cell, idx) => {
        const text = cell.textContent.trim();

        // 0 Invoice No, 1 Quotation No, 2 Client, 3 Company => text
        if (idx <= 3) return text;

        // 4 GST, 5 State => text
        if (idx === 4 || idx === 5) return text;

        // 6 Invoice Date => TEXT yyyy-mm-dd
        if (idx === 6) return toYMD(text);

        // 7..13 numeric amounts (Base..Net)
        const n = toNumber(text);
        return (n === null ? text : n);
      });
      aoa.push(rowVals);
    });

    const ws = XLSX.utils.aoa_to_sheet(aoa);

    // widen date column (now at index 6 => column G)
    ws['!cols'] = ws['!cols'] || [];
    ws['!cols'][6] = { wch: 12 };

    // helpers
    function colLetters(colIdx){
      let s=''; colIdx++;
      while(colIdx){ let m=(colIdx-1)%26; s = String.fromCharCode(65+m) + s; colIdx = Math.floor((colIdx-1)/26); }
      return s;
    }

    // constants for enforcement (after slicing last 2)
    // A:0 B:1 C:2 D:3 E:4 F:5 G:6 H:7 I:8 J:9 K:10 L:11 M:12 N:13
    const DATE_COL = 6;                 // G
    const TEXT_COLS = [0,1,2,3,4,5];    // Invoice, Quotation, Client, Company, GST, State
    const NUM_COLS  = [7,8,9,10,11,12,13]; // Base..Net

    const rng = XLSX.utils.decode_range(ws['!ref'] || 'A1');

    for (let r = rng.s.r + 1; r <= rng.e.r; r++) { // skip header row
      // force text columns to 's'
      TEXT_COLS.forEach(ci => {
        const addr = colLetters(ci) + (r + 1);
        if (ws[addr]) { ws[addr].t = 's'; ws[addr].v = String(ws[addr].v ?? ''); }
      });

      // date column already formatted as text yyyy-mm-dd above
      const dAddr = colLetters(DATE_COL) + (r + 1);
      if (ws[dAddr]) { ws[dAddr].t = 's'; ws[dAddr].v = String(ws[dAddr].v ?? ''); }

      // numeric columns: ensure numbers with 2 decimals
      NUM_COLS.forEach(ci => {
        const addr = colLetters(ci) + (r + 1);
        const cell = ws[addr];
        if (!cell) return;
        if (typeof cell.v === 'number') { cell.t = 'n'; cell.z = '0.00'; }
        else {
          const n = Number(cell.v);
          if (!isNaN(n)) { cell.v = n; cell.t = 'n'; cell.z = '0.00'; }
        }
      });
    }

    XLSX.utils.book_append_sheet(workbook, ws, 'Invoices');
    XLSX.writeFile(workbook, 'Finalized_Invoices.xlsx');
  });


    applyFilters();

    /* --- then below this keep your existing modal/pending-button JS unchanged --- */

      // Payment & Advance combined modal functionality
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
      const advanceDocSelect = document.getElementById('advance-doc-select');
      const advanceDocInput = document.getElementById('advance-doc-input');
      const advanceAmountInput = document.getElementById('advance-amount');
      const advanceDetailsInput = document.getElementById('advance-details');
      const advanceDateInput = document.getElementById('advance-date');

      let currentId = null;
      let currentClientId = null;
      let currentInvoiceNo = null;
      let isAdvanceMode = false; // start in regular payment mode

      // Toggle payment mode between Regular and Advance
      paymentModeToggle.addEventListener('click', function () {
        if (isAdvanceMode) {
          paymentSection.style.display = 'block';
          advanceSection.style.display = 'none';
          paymentModeToggle.textContent = 'Switch to Advance Payment';
          isAdvanceMode = false;
        } else {
          paymentSection.style.display = 'none';
          advanceSection.style.display = 'block';
          paymentModeToggle.textContent = 'Switch to Regular Payment';
          isAdvanceMode = true;
        }
      });

      // Show modal on pending-buttons click, init state
      pendingButtons.forEach(button => {
        button.addEventListener('click', function () {
          currentId = this.getAttribute('data-id');

          // Locate related advance button info in row
          const adjustAdvanceBtn = this.closest('tr').querySelector('.btn-warning.adjust-advance');
          if (!adjustAdvanceBtn) {
            alert('Advance adjustment data not found.');
            return;
          }
          currentClientId = adjustAdvanceBtn.getAttribute('data-client-id') || null;
          currentInvoiceNo = adjustAdvanceBtn.getAttribute('data-invoice-no') || null;

          popupNet.textContent = this.getAttribute('data-net');
          popupPending.textContent = this.getAttribute('data-pending');

          // Reset inputs
          amountPaidInput.value = '';
          advanceDocSelect.innerHTML = '<option value="">-- Select --</option>';
          advanceDocInput.value = '';
          advanceAmountInput.value = '';
          advanceDetailsInput.value = '';
          advanceDateInput.value = new Date().toISOString().split('T')[0];

          // Show regular payment by default
          paymentSection.style.display = 'block';
          advanceSection.style.display = 'none';
          paymentModeToggle.textContent = 'Switch to Advance Payment';
          isAdvanceMode = false;

          // Load advance entries for client
          if (currentClientId) {
            fetch('fetch_advance_entries.php?client_id=' + encodeURIComponent(currentClientId))
              .then(res => res.json())
              .then(data => {
                if (Array.isArray(data) && data.length) {
                  data.forEach(entry => {
                    const option = document.createElement('option');
                    option.value = JSON.stringify(entry);
                    option.textContent = entry.advance_doc_no + ' (₹' + entry.pending_amount + ')';
                    advanceDocSelect.appendChild(option);
                  });
                }
              })
              .catch(() => {
                advanceDocSelect.innerHTML = '<option value="">-- Select --</option>';
              });
          }

          popup.style.display = 'block';
          overlay.style.display = 'block';
        });
      });

      // Advance doc select change
      advanceDocSelect.addEventListener('change', function () {
        const selected = this.value ? JSON.parse(this.value) : {};
        advanceDocInput.value = selected.advance_doc_no || '';
        advanceAmountInput.max = Math.min(
          parseFloat(selected.pending_amount || 0),
          parseFloat(popupPending.textContent)
        );
        advanceAmountInput.value = '';
      });

      // Submit button for regular or advance payment
      submitPaymentButton.addEventListener('click', function () {
        if (isAdvanceMode) {
          // Advance payment submit
          const docNo = advanceDocInput.value;
          const amount = parseFloat(advanceAmountInput.value);
          const paymentDetails = advanceDetailsInput.value;
          const paymentDate = advanceDateInput.value;
          const maxAllowed = parseFloat(advanceAmountInput.max);

          if (!docNo || isNaN(amount) || amount <= 0 || amount > maxAllowed) {
            alert('Invalid or excess amount.');
            return;
          }

          fetch('adjust_advance_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              invoice_id: currentId,
              invoice_no: currentInvoiceNo,
              client_id: currentClientId,
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
          // Regular payment submit
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

          fetch('update_pending_amount.php', {
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

      // Close popup on overlay or close button click
      overlay.addEventListener('click', function () {
        popup.style.display = 'none';
        overlay.style.display = 'none';
      });
      document.getElementById('close-popup').addEventListener('click', function () {
        popup.style.display = 'none';
        overlay.style.display = 'none';
      });
    });
  </script>
</body>
</html>
