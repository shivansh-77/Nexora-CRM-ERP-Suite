<?php
session_start();
include('connection.php');
include('topbar.php');

// Fetch projects and join with contact for company_name and contact_person
$query = "
SELECT p.*, c.company_name, c.contact_person
FROM projects p
LEFT JOIN contact c ON p.contact_id = c.id
ORDER BY p.id DESC
";
$result = mysqli_query($connection, $query);
$projects = [];
if($result && mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        $projects[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
<title>Projects</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<style>
html, body { height: 100%; margin:0; overflow:hidden; font-family: Arial; }
.main-content { height: 100vh; display:flex; flex-direction: column; overflow:hidden; }
.user-table-wrapper {
  width: calc(100% - 260px);
  margin-left: 260px;
  margin-top: 140px;
  height: calc(100vh - 150px);
  overflow: auto;
  border: 1px solid #ddd;
  background-color: white;
  -webkit-overflow-scrolling: touch;
  scrollbar-gutter: stable;
}
.user-table { width:100%; border-collapse: collapse; display: table; white-space: nowrap; }
.user-table thead { position: sticky; top: 0; background-color: #2c3e50; z-index:10; }
.user-table th, .user-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
.user-table th { background-color: #2c3e50; color:white; }
.user-table tr:nth-child(even){ background-color:#f9f9f9; }
.user-table tr:hover{ background-color:#f1f1f1; }
.user-table td:last-child { text-align:right; padding:5px 8px; }
.btn-primary, .btn-secondary, .btn-danger, .btn-warning {
  padding: 5px 10px; border:none; border-radius:4px; color:white; cursor:pointer;
}
.btn-primary { background-color: red; }
.btn-secondary { background-color: #6c757d; }
.btn-danger { background-color: #dc3545; }
.btn-warning { background-color: #3498db; color:black; }

.leadforhead {
  position: fixed; width: calc(100% - 290px); height: 50px; display:flex;
  justify-content: space-between; align-items:center; background-color:#2c3e50; color:white;
  padding: 0 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index:1000; margin-left:260px; margin-top:80px;
}
.search-bar { display:flex; align-items:center; background:white; border:1px solid #ddd; border-radius:5px; overflow:hidden; }
.search-input { border:none; padding:8px; outline:none; font-size:14px; width:273px; }
.search-input:focus{ outline:none; }
.btn-search { background-color: #3498db; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer; font-size:14px; }
#downloadExcel { background-color:green; }
</style>
</head>
<body>
<div class="leadforhead">
  <h2 class="leadfor">Projects</h2>
  <div style="display:flex; align-items:center; gap:10px;">
    <div class="search-bar">
      <input type="text" id="searchInput" class="search-input" placeholder="Search...">
      <button class="btn-search" id="searchButton">🔍</button>
    </div>
    <a href="project_create.php"><button class="btn-primary" title="Add Project">➕</button></a>
    <button id="downloadExcel" class="btn-primary" title="Download Excel File">📥</button>
  </div>
</div>

<div class="user-table-wrapper">
  <table class="user-table">
    <thead>
      <tr>
        <th>Project No.</th><th>Project Name</th><th>Project For</th><th>Client Name</th>
        <th>Report Submission</th><th>Next Report Date</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if(!empty($projects)){
          foreach($projects as $p){
              echo "<tr>";
              echo "<td>{$p['project_no']}</td>";
              echo "<td>{$p['project_name']}</td>";
              echo "<td>{$p['company_name']}</td>";

              echo "<td>{$p['contact_no']}</td>";
              echo "<td>{$p['project_report_submission']}</td>";
              echo "<td>{$p['next_report_date']}</td>";
              echo "<td>{$p['start_date']}</td>";
              echo "<td>{$p['end_date']}</td>";
              echo "<td>{$p['status']}</td>";
              echo "<td>
                <button class='btn-warning' onclick=\"window.location.href='project_update.php?id={$p['id']}'\">✏️</button>
                <button class='btn-danger' onclick=\"if(confirm('Delete this project?')) window.location.href='project_delete.php?id={$p['id']}'\">🗑️</button>
              </td>";
              echo "</tr>";
          }
      }else{
          echo "<tr><td colspan='11' style='text-align:center;'>No projects found</td></tr>";
      }
      ?>
    </tbody>
  </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script>
// Search
document.getElementById('searchInput').addEventListener('keyup', function(){
    const term = this.value.toLowerCase();
    document.querySelectorAll('.user-table tbody tr').forEach(row=>{
        row.style.display = Array.from(row.cells).some(td=> td.textContent.toLowerCase().includes(term)) ? '' : 'none';
    });
});

// Export Excel
document.getElementById('downloadExcel').addEventListener('click', function(){
    const table = document.querySelector('.user-table');
    const rows = table.querySelectorAll('tr');
    const data = [];
    rows.forEach(row=>{
        const rowData = Array.from(row.cells).map(td=>td.textContent);
        rowData.pop(); // Remove Actions
        data.push(rowData);
    });
    const ws = XLSX.utils.aoa_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Projects');
    XLSX.writeFile(wb, 'projects.xlsx');
});
</script>
</body>
</html>
