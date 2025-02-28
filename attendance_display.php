<?php
session_start();
include('connection.php');
include('topbar.php');

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Attendance Display</title>
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
    </style>
  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">Attendance</h2>
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
      <table class="user-table" id="attendanceTable">
        <thead>
          <!-- Filter Row -->
          <tr>
            <th><input type="text" id="idFilter" class="filter-input" placeholder="Search ID"></th>
            <th><input type="text" id="userIdFilter" class="filter-input" placeholder="Search User ID"></th>
            <th><input type="text" id="userNameFilter" class="filter-input" placeholder="Search User Name"></th>
            <th><input type="text" id="loginTimeFilter" class="filter-input" placeholder="Search Checkin Time"></th>
            <th><input type="text" id="logoutTimeFilter" class="filter-input" placeholder="Search Checkout Time"></th>
            <th><input type="text" id="sessionDurationFilter" class="filter-input" placeholder="Search Duration"></th>
            <th>
              <select id="sessionStatusFilter" class="filter-select">
                <option value="all">All</option>
                <option value="active">Active</option>
                <option value="ended">Ended</option>
              </select>
            </th>
            <th><input type="text" id="locationFilter" class="filter-input" placeholder="Search Location"></th>
          </tr>

          <!-- Table Headings Row -->
          <tr>
            <th>ID</th>
            <th>User ID</th>
            <th>User Name</th>
            <th>Checkin Time</th>
            <th>Checkout Time</th>
            <th>Session Duration</th>
            <th>Session Status</th>
            <th>Location</th>
          </tr>
        </thead>
        <tbody>
          <?php
          include('connection.php');
          $query = "SELECT * FROM attendance";
          $result = mysqli_query($connection, $query);

          if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
              echo "<tr>
                      <td>{$row['id']}</td>
                      <td>{$row['user_id']}</td>
                      <td>{$row['user_name']}</td>
                      <td>{$row['checkin_time']}</td>
                      <td>{$row['checkout_time']}</td>
                      <td>{$row['session_duration']}</td>
                      <td>{$row['session_status']}</td>
                      <td>{$row['checkin_location']}</td>
                    </tr>";
            }
          } else {
            echo "<tr><td colspan='9'>No records found</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>

    <!-- Include SheetJS library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
      document.getElementById("downloadExcel").addEventListener("click", function() {
        let table = document.getElementById("attendanceTable");

        // Clone the table to avoid modifying the original
        let clonedTable = table.cloneNode(true);

        // Remove the first row (filter row)
        clonedTable.deleteRow(0);

        let wb = XLSX.utils.book_new();
        let ws = XLSX.utils.table_to_sheet(clonedTable, { raw: true });

        XLSX.utils.book_append_sheet(wb, ws, "Attendance Records");
        XLSX.writeFile(wb, "Attendance_Records.xlsx");
      });
    </script>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        // Set default date filters to today's date
        const today = new Date().toISOString().split('T')[0]; // Format: YYYY-MM-DD
        document.getElementById('startDateFilter').value = today;
        document.getElementById('endDateFilter').value = today;

        // Set default option for timePeriodFilter to "Today"
        document.getElementById('timePeriodFilter').value = 'today';

        // Call filterTable to apply the default filters
        filterTable();

        // Add event listener for timePeriodFilter
        document.getElementById('timePeriodFilter').addEventListener('change', function () {
          const selectedOption = this.value;
          const today = new Date();
          const startDateFilter = document.getElementById('startDateFilter');
          const endDateFilter = document.getElementById('endDateFilter');

          switch (selectedOption) {
            case 'today':
              startDateFilter.value = today.toISOString().split('T')[0];
              endDateFilter.value = today.toISOString().split('T')[0];
              break;
            case 'lastMonth':
              const firstDayOfLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
              const lastDayOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
              startDateFilter.value = firstDayOfLastMonth.toISOString().split('T')[0];
              endDateFilter.value = lastDayOfLastMonth.toISOString().split('T')[0];
              break;
            case 'last3Months':
              const firstDayOfLast3Months = new Date(today.getFullYear(), today.getMonth() - 3, 1);
              const lastDayOfLast3Months = new Date(today.getFullYear(), today.getMonth(), 0);
              startDateFilter.value = firstDayOfLast3Months.toISOString().split('T')[0];
              endDateFilter.value = lastDayOfLast3Months.toISOString().split('T')[0];
              break;
            case 'thisMonth':
              const firstDayOfThisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
              const lastDayOfThisMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
              startDateFilter.value = firstDayOfThisMonth.toISOString().split('T')[0];
              endDateFilter.value = lastDayOfThisMonth.toISOString().split('T')[0];
              break;
            case 'all':
              startDateFilter.value = '';
              endDateFilter.value = '';
              break;
          }

          // Apply filters after updating the date range
          filterTable();
        });

        // Add event listeners for other filters
        document.querySelectorAll('.filter-input, .filter-select').forEach(input => {
          input.addEventListener('input', filterTable);
        });

        document.getElementById('startDateFilter').addEventListener('change', filterTable);
        document.getElementById('endDateFilter').addEventListener('change', filterTable);

        function filterTable() {
        const searchQuery = document.getElementById('globalSearch').value.toLowerCase();
        const idFilter = document.getElementById('idFilter').value.toLowerCase();
        const userIdFilter = document.getElementById('userIdFilter').value.toLowerCase();
        const userNameFilter = document.getElementById('userNameFilter').value.toLowerCase();
        const loginTimeFilter = document.getElementById('loginTimeFilter').value.toLowerCase();
        const logoutTimeFilter = document.getElementById('logoutTimeFilter').value.toLowerCase();
        const sessionDurationFilter = document.getElementById('sessionDurationFilter').value.toLowerCase();
        const sessionStatusFilter = document.getElementById('sessionStatusFilter').value;
        const locationFilter = document.getElementById('locationFilter').value.toLowerCase();
        const startDate = document.getElementById('startDateFilter').value;
        const endDate = document.getElementById('endDateFilter').value;

        document.querySelectorAll('.user-table tbody tr').forEach(row => {
          const rowText = row.innerText.toLowerCase();
          const idText = row.children[0].textContent.trim().toLowerCase();
          const userIdText = row.children[1].textContent.trim().toLowerCase();
          const userNameText = row.children[2].textContent.trim().toLowerCase();
          const loginTimeText = row.children[3].textContent.trim();
          const logoutTimeText = row.children[4].textContent.trim().toLowerCase();
          const sessionDurationText = row.children[5].textContent.trim().toLowerCase();
          const sessionStatusText = row.children[6].textContent.trim();
          const locationText = row.children[7].textContent.trim().toLowerCase();

          // Parse the loginTimeText into a Date object
          const rowDate = loginTimeText ? new Date(loginTimeText) : null;

          // Parse the start and end dates into Date objects
          const start = startDate ? new Date(startDate) : null;
          const end = endDate ? new Date(endDate) : null;
          if (end) end.setHours(23, 59, 59, 999); // Include the full end date

          // Check if the row date falls within the selected range
          let dateMatch = true;
          if (start && end) {
            dateMatch = rowDate && rowDate >= start && rowDate <= end;
          } else if (start) {
            dateMatch = rowDate && rowDate >= start;
          } else if (end) {
            dateMatch = rowDate && rowDate <= end;
          }

          // Check if the row matches all filters
          let showRow = (idFilter === '' || idText.includes(idFilter)) &&
                        (userIdFilter === '' || userIdText.includes(userIdFilter)) &&
                        (userNameFilter === '' || userNameText.includes(userNameFilter)) &&
                        (loginTimeFilter === '' || loginTimeText.toLowerCase().includes(loginTimeFilter)) &&
                        (logoutTimeFilter === '' || logoutTimeText.includes(logoutTimeFilter)) &&
                        (sessionDurationFilter === '' || sessionDurationText.includes(sessionDurationFilter)) &&
                        (sessionStatusFilter === 'all' || sessionStatusText === sessionStatusFilter) &&
                        (locationFilter === '' || locationText.includes(locationFilter)) &&
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
