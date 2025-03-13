<?php
session_start();
include('connection.php');
include('topbar.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Leave Balances</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
    <style>
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
        min-height: 100vh; /* Ensures it doesn't shrink too much */
        overflow-y: auto; /* Enables vertical scrolling */
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
        #downloadExcel{
          background-color: green;
        }
    </style>
</head>
<body>
    <!-- Header and Actions -->
    <div class="leadforhead">
        <h2>Leave Balances</h2>
        <div class="lead-actions">
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search...">
                <button class="btn-search" id="searchButton">🔍</button>
            </div>
            <button id="updateLeaveBalance" class="btn-primary" title="Update all the User's leave balance up to date">Update</button>
            <button id="downloadExcel" class="btn-primary" title="Download Excel File">
                <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px;">
            </button>
        </div>
    </div>

    <!-- Leave Balance Table -->
    <div class="user-table-wrapper">
        <table class="user-table">
            <thead>
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
                // Fetch data from the user_leave_balance table
                $leaveQuery = "SELECT * FROM user_leave_balance";
                $leaveResult = mysqli_query($connection, $leaveQuery);

                if (mysqli_num_rows($leaveResult) > 0) {
                    while ($leaveRow = mysqli_fetch_assoc($leaveResult)) {
                        echo "<tr>
                                <td>{$leaveRow['id']}</td>
                                <td>{$leaveRow['name']}</td>
                                <td>{$leaveRow['D.O.J']}</td>
                                <td>{$leaveRow['total_sick_leaves']}</td>
                                <td>{$leaveRow['total_earned_leaves']}</td>
                                <td>{$leaveRow['sick_leaves_taken']}</td>
                                <td>{$leaveRow['earned_leaves_taken']}</td>
                                <td>{$leaveRow['half_day_leaves_taken']}</td>
                                <td>{$leaveRow['last_updated']}</td>
                                <td>{$leaveRow['next_update']}</td>
                              </tr>";
                    }
                } else {
                   echo "<tr><td colspan='10' style='text-align: center;'>No records found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- JavaScript for Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Search Functionality
            const searchInput = document.getElementById('searchInput');
            const tableRows = document.querySelectorAll('.user-table tbody tr');

            searchInput.addEventListener('keyup', function () {
                const searchTerm = searchInput.value.toLowerCase();

                tableRows.forEach(function (row) {
                    const cells = row.querySelectorAll('td');
                    let rowText = '';

                    cells.forEach(function (cell) {
                        rowText += cell.textContent.toLowerCase() + ' ';
                    });

                    // Toggle row visibility based on search term
                    row.style.display = rowText.includes(searchTerm) ? '' : 'none';
                });
            });

            // Update Leave Balance Button
            document.getElementById('updateLeaveBalance').addEventListener('click', function () {
                const confirmUpdate = confirm('Do you want to update the leave balance of all users?');
                if (confirmUpdate) {
                    const xhr = new XMLHttpRequest();
                    xhr.open('GET', 'update_leave_balances.php', true);
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            alert('Leave balances updated successfully!');
                            window.location.reload();
                        } else {
                            alert('Failed to update leave balances. Please try again.');
                        }
                    };
                    xhr.onerror = function () {
                        alert('An error occurred while updating leave balances.');
                    };
                    xhr.send();
                }
            });

            // Download Excel Button
            document.getElementById('downloadExcel').addEventListener('click', function () {
                const table = document.querySelector('.user-table');
                const clonedTable = table.cloneNode(true);
                const ws = XLSX.utils.table_to_sheet(clonedTable, { raw: true });
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Leave Balances');
                XLSX.writeFile(wb, 'leave_balances.xlsx');
            });
        });
    </script>
</body>
</html>
