<?php
session_start();
include('connection.php');
include('topbar.php');

// Get parameters from URL
$fy_code = isset($_GET['fy_code']) ? mysqli_real_escape_string($connection, $_GET['fy_code']) : '';
$month_id = isset($_GET['month_id']) ? intval($_GET['month_id']) : 0;
$month_name = isset($_GET['month']) ? mysqli_real_escape_string($connection, $_GET['month']) : '';

// Validate parameters
if(empty($fy_code) || $month_id == 0) {
    die('Error: Required parameters not specified.');
}

// Verify the month exists
$month_query = "SELECT * FROM salary_sheet_months WHERE id = ? AND fy_code = ?";
$month_stmt = $connection->prepare($month_query);
$month_stmt->bind_param("is", $month_id, $fy_code);
$month_stmt->execute();
$month_result = $month_stmt->get_result();
$month_data = $month_result->fetch_assoc();

if(!$month_data) {
    die('Invalid month or financial year');
}

// Fetch salary data ONLY for this month_id
$query = "SELECT * FROM salary WHERE month_id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $month_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <title>Salary Sheets - <?php echo htmlspecialchars($fy_code . " - " . $month_name); ?></title>
    <!-- Add this in your head section -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
    <style>
    html, body {
        overflow: hidden;
        height: 100%;
        margin: 0;
    }

    .user-table-wrapper {
        width: calc(100% - 260px);
        margin-left: 260px;
        margin-top: 140px;
        max-height: calc(100vh - 150px); /* Adjust based on your layout */
        overflow-y: auto; /* Enable vertical scrolling */
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
        padding: 10px;
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
        padding: 6px;
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
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        color: white;
        cursor: pointer;
        margin: 2px;
    }

    .btn-primary { background-color: #e74c3c; color: black; padding:8px 10px; text-decoration: None;}
    .btn-secondary { background-color: #6c757d; }
    .btn-danger { background-color: #dc3545; }
    .btn-warning { background-color: #3498db; }
    .btn-view { background-color: #2ecc71; }
    .btn-unlock { background-color: #50C878; }
    .btn-lock { background-color: #DC143C; }

    .status-pending { color: orange; }
    .status-approved { color: green; }
    .status-rejected { color: red; }

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

    table th:last-child, table td:last-child {
        width: 150px;
        text-align: center;
    }
    table th:first-child, table td:first-child {
        width: 100px;
        text-align: center;
    }
    .scrollable-table {
        overflow-x: auto;
        white-space: nowrap;
    }
    .btn-pending {
    background-color: #ff4444; /* Red */
    color: white;
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin: 2px;
}

.btn-approved {
    background-color: #1E5631; /* Green */
    color: white;
    padding: 5px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin: 2px;
}

.btn-approved:hover, .btn-pending:hover {
    opacity: 0.8;
}
.btn-approve-all {
    background-color: #28a745; /* Green */
    color: white;
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    margin-left: 10px;
    transition: background-color 0.3s;
}

.btn-approve-all:hover {
    background-color: #218838; /* Darker green */
}

.btn-approve-all:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}

.btn-unlock{
  padding: 8 16px;
  text-decoration: None;
    border-radius: 4px;
    background-color: #D4E6FF;
    border: none;
    border-radius: 4px;
    color: white;
    cursor: pointer;
    margin: 0px;
}

#downloadExcel{
  background-color: green;
}

.btn-excel {
    background-color: #2c3e50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  padding: 12 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;

}

.btn-excel img {
    width: 16px;
    height: 16px;
    filter: brightness(0) invert(1); /* Makes the icon white */
}

.button-group {
    display: flex;
    gap: 10px; /* Adds space between buttons */
    align-items: center;
}

.button-group button,
.button-group a.btn-primary {
    width: 45px; /* Fixed width */
    height: 34px; /* Fixed height */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0; /* Remove padding since we're using fixed dimensions */
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 16px; /* Adjust icon size */
}

/* Specific button styles */
.btn-approve-all {
    background-color: #e74c3c;
    color: black;
}

.btn-unlock {
    background-color: #50C878;
    color: white;
}

.btn-primary {
    background-color: #e74c3c;
    color: black;
}

#downloadExcel {
    background-color: green;
    color: white;
}

/* Ensure the Excel icon fits properly */
#downloadExcel img {
    width: 20px;
    height: 20px;
    object-fit: contain;
}
    </style>
</head>
<body>
    <div class="leadforhead">
        <h3 class="leadfor">Salary Sheets - <?php echo htmlspecialchars($fy_code . " - " . $month_name); ?></h3>
        <div class="lead-actions">
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search...">
                <button class="btn-search" id="searchButton">🔍</button>
            </div>
            <?php
  // Calculate start_date and end_date from the month_data
  $start_date = date('Y-m-d', strtotime('first day of ' . $month_data['month']));
  $end_date = date('Y-m-d', strtotime('last day of ' . $month_data['month']));
  ?>

  <div class="button-group">
      <button class="btn-approve-all" title="Approve all salary sheets for this month" onclick="approveAllForMonth()">
          🔓
      </button>

      <button class="btn-unlock" title="Generate all salary sheets" id="generateAllSalariesBtn">
          🧮
      </button>

      <a href="salary_sheet1.php?fy_code=<?= urlencode($fy_code) ?>&month_id=<?= $month_id ?>&month=<?= urlencode($month_data['month']) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>"
         class="btn-primary"
         title="Add New Salary Sheet">
         ➕
      </a>

      <button class="btn-primary" id="downloadExcel" title="Download as Excel" onclick="exportToExcel()">
          <img src="Excel-icon.png" alt="Excel Icon">
      </button>
  </div>
        </div>
    </div>
    <div class="user-table-wrapper">
        <div class="scrollable-table">
          <table class="user-table">
    <thead>
        <tr>

            <th>Salary Sheet No.</th>
            <th>Employee Name</th>

            <th>Month</th>
            <th>Working Days</th>
            <th>Weekends</th>
            <th>Holidays</th>
            <th>Sick Leaves</th>
            <th>Earned Leaves</th>
            <th>Half Days</th>
            <th>LWP</th>
            <th>Shortcoming Entries</th>
            <th>Shortcoming Days</th>
            <th>Payable Days</th>
            <th>Total Days</th>
            <th>Salary/Day</th>
            <th>Payable Salary</th>
            <th>Additional Fund</th>
            <th>Total Amount</th>
            <th>Created At</th>
            <th>FY Code</th>
            <th>Month ID</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
<?php
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $viewUrl = "view_salary.php?id={$row['id']}&fy_code=" . urlencode($row['fy_code']) . "&month=" . urlencode($row['month']) . "&month_id=" . urlencode($row['month_id']);

        echo "<tr ondblclick=\"window.location.href='{$viewUrl}'\" style='cursor: pointer;'>
            <td>" . htmlspecialchars($row['salary_sheet_no']) . "</td>
            <td>" . htmlspecialchars($row['employee_name']) . "</td>
            <td>" . htmlspecialchars($row['month']) . "</td>
            <td>" . htmlspecialchars($row['working_days']) . "</td>
            <td>" . htmlspecialchars($row['weekends']) . "</td>
            <td>" . htmlspecialchars($row['holidays']) . "</td>
            <td>" . htmlspecialchars($row['sick_leaves']) . "</td>
            <td>" . htmlspecialchars($row['earned_leaves']) . "</td>
            <td>" . htmlspecialchars($row['half_days']) . "</td>
            <td>" . htmlspecialchars($row['lwp']) . "</td>
            <td>" . htmlspecialchars($row['shortcoming_days']) . "</td>
            <td>" . htmlspecialchars($row['shortcoming_entries']) . "</td>
            <td>" . htmlspecialchars($row['payable_days']) . "</td>
            <td>" . htmlspecialchars($row['total_days']) . "</td>
            <td>" . htmlspecialchars($row['salary_per_day']) . "</td>
            <td>" . htmlspecialchars($row['payable_salary']) . "</td>
            <td>" . htmlspecialchars($row['additional_fund']) . "</td>
            <td>" . htmlspecialchars($row['total_amount']) . "</td>
            <td>" . htmlspecialchars($row['created_at']) . "</td>
            <td>" . htmlspecialchars($row['fy_code']) . "</td>
            <td>" . htmlspecialchars($row['month_id']) . "</td>
            <td>
                <button id='statusBtn_" . $row['id'] . "'
                        class='" . ($row['status'] == 'Approved' ? 'btn-approved' : 'btn-pending') . "'
                        onclick=\"updateStatus(" . $row['id'] . ", '" . htmlspecialchars($row['fy_code']) . "', '" . htmlspecialchars($row['month']) . "', " . htmlspecialchars($row['month_id']) . ")\">
                    " . htmlspecialchars($row['status']) . "
                </button>
            </td>
            <td>
                <button class='btn-view'
                    onclick=\"window.location.href='{$viewUrl}'\">
                    👁️
                </button>
            </td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='22' style='text-align: center;'>No salary records found for " . htmlspecialchars($month_name) . "</td></tr>";
}
?>
</tbody>


</table>
        </div>
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

        function updateAllStatus(action) {
            const monthId = <?php echo $month_id; ?>;
            const fyCode = "<?php echo $fy_code; ?>";

            if(confirm(`Are you sure you want to ${action} all salary sheets for ${month_name}?`)) {
                window.location.href = `update_all_status.php?month_id=${monthId}&fy_code=${fyCode}&action=${action}`;
            }
        }

        function generateAllSalarySheets() {
            if (confirm("Are you sure you want to generate salary sheets for all employees?")) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "generate_all_salary_sheets.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                const params = `fy_code=${encodeURIComponent('<?= $fy_code ?>')}&month_id=${encodeURIComponent('<?= $month_id ?>')}&month=${encodeURIComponent('<?= $month_data["month"] ?>')}&start_date=${encodeURIComponent('<?= $start_date ?>')}&end_date=${encodeURIComponent('<?= $end_date ?>')}`;

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                alert(`Success: ${response.message}\nProcessed: ${response.processed} employees`);
                                if (response.processed > 0) {
                                    window.location.reload();
                                }
                            } else {
                                alert(`Error: ${response.message}\n${response.errors ? response.errors.join('\n') : ''}`);
                            }
                        } catch (e) {
                            alert("Invalid response from server");
                            console.error(e);
                        }
                    } else {
                        alert("Request failed with status: " + xhr.status);
                    }
                };

                xhr.onerror = function() {
                    alert("Request failed");
                };

                xhr.send(params);
            }
        }

