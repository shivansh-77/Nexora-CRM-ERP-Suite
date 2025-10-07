<?php
session_start();
include('connection.php');
include('topbar.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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

// Step 2: Fetch Quotation Records
if (!empty($fy_codes)) {
    $fy_codes_string = implode("','", $fy_codes);
    $query = "SELECT * FROM quotations
              WHERE fy_code IN ('$fy_codes_string')
              ORDER BY
                  CAST(SUBSTRING_INDEX(quotation_no, '/', -1) AS UNSIGNED) DESC,
                  quotation_date DESC";
} else {
    $query = "SELECT * FROM quotations WHERE 0";
}

$result = mysqli_query($connection, $query);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <title>Quotation Display</title>

    <style>
        html, body {
            overflow: hidden;
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
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

        /* Both filter row and headings row use same dark background */
        .user-table thead tr.filters-row th,
        .user-table thead tr.headings-row th {
            background-color: #2c3e50;
            color: white;
            text-align: left;
            position: sticky;
            z-index: 2;
        }

        /* filter row sticks at top first */
        .user-table thead tr.filters-row th {
            top: 0;
            padding: 6px;
            font-weight: 500;
        }

        /* headings row sticks below filters */
        .user-table thead tr.headings-row th {
            top: 44px; /* adjust if filter row height changes */
            padding: 8px;
            font-weight: 700;
        }

        .user-table td {
            text-align: left;
            padding: 7px;
            background: white;
        }

        .user-table tr:nth-child(even) td {
            background-color: #f9f9f9;
        }

        .user-table tr:hover td {
            background-color: #f1f1f1;
        }

        .user-table td.actions {
            text-align: center;
            white-space: nowrap;
        }

        .filter-input, .filter-select, .date-filter {
            width: 100%;
            padding: 6px 8px;
            box-sizing: border-box;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 13px;
        }

        .date-filter {
            width: 120px;
            padding: 6px;
        }

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
            gap: 8px;
        }

        .global-search-small {
            width: 220px;
            padding: 7px 10px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        #downloadExcel {
            background-color: #1f8f1f;
            color: white;
            border: none;
            padding: 8px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            padding: 6px 8px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }

        /* ensure thead rows appear above table body when sticky */
        .user-table thead tr { position: sticky; z-index: 2; }
    </style>

    <!-- SheetJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
</head>
<body>
    <div class="leadforhead">
        <h2 class="leadfor">Quotation Display</h2>
        <div class="lead-actions">
            <input type="text" id="globalSearch" class="global-search-small" placeholder="Search all records...">
            <input type="date" id="startDate" class="date-filter" title="Start date">
            <input type="date" id="endDate" class="date-filter" title="End date">
            <a href="quotation.php"><button class="btn-primary" title="Add new Quotation">➕</button></a>
            <button id="downloadExcel" class="btn-primary" title="Export to Excel">
              <img src="Excel-icon.png" alt="Export to Excel" style="width: 27px; height: 20px;">
            </button>
        </div>
    </div>

    <div class="user-table-wrapper">
        <table class="user-table" id="quotationTable">
            <thead>
                <!-- Filters Row -->
                <tr class="filters-row">
                    <th><input type="text" class="filter-input col-filter" data-col="0" placeholder="Filter Id"></th>
                    <th><input type="text" class="filter-input col-filter" data-col="1" placeholder="Filter Quotation No"></th>
                    <th><input type="text" class="filter-input col-filter" data-col="2" placeholder="Filter Client"></th>
                    <th><input type="text" class="filter-input col-filter" data-col="3" placeholder="Filter Company"></th>
                    <th><input type="text" class="filter-input col-filter" data-col="4" placeholder="Filter Gross"></th>
                    <th><input type="text" class="filter-input col-filter" data-col="5" placeholder="Filter Discount"></th>
                    <th><input type="text" class="filter-input col-filter" data-col="6" placeholder="Filter Net"></th>
                    <th><input type="date" class="filter-input col-filter" data-col="7" placeholder="Filter Date"></th>
                    <th></th>
                </tr>

                <!-- Headings Row -->
                <tr class="headings-row">
                    <th>Id</th>
                    <th>Quotation No</th>
                    <th>Client Name</th>
                    <th>Company Name</th>
                    <th>Gross Amount</th>
                    <th>Discount</th>
                    <th>Net Amount</th>
                    <th>Quotation Date</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                      echo "<tr ondblclick=\"window.location.href='quotation_form_display.php?id={$row['id']}'\" style='cursor: pointer;'>
                                <td>{$row['id']}</td>
                                <td>{$row['quotation_no']}</td>
                                <td>{$row['client_name']}</td>
                                <td>{$row['client_company_name']}</td>
                                <td>{$row['gross_amount']}</td>
                                <td>{$row['discount']}</td>
                                <td>{$row['net_amount']}</td>
                                <td>{$row['quotation_date']}</td>
                                <td class='actions'>
                                    <button class='btn-secondary' title='View' onclick=\"window.location.href='quotation_form_display.php?id={$row['id']}'\">📋</button>
                                    <button class='btn-secondary' title='Edit' onclick=\"window.location.href='quotation_edit2.php?id={$row['id']}'\">✏️</button>
                                    <button class='btn-secondary' title='Make Invoice' onclick=\"if(confirm('Make invoice for this quotation?')) window.location.href='register_invoice.php?id={$row['id']}'\">📄</button>
                                    <button class='btn-secondary' title='Delete' onclick=\"if(confirm('Delete this record?')) window.location.href='delete_quotation.php?id={$row['id']}'\">🗑️</button>
                                    <button class='btn-secondary' title='Email' onclick=\"window.location.href='send_quotation_email.php?id={$row['id']}'\">📧</button>
                                </td>
                            </tr>";
                    }
                } else {
                   echo "<tr><td colspan='9' style='text-align: center;'>No records found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const globalSearch = document.getElementById('globalSearch');
  const startDate = document.getElementById('startDate');
  const endDate = document.getElementById('endDate');
  const colFilters = document.querySelectorAll('.col-filter');
  const table = document.getElementById('quotationTable');
  const tbody = table.querySelector('tbody');
  const downloadExcelButton = document.getElementById('downloadExcel');

  function parseDateFromCell(text) {
    if (!text) return null;
    text = text.trim();
    const d = new Date(text);
    if (!isNaN(d.getTime())) return d;
    const m = text.match(/^(\d{2})[-\/](\d{2})[-\/](\d{4})$/);
    if (m) return new Date(`${m[3]}-${m[2]}-${m[1]}`);
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

      // global search across all columns (except actions)
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
        if (idx === 7 && document.querySelector(`.col-filter[data-col="7"]`).value) {
          const fDate = new Date(document.querySelector(`.col-filter[data-col="7"]`).value);
          const cDate = parseDateFromCell(cells[7].textContent);
          if (!cDate || cDate.toDateString() !== fDate.toDateString()) visible = false;
        } else {
          if (!cText.includes(val)) visible = false;
        }
      });

      // start/end date range filter on Quotation Date column (index 7)
      if (visible && (sDate || eDate)) {
        const cDate = parseDateFromCell(cells[7].textContent);
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

  // Excel export — include headings (second thead row) and visible rows; skip last Actions column
  downloadExcelButton.addEventListener('click', function () {
    const visibleRows = Array.from(table.querySelectorAll('tbody tr')).filter(r => r.style.display !== 'none');
    const workbook = XLSX.utils.book_new();
    const worksheetData = [];

    let headerCells = table.querySelectorAll('thead tr.headings-row th');
    if (!headerCells || headerCells.length === 0) headerCells = table.querySelectorAll('thead th');
    const headerRow = [];
    headerCells.forEach((h, idx) => {
      if (idx !== headerCells.length - 1) headerRow.push(h.textContent.trim());
    });
    worksheetData.push(headerRow);

    visibleRows.forEach(row => {
      const rowData = [];
      const cells = row.querySelectorAll('td');
      cells.forEach((cell, idx) => {
        if (idx !== cells.length - 1) rowData.push(cell.textContent.trim());
      });
      worksheetData.push(rowData);
    });

    const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Quotations');
    XLSX.writeFile(workbook, 'quotations.xlsx');
  });

  // initial filter run
  applyFilters();
});
</script>
</body>
</html>
