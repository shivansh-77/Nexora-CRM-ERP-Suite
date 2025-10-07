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
    <title>Operation Group Display</title>
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
        max-height: calc(100vh - 150px); /* Dynamic height based on viewport */
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
        padding: 12px; /* Increased padding for wider columns */
        border: 1px solid #ddd;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        text-align: center; /* Center align content in all columns */
    }
    .user-table th {
        background-color: #2c3e50; /* Header color */
        color: white;
        position: sticky; /* Make headers sticky */
        top: 0; /* Stick to the top */
        z-index: 1; /* Ensure headers are above the body */
    }
    .user-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .user-table tr:hover {
        background-color: #f1f1f1;
    }
    .btn-danger, .btn-warning {
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        color: white;
        cursor: pointer;
    }
    .btn-danger {
        background-color: #dc3545;
    }
    .btn-warning {
        background-color: #3498db;
        color: black;
    }
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
    /* Exclude the last column from center alignment */
    .user-table th:last-child,
    .user-table td:last-child {
        text-align: center; /* Align last column to the center */
    }
    table th:last-child, table td:last-child {
        width: 150px; /* Adjust the width as needed */
        text-align: center;
    }
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2 class="leadfor">Operation Groups</h2>
        <div class="lead-actions">
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search...">
                <button class="btn-search" id="searchButton">🔍</button>
            </div>
            <a href="operation_group_add.php">
                <button class="btn-primary" title="Add New Operation Group">➕</button>
            </a>
        </div>
    </div>
    <div class="user-table-wrapper">
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Operation Group</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT * FROM operation_group ORDER BY id DESC";
                $result = mysqli_query($connection, $query);
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>
                                <td>" . ($row['id'] ?? 'N/A') . "</td>
                                <td>" . ($row['group_name'] ?? 'N/A') . "</td>
                                <td>
                                    <div class='lead-actions'>
                                        <!-- Edit Button -->
                                        <button class='btn-warning edit-btn' title='Update Operation Group'
                                            onclick=\"window.location.href='operation_group_update.php?id=" . $row['id'] . "';\">
                                            ✏️
                                        </button>
                                        <!-- Delete Button -->
                                        <button class='btn-danger' title='Delete this Operation Group'
                                            onclick=\"if(confirm('Are you sure you want to delete this record?')) {
                                                window.location.href='operation_group_delete.php?id=" . $row['id'] . "';
                                            }\">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align: center;'>No records found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const tableRows = document.querySelectorAll('.user-table tbody tr');
        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toLowerCase();
            tableRows.forEach(function(row) {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(searchTerm) ? '' : 'none';
            });
        });
    });
</script>
</body>
</html>
