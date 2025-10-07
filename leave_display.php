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
    <title>Leave Display</title>
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
        max-height: calc(100vh - 150px); /* Adjust based on your layout */
        overflow-y: auto; /* Enable vertical scrolling */
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
        white-space: nowrap; /* Ensure text stays in a single line */
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
    .user-table thead tr:nth-child(2) th {
        top: 50px;
        background-color: #2c3e50;
    }
    .user-table td {
        text-align: left;
        padding: 8px;
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
        white-space: nowrap; /* Ensure no wrapping on hover */
        overflow: hidden; /* Prevent overflow */
        text-overflow: ellipsis; /* Show ellipsis if text overflows */
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
    .highlight-red {
        background-color: #ffcccc;
    }
    .highlight-red:hover {
        background-color: #ffcccc !important;
    }

    /* read-only column-filter appearance */
    .col-filter-readonly {
        background-color: #fff;
        color: #333;
        border: 1px solid #ddd;
        padding: 6px;
        border-radius: 6px;
        width: 100%;
        box-sizing: border-box;
    }
    .col-filter-readonly[readonly] {
        cursor: default;
    }
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2 class="leadfor">Leave Requests</h2>
        <div class="lead-actions">
            <input type="text" id="globalSearch" class="filter-input" placeholder="Search all records...">
            <select id="timePeriodFilter" class="filter-select">
                <option value="all" selected>All</option>
                <option value="today">Today</option>
                <option value="thisMonth">This Month</option>
                <option value="lastMonth">Last Month</option>
                <option value="last3Months">Last 3 Months</option>
            </select>
            <!-- Top-level date range controls (used for filtering) -->
            <input type="date" id="startDateFilter" class="date-filter" title="Start date">
            <input type="date" id="endDateFilter" class="date-filter" title="End date">
            <button id="downloadExcel" class="btn-primary" title="Download Excel File">
                <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;">
            </button>
            <!-- <a style="text-decoration:None;" href="user_leave_add.php" class="btn-primary">➕</a> -->
        </div>
    </div>

    <div class="user-table-wrapper">
        <table class="user-table" id="leaveTable">
            <thead>
                <!-- Filter Row (textual/status filters + read-only date mirrors for visual consistency) -->
                <tr>
                    <th><input type="text" id="idFilter" class="filter-input" placeholder="Search ID"></th>
                    <th><input type="text" id="userNameFilter" class="filter-input" placeholder="Search User Name"></th>
                    <th><input type="text" id="leaveTypeFilter" class="filter-input" placeholder="Search Leave Type"></th>
                    <th>
                        <!-- Start Date column read-only mirror (reflects top startDateFilter) -->
                        <input type="text" id="startDateColumnFilter" class="col-filter-readonly" readonly placeholder="Controlled by top filter">
                    </th>
                    <th>
                        <!-- End Date column read-only mirror (reflects top endDateFilter) -->
                        <input type="text" id="endDateColumnFilter" class="col-filter-readonly" readonly placeholder="Controlled by top filter">
                    </th>
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
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Escape output to avoid broken HTML (minimal escaping)
                        $id = htmlspecialchars($row['id']);
                        $user_name = htmlspecialchars($row['user_name']);
                        $leave_type = htmlspecialchars($row['leave_type']);
                        $start_date = htmlspecialchars($row['start_date']);
                        $end_date = htmlspecialchars($row['end_date']);
                        $total_days = htmlspecialchars($row['total_days']);
                        $status = htmlspecialchars($row['status']);
                        $approver_name = htmlspecialchars($row['approver_name']);
                        echo "<tr>
                                <td>{$id}</td>
                                <td>{$user_name}</td>
                                <td>{$leave_type}</td>
                                <td>{$start_date}</td>
                                <td>{$end_date}</td>
                                <td>{$total_days}</td>
                                <td>{$status}</td>
                                <td>{$approver_name}</td>
                              </tr>";
                    }
                } else {
                   echo "<tr><td colspan='8' style='text-align: center;'>No records found</td></tr>";
                }
                if ($result) mysqli_free_result($result);
                ?>
            </tbody>
        </table>
    </div>

    <!-- Include SheetJS library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        // Export to Excel: remove the filter header row before exporting
        document.getElementById("downloadExcel").addEventListener("click", function() {
            let table = document.getElementById("leaveTable");
            // Clone the table to avoid modifying the original
            let clonedTable = table.cloneNode(true);

            // Remove the filter row (first thead tr) if present
            const thead = clonedTable.querySelector('thead');
            if (thead) {
                const filterRow = thead.querySelectorAll('tr')[0];
                if (filterRow) filterRow.remove();
            }

            // Convert to worksheet and download
            let wb = XLSX.utils.book_new();
            let ws = XLSX.utils.table_to_sheet(clonedTable, { raw: true });
            XLSX.utils.book_append_sheet(wb, ws, "Leave Records");
            XLSX.writeFile(wb, "Leave_Records.xlsx");
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // Helper parse function:
        // Accepts YYYY-MM-DD, DD-MM-YYYY, DD/MM/YYYY and returns a Date object (or null)
        function parseDateFlexible(dateString) {
            if (!dateString) return null;
            dateString = dateString.trim();
            // If already in ISO format YYYY-MM-DD
            const isoMatch = dateString.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (isoMatch) {
                return new Date(parseInt(isoMatch[1],10), parseInt(isoMatch[2],10)-1, parseInt(isoMatch[3],10));
            }
            // DD-MM-YYYY or DD/MM/YYYY
            const parts = dateString.split(/[-\/]/);
            if (parts.length === 3) {
                // If first part is 4-digit, treat as ISO
                if (parts[0].length === 4) {
                    return new Date(parseInt(parts[0],10), parseInt(parts[1],10)-1, parseInt(parts[2],10));
                } else {
                    const day = parseInt(parts[0], 10);
                    const month = parseInt(parts[1], 10) - 1;
                    const year = parseInt(parts[2], 10);
                    return new Date(year, month, day);
                }
            }
            // fallback: let Date try
            const d = new Date(dateString);
            return isNaN(d.getTime()) ? null : d;
        }

        // Format date to YYYY-MM-DD for input[type=date] and for display in readonly column filters
        function formatForInput(date) {
            if (!date || !(date instanceof Date)) return '';
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        // Utilities to compute month start/end
        function firstDayOfMonth(year, monthIndex) {
            return new Date(year, monthIndex, 1);
        }
        function lastDayOfMonth(year, monthIndex) {
            return new Date(year, monthIndex + 1, 0);
        }

        // DOM elements
        const timePeriodSelect = document.getElementById('timePeriodFilter');
        const startDateInput = document.getElementById('startDateFilter');
        const endDateInput = document.getElementById('endDateFilter');
        const startDateColumnInput = document.getElementById('startDateColumnFilter');
        const endDateColumnInput = document.getElementById('endDateColumnFilter');

        // Initialize: 'all' selected, top date inputs empty, mirrored column filters empty
        timePeriodSelect.value = 'all';
        startDateInput.value = '';
        endDateInput.value = '';
        startDateColumnInput.value = '';
        endDateColumnInput.value = '';

        // Add event listeners for filters
        document.getElementById('globalSearch').addEventListener('input', filterTable);
        document.getElementById('idFilter').addEventListener('input', filterTable);
        document.getElementById('userNameFilter').addEventListener('input', filterTable);
        document.getElementById('leaveTypeFilter').addEventListener('input', filterTable);

        // date inputs: when user changes them manually, set timePeriod to 'all' and update mirrored column inputs
        startDateInput.addEventListener('input', function() {
            if (this.value) timePeriodSelect.value = 'all';
            startDateColumnInput.value = this.value ? this.value : '';
            filterTable();
        });
        endDateInput.addEventListener('input', function() {
            if (this.value) timePeriodSelect.value = 'all';
            endDateColumnInput.value = this.value ? this.value : '';
            filterTable();
        });

        document.getElementById('totalDaysFilter').addEventListener('input', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);
        document.getElementById('approverNameFilter').addEventListener('input', filterTable);

        // timePeriod change -> set start and end date inputs accordingly, mirror them in column filters and filter
        timePeriodSelect.addEventListener('change', function () {
            const selected = this.value;
            const today = new Date();
            let start = null;
            let end = null;

            switch (selected) {
                case 'today':
                    start = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                    end = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                    break;
                case 'thisMonth':
                    start = firstDayOfMonth(today.getFullYear(), today.getMonth());
                    end = lastDayOfMonth(today.getFullYear(), today.getMonth());
                    break;
                case 'lastMonth':
                    const lmYear = (today.getMonth() === 0) ? today.getFullYear() - 1 : today.getFullYear();
                    const lmMonth = (today.getMonth() === 0) ? 11 : today.getMonth() - 1;
                    start = firstDayOfMonth(lmYear, lmMonth);
                    end = lastDayOfMonth(lmYear, lmMonth);
                    break;
                case 'last3Months':
                    // Ignore current month, take previous 3 months
                    // start = first day of (currentMonth - 3)
                    // end = last day of (currentMonth - 1)
                    const startMonthDate = new Date(today.getFullYear(), today.getMonth() - 3, 1);
                    const endMonthDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    start = firstDayOfMonth(startMonthDate.getFullYear(), startMonthDate.getMonth());
                    end = lastDayOfMonth(endMonthDate.getFullYear(), endMonthDate.getMonth());
                    break;
                case 'all':
                default:
                    start = null;
                    end = null;
                    break;
            }

            startDateInput.value = start ? formatForInput(start) : '';
            endDateInput.value = end ? formatForInput(end) : '';
            // Mirror values in the readonly column filters
            startDateColumnInput.value = startDateInput.value;
            endDateColumnInput.value = endDateInput.value;

            filterTable();
        });

        // Filtering logic: uses the table's start_date and end_date columns (columns 3 and 4)
        function filterTable() {
            const searchQuery = document.getElementById('globalSearch').value.toLowerCase();
            const idFilter = document.getElementById('idFilter').value.toLowerCase();
            const userNameFilter = document.getElementById('userNameFilter').value.toLowerCase();
            const leaveTypeFilter = document.getElementById('leaveTypeFilter').value.toLowerCase();
            const startDateFilterValue = document.getElementById('startDateFilter').value; // YYYY-MM-DD or empty
            const endDateFilterValue = document.getElementById('endDateFilter').value;     // YYYY-MM-DD or empty
            const totalDaysFilter = document.getElementById('totalDaysFilter').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const approverNameFilter = document.getElementById('approverNameFilter').value.toLowerCase();

            const startFilterDate = startDateFilterValue ? parseDateFromInput(startDateFilterValue) : null;
            const endFilterDate = endDateFilterValue ? parseDateFromInput(endDateFilterValue) : null;

            document.querySelectorAll('.user-table tbody tr').forEach(row => {
                const rowText = row.innerText.toLowerCase();
                const idText = (row.children[0] && row.children[0].textContent) ? row.children[0].textContent.trim().toLowerCase() : '';
                const userNameText = (row.children[1] && row.children[1].textContent) ? row.children[1].textContent.trim().toLowerCase() : '';
                const leaveTypeText = (row.children[2] && row.children[2].textContent) ? row.children[2].textContent.trim().toLowerCase() : '';
                const startDateText = (row.children[3] && row.children[3].textContent) ? row.children[3].textContent.trim() : '';
                const endDateText = (row.children[4] && row.children[4].textContent) ? row.children[4].textContent.trim() : '';
                const totalDaysText = (row.children[5] && row.children[5].textContent) ? row.children[5].textContent.trim().toLowerCase() : '';
                const statusText = (row.children[6] && row.children[6].textContent) ? row.children[6].textContent.trim() : '';
                const approverNameText = (row.children[7] && row.children[7].textContent) ? row.children[7].textContent.trim().toLowerCase() : '';

                // Parse row dates (flexible)
                const rowStartDate = parseDateFlexible(startDateText); // prefer start_date column
                const rowEndDate = parseDateFlexible(endDateText);     // end_date column

                // DATE MATCH RULE:
                // - If no date filter set -> dateMatch = true (do not filter by date)
                // - If startFilter and/or endFilter provided:
                //     We check for overlap between [rowStartDate, rowEndDate] and [startFilterDate, endFilterDate].
                // - For single-day entries where start==end it still works (overlap check).
                let dateMatch = true;
                if (startFilterDate || endFilterDate) {
                    // If row has no start date, it cannot match
                    if (!rowStartDate) {
                        dateMatch = false;
                    } else {
                        // Determine the entry's effective start and end
                        const entryStart = rowStartDate;
                        const entryEnd = rowEndDate ? rowEndDate : rowStartDate; // if end missing, use start

                        // If only start filter is set
                        if (startFilterDate && !endFilterDate) {
                            // entry matches if entryEnd >= startFilterDate
                            dateMatch = entryEnd >= startFilterDate;
                        } else if (!startFilterDate && endFilterDate) {
                            // entry matches if entryStart <= endFilterDate
                            dateMatch = entryStart <= endFilterDate;
                        } else {
                            // Both start and end filters present -> overlap test:
                            // overlap exists if entryStart <= filterEnd AND entryEnd >= filterStart
                            dateMatch = (entryStart <= endFilterDate) && (entryEnd >= startFilterDate);
                        }
                    }
                }

                // Combined other filters
                let showRow = (idFilter === '' || idText.includes(idFilter)) &&
                              (userNameFilter === '' || userNameText.includes(userNameFilter)) &&
                              (leaveTypeFilter === '' || leaveTypeText.includes(leaveTypeFilter)) &&
                              (totalDaysFilter === '' || totalDaysText.includes(totalDaysFilter)) &&
                              (statusFilter === 'all' || statusText === statusFilter) &&
                              (approverNameFilter === '' || approverNameText.includes(approverNameFilter)) &&
                              dateMatch &&
                              (searchQuery === '' || rowText.includes(searchQuery));

                row.style.display = showRow ? '' : 'none';
            });
        }

        // Small helper to parse YYYY-MM-DD from input[type=date] reliably into Date
        function parseDateFromInput(value) {
            if (!value) return null;
            // value expected as YYYY-MM-DD
            const parts = value.split('-');
            if (parts.length === 3) {
                return new Date(parseInt(parts[0],10), parseInt(parts[1],10)-1, parseInt(parts[2],10));
            }
            const d = new Date(value);
            return isNaN(d.getTime()) ? null : d;
        }

        // Initial filter application
        filterTable();
    });
    </script>
</body>
</html>
