<?php
session_start();
include('connection.php');
include('topbar.php');

// Get the fy_code from the URL
if (isset($_GET['fy_code'])) {
    $fy_code = mysqli_real_escape_string($connection, $_GET['fy_code']);

    // Fetch data filtered by fy_code
    $query = "SELECT
                  e.id, e.emp_id, e.emp_name, e.fy_code, e.permission
              FROM
                  emp_fy_permission e
              WHERE
                  e.fy_code = '$fy_code'";

    $result = mysqli_query($connection, $query);
} else {
    // Redirect or display an error if fy_code is missing
    die('Error: FY Code not specified.');
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>User FY Permission</title>
    <style>
    /* Table Styles */
    /* Center the table horizontally */
    .user-table-wrapper {
        display: flex;
        justify-content: center; /* Center horizontally */
        align-items: center; /* Center vertically if needed */
        margin-top: 145px;
        overflow-x: auto; /* Allow horizontal scrolling if needed */

    }

    /* Adjust table width and column width */
    .user-table {
        width: 78%; /* Adjust width as needed */
        border-collapse: collapse;
        background-color: white;
        table-layout: fixed; /* Fixed layout for equal column widths */
        margin-left: 240px;
    }

    .user-table th, .user-table td {
        padding: 20px; /* Padding for better spacing */
        border: 1px solid #ddd;
        text-align: left;
        word-wrap: break-word; /* Wrap text if too long */
    }

    .user-table th {
        background-color: #2c3e50; /* Header color */
        color: white;
        font-size: 18px; /* Adjust font size for header */
    }

    .user-table td {
        font-size: 18px; /* Adjust font size for table data */
    }

    .user-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .user-table tr:hover {
        background-color: #f1f1f1;
    }

    /* Scrollbar styling */
    .user-table {
        overflow: auto;
        white-space: nowrap;
    }

    .user-table th:last-child {
         /* Center align buttons */
        width: 12%; /* Adjust action column width */
    }

    /* Adjust action button column width */
    .user-table td:last-child {
         /* Center align buttons */
        width: 12%; /* Adjust action column width */
    }

    .btn-primary, .btn-secondary, .btn-danger, .btn-warning {
      padding: 5px 10px;
      border: none;
      border-radius: 4px;
      color: white;
      cursor: pointer;
    }

    .btn-primary {
      background-color: #a5f3fc;
    }

    .btn-secondary {
      background-color: #6c757d;
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
      width: 75%;
      height: 50px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #2c3e50;
      color: white;
      padding: 0 20px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      z-index: 1000;
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
    width: 15%;
}

.permission-checkbox {
        width: 25px; /* Adjust width */
        height: 25px; /* Adjust height */
        cursor: pointer; /* Add a pointer cursor for better UX */
    }

    </style>
  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">User FY Permission <?php echo"$fy_code"; ?></h2>
      <div class="lead-actions">
        <div class="search-bar">
          <input type="text" id="searchInput" class="search-input" placeholder="Search...">
          <button class="btn-search" id="searchButton">🔍</button>
        </div>
        <div>
    <button style="background-color:#50C878;" class="btn-primary" onclick="updatePermissions('allowed')">🔓</button>
    <button style="background-color:#DC143C;" class="btn-primary" onclick="updatePermissions('not_allowed')">🔏</button>
    <a style="text-decoration:None; margin-left:20px;" href="add_emp_fy_permission.php?fy_code=<?php echo $fy_code; ?>" class="btn btn-primary">➕</a>

    <script>
    function updatePermissions(action) {
        const fyCode = "<?php echo $fy_code; ?>"; // Get the current FY Code from PHP
        let url = '';

        if (action === 'allowed') {
            url = `update_permission_allowed.php?fy_code=${fyCode}`;
        } else if (action === 'not_allowed') {
            url = `update_permission_not_allowed.php?fy_code=${fyCode}`;
        }

        if (url) {
            window.location.href = url; // Redirect to the URL
        }
    }
</script>
</div>
      </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table">
    <thead>
        <tr>
            <th>Employee Id</th>
            <th>Employee Name</th>
            <th>FY Code</th>
            <th>Permission</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Fetch data from the emp_fy_permission table
        // Get the fy_code from the URL
        if (isset($_GET['fy_code'])) {
            $fy_code = mysqli_real_escape_string($connection, $_GET['fy_code']);

            // Fetch data filtered by fy_code
            $query = "SELECT
                          e.id, e.emp_id, e.emp_name, e.fy_code, e.permission
                      FROM
                          emp_fy_permission e
                      WHERE
                          e.fy_code = '$fy_code'";

            $result = mysqli_query($connection, $query);
        } else {
            // Redirect or display an error if fy_code is missing
            die('Error: FY Code not specified.');
        }
        ?>
        <?php

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Determine if the checkbox should be checked
                $checked = $row['permission'] == 1 ? "checked" : "";

                echo "<tr>
                        <td>{$row['emp_id']}</td>
                        <td>{$row['emp_name']}</td>
                        <td>{$row['fy_code']}</td>
                        <td>
                            <input type='checkbox' class='permission-checkbox' data-id='{$row['id']}' {$checked}>
                        </td>

                    </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No follow-up records found</td></tr>";
        }
        ?>
    </tbody>
</table>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                  rowText += cell.textContent.toLowerCase() + ' '; // Concatenate all cell texts
              });

              // Toggle row visibility based on search term
              if (rowText.includes(searchTerm)) {
                  row.style.display = ''; // Show row
              } else {
                  row.style.display = 'none'; // Hide row
              }
          });
      });
  });
    $(document).ready(function () {
        // Event listener for checkbox toggle
        $(".permission-checkbox").on("change", function () {
            var employeeId = $(this).data("id"); // Get the employee ID
            var permissionValue = $(this).is(":checked") ? 1 : 0; // Get checkbox state (1 or 0)

            // Send AJAX request to update the database
            $.ajax({
                url: "update_permission.php", // PHP file to handle the update
                method: "POST",
                data: {
                    id: employeeId,
                    permission: permissionValue
                },
                success: function (response) {
                    alert(response); // Display success message
                },
                error: function () {
                    alert("Error updating permission");
                }
            });
        });
    });
</script>

</html>
