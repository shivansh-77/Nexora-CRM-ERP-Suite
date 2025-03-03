<?php
// Start the session
session_start();

// Include the database connection file
include 'connection.php';

// Handle the AJAX request to update leave status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_id']) && isset($_POST['status'])) {
    // Get the leave ID and status from the POST request
    $leave_id = $_POST['leave_id'];
    $status = $_POST['status'];

    // Fetch the leave details from the database
    $sql = "SELECT user_id, leave_type, total_days FROM user_leave WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $leave = $result->fetch_assoc();

    // Update the leave status in the database
    $sql = "UPDATE user_leave SET status = ?, approved_on = NOW() WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("si", $status, $leave_id);
    $stmt->execute();

    // If the leave is approved, update the user_leave_balance table
    if ($status === 'Approved') {
        $user_id = $leave['user_id'];
        $leave_type = $leave['leave_type'];
        $total_days = $leave['total_days'];

        // Determine which column to update based on the leave type
        if ($leave_type === 'Sick Leave') {
            $column_to_update = 'sick_leaves_taken';
        } elseif ($leave_type === 'Earned Leave') {
            $column_to_update = 'earned_leaves_taken';
        } elseif ($leave_type === 'Half Day') {
            $column_to_update = 'half_day_leaves_taken';
        } else {
            // Handle other leave types if necessary
            $column_to_update = null;
        }

        // Update the user_leave_balance table
        if ($column_to_update) {
            $sql = "UPDATE user_leave_balance SET $column_to_update = $column_to_update + ? WHERE user_id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("di", $total_days, $user_id); // Use "d" for decimal values
            $stmt->execute();
        }
    }

    // Close the statement
    $stmt->close();

    // Send a response back to the AJAX request
    echo "Leave status updated successfully";
    exit; // Stop further execution after handling the AJAX request
}

// Include topbar.php only if it's not an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    include 'topbar.php';
}

// Get the current user's ID from the session
$approver_id = $_SESSION['user_id'];

// Get the selected filter from the request, default to 'Pending'
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'Pending';

// Fetch leaves for the current approver based on the selected filter
$sql = "SELECT * FROM user_leave WHERE approver_id = ?";
if ($filter !== 'All') {
    $sql .= " AND status = ?";
}
$stmt = $connection->prepare($sql);
if ($filter !== 'All') {
    $stmt->bind_param("is", $approver_id, $filter);
} else {
    $stmt->bind_param("i", $approver_id);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Approval Display</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
    <style>

    html, body {
        overflow: hidden;
        height: 100%;
        margin: 0;
    }

        /* Table Styles */
        .user-table-wrapper {
            width: calc(100% - 260px); /* Adjust width to account for sidebar */
            margin-left: 260px; /* Align with sidebar */
            margin-top: 140px; /* Adjust for topbar */
            overflow: auto; /* Enable scrolling for the table */
            max-height: 475px; /* Set max height for vertical scrolling */
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
            width: 12px; /* Further reduce the width of the action column */
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
        .approve-btn {
            background-color: green;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .reject-btn {
            background-color: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        /* Style for the filter dropdown */
      #statusFilter {
          background-color: white; /* Set background to white */
          color: black; /* Set text color to black */
          border: 1px solid #ddd; /* Add a border */
          border-radius: 4px; /* Rounded corners */
          padding: 5px 10px; /* Add padding */
          cursor: pointer; /* Show pointer cursor */
          margin-right: 10px; /* Add some spacing */
      }
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2 class="leadfor">Pending Leaves</h2>
        <div class="lead-actions">
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search...">
                <button class="btn-search" id="searchButton">🔍</button>
            </div>
            <select id="statusFilter" class="btn-primary">
                <option value="All" <?php echo ($filter === 'All') ? 'selected' : ''; ?>>All</option>
                <option value="Pending" <?php echo ($filter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="Approved" <?php echo ($filter === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                <option value="Rejected" <?php echo ($filter === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
            </select>
            <button id="downloadExcel" class="btn-primary">
                <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;">
            </button>
        </div>
    </div>
    <div class="user-table-wrapper">
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User Name</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Applied On</th>
                    <th>Total Days</th>
                    <th id="actionHeader">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['user_name']; ?></td>
                        <td><?php echo $row['leave_type']; ?></td>
                        <td><?php echo $row['start_date']; ?></td>
                        <td><?php echo $row['end_date']; ?></td>
                        <td><?php echo $row['status']; ?></td>
                        <td><?php echo $row['applied_on']; ?></td>
                        <td><?php echo $row['total_days']; ?></td>
                        <td>
                            <?php if ($row['status'] === 'Pending'): ?>
                                <button class="approve-btn" onclick="updateLeaveStatus(<?php echo $row['id']; ?>, 'Approved')">Approve</button>
                                <button class="reject-btn" onclick="updateLeaveStatus(<?php echo $row['id']; ?>, 'Rejected')">Reject</button>
                            <?php else: ?>
                                <?php echo $row['approved_on']; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <script>
        // Download Excel
        const downloadExcelButton = document.getElementById('downloadExcel');
        downloadExcelButton.addEventListener('click', function() {
            const table = document.querySelector('.user-table');
            const clonedTable = table.cloneNode(true);
            const actionColumn = clonedTable.querySelectorAll('th:last-child, td:last-child');

            actionColumn.forEach(col => col.remove());

            const ws = XLSX.utils.table_to_sheet(clonedTable, { raw: true });
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Item Sales');
            XLSX.writeFile(wb, 'item_sales.xlsx');
        });

        function updateLeaveStatus(leaveId, status) {
            if (confirm('Are you sure you want to ' + status.toLowerCase() + ' this leave?')) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "leave_approval_display.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        alert(xhr.responseText); // Show the response message
                        location.reload(); // Reload the page to reflect the changes
                    }
                };
                xhr.send("leave_id=" + leaveId + "&status=" + status);
            }
        }

        // Handle filter change
        const statusFilter = document.getElementById('statusFilter');
        statusFilter.addEventListener('change', function() {
            const filter = this.value;
            window.location.href = `leave_approval_display.php?filter=${filter}`;
        });

        // Update the action header based on the selected filter
        const actionHeader = document.getElementById('actionHeader');
        const filter = "<?php echo $filter; ?>";
        if (filter === 'Approved') {
            actionHeader.textContent = 'Approved on';
        } else if (filter === 'Rejected') {
            actionHeader.textContent = 'Rejected on';
        } else {
            actionHeader.textContent = 'Action';
        }
    </script>
</body>
</html>

<?php
// Close the database connection
$connection->close();
?>
