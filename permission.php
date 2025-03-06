<?php
session_start();
include('connection.php');
include('topbar.php');

// Fetch user ID and name from the URL
$user_id = $_GET['id'] ?? null;
$user_name = $_GET['name'] ?? null;

if (!$user_id) {
    die("Invalid user ID.");
}

// Define menu options
$menus = ["Dashboard", "CRM", "CMS", "Sales", "Human Resource", "Settings"];

// Fetch existing permissions for the user (example query)
$permissions = []; // Replace with actual query to fetch permissions

// Handle form submission to update permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update permissions in the database
    // Example: foreach ($_POST['access'] as $menu) { update permission for $menu }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Access</title>
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
        margin-top: 142px; /* Adjust for topbar */
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
        padding: 12px; /* Increased padding for wider columns */
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

    .user-table th,
    .user-table td {
        text-align: center; /* Center align content in all columns */
    }

    /* Exclude the last column from center alignment */
    .user-table th:last-child,
    .user-table td:last-child {
        text-align: center; /* Align last column to the left (or adjust as needed) */
    }

    table th:last-child, table td:last-child {
        width: 100px; /* Adjust the width as needed */
        text-align: center;
    }
    </style>
</head>
<body>
    <div class="leadforhead">
        <h2 class="leadfor">Permissions for User: <?= htmlspecialchars($user_name) ?></h2>
        <div class="lead-actions">
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search...">
                <button class="btn-search" id="searchButton">🔍</button>
            </div>
        </div>
    </div>
    <div class="user-table-wrapper">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Menu</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
        <?php foreach ($menus as $menu): ?>
            <tr>
                <td><?= htmlspecialchars($menu) ?></td>
                <td>
                    <button type="button" class="btn btn-view"
                        onclick="window.location.href='submenu_view.php?menu=<?= urlencode($menu) ?>&id=<?= urlencode($user_id) ?>&name=<?= urlencode($user_name) ?>';">
                        ℹ️
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>

        </table>
    </div>
</body>
</html>