function updateStatus(salaryId) {
    const button = document.getElementById('statusBtn_' + salaryId);
    const currentStatus = button.textContent.trim();
    const newStatus = currentStatus === 'Pending' ? 'Approved' : 'Pending';

    if (confirm("Are you sure you want to change the status to " + newStatus + "?")) {
        // Send an AJAX request to update the status
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "update_salary_status.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    // Update button appearance without page reload
                    button.textContent = newStatus;
                    button.className = newStatus === 'Approved' ? 'btn-approved' : 'btn-pending';
                } else {
                    alert("Error updating status: " + xhr.statusText);
                }
            }
        };

        xhr.send("id=" + salaryId + "&status=" + newStatus);
    }
}
function approveAllForMonth() {
    const monthId = <?php echo $month_id; ?>;
    const fyCode = "<?php echo $fy_code; ?>";
    const monthName = "<?php echo $month_name; ?>";

    if (confirm(`Are you sure you want to approve ALL salary sheets for ${monthName}?`)) {
        // Disable button and show loading
        const btn = document.querySelector('.btn-approve-all');
        btn.innerHTML = '⏳ Approving...';
        btn.disabled = true;

        // AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "approve_all_salaries.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onload = function() {
            btn.innerHTML = '✅ Approve All';
            btn.disabled = false;

            if (this.status === 200) {
                alert(this.responseText);
                // Update all status buttons on the page
                document.querySelectorAll('[id^="statusBtn_"]').forEach(button => {
                    if (button.textContent.trim() === 'Pending') {
                        button.textContent = 'Approved';
                        button.className = 'btn-approved';
                    }
                });
            } else {
                alert("Error: " + this.statusText);
            }
        };

        xhr.onerror = function() {
            btn.innerHTML = '✅ Approve All';
            btn.disabled = false;
            alert("Request failed");
        };

        xhr.send(`month_id=${monthId}&fy_code=${encodeURIComponent(fyCode)}`);
    }
}


