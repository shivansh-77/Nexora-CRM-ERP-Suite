<?php
session_start();
include('connection.php');
include('topbar.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Users</title>
    <style>
      /* Table Styles */
      .user-table {
        width: calc(100% - 280px); /* Adjust for sidebar width */
        margin-left: 260px; /* Align with sidebar */
        margin-top: 150px; /* Adjust for topbar */
        border-collapse: collapse;
        background-color: white;
      }
      .user-table th {
        background-color: #2c3e50; /* Same as the dashboard color */
        color: white;
        padding: 10px;
        text-align: left;
      }
      .user-table td {
        padding: 10px;
        border: 1px solid #ddd;
      }
      .user-table tr:nth-child(even) {
        background-color: #f9f9f9;
      }
      .user-table tr:hover {
        background-color: #f1f1f1;
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
  .user-table td:last-child {
  text-align: right; /* Align the buttons to the right of the cell */
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
    </style>
  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">Users</h2>
      <div class="lead-actions">
        <div class="search-bar">
          <input type="text" id="searchInput" class="search-input" placeholder="Search...">
          <button class="btn-search" id="searchButton">🔍</button>
        </div>
        <a href="form.php">
          <button class="btn-primary" id="openModal" data-mode="add">➕</button>
        </a>
      </div>
    </div>
    <div>
      <table class="user-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Department</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Designation</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $query = "SELECT * FROM login_db";
  $result = mysqli_query($connection, $query);
  if (mysqli_num_rows($result) > 0) {
      while ($row = mysqli_fetch_assoc($result)) {
          echo "<tr>
                  <td>{$row['name']}</td>
                  <td>{$row['department']}</td>
                  <td>{$row['email']}</td>
                  <td>{$row['phone']}</td>
                  <td>{$row['address']}</td>
                  <td>{$row['designation']}</td>
                  <td>
                      <button class='btn-warning edit-btn' onclick=\"window.location.href='update_form.php?id={$row['id']}'\">✏️</button>
                      <button class='btn-danger' onclick=\"if(confirm('Are you sure you want to delete this record?')) { window.location.href='delete_form.php?id={$row['id']}'; }\">🗑️</button>
                  </td>
                </tr>";
      }
  }
 else {
              echo "<tr><td colspan='7'>No users found</td></tr>";
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
</script>

  </body>
</html>
