<?php
session_start();
include('connection.php');
include('topbar.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Item Category</title>
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
            padding: 15px; /* Increased padding for wider columns */
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
    </style>
  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">Item Category</h2>
      <div class="lead-actions">
        <div class="search-bar">
          <input type="text" id="searchInput" class="search-input" placeholder="Search...">
          <button class="btn-search" id="searchButton">🔍</button>
        </div>
        <a href="item_category_add.php">
          <button class="btn-primary" id="openModal" data-mode="add">➕</button>
        </a>
      </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table">
          <thead>
              <tr>
                  <th>Code</th>
                  <th>Description</th>
                  <th>Actions</th>
              </tr>
          </thead>
          <tbody>
              <?php
              // Fetch data from the unit_of_measurement table
              $query = "SELECT * FROM item_category";
              $result = mysqli_query($connection, $query);

              if (mysqli_num_rows($result) > 0) {
                  while ($row = mysqli_fetch_assoc($result)) {
                      echo "<tr>

                              <td>" . ($row['code'] ?? 'N/A') . "</td>
                              <td>" . ($row['description'] ?? 'N/A') . "</td>
                              <td>
                                  <button class='btn-warning edit-btn'
                                      onclick=\"window.location.href='item_category_edit.php?id={$row['id']}';\">✏️</button>

                                  <button class='btn-danger'
                                      onclick=\"if(confirm('Are you sure you want to delete this record?')) {
                                          window.location.href='item_category_delete.php?id={$row['id']}';
                                      }\">🗑️</button>
                              </td>
                            </tr>";
                  }
              } else {
                  echo "<tr><td colspan='4'>No Unit of Measurement records found</td></tr>";
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
