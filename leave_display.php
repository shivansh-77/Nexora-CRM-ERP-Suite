<?php
session_start();
include('connection.php');
include('topbar.php');
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Leave Display</title>
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
            max-height: calc(100vh - 140px); /* Dynamic height based on viewport */
            min-height: 526px; /* Ensures it doesn't shrink too much */
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
        <h2 class="leadfor">Leave Requests</h2>
        <div class="lead-actions">
            <input type="text" id="globalSearch" class="filter-input" placeholder="Search all records...">
            <select id="timePeriodFilter" class="filter-select">
                <option value="all">All</option>
                <option value="today" selected>Today</option>
                <option value="thisMonth">This Month</option>
                <option value="lastMonth">Last Month</option>
                <option value="last3Months">Last 3 Months</option>
            </select>
            <input type="date" id="startDateFilter" class="date-filter">
            <input type="date" id="endDateFilter" class="date-filter">
            <button id="downloadExcel" class="btn-primary" title="Download Excel File">
                <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;">
            </button>
            <!-- <a style="text-decoration:None;" href="user_leave_add.php" class="btn-primary">➕</a> -->
        </div>
    </div>

    <div class="user-table-wrapper">
        <table class="user-table" id="leaveTable">
            <thead>
                <!-- Filter Row -->
                <tr>
                    <th><input type="text" id="idFilter" class="filter-input" placeholder="Search ID"></th>
                    <th><input type="text" id="userNameFilter" class="filter-input" placeholder="Search User Name"></th>
                    <th><input type="text" id="leaveTypeFilter" class="filter-input" placeholder="Search Leave Type"></th>
                    <th><input type="text" id="startDateFilter" class="filter-input" placeholder="Search Start Date"></th>
                    <th><input type="text" id="endDateFilter" class="filter-input" placeholder="Search End Date"></th>
                    <th><input type="text" id="totalDaysFilter" class="filter-input" placeholder="Search Total Days"></th>
                    <th>
                        <select id="statusFilter" class="filter-select">
                            <option value="all">All</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </th>
                    <th><input type="text" id="approverNameFilter" class="filter-input" placeholder="Search Approver Name"></th>
                </tr>

                <!-- Table Headings Row -->
                <tr>
                    <th>ID</th>
                    <th>User Name</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Total Days</th>
                    <th>Status</th>
                    <th>Approver Name</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch all entries from the user_leave table
                $query = "SELECT * FROM user_leave ORDER BY id DESC";
                $result = mysqli_query($connection, $query);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['user_name']}</td>
                                <td>{$row['leave_type']}</td>
                                <td>{$row['start_date']}</td>
                                <td>{$row['end_date']}</td>
                                <td>{$row['total_days']}</td>
                                <td>{$row['status']}</td>
                                <td>{$row['approver_name']}</td>
                              </tr>";
                    }
                } else {
                   echo "<tr><td colspan='8' style='text-align: center;'>No records found</td></tr>";
                }
                mysqli_free_result($result);
                ?>
            </tbody>
        </table>
    </div>

    <!-- Include SheetJS library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.getElementById("downloadExcel").addEventListener("click", function() {
            let table = document.getElementById("leaveTable");

            // Clone the table to avoid modifying the original
            let clonedTable = table.cloneNode(true);

            // Remove the first row (filter row)
            clonedTable.deleteRow(0);

            let wb = XLSX.utils.book_new();
            let ws = XLSX.utils.table_to_sheet(clonedTable, { raw: true });

            XLSX.utils.book_append_sheet(wb, ws, "Leave Records");
            XLSX.writeFile(wb, "Leave_Records.xlsx");
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Helper function to parse dates in DD-MM-YYYY or DD/MM/YYYY format
        function parseDate(dateString) {
            if (!dateString) return null;
            const parts = dateString.split(/[-\/]/); // Split by '-' or '/'
            if (parts.length === 3) {
                const day = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1; // Months are 0-based in JavaScript
                const year = parseInt(parts[2], 10);
                return new Date(year, month, day);
            }
            return null;
        }

        // Set default date filters to the first and last day of the current month
        const today = new Date();
        const firstDayOfThisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDayOfThisMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

        const formattedFirstDay = `${firstDayOfThisMonth.getFullYear()}-${String(firstDayOfThisMonth.getMonth() + 1).padStart(2, '0')}-${String(firstDayOfThisMonth.getDate()).padStart(2, '0')}`;
        const formattedLastDay = `${lastDayOfThisMonth.getFullYear()}-${String(lastDayOfThisMonth.getMonth() + 1).padStart(2, '0')}-${String(lastDayOfThisMonth.getDate()).padStart(2, '0')}`;

        document.getElementById('startDateFilter').value = formattedFirstDay;
        document.getElementById('endDateFilter').value = formattedLastDay;

        // Set default option for timePeriodFilter to "This Month"
        document.getElementById('timePeriodFilter').value = 'thisMonth';

        // Call filterTable to apply the default filters
        filterTable();

        // Add event listeners for all filter inputs
        document.getElementById('globalSearch').addEventListener('input', filterTable);
        document.getElementById('idFilter').addEventListener('input', filterTable);
        document.getElementById('userNameFilter').addEventListener('input', filterTable);
        document.getElementById('leaveTypeFilter').addEventListener('input', filterTable);
        document.getElementById('startDateFilter').addEventListener('input', filterTable);
        document.getElementById('endDateFilter').addEventListener('input', filterTable);
        document.getElementById('totalDaysFilter').addEventListener('input', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);
        document.getElementById('approverNameFilter').addEventListener('input', filterTable);
        document.getElementById('timePeriodFilter').addEventListener('change', function () {
            const selectedOption = this.value;
            const today = new Date();
            const startDateFilter = document.getElementById('startDateFilter');
            const endDateFilter = document.getElementById('endDateFilter');

            switch (selectedOption) {
                case 'today':
                    const formattedToday = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                    startDateFilter.value = formattedToday;
                    endDateFilter.value = formattedToday;
                    break;
                case 'lastMonth':
                    const firstDayOfLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const lastDayOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                    startDateFilter.value = `${firstDayOfLastMonth.getFullYear()}-${String(firstDayOfLastMonth.getMonth() + 1).padStart(2, '0')}-${String(firstDayOfLastMonth.getDate()).padStart(2, '0')}`;
                    endDateFilter.value = `${lastDayOfLastMonth.getFullYear()}-${String(lastDayOfLastMonth.getMonth() + 1).padStart(2, '0')}-${String(lastDayOfLastMonth.getDate()).padStart(2, '0')}`;
                    break;
                case 'last3Months':
                    const firstDayOfLast3Months = new Date(today.getFullYear(), today.getMonth() - 3, 1);
                    const lastDayOfLast3Months = new Date(today.getFullYear(), today.getMonth(), 0);
                    startDateFilter.value = `${firstDayOfLast3Months.getFullYear()}-${String(firstDayOfLast3Months.getMonth() + 1).padStart(2, '0')}-${String(firstDayOfLast3Months.getDate()).padStart(2, '0')}`;
                    endDateFilter.value = `${lastDayOfLast3Months.getFullYear()}-${String(lastDayOfLast3Months.getMonth() + 1).padStart(2, '0')}-${String(lastDayOfLast3Months.getDate()).padStart(2, '0')}`;
                    break;
                case 'thisMonth':
                    const firstDayOfThisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    const lastDayOfThisMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    startDateFilter.value = `${firstDayOfThisMonth.getFullYear()}-${String(firstDayOfThisMonth.getMonth() + 1).padStart(2, '0')}-${String(firstDayOfThisMonth.getDate()).padStart(2, '0')}`;
                    endDateFilter.value = `${lastDayOfThisMonth.getFullYear()}-${String(lastDayOfThisMonth.getMonth() + 1).padStart(2, '0')}-${String(lastDayOfThisMonth.getDate()).padStart(2, '0')}`;
                    break;
                case 'all':
                    startDateFilter.value = '';
                    endDateFilter.value = '';
                    break;
            }

            // Apply filters after updating the date range
            filterTable();
        });

        function filterTable() {
            const searchQuery = document.getElementById('globalSearch').value.toLowerCase();
            const idFilter = document.getElementById('idFilter').value.toLowerCase();
            const userNameFilter = document.getElementById('userNameFilter').value.toLowerCase();
            const leaveTypeFilter = document.getElementById('leaveTypeFilter').value.toLowerCase();
            const startDateFilterValue = document.getElementById('startDateFilter').value;
            const endDateFilterValue = document.getElementById('endDateFilter').value;
            const totalDaysFilter = document.getElementById('totalDaysFilter').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const approverNameFilter = document.getElementById('approverNameFilter').value.toLowerCase();

            document.querySelectorAll('.user-table tbody tr').forEach(row => {
                const rowText = row.innerText.toLowerCase();
                const idText = row.children[0].textContent.trim().toLowerCase();
                const userNameText = row.children[1].textContent.trim().toLowerCase();
                const leaveTypeText = row.children[2].textContent.trim().toLowerCase();
                const startDateText = row.children[3].textContent.trim();
                const endDateText = row.children[4].textContent.trim();
                const totalDaysText = row.children[5].textContent.trim().toLowerCase();
                const statusText = row.children[6].textContent.trim();
                const approverNameText = row.children[7].textContent.trim().toLowerCase();

                // Parse the start and end dates into Date objects
                const rowStartDate = parseDate(startDateText);
                const rowEndDate = parseDate(endDateText);
                const startDate = parseDate(startDateFilterValue);
                const endDate = parseDate(endDateFilterValue);

                // Check if the row date falls within the selected range
                let dateMatch = true;
                if (startDate && endDate) {
                    dateMatch = rowStartDate && rowStartDate >= startDate && rowEndDate <= endDate;
                } else if (startDate) {
                    dateMatch = rowStartDate && rowStartDate >= startDate;
                } else if (endDate) {
                    dateMatch = rowEndDate && rowEndDate <= endDate;
                }

                // Check if the row matches all filters
                let showRow = (idFilter === '' || idText.includes(idFilter)) &&
                              (userNameFilter === '' || userNameText.includes(userNameFilter)) &&
                              (leaveTypeFilter === '' || leaveTypeText.includes(leaveTypeFilter)) &&
                              (totalDaysFilter === '' || totalDaysText.includes(totalDaysFilter)) &&
                              (statusFilter === 'all' || statusText === statusFilter) &&
                              (approverNameFilter === '' || approverNameText.includes(approverNameFilter)) &&
                              dateMatch &&
                              (searchQuery === '' || rowText.includes(searchQuery));

                // Show or hide the row based on the filters
                row.style.display = showRow ? '' : 'none';
            });
        }
    });
    </script>
</body>
</html>
