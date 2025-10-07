<?php
session_start();
include('connection.php');
include('topbar.php');

// Fetch rows
$rows = [];
$q = "SELECT id, gstin_uin_of_recipient, receiver_name, invoice_number, invoice_date, invoice_value,
             place_of_supply, reverse_charge, applicable_tax_rate, invoice_type, e_commerce_gstin,
             rate, taxable_value, cess_amount
      FROM gst_report
      ORDER BY id DESC";
$res = mysqli_query($connection, $q);
if ($res && mysqli_num_rows($res) > 0) {
    while ($r = mysqli_fetch_assoc($res)) {
        // normalize/strong types for export
        $rows[] = [
            "Id"                      => (int)$r['id'],
            "GSTIN/UIN of Recipient"  => $r['gstin_uin_of_recipient'],
            "Receiver Name"           => $r['receiver_name'],
            "Invoice Number"          => $r['invoice_number'],
            "Invoice Date"            => $r['invoice_date'], // ISO Y-m-d
            "Invoice Value"           => (float)$r['invoice_value'],
            "Place Of Supply"         => $r['place_of_supply'],
            "Reverse Charge"          => $r['reverse_charge'],
            "Applicable % of Tax Rate"=> (float)$r['applicable_tax_rate'],
            "Invoice Type"            => $r['invoice_type'],
            "E-Commerce GSTIN"        => $r['e_commerce_gstin'],
            "Rate (%)"                => (float)$r['rate'],
            "Taxable Value"           => (float)$r['taxable_value'],
            "Cess Amount"             => (float)$r['cess_amount']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <link rel="icon" type="image/png" href="favicon.png">
  <title>GST Report</title>
  <style>
      /* Table Styles */
      /* Prevent the body from scrolling */
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
  max-height: calc(100vh - 150px); /* Dynamic height based on viewport */;
  overflow-y: auto; /* Enables vertical scrolling */
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
          /* padding: 10px; */
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
          width: 120px; /* Adjust width */
          padding: 7px; /* Smaller padding */
          font-size: 14px; /* Reduce font size */
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
  </style>
</head>
<body>
  <div class="leadforhead">
    <h2 class="leadfor">GST Report</h2>
    <div class="lead-actions">
      <input type="text" id="globalSearch" class="filter-input" placeholder="Search all records...">
      <input type="date" id="startDateFilter" class="date-filter">
      <input type="date" id="endDateFilter" class="date-filter">
      <a href="gst_report_create.php">
    <button class="btn-primary" id="openModal" data-mode="add" title="Add New Item">➕</button>
</a>
      <button id="downloadExcel" title="Download Excel File" class="btn-primary">
        <img src="Excel-icon.png" alt="Export to Excel" style="width:20px;height:20px;margin-right:0;">
      </button>
    </div>
  </div>

  <div class="user-table-wrapper">
    <table class="user-table" id="gstTable">
      <thead>
        <!-- Filter Row -->
        <tr>
          <th><input type="text" id="idFilter" class="filter-input" placeholder="Search ID"></th>
          <th><input type="text" id="gstinFilter" class="filter-input" placeholder="Search GSTIN"></th>
          <th><input type="text" id="receiverFilter" class="filter-input" placeholder="Search Receiver"></th>
          <th><input type="text" id="invNoFilter" class="filter-input" placeholder="Search Invoice No"></th>
          <th><input type="text" id="invDateFilter" class="filter-input" placeholder="dd-mm-yyyy"></th>
          <th><input type="text" id="invValFilter" class="filter-input" placeholder="Search Value"></th>
          <th><input type="text" id="posFilter" class="filter-input" placeholder="Search POS"></th>
          <th>
            <select id="revChgFilter" class="filter-select">
              <option value="all">All</option>
              <option value="Y">Y</option>
              <option value="N">N</option>
            </select>
          </th>
          <th><input type="text" id="appRateFilter" class="filter-input" placeholder="Tax %"></th>
          <th>
            <select id="invTypeFilter" class="filter-select">
              <option value="all">All</option>
              <option>Regular B2B</option>
              <option>SEZ</option>
              <option>Export</option>
              <option>Deemed Export</option>
            </select>
          </th>
          <th><input type="text" id="ecomFilter" class="filter-input" placeholder="Search E-Com GSTIN"></th>
          <th><input type="text" id="rateFilter" class="filter-input" placeholder="Rate %"></th>
          <th><input type="text" id="taxableFilter" class="filter-input" placeholder="Taxable"></th>
          <th><input type="text" id="cessFilter" class="filter-input" placeholder="Cess"></th>
          <th></th>
        </tr>
        <!-- Headings -->
        <tr>
          <th>Id</th>
          <th>GSTIN/UIN of Recipient</th>
          <th>Receiver Name</th>
          <th>Invoice Number</th>
          <th>Invoice Date</th>
          <th>Invoice Value</th>
          <th>Place Of Supply</th>
          <th>Reverse Charge</th>
          <th>Applicable % of Tax Rate</th>
          <th>Invoice Type</th>
          <th>E-Commerce GSTIN</th>
          <th>Rate (%)</th>
          <th>Taxable Value</th>
          <th>Cess Amount</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)): ?>
          <?php foreach ($rows as $r): ?>
            <?php
              // Display formats (keep numbers readable; export uses typed data separately)
              $invDateDisp = $r['Invoice Date'] ? date("d-m-Y", strtotime($r['Invoice Date'])) : '';
              $id   = (int)$r['Id'];
              $href = "gst_report_view.php?id=".$id;
            ?>
            <tr ondblclick="window.location.href='<?= htmlspecialchars($href) ?>'" style="cursor:pointer;">
              <td><?= $id ?></td>
              <td><?= htmlspecialchars($r['GSTIN/UIN of Recipient']) ?></td>
              <td><?= htmlspecialchars($r['Receiver Name']) ?></td>
              <td><?= htmlspecialchars($r['Invoice Number']) ?></td>
              <td><?= $invDateDisp ?></td>
              <td><?= number_format($r['Invoice Value'], 2) ?></td>
              <td><?= htmlspecialchars($r['Place Of Supply']) ?></td>
              <td><?= htmlspecialchars($r['Reverse Charge']) ?></td>
              <td><?= number_format($r['Applicable % of Tax Rate'], 2) ?></td>
              <td><?= htmlspecialchars($r['Invoice Type']) ?></td>
              <td><?= htmlspecialchars($r['E-Commerce GSTIN']) ?></td>
              <td><?= number_format($r['Rate (%)'], 2) ?></td>
              <td><?= number_format($r['Taxable Value'], 2) ?></td>
              <td><?= number_format($r['Cess Amount'], 2) ?></td>
              <td><button class="btn-secondary" onclick="window.location.href='<?= htmlspecialchars($href) ?>'">View</button></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="15" style="text-align:center;">No records found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- SheetJS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

  <script>
  // Embed typed data for export (numbers stay numbers, dates stay dates)
  const rowsData = <?php echo json_encode($rows, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;

  document.getElementById("downloadExcel").addEventListener("click", function () {
      // If a date range is applied, filter export too
      const start = document.getElementById('startDateFilter').value ? new Date(document.getElementById('startDateFilter').value) : null;
      const endI  = document.getElementById('endDateFilter').value ? new Date(document.getElementById('endDateFilter').value) : null;
      if (endI) endI.setHours(23,59,59,999);

      const exportRows = rowsData.filter(r => {
          if (!start && !endI) return true;
          const d = r["Invoice Date"] ? new Date(r["Invoice Date"]) : null;
          if (!d) return false;
          if (start && d < start) return false;
          if (endI && d > endI) return false;
          return true;
      });

      // Create worksheet with correct types
      const ws = XLSX.utils.json_to_sheet(exportRows, {
        header: [
          "Id","GSTIN/UIN of Recipient","Receiver Name","Invoice Number","Invoice Date",
          "Invoice Value","Place Of Supply","Reverse Charge","Applicable % of Tax Rate",
          "Invoice Type","E-Commerce GSTIN","Rate (%)","Taxable Value","Cess Amount"
        ],
        cellDates: true // let Excel recognize dates
      });

      // Ensure date column has date type
      const range = XLSX.utils.decode_range(ws['!ref']);
      for (let R = range.s.r + 1; R <= range.e.r; ++R) {
        const addr = XLSX.utils.encode_cell({r:R, c:4}); // "Invoice Date" column index 4
        if (ws[addr] && ws[addr].v) { ws[addr].t = 'd'; }
      }

      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, "GST Report");
      XLSX.writeFile(wb, "GST_Report.xlsx");
  });

  // --- Filtering like party_ledger ---
  function filterTable(){
    const q = (id)=>document.getElementById(id);
    const global = q('globalSearch').value.toLowerCase();
    const idF = q('idFilter').value.toLowerCase();
    const gstinF = q('gstinFilter').value.toLowerCase();
    const recvF = q('receiverFilter').value.toLowerCase();
    const invNoF = q('invNoFilter').value.toLowerCase();
    const invDateF = q('invDateFilter').value.trim(); // dd-mm-yyyy
    const invValF = q('invValFilter').value.toLowerCase();
    const posF = q('posFilter').value.toLowerCase();
    const revF = q('revChgFilter').value;
    const appRateF = q('appRateFilter').value.toLowerCase();
    const invTypeF = q('invTypeFilter').value;
    const ecomF = q('ecomFilter').value.toLowerCase();
    const rateF = q('rateFilter').value.toLowerCase();
    const taxableF = q('taxableFilter').value.toLowerCase();
    const cessF = q('cessFilter').value.toLowerCase();
    const start = q('startDateFilter').value ? new Date(q('startDateFilter').value) : null;
    const end   = q('endDateFilter').value ? new Date(q('endDateFilter').value) : null;
    if (end) end.setHours(23,59,59,999);

    document.querySelectorAll('#gstTable tbody tr').forEach(row=>{
      const t = [...row.children].map(td=>td.textContent.trim());
      // map indices according to header
      const rowObj = {
        id:t[0], gstin:t[1].toLowerCase(), recv:t[2].toLowerCase(), invno:t[3].toLowerCase(),
        invdate:t[4], invval:t[5].toLowerCase(), pos:t[6].toLowerCase(), rev:t[7],
        apprate:t[8].toLowerCase(), invtype:t[9], ecom:t[10].toLowerCase(),
        rate:t[11].toLowerCase(), taxable:t[12].toLowerCase(), cess:t[13].toLowerCase()
      };

      // date checks
      let dateMatch = true;
      const dText = rowObj.invdate;
      const d = dText ? new Date(dText.split('-').reverse().join('-')) : null; // dd-mm-yyyy -> yyyy-mm-dd
      if (start && (!d || d < start)) dateMatch = false;
      if (end && (!d || d > end)) dateMatch = false;

      const show =
        (idF==='' || rowObj.id.toLowerCase().includes(idF)) &&
        (gstinF==='' || rowObj.gstin.includes(gstinF)) &&
        (recvF==='' || rowObj.recv.includes(recvF)) &&
        (invNoF==='' || rowObj.invno.includes(invNoF)) &&
        (invDateF==='' || rowObj.invdate.includes(invDateF)) &&
        (invValF==='' || rowObj.invval.includes(invValF)) &&
        (posF==='' || rowObj.pos.includes(posF)) &&
        (revF==='all' || rowObj.rev===revF) &&
        (appRateF==='' || rowObj.apprate.includes(appRateF)) &&
        (invTypeF==='all' || rowObj.invtype===invTypeF) &&
        (ecomF==='' || rowObj.ecom.includes(ecomF)) &&
        (rateF==='' || rowObj.rate.includes(rateF)) &&
        (taxableF==='' || rowObj.taxable.includes(taxableF)) &&
        (cessF==='' || rowObj.cess.includes(cessF)) &&
        dateMatch &&
        (global==='' || row.innerText.toLowerCase().includes(global));

      row.style.display = show ? '' : 'none';
    });
  }

  document.querySelectorAll('.filter-input, .filter-select').forEach(el=>{
    el.addEventListener('input', filterTable);
    el.addEventListener('change', filterTable);
  });
  document.getElementById('startDateFilter').addEventListener('change', filterTable);
  document.getElementById('endDateFilter').addEventListener('change', filterTable);
  </script>
</body>
</html>
