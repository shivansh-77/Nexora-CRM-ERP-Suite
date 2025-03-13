<?php
session_start();
include('connection.php');
include('topbar.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Unit Values</title>
    <style>
    /* Table Styles */
    /* Center the table horizontally */
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
        width: 10%; /* Adjust action column width */
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
    text-align: left; /* Align last column to the left (or adjust as needed) */
}

    </style>
  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">Unit Values</h2>
      <div class="lead-actions">
        <div class="search-bar">
          <input type="text" id="searchInput" class="search-input" placeholder="Search...">
          <button class="btn-search" id="searchButton">🔍</button>
        </div>
        <a href="item_unit_add.php">
      <button class="btn-primary" id="openModal" data-mode="add" title="Add New Item Unit">➕</button>
  </a>
      </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table">
          <thead>
              <tr>
                  <th>Unit</th>
                  <th>Value</th>
                  <th>Actions</th>
              </tr>
          </thead>
          <tbody>
              <?php
              // Fetch data from the unit_of_measurement table
              $query = "SELECT * FROM item_unit";
              $result = mysqli_query($connection, $query);

              if (mysqli_num_rows($result) > 0) {
                  while ($row = mysqli_fetch_assoc($result)) {
                      echo "<tr>

                              <td>" . ($row['unit'] ?? 'N/A') . "</td>
                              <td>" . ($row['value'] ?? 'N/A') . "</td>
                              <td>
                                  <button class='btn-warning edit-btn' title='Update Item unit'
                                      onclick=\"window.location.href='item_unit_edit.php?id={$row['id']}';\">✏️</button>

                                  <button class='btn-danger' title='Delete Item Unit'
                                      onclick=\"if(confirm('Are you sure you want to delete this record?')) {
                                          window.location.href='item_unit_delete.php?id={$row['id']}';
                                      }\">🗑️</button>
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
  document.addEventListener('DOMContentLoaded', () => {
      const checkboxes = document.querySelectorAll('.current-checkbox');

      checkboxes.forEach((checkbox) => {
          checkbox.addEventListener('change', (event) => {
              if (event.target.checked) {
                  // Uncheck all other checkboxes and set their status to 0
                  checkboxes.forEach((cb) => {
                      if (cb !== event.target) {
                          cb.checked = false;

                          // Make an AJAX call to update the other checkboxes to 0
                          const id = cb.dataset.id;
                          fetch(`update_current_status.php?id=${id}&status=0`, {
                              method: 'POST'
                          })
                          .then(response => response.json())
                          .then(data => {
                              console.log(data.message); // Optionally handle success message
                          })
                          .catch(error => {
                              console.error('Error updating current status:', error);
                          });
                      }
                  });

                  // Make an AJAX call to update the current checkbox to 1
                  const id = event.target.dataset.id;
                  fetch(`update_current_status.php?id=${id}&status=1`, {
                      method: 'POST'
                  })
                  .then(response => response.json())
                  .then(data => {
                      console.log(data.message); // Optionally handle success message
                  })
                  .catch(error => {
                      console.error('Error updating current status:', error);
                  });
              }
          });
      });
  });

</script>
</html>
