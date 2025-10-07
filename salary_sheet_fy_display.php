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
    <title>Salary Sheet Financial Years</title>
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
        max-height: calc(100vh - 150px);
        min-height: 100vh;
        overflow-y: auto;
        border: 1px solid #ddd;
        background-color: white;
    }

    .user-table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        table-layout: auto;
    }

    .user-table th, .user-table td {
        padding: 12px;
        border: 1px solid #ddd;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .user-table th {
        background-color: #2c3e50;
        color: white;
        text-align: left;
        position: sticky;
        top: 0;
        z-index: 1;
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
        text-align: center;
        width: 100px;
    }

    .btn-view {
        background-color: #2ecc71;
        color: white;
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

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
        text-align: center;
    }

    .current-indicator {
      color: green;
      font-weight: bold;
      font-size: 1.2em; /* 50% larger than normal text */
      /* padding: 0 0px; /* Add some space around it */ */
  }
    </style>
  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">Salary Sheet Financial Years</h2>
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
                <th>FY Code</th>
                <th>Start Year</th>
                <th>End Year</th>
                <th>Current</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch data from the financial_years table
            $query = "SELECT * FROM financial_years ORDER BY start_date DESC";
            $result = mysqli_query($connection, $query);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $isCurrent = $row['is_current'] == 1;
                    echo "<tr>
                            <td>" . htmlspecialchars($row['fy_code'] ?? 'N/A') . "</td>
                            <td>" . htmlspecialchars($row['start_date'] ?? 'N/A') . "</td>
                            <td>" . htmlspecialchars($row['end_date'] ?? 'N/A') . "</td>
                            <td>";

                    // Show indicator for current FY (non-interactive)
                    if ($isCurrent) {
                        echo "<span class='current-indicator'>✓</span>";
                    }

                    echo "</td>
                            <td>
                                <button class='btn-view'
                                    onclick=\"window.location.href='salary_fy_months.php?fy_code=" . urlencode($row['fy_code']) . "'\">
                                    View Months
                                </button>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='5' style='text-align: center;'>No financial years found</td></tr>";
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
      });
    </script>
  </body>
</html>
