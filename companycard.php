<?php
session_start();
include('connection.php');
include('topbar.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Company Card</title>
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
        min-height: 15px; /* Ensures it doesn't shrink too much */
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
            padding: 7px;
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
    </style>

  </head>
  <body>
    <div class="leadforhead">
      <h2 class="leadfor">Company Card</h2>
      <div class="lead-actions">
        <div class="search-bar">
          <input type="text" id="searchInput" class="search-input" placeholder="Search...">
          <button class="btn-search" id="searchButton">🔍</button>
        </div>
        <a href="add_companycard.php">
          <button class="btn-primary" id="openModal" data-mode="add" title="Add new Company Card">➕</button>
        </a>
      </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table">
          <thead>
              <tr>
                  <th>Id</th>
                  <th>Company Logo</th>
                  <th>Company Name</th>
                  <th>Address</th>
                  <th>Pincode</th>
                  <th>City</th>
                  <th>State</th>
                  <th>Country</th>
                  <th>Contact No.</th>
                  <th>Whatsapp No.</th>
                  <th>Email ID</th>
                  <th>PAN Card</th>
                  <th>GST No</th>
                  <th>Registration No</th>
                  <th>Company Type</th>
                  <th>Actions</th>
              </tr>
          </thead>
          <tbody>
      <?php
      // Fetch data from the company_card table
      $query = "SELECT * FROM company_card";
      $result = mysqli_query($connection, $query);

      if (mysqli_num_rows($result) > 0) {
          while ($row = mysqli_fetch_assoc($result)) {
              echo "<tr>
                      <td>" . ($row['id'] ?? 'N/A') . "</td>
                      <td><img src='" . ($row['company_logo'] ?? '') . "' alt='Company Logo' style='width:100%; height:auto; object-fit:contain;'></td>
                      <td>" . ($row['company_name'] ?? 'N/A') . "</td>
                      <td>" . ($row['address'] ?? 'N/A') . "</td>
                      <td>" . ($row['pincode'] ?? 'N/A') . "</td>
                      <td>" . ($row['city'] ?? 'N/A') . "</td>
                      <td>" . ($row['state'] ?? 'N/A') . "</td>
                      <td>" . ($row['country'] ?? 'N/A') . "</td>
                      <td>" . ($row['contact_no'] ?? 'N/A') . "</td>
                      <td>" . ($row['whatsapp_no'] ?? 'N/A') . "</td>
                      <td>" . ($row['email_id'] ?? 'N/A') . "</td>
                      <td>" . ($row['pancard'] ?? 'N/A') . "</td>
                      <td>" . ($row['gstno'] ?? 'N/A') . "</td>
                      <td>" . ($row['registration_no'] ?? 'N/A') . "</td>
                      <td>" . ($row['company_type'] ?? 'N/A') . "</td>
                      <td>
                          <button class='btn-warning edit-btn' title='Update this Company Card'
                              onclick=\"window.location.href='update_companycard.php?id={$row['id']}'\">✏️</button>
                          <button class='btn-danger' title='Delete this Company Card'
                              onclick=\"if(confirm('Are you sure you want to delete this record?')) {
                                  window.location.href='delete_companycard.php?id={$row['id']}';
                              }\">🗑️</button>
                      </td>

                    </tr>";
          }
      } else {
          echo "<tr><td colspan='14'>No company records found</td></tr>";
      }
      ?>
          </tbody>
      </table>

</tbody>
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

</html>
