<?php
session_start();
include('connection.php');
include('topbar.php');

// Get the fy_code from URL parameter
$fy_code = isset($_GET['fy_code']) ? $_GET['fy_code'] : '';

// Validate fy_code
if(empty($fy_code)) {
    die("Financial Year code is required");
}

// Fetch financial year details
$fy_query = "SELECT * FROM financial_years WHERE fy_code = ?";
$fy_stmt = $connection->prepare($fy_query);
$fy_stmt->bind_param("s", $fy_code);
$fy_stmt->execute();
$fy_result = $fy_stmt->get_result();
$fy_data = $fy_result->fetch_assoc();

if(!$fy_data) {
    die("Invalid Financial Year code");
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <title>Salary Months - <?php echo htmlspecialchars($fy_code); ?></title>
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
        width: 150px;
    }

    .btn-primary, .btn-secondary, .btn-danger, .btn-warning, .btn-view {
        padding:8px 14px;
        border: none;
        border-radius: 4px;
        color: white;
        cursor: pointer;
        margin: 2px;
    }

    .btn-primary { background-color: #e74c3c; color: black; }
    .btn-secondary { background-color: #6c757d; }
    .btn-danger { background-color: #dc3545; }
    .btn-warning { background-color: #3498db; }
    .btn-view { background-color: #2ecc71; }

    .status-active {
        color: green;
        font-weight: bold;
    }
    .status-inactive {
        color: orange;
    }
    .status-locked {
        color: red;
        font-weight: bold;
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
    </style>
  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">Salary Months - <?php echo htmlspecialchars($fy_data['fy_code']); ?></h2>
      <div class="lead-actions">
        <div class="search-bar">
          <input type="text" id="searchInput" class="search-input" placeholder="Search...">
          <button class="btn-search" id="searchButton">🔍</button>
        </div>
        <a href="salary_month_add.php?fy_code=<?php echo urlencode($fy_code); ?>">
          <button class="btn-primary" id="openModal" title="Add New Month" data-mode="add">➕</button>
        </a>
      </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table">
        <thead>
            <tr>
                <th>FY Code</th>
                <th>Month</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Created On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch months for this financial year
            $query = "SELECT * FROM salary_sheet_months WHERE fy_code = ? ORDER BY start_date";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("s", $fy_code);
            $stmt->execute();
            $result = $stmt->get_result();

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $status_class = 'status-' . $row['status'];
                    echo "<tr>
                            <td>" . htmlspecialchars($row['fy_code']) . "</td>
                            <td>" . htmlspecialchars($row['month']) . "</td>
                            <td>" . htmlspecialchars($row['start_date']) . "</td>
                            <td>" . htmlspecialchars($row['end_date']) . "</td>
                            <td>" . htmlspecialchars($row['created_on']) . "</td>

                            <td>
                                <button class='btn-view'
                                    onclick=\"window.location.href='salary_sheets.php?fy_code=" . urlencode($row['fy_code']) . "&month_id=" . $row['id'] . "&month=" . urlencode($row['month']) . "'\">
                                    View
                                </button>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='7' style='text-align: center;'>No months found for this financial year</td></tr>";
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
