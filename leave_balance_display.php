<?php
session_start(); // Start the session

// Include your database connection file
include('connection.php');

// Check if the user is logged in (optional, depending on your use case)
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('User not logged in.'); window.location.href='login.php';</script>";
    exit;
}

// Get today's date
$today = new DateTime();
$todayFormatted = $today->format('Y-m-d');

// Fetch all users from the user_leave_balance table
$leaveBalanceQuery = "SELECT * FROM user_leave_balance";
$stmt = $connection->prepare($leaveBalanceQuery);
$stmt->execute();
$leaveBalanceResult = $stmt->get_result();

if ($leaveBalanceResult && mysqli_num_rows($leaveBalanceResult) > 0) {
    while ($leaveBalance = $leaveBalanceResult->fetch_assoc()) {
        $user_id = $leaveBalance['user_id'];
        $next_update = $leaveBalance['next_update'];

        // Check if today's date matches or exceeds the next_update date
        if ($today >= new DateTime($next_update)) {
            // Add 1.5 earned leaves
            $earnedLeavesToAdd = 1.5;
            $newTotalEarnedLeaves = $leaveBalance['total_earned_leaves'] + $earnedLeavesToAdd;

            // Calculate the next_update date (next month's same date)
            $nextUpdateDate = new DateTime($next_update);
            $nextUpdateDate->modify('+1 month');
            $nextUpdateDateFormatted = $nextUpdateDate->format('Y-m-d');

            // Update the user_leave_balance table for this user
            $updateQuery = "UPDATE user_leave_balance
                            SET total_earned_leaves = ?, last_updated = ?, next_update = ?
                            WHERE user_id = ?";
            $stmt = $connection->prepare($updateQuery);
            $stmt->bind_param("dssi", $newTotalEarnedLeaves, $todayFormatted, $nextUpdateDateFormatted, $user_id);
            $stmt->execute();
        }
    }
} else {
    echo "<script>alert('No users found in leave balance table.'); window.location.href='user_leave_display.php';</script>";
    exit;
}

