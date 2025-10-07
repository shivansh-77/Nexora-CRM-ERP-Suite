<?php
session_start();
include('connection.php');
include('topbar.php');

// Function to get the start and end dates for filters
function getDateRange($filter) {
    $today = date('Y-m-d');
    switch ($filter) {
        case 'today':
            return ["start" => $today, "end" => $today];
        case 'this_month':
            return ["start" => date('Y-m-01'), "end" => date('Y-m-t')];
        case 'last_3_months':
            return ["start" => date('Y-m-01', strtotime('-2 months')), "end" => date('Y-m-t')];
        case 'this_year':
            return ["start" => date('Y-01-01'), "end" => date('Y-12-31')];
        default:
            return ["start" => null, "end" => null];
    }
}

// Handle filter selection
$filter = $_GET['filter'] ?? null;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// If a filter is selected, override the start and end dates
if ($filter && $filter !== 'all') {
    $dateRange = getDateRange($filter);
    $startDate = $dateRange['start'];
    $endDate = $dateRange['end'];
}

// Build the query based on filters
$query = "SELECT * FROM expense";
$conditions = [];

if ($startDate && $endDate) {
    $conditions[] = "date BETWEEN '$startDate' AND '$endDate'";
} elseif ($startDate) {
    $conditions[] = "date >= '$startDate'";
} elseif ($endDate) {
    $conditions[] = "date <= '$endDate'";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY id DESC";

// Fetch data from the database
$result = mysqli_query($connection, $query);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <title>Expense Tracker Display</title>
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
        }

        .user-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .user-table tr:hover {
            background-color: #f1f1f1;
        }

        .user-table td:last-child {
            text-align: left; /* Align buttons to the right */
            width: 20px; /* Further reduce the width of the action column */
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
            margin-right: 30px;
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

        #downloadExcel {
            background-color: green;
        }

        .filter-container {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-right: 20px;
        }

        .filter-container select,
        .filter-container input[type="date"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2 class="leadfor">Expense Tracker</h2>
        <div class="lead-actions">
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search...">
                <button class="btn-search" id="searchButton">🔍</button>
            </div>
            <div class="filter-container">
                <select id="dateFilter">
                    <option value="all">All</option>
                    <option value="today">Today</option>
                    <option value="this_month">This Month</option>
                    <option value="last_3_months">Last 3 Months</option>
                    <option value="this_year">This Year</option>
                </select>
                <input type="date" id="startDate">
                <input type="date" id="endDate">
            </div>
            <a href="expense_add.php">
                <button class="btn-primary" id="openModal" title="Add New Expense" data-mode="add">➕</button>
            </a>
            <button id="downloadExcel" class="btn-primary">
                <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;">
            </button>
        </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table">
    <thead>
        <tr>
            <th>Voucher No.</th>
            <th>Expense Type</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Added By</th>
            <th>Remark</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
              echo "<tr ondblclick=\"window.location.href='expense_edit.php?id={$row['id']}'\" style='cursor: pointer;'>

                        <td>{$row['voucher_no']}</td>
                        <td>{$row['expense_type']}</td>
                        <td>{$row['amount']}</td>
                        <td>{$row['date']}</td>
                        <td>{$row['user_name']}</td>
                        <td>{$row['remark']}</td>
                        <td>
                            <button class='btn-warning edit-btn' title='Update Expense'
                                onclick=\"window.location.href='expense_edit.php?id={$row['id']}';\">✏️</button>
                            <button class='btn-danger' title='Delete this Expense'
                                onclick=\"if(confirm('Are you sure you want to delete this record?')) {
                                    window.location.href='expense_delete.php?id={$row['id']}';
                                }\">🗑️</button>
                        </td>
                      </tr>";
            }
        } else {
           echo "<tr><td colspan='7' style='text-align: center;'>No records found</td></tr>";
        }
        ?>
    </tbody>
</table>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const tableRows = document.querySelectorAll('.user-table tbody tr');
        const dateFilter = document.getElementById('dateFilter');
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');

        // Search functionality
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

        // Apply filter functionality
        dateFilter.addEventListener('change', applyFilters);
        startDateInput.addEventListener('change', applyFilters);
        endDateInput.addEventListener('change', applyFilters);

        function applyFilters() {
            const filter = dateFilter.value;
            const start = startDateInput.value;
            const end = endDateInput.value;

            let url = 'expense_display.php?';

            if (filter === 'all') {
                url += 'filter=all';
            } else if (filter) {
                url += `filter=${filter}`;
            } else {
                if (start) url += `start_date=${start}`;
                if (end) {
                    if (start) {
                        url += `&end_date=${end}`;
                    } else {
                        url += `end_date=${end}`;
                    }
                }
            }

            // Update the URL without refreshing the page
            history.pushState({ filter, start, end }, '', url);

            // Fetch and display the filtered data
            fetchFilteredData(filter, start, end);
        }

        function fetchFilteredData(filter, start, end) {
            // Construct the query parameters
            const params = new URLSearchParams();
            if (filter) params.append('filter', filter);
            if (start) params.append('start_date', start);
            if (end) params.append('end_date', end);

            // Fetch the data from the server
            fetch(`expense_display.php?${params.toString()}`)
                .then(response => response.text())
                .then(data => {
                    // Update the table content with the filtered data
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const newTableRows = doc.querySelectorAll('.user-table tbody tr');
                    const tableBody = document.querySelector('.user-table tbody');
                    tableBody.innerHTML = '';
                    newTableRows.forEach(row => tableBody.appendChild(row));
                })
                .catch(error => console.error('Error fetching filtered data:', error));
        }

        // Download Excel
        const downloadExcelButton = document.getElementById('downloadExcel');
        downloadExcelButton.addEventListener('click', function() {
            const table = document.querySelector('.user-table');
            const clonedTable = table.cloneNode(true);
            const actionColumn = clonedTable.querySelectorAll('th:last-child, td:last-child');

            actionColumn.forEach(col => col.remove());

            const ws = XLSX.utils.table_to_sheet(clonedTable, { raw: true });
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Expense Tracker');
            XLSX.writeFile(wb, 'expense_tracker.xlsx');
        });
    });

    </script>
</body>
</html>