document.getElementById('generateAllSalariesBtn').addEventListener('click', function() {
    // Get the dates from PHP variables
    const startDate = "<?php echo $start_date; ?>";
    const endDate = "<?php echo $end_date; ?>";

    if (confirm("Are you sure you want to generate salary sheets for all employees?")) {
        fetch('generate_all_salaries.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                fy_code: "<?php echo $fy_code; ?>",
                month_id: <?php echo $month_id; ?>,
                month: "<?php echo $month_data['month']; ?>",
                start_date: startDate,
                end_date: endDate
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message || 'All salary sheets generated successfully!');
                location.reload(); // Refresh to show new data
            } else {
                const errorMsg = data.errors ? data.errors.join('\n') :
                                (data.error || 'Unknown error occurred');
                alert('Error: ' + errorMsg);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to generate salary sheets. Check console for details.');
        });
    }
});

function exportToExcel() {
    // Get the table element
    const table = document.querySelector('.user-table');

    // Clone the table to manipulate without affecting the display
    const clone = table.cloneNode(true);

    // Remove the Actions column (last column)
    const rows = clone.querySelectorAll('tr');
    rows.forEach(row => {
        if (row.cells.length > 0) {
            row.deleteCell(row.cells.length - 1); // Remove last column (Actions)
        }
    });

    // Remove the Created At column (18th column - adjust index if needed)
    rows.forEach(row => {
        if (row.cells.length > 17) { // Check if row has enough columns
            row.deleteCell(17); // Remove Created At column (0-based index)
        }
    });

    // Create a workbook from the modified table
    const wb = XLSX.utils.table_to_book(clone, {sheet:"Salary Sheet"});

    // Format dates in the worksheet
    const ws = wb.Sheets["Salary Sheet"];
    const range = XLSX.utils.decode_range(ws['!ref']);

    // Format date columns (adjust column indices as needed)
    for (let R = range.s.r; R <= range.e.r; ++R) {
        for (let C = range.s.c; C <= range.e.c; ++C) {
            const cell_address = {c:C, r:R};
            const cell_ref = XLSX.utils.encode_cell(cell_address);
            if (ws[cell_ref] && ws[cell_ref].t === 's' && ws[cell_ref].v.match(/\d{4}-\d{2}-\d{2}/)) {
                // Convert date strings to Excel date format
                const date = new Date(ws[cell_ref].v);
                ws[cell_ref] = {t:'n', v:date, z:XLSX.SSF._table[14]};
            }
        }
    }

    // Generate a file name
    const fileName = `Salary_Sheet_<?php echo $fy_code . '_' . $month_name; ?>_${new Date().toISOString().slice(0,10)}.xlsx`;

    // Export to Excel
    XLSX.writeFile(wb, fileName);
}
    </script>
</body>
</html>