// Optionally, you can redirect or display a success message
echo "<script>alert('Leave balances updated for all users.'); window.location.href='user_leave_display.php';</script>";
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Leave Balance Display</title>
    <style>
        /* Table Styles */
        /* Prevent the body from scrolling */
        html, body {
            overflow: hidden;
            height: 100%;
            margin: 0;
        }

        /* Table Wrapper with Scroll */
        .user-table-wrapper {
            width: calc(100% - 260px);
            margin-left: 260px;
            margin-top: 140px;
            max-height: 525px; /* Fixed height for the table wrapper */
            overflow-y: auto; /* Enable vertical scrolling only inside the table */
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
            width: 79%;
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


        .highlight-red {
          background-color: #ffcccc; /* Light red background */
          }

               /* Ensure the highlight remains on hover */
          .highlight-red:hover {
          background-color: #ffcccc !important; /* Force the same color on hover */
        }
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2 class="leadfor">Leave Balance Display</h2>
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
            <button id="downloadExcel" class="btn-primary">
                <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;">
            </button>
        </div>
    </div>

    <div class="user-table-wrapper">
        <table class="user-table" id="leaveTable">
            <thead>
                <!-- Filter Row -->
                <tr>
                    <th><input type="text" id="idFilter" class="filter-input" placeholder="Search ID"></th>
                    <th><input type="text" id="userNameFilter" class="filter-input" placeholder="Search User Name"></th>
                    <th><input type="text" id="dojFilter" class="filter-input" placeholder="Search D.O.J"></th>
                    <th><input type="text" id="totalSickLeavesFilter" class="filter-input" placeholder="Search Total Sick Leaves"></th>
                    <th><input type="text" id="totalEarnedLeavesFilter" class="filter-input" placeholder="Search Total Earned Leaves"></th>
                    <th><input type="text" id="sickLeavesTakenFilter" class="filter-input" placeholder="Search Sick Leaves Taken"></th>
                    <th><input type="text" id="earnedLeavesTakenFilter" class="filter-input" placeholder="Search Earned Leaves Taken"></th>
                    <th><input type="text" id="halfDayLeavesTakenFilter" class="filter-input" placeholder="Search Half Day Leaves Taken"></th>
                    <th><input type="text" id="lastUpdatedFilter" class="filter-input" placeholder="Search Last Updated"></th>
                    <th><input type="text" id="nextUpdateFilter" class="filter-input" placeholder="Search Next Update"></th>
                </tr>

                <!-- Table Headings Row -->
                <tr>
                    <th>ID</th>
                    <th>User Name</th>
                    <th>D.O.J</th>
                    <th>Total Sick Leaves</th>
                    <th>Total Earned Leaves</th>
                    <th>Sick Leaves Taken</th>
                    <th>Earned Leaves Taken</th>
                    <th>Half Day Leaves Taken</th>
                    <th>Last Updated</th>
                    <th>Next Update</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch all entries from the user_leave_balance table
                $query = "SELECT * FROM user_leave_balance";
                $result = mysqli_query($connection, $query);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['name']}</td>
                                <td>{$row['D.O.J']}</td>
                                <td>{$row['total_sick_leaves']}</td>
                                <td>{$row['total_earned_leaves']}</td>
                                <td>{$row['sick_leaves_taken']}</td>
                                <td>{$row['earned_leaves_taken']}</td>
                                <td>{$row['half_day_leaves_taken']}</td>
                                <td>{$row['last_updated']}</td>
                                <td>{$row['next_update']}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='10'>No leave balance records found</td></tr>";
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

            XLSX.utils.book_append_sheet(wb, ws, "Leave Balance Records");
            XLSX.writeFile(wb, "Leave_Balance_Records.xlsx");
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
        document.getElementById('dojFilter').addEventListener('input', filterTable);
        document.getElementById('totalSickLeavesFilter').addEventListener('input', filterTable);
        document.getElementById('totalEarnedLeavesFilter').addEventListener('input', filterTable);
        document.getElementById('sickLeavesTakenFilter').addEventListener('input', filterTable);
        document.getElementById('earnedLeavesTakenFilter').addEventListener('input', filterTable);
        document.getElementById('halfDayLeavesTakenFilter').addEventListener('input', filterTable);
        document.getElementById('lastUpdatedFilter').addEventListener('input', filterTable);
        document.getElementById('nextUpdateFilter').addEventListener('input', filterTable);
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
            const dojFilter = document.getElementById('dojFilter').value.toLowerCase();
            const totalSickLeavesFilter = document.getElementById('totalSickLeavesFilter').value.toLowerCase();
            const totalEarnedLeavesFilter = document.getElementById('totalEarnedLeavesFilter').value.toLowerCase();
            const sickLeavesTakenFilter = document.getElementById('sickLeavesTakenFilter').value.toLowerCase();
            const earnedLeavesTakenFilter = document.getElementById('earnedLeavesTakenFilter').value.toLowerCase();
            const halfDayLeavesTakenFilter = document.getElementById('halfDayLeavesTakenFilter').value.toLowerCase();
            const lastUpdatedFilter = document.getElementById('lastUpdatedFilter').value.toLowerCase();
            const nextUpdateFilter = document.getElementById('nextUpdateFilter').value.toLowerCase();

            document.querySelectorAll('.user-table tbody tr').forEach(row => {
                const rowText = row.innerText.toLowerCase();
                const idText = row.children[0].textContent.trim().toLowerCase();
                const userNameText = row.children[1].textContent.trim().toLowerCase();
                const dojText = row.children[2].textContent.trim().toLowerCase();
                const totalSickLeavesText = row.children[3].textContent.trim().toLowerCase();
                const totalEarnedLeavesText = row.children[4].textContent.trim().toLowerCase();
                const sickLeavesTakenText = row.children[5].textContent.trim().toLowerCase();
                const earnedLeavesTakenText = row.children[6].textContent.trim().toLowerCase();
                const halfDayLeavesTakenText = row.children[7].textContent.trim().toLowerCase();
                const lastUpdatedText = row.children[8].textContent.trim().toLowerCase();
                const nextUpdateText = row.children[9].textContent.trim().toLowerCase();

                // Check if the row matches all filters
                let showRow = (idFilter === '' || idText.includes(idFilter)) &&
                              (userNameFilter === '' || userNameText.includes(userNameFilter)) &&
                              (dojFilter === '' || dojText.includes(dojFilter)) &&
                              (totalSickLeavesFilter === '' || totalSickLeavesText.includes(totalSickLeavesFilter)) &&
                              (totalEarnedLeavesFilter === '' || totalEarnedLeavesText.includes(totalEarnedLeavesFilter)) &&
                              (sickLeavesTakenFilter === '' || sickLeavesTakenText.includes(sickLeavesTakenFilter)) &&
                              (earnedLeavesTakenFilter === '' || earnedLeavesTakenText.includes(earnedLeavesTakenFilter)) &&
                              (halfDayLeavesTakenFilter === '' || halfDayLeavesTakenText.includes(halfDayLeavesTakenFilter)) &&
                              (lastUpdatedFilter === '' || lastUpdatedText.includes(lastUpdatedFilter)) &&
                              (nextUpdateFilter === '' || nextUpdateText.includes(nextUpdateFilter)) &&
                              (searchQuery === '' || rowText.includes(searchQuery));

                // Show or hide the row based on the filters
                row.style.display = showRow ? '' : 'none';
            });
        }
    });
    </script>
</body>
</html>
