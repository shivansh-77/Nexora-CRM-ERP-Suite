<?php
session_start();
include('connection.php');
include('topbar.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];

// Step 1: Fetch Allowed FY Codes
$fy_codes = [];
$fy_query = "SELECT fy_code FROM emp_fy_permission WHERE emp_id = ? AND permission = 1";
$stmt = $connection->prepare($fy_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $fy_codes[] = $row['fy_code'];
}

// Step 2: Fetch Draft Invoice Records
if (!empty($fy_codes)) {
    // Convert the fy_codes array to a comma-separated string for the SQL IN clause
    $fy_codes_string = implode("','", $fy_codes);
    $query = "SELECT * FROM invoices WHERE status = 'Closed' AND fy_code IN ('$fy_codes_string')";
} else {
    // If no fy_codes, set query to an empty result
    $query = "SELECT * FROM invoices WHERE 0"; // Returns no results
}

$result = mysqli_query($connection, $query);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Closed Invoices</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.4/xlsx.full.min.js"></script>
    <style>
        /* Your existing CSS styles */
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
            padding: 7px;
        }

        .user-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .user-table tr:hover {
            background-color: #f1f1f1;
        }

        .user-table td:last-child {
            text-align: center; /* Align buttons to the right */
            width: 10px; /* Further reduce the width of the action column */
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


        table tr td:nth-last-child(2) {
            text-align: center;
        }


        #downloadExcel {
            background-color: green;
        }

        </style>
   </head>
   <body>
       <div class="leadforhead">
           <h2 class="leadfor">Closed Invoices</h2>
           <div class="lead-actions">
               <div class="search-bar">
                   <input type="text" id="searchInput" class="search-input" placeholder="Search...">
                   <button class="btn-search" id="searchButton">🔍</button>
               </div>

 <button id="downloadExcel" class="btn-primary" title="Export to Excel">
     <img src="Excel-icon.png" alt="Export to Excel" style="width: 20px; height: 20px; margin-right: 0px;">
 </button>

           </div>
       </div>

       <div class="user-table-wrapper">
           <table class="user-table">
               <thead>
                   <tr>
                       <th>Id</th>
                       <th>Invoice No</th>
                       <th>Quotation No</th>
                       <th>Client Name</th>
                       <th>Invoice Date</th>
                       <th>Gross Amount</th>
                       <th>Discount</th>
                       <th>Net Amount</th>
                       <th>Actions</th>
                   </tr>
               </thead>
               <tbody>
                   <?php
                   if (mysqli_num_rows($result) > 0) {
                       while ($row = mysqli_fetch_assoc($result)) {
                           echo "<tr>
                                   <td>{$row['id']}</td>
                                   <td>{$row['invoice_no']}</td>
                                   <td>{$row['quotation_no']}</td>
                                   <td>{$row['client_name']}</td>
                                   <td>{$row['invoice_date']}</td>
                                   <td>{$row['gross_amount']}</td>
                                   <td>{$row['discount']}</td>
                                   <td>{$row['net_amount']}</td>
                                   <td>
                                       <button class='btn-secondary' title='Print Invoice' onclick=\"window.location.href='invoice1.php?id={$row['id']}'\">🖨️</button>
                                   </td>
                               </tr>";
                       }
                   } else {
                       echo "<tr><td colspan='9' style='text-align: center;'>No records found</td></tr>";
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

          // Download Excel
          const downloadExcelButton = document.getElementById('downloadExcel');
          downloadExcelButton.addEventListener('click', function() {
              const table = document.querySelector('.user-table');
              const clonedTable = table.cloneNode(true);
              const actionColumn = clonedTable.querySelectorAll('th:last-child, td:last-child');

              actionColumn.forEach(col => col.remove());

              const ws = XLSX.utils.table_to_sheet(clonedTable, { raw: true });
              const wb = XLSX.utils.book_new();
              XLSX.utils.book_append_sheet(wb, ws, 'Closed Invoices');
              XLSX.writeFile(wb, 'closed_invoices.xlsx');
          });
      });
    </script>
  </body>
</html>
