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
    <title>Holiday Display</title>
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
            min-height: 100vh;
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
            padding: 12px;
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

        .user-table td {
            text-align: left;
            padding: 12px;
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
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2>Holiday Display</h2>
        <div class="lead-actions">
            <input type="text" id="globalSearch" class="filter-input" placeholder="Search all records...">
            <select id="timePeriodFilter" class="filter-select">
                <option value="all">All</option>
                <option value="thisMonth">This Month</option>
                <option value="thisYear" selected>This Year</option>
            </select>
            <input type="date" id="startDateFilter" class="date-filter">
            <input type="date" id="endDateFilter" class="date-filter">
            <button id="downloadExcel" class="btn-primary" title="Download Excel File">
                <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;">
            </button>
            <!-- <a style="text-decoration:None;" href="holidays_add.php" class="btn-primary" title="Add New Holiday">➕</a> -->
        </div>
    </div>

    <div class="user-table-wrapper">
        <table class="user-table" id="holidayTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Holiday Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Total Days</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT * FROM holidays";
                $stmt = $connection->prepare($query);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['holiday_name']}</td>
                                <td>{$row['start_date']}</td>
                                <td>{$row['end_date']}</td>
                                <td>{$row['total_days']}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align: center;'>No records found</td></tr>";
                }
                $stmt->close();
                ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.getElementById("downloadExcel").addEventListener("click", function() {
            let table = document.getElementById("holidayTable");
            let wb = XLSX.utils.book_new();
            let ws = XLSX.utils.table_to_sheet(table, { raw: true });

            XLSX.utils.book_append_sheet(wb, ws, "Holiday Records");
            XLSX.writeFile(wb, "Holiday_Records.xlsx");
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Get current date
        const today = new Date();

        // Set first and last day of current month
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

        // Set first and last day of current year
        const firstDayOfYear = new Date(today.getFullYear(), 0, 1);
        const lastDayOfYear = new Date(today.getFullYear(), 11, 31);

        // Format dates for input fields (YYYY-MM-DD)
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Set initial date filter values for "This Year"
        document.getElementById('startDateFilter').value = formatDate(firstDayOfYear);
        document.getElementById('endDateFilter').value = formatDate(lastDayOfYear);

        // Function to parse date string (handles both YYYY-MM-DD and DD/MM/YYYY formats)
        function parseDate(dateString) {
            if (!dateString) return null;

            // Try YYYY-MM-DD format first
            const parts1 = dateString.split('-');
            if (parts1.length === 3) {
                const year = parseInt(parts1[0], 10);
                const month = parseInt(parts1[1], 10) - 1;
                const day = parseInt(parts1[2], 10);
                return new Date(year, month, day);
            }

            // Try DD/MM/YYYY format
            const parts2 = dateString.split('/');
            if (parts2.length === 3) {
                const day = parseInt(parts2[0], 10);
                const month = parseInt(parts2[1], 10) - 1;
                const year = parseInt(parts2[2], 10);
                return new Date(year, month, day);
            }

            return null;
        }

        // Filter table based on search and date range
        function filterTable() {
            const searchQuery = document.getElementById('globalSearch').value.toLowerCase();
            const startDateFilterValue = document.getElementById('startDateFilter').value;
            const endDateFilterValue = document.getElementById('endDateFilter').value;
            const periodFilter = document.getElementById('timePeriodFilter').value;

            const startDate = parseDate(startDateFilterValue);
            const endDate = parseDate(endDateFilterValue);

            document.querySelectorAll('#holidayTable tbody tr').forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const startDateCell = row.cells[2].textContent.trim();
                const rowStartDate = parseDate(startDateCell);

                // Check if row matches search query
                const matchesSearch = searchQuery === '' || rowText.includes(searchQuery);

                // Check if row matches date filter
                let matchesDate = true;

                if (periodFilter === 'thisMonth') {
                    matchesDate = rowStartDate &&
                                 rowStartDate.getFullYear() === today.getFullYear() &&
                                 rowStartDate.getMonth() === today.getMonth();
                }
                else if (periodFilter === 'thisYear') {
                    matchesDate = rowStartDate &&
                                 rowStartDate.getFullYear() === today.getFullYear();
                }
                else if (startDate && endDate) {
                    matchesDate = rowStartDate &&
                                 rowStartDate >= startDate &&
                                 rowStartDate <= endDate;
                }
                else if (startDate) {
                    matchesDate = rowStartDate && rowStartDate >= startDate;
                }
                else if (endDate) {
                    matchesDate = rowStartDate && rowStartDate <= endDate;
                }

                // Show/hide row based on filters
                row.style.display = (matchesSearch && matchesDate) ? '' : 'none';
            });
        }

        // Event listeners
        document.getElementById('globalSearch').addEventListener('input', filterTable);
        document.getElementById('startDateFilter').addEventListener('change', filterTable);
        document.getElementById('endDateFilter').addEventListener('change', filterTable);

        document.getElementById('timePeriodFilter').addEventListener('change', function() {
            const period = this.value;

            switch(period) {
                case 'thisMonth':
                    document.getElementById('startDateFilter').value = formatDate(firstDayOfMonth);
                    document.getElementById('endDateFilter').value = formatDate(lastDayOfMonth);
                    break;
                case 'thisYear':
                    document.getElementById('startDateFilter').value = formatDate(firstDayOfYear);
                    document.getElementById('endDateFilter').value = formatDate(lastDayOfYear);
                    break;
                case 'all':
                    document.getElementById('startDateFilter').value = '';
                    document.getElementById('endDateFilter').value = '';
                    break;
            }

            filterTable();
        });

        // Initial filter
        filterTable();
    });
    </script>
</body>
</html>
