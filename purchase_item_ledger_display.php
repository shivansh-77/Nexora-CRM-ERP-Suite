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
    <title>Purchase Item Ledger Display</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
    <style>
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

        .filter-input, .filter-select {
            width: 100%;
            padding: 6px;
            box-sizing: border-box;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .date-filter {
            width: 120px;
            padding: 7px;
            font-size: 14px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        #downloadExcel {
            background-color: green;
        }
    </style>
</head>
<body>
<div class="leadforhead">
    <h2 class="leadfor">Purchase Item Ledger</h2>
    <div class="lead-actions">
        <div class="search-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="Search...">
            <button class="btn-search" id="searchButton">🔍</button>
        </div>
        <input type="date" id="startDate" class="date-filter" placeholder="Start Date">
        <input type="date" id="endDate" class="date-filter" placeholder="End Date">
        <button id="downloadExcel" class="btn-primary" title="Export to Excel">
            <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;">
        </button>
    </div>
</div>
<div class="user-table-wrapper">
    <table class="user-table">
        <thead>
          <tr>
            <th><input type="text" class="filter-input" data-column="0"></th>
            <th><input type="text" class="filter-input" data-column="1"></th>
            <th><input type="text" class="filter-input" data-column="2"></th>
            <th><input type="text" class="filter-input" data-column="3"></th>
            <th><input type="text" class="filter-input" data-column="4"></th>
            <th><input type="text" class="filter-input" data-column="5"></th>
            <th><input type="text" class="filter-input" data-column="6"></th>
            <th><input type="text" class="filter-input" data-column="7"></th>
            <th><input type="text" class="filter-input" data-column="8"></th>
            <th><input type="text" class="filter-input" data-column="9"></th>
            <th><input type="text" class="filter-input" data-column="10"></th>
            <th><input type="text" class="filter-input" data-column="11"></th>
          </tr>

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
        // Modify the query to filter by document_type = 'Purchase'
        $query = "SELECT * FROM item_ledger_history WHERE document_type = 'Purchase' ORDER BY id DESC";
        $result = mysqli_query($connection, $query);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('.user-table tbody tr');
    const filterInputs = document.querySelectorAll('.filter-input');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = searchInput.value.toLowerCase();
        tableRows.forEach(function(row) {
            const cells = row.querySelectorAll('td');
            let rowText = '';
            cells.forEach(function(cell) {
                rowText += cell.textContent.toLowerCase() + ' ';
            });
            if (rowText.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    filterInputs.forEach(function(input) {
        input.addEventListener('keyup', function() {
            const filterValue = input.value.toLowerCase();
            const columnIndex = input.getAttribute('data-column');
            tableRows.forEach(function(row) {
                const cell = row.querySelectorAll('td')[columnIndex];
                if (cell) {
                    const cellText = cell.textContent.toLowerCase();
                    if (cellText.includes(filterValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    });

    startDateInput.addEventListener('change', filterByDate);
    endDateInput.addEventListener('change', filterByDate);

    function filterByDate() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        tableRows.forEach(function(row) {
            const dateCell = row.querySelectorAll('td')[9];
            if (dateCell) {
                const dateValue = dateCell.textContent.trim();
                const isVisible = (!startDate || dateValue >= startDate) && (!endDate || dateValue <= endDate);
                row.style.display = isVisible ? '' : 'none';
            }
        });
    }

    const downloadExcelButton = document.getElementById('downloadExcel');
    downloadExcelButton.addEventListener('click', function() {
        const table = document.querySelector('.user-table');
        const ws = XLSX.utils.table_to_sheet(table, { raw: true });
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Purchase Item Ledger');
        XLSX.writeFile(wb, 'purchase_item_ledger.xlsx');
    });
});
</script>
</body>
</html>
