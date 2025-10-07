<?php
include('connection.php');

// ----- Generate next Project No (PR/yy/NNNN) -----
$yy = date('y');

// Find max series for the current year
$maxSeriesRes = mysqli_query(
  $connection,
  "SELECT MAX(CAST(SUBSTRING_INDEX(project_no, '/', -1) AS UNSIGNED)) AS max_series
   FROM projects
   WHERE project_no LIKE 'PR/$yy/%'"
);
$maxRow = $maxSeriesRes ? mysqli_fetch_assoc($maxSeriesRes) : null;
$nextSeriesNum = isset($maxRow['max_series']) && $maxRow['max_series'] !== null
  ? ((int)$maxRow['max_series'] + 1)
  : 1;

$nextSeriesPadded = str_pad($nextSeriesNum, 4, '0', STR_PAD_LEFT);
$next_project_no = "PR/$yy/$nextSeriesPadded";


// Pre-load groups
$op_groups = mysqli_query($connection, "SELECT id, group_name FROM operation_group ORDER BY group_name");
$task_groups = mysqli_query($connection, "SELECT id, group_name FROM task_group ORDER BY group_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Project Registration</title>

<style>
  :root {
    --ink:#2c3e50;
    --muted:#6b7280;
    --line:#e5e7eb;
    --panel:#ffffff;
    --bg:#2c3e50;
    --accent:#14a44d;
    --danger:#dc3545;
  }
  * { box-sizing: border-box; }
  body {
    margin:0; background:var(--bg);
    font-family:"Segoe UI", system-ui, -apple-system, Arial, sans-serif;
    color:#111827;
  }

  .shell {
    max-width: 1200px;
    margin: 32px auto 60px;
    padding: 22px 20px 26px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0,0,0,.12);
  }

  .shell-header {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    margin-bottom: 14px;
  }
  .shell-header h1 {
    grid-column: 2;
    margin: 0;
    text-align: center;
    font-size: 28px;
    font-weight: 800;
    color: var(--ink);
    letter-spacing: .3px;
  }
  .close-btn {
    grid-column: 3;
    justify-self: end;
    text-decoration: none;
    color:#9aa3af;
    font-size: 26px;
    line-height: 1;
    padding: 4px 8px;
    border-radius: 8px;
  }
  .close-btn:hover { color:#6b7280; background:#f3f4f6; }

  .form-card {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 18px 18px 8px;
  }

  .form-grid10 {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px 14px;
    align-items: end;
  }
  .field label {
    display:block;
    font-size: 13px;
    font-weight: 600;
    color:#374151;
    margin: 4px 0 6px;
  }
  .required { color:#ef4444; }
  .input, .select {
    width:100%; height:38px; padding:0 10px;
    border:1px solid #d1d5db; border-radius:8px;
    background:#fff; font-size:14px; outline:none;
  }
  .input:focus, .select:focus {
    border-color:#3b82f6;
    box-shadow:0 0 0 3px rgba(59,130,246,.08);
  }

  .btn {
    border:none;
    cursor:pointer;
    border-radius:10px;
    padding:10px 18px;
    font-weight:600;
    font-size:15px;
  }
  .btn-primary { background:var(--accent); color:#fff; }
  .btn-danger { background:var(--danger); color:#fff; }

  .gap-strong { height:22px; }

  .lists-shell {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
  }
  @media(max-width:1000px){ .lists-shell { grid-template-columns:1fr; } }

  .panel {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 12px;
  }
  .panel h2 {
    margin: 2px 4px 10px;
    font-size: 26px;
    font-weight: 800;
    color: #374151;
    letter-spacing: .2px;
    display:flex;
    align-items:center;
    justify-content:space-between;
  }

  .search-wrap { position: relative; }
  .search {
    height: 34px; width: 220px; padding: 0 34px 0 10px;
    border:1px solid #d1d5db; border-radius:8px; font-size:14px;
  }
  .search-clear {
    position:absolute; right:6px; top:50%;
    transform:translateY(-50%);
    width:24px; height:24px; border-radius:6px;
    border:1px solid #e5e7eb; background:#f8fafc;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; font-size:14px; color:#475569;
  }
  .search-clear:hover { background:#eef2f7; }

  .listbox {
    margin-top:8px;
    border:1px solid var(--line);
    border-radius:10px;
    background:#fff;
    max-height:420px;
    overflow:auto;
  }
  .row {
    display:flex; gap:10px; align-items:center;
    padding:10px 12px; border-bottom:1px solid #f3f4f6;
  }
  .row:last-child { border-bottom:0; }
  .row:hover { background:#fafafa; }
  .row.selected { background:#f5f7fb; }
  .qty input {
    width:76px; height:34px; border:1px solid #d1d5db;
    border-radius:8px; padding:0 8px; text-align:center;
  }
  .muted { color:var(--muted); font-size:12px; padding:6px 4px; }

  #projectList {
    position:absolute; z-index:50; background:#fff; border:1px solid var(--line);
    width:100%; max-height:220px; overflow-y:auto; display:none; border-radius:8px;
  }
  .project-item { padding:8px 10px; cursor:pointer; }
  .project-item:hover { background:#f3f4f6; }

  /* Submit row at bottom */
  .submit-row {
    text-align:center;
    padding: 24px 0 6px;
  }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
<div class="shell">
  <div class="shell-header">
    <div></div>
    <h1>Project</h1>
    <a href="project_display.php" class="close-btn" title="Back">×</a>
  </div>

  <form class="form-card" action="" method="post" id="projectForm" novalidate>
    <div class="form-grid10">

      <div class="field">
  <label>Project No. <span class="required">*</span></label>
  <input type="text" name="project_no" id="project_no" class="input" value="<?= htmlspecialchars($next_project_no) ?>" readonly>
</div>

      <div class="field" style="grid-column: span 1; position: relative;">
        <label>Project For <span class="required">*</span></label>
        <input type="text" name="project_for" id="project_for" class="input" required autocomplete="off" placeholder="Start typing company/person…">
        <div id="projectList"></div>
      </div>

      <div class="field">
        <label>Project Name <span class="required">*</span></label>
        <input type="text" name="project_name" id="project_name" class="input" required placeholder="Enter project name">
      </div>

      <div class="field">
        <label>Client Name</label>
        <input type="text" name="contact_person" id="contact_person" class="input" >
      </div>

      <div class="field">
        <label>Project Report Submission</label>
        <select name="report_frequency" id="report_frequency" class="select">
          <option value="">Select</option>
          <option value="Daily">Daily</option>
          <option value="Weekly">Weekly</option>
          <option value="Monthly">Monthly</option>
          <option value="Yearly">Yearly</option>
        </select>
      </div>

      <div class="field">
        <label>Next Project Report Date</label>
        <input type="date" name="next_report_date" id="next_report_date" class="input">
      </div>

      <div class="field">
        <label>Start Date <span class="required">*</span></label>
        <input type="date" name="start_date" id="start_date" class="input" required>
      </div>

      <div class="field">
        <label>End Date <span class="required">*</span></label>
        <input type="date" name="end_date" id="end_date" class="input" required>
      </div>

      <div class="field">
        <label>Operation Group <span class="required">*</span></label>
        <select name="operation_group_id" id="operation_group_id" class="select" required>
          <option value="">Select Operation Group</option>
          <?php while($g = mysqli_fetch_assoc($op_groups)): ?>
            <option value="<?= htmlspecialchars($g['id']) ?>"><?= htmlspecialchars($g['group_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="field">
        <label>Task Group <span class="required">*</span></label>
        <select name="task_group_id" id="task_group_id" class="select" required>
          <option value="">Select Task Group</option>
          <?php while($tg = mysqli_fetch_assoc($task_groups)): ?>
            <option value="<?= htmlspecialchars($tg['id']) ?>"><?= htmlspecialchars($tg['group_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <input type="hidden" name="contact_id" id="contact_id">
      <input type="hidden" name="contact_no" id="contact_no">
    </div>
  </form>

  <div class="gap-strong"></div>

  <div class="lists-shell">
    <div class="panel">
      <h2>Operations
        <span class="search-wrap">
          <input type="text" id="opSearch" class="search" placeholder="Search operations…">
          <button type="button" class="search-clear" id="opSearchClear" title="Clear">✕</button>
        </span>
      </h2>
      <div class="listbox" id="opList">
        <div class="muted">Select Operation Group to load operations here.</div>
      </div>
      <div class="muted">Checked items float to the top automatically.</div>
    </div>

    <div class="panel">
      <h2>Tasks
        <span class="search-wrap">
          <input type="text" id="taskSearch" class="search" placeholder="Search tasks…">
          <button type="button" class="search-clear" id="taskSearchClear" title="Clear">✕</button>
        </span>
      </h2>
      <div class="listbox" id="taskList">
        <div class="muted">Select Task Group to load tasks here.</div>
      </div>
      <div class="muted">Quantity is enabled when checked. Selected tasks float to the top.</div>
    </div>
  </div>

  <!-- ✅ Buttons moved to very bottom -->
  <div class="submit-row">
    <button type="submit" form="projectForm" name="create" class="btn btn-primary">Create Project</button>
    <button type="button" class="btn btn-danger" onclick="window.history.back();">Cancel</button>
  </div>
</div>
</body>
</html>


<script>
$(function(){

  // ------- Project For autocomplete -------
  $("#project_for").on("input", function(){
    const query = $(this).val().trim();
    if(query.length === 0){
      $("#projectList").hide().empty();
      return;
    }
    $.post("project_autocomplete.php", { query }, function(html){
      // Only show the dropdown if there is at least one .project-item
      const hasItems = /class=["']project-item["']/.test(html);
      if (hasItems) {
        $("#projectList").html(html).show();
      } else {
        $("#projectList").hide().empty();
      }
    }).fail(function(){
      $("#projectList").hide().empty();
    });
  });

  // Click to select from suggestions
  $(document).on("click", ".project-item", function(){
    const company = $(this).data('company') ?? null;
    const person  = $(this).data('person') ?? null;
    const full    = $(this).text();
    const parts   = full.split(" - ");

    $("#project_for").val(company || parts[0] || "");
    $("#contact_person").val(person || parts[1] || "");
    $("#contact_id").val($(this).data('id'));
    $("#contact_no").val($(this).data('contact') || "");
    $("#projectList").hide().empty();
  });

  // Click outside to close
  $(document).on("click", function(e){
    if(!$(e.target).closest("#project_for, #projectList").length){
      $("#projectList").hide().empty();
    }
  });

  // ------- Auto next report date -------
  $("#report_frequency").on("change", function(){
    const freq = $(this).val();
    if(!freq) return;
    const startVal = $("#start_date").val();
    const base = startVal ? new Date(startVal) : new Date();
    const next = new Date(base.getTime());
    if (freq === "Daily") next.setDate(base.getDate()+1);
    if (freq === "Weekly") next.setDate(base.getDate()+7);
    if (freq === "Monthly") next.setMonth(base.getMonth()+1);
    if (freq === "Yearly") next.setFullYear(base.getFullYear()+1);
    const m = String(next.getMonth()+1).padStart(2,"0");
    const d = String(next.getDate()).padStart(2,"0");
    $("#next_report_date").val(`${next.getFullYear()}-${m}-${d}`);
  });

  // ------- Build rows for lists -------
  function opRow(item){
    return $(`
      <div class="row" data-type="op" data-id="${item.id}" data-name="${$('<div>').text(item.name).html()}">
        <input type="checkbox" class="pick-op">
        <div class="name">${$('<div>').text(item.name).html()}</div>
      </div>
    `);
  }
  function taskRow(item){
    return $(`
      <div class="row" data-type="task" data-id="${item.id}" data-name="${$('<div>').text(item.name).html()}">
        <input type="checkbox" class="pick-task">
        <div class="name">${$('<div>').text(item.name).html()}</div>
        <div class="qty"><input type="number" class="task-qty" value="1" min="1" step="1" disabled></div>
      </div>
    `);
  }

  function renderList($box, items, kind){
    $box.empty();
    if(!items || !items.length){
      $box.append('<div class="muted">No items found in this group.</div>');
      return;
    }
    items.forEach(it => $box.append(kind==='op' ? opRow(it) : taskRow(it)));
  }

  // ------- Load operations by group -------
  let lastOpIndex = -1;
  $("#operation_group_id").on("change", function(){
    if(lastOpIndex !== -1 && $("#opList .row").length){
      if(!confirm("Changing Operation Group will clear current selections. Continue?")){
        this.selectedIndex = lastOpIndex; return;
      }
    }
    lastOpIndex = this.selectedIndex;
    $("#opList").html('<div class="muted">Loading operations…</div>');
    const gid = $(this).val();
    if(!gid){ $("#opList").html('<div class="muted">Select a group first.</div>'); return; }
    $.post('fetch_operations_by_group.php', { operation_group_id: gid }, function(list){
      renderList($("#opList"), list || [], 'op');
      $("#opSearch").val('').trigger('input');
    }, 'json').fail(function(){
      $("#opList").html('<div class="muted">Error loading operations.</div>');
    });
  });

  // ------- Load tasks by group -------
  let lastTaskIndex = -1;
  $("#task_group_id").on("change", function(){
    if(lastTaskIndex !== -1 && $("#taskList .row").length){
      if(!confirm("Changing Task Group will clear current selections. Continue?")){
        this.selectedIndex = lastTaskIndex; return;
      }
    }
    lastTaskIndex = this.selectedIndex;
    $("#taskList").html('<div class="muted">Loading tasks…</div>');
    const gid = $(this).val();
    if(!gid){ $("#taskList").html('<div class="muted">Select a group first.</div>'); return; }
    $.post('fetch_tasks_by_group.php', { task_group_id: gid }, function(list){
      renderList($("#taskList"), list || [], 'task');
      $("#taskSearch").val('').trigger('input');
    }, 'json').fail(function(){
      $("#taskList").html('<div class="muted">Error loading tasks.</div>');
    });
  });

  // ------- Selection behavior (float top + enable qty) -------
  $(document).on("change", ".pick-op", function(){
    const $row = $(this).closest('.row');
    const $box = $row.parent();
    if(this.checked){ $row.addClass('selected').prependTo($box); }
    else { $row.removeClass('selected').appendTo($box); }
  });

  $(document).on("change", ".pick-task", function(){
    const $row = $(this).closest('.row');
    const $box = $row.parent();
    const $qty = $row.find(".task-qty");
    if(this.checked){ $row.addClass('selected').prependTo($box); $qty.prop('disabled', false); }
    else { $row.removeClass('selected').appendTo($box); $qty.prop('disabled', true); }
  });

  // ------- Search filters + CLEAR buttons -------
  function filterBox($box, term){
    term = (term||'').toLowerCase();
    $box.find('.row').each(function(){
      const name = String($(this).data('name')||'').toLowerCase();
      $(this).toggle(name.includes(term));
    });
  }
  $("#opSearch").on("input", function(){ filterBox($("#opList"), $(this).val()); });
  $("#taskSearch").on("input", function(){ filterBox($("#taskList"), $(this).val()); });

  $("#opSearchClear").on("click", function(){ $("#opSearch").val('').trigger('input'); });
  $("#taskSearchClear").on("click", function(){ $("#taskSearch").val('').trigger('input'); });

  // ------- Client-side validation + confirmation + prevent refresh/data loss -------
  $("#projectForm").on("submit", function(e){
    e.preventDefault(); // stop native submit so we can confirm first

    // require at least one of each
    if($("#opList .pick-op:checked").length === 0){
      alert("Please select at least one Operation.");
      return false;
    }
    if($("#taskList .pick-task:checked").length === 0){
      alert("Please select at least one Task.");
      return false;
    }

    // require project_for and project_name
    if(!$("#project_for").val().trim()){
      alert("Please fill Project For.");
      return false;
    }
    if(!$("#project_name").val().trim()){
      alert("Please fill Project Name.");
      return false;
    }

    // dates
    const startVal = $("#start_date").val();
    const endVal   = $("#end_date").val();
    if(startVal && endVal && new Date(endVal) < new Date(startVal)){
      alert("End Date cannot be before Start Date!");
      return false;
    }
    const nextVal = $("#next_report_date").val();
    if(nextVal && startVal && new Date(nextVal) < new Date(startVal)){
      alert("Next Project Report Date cannot be before Start Date!");
      return false;
    }

    // confirmation
    if(!confirm("Do you want to create this project?")){
      return false;
    }

    // strip any old hidden fields (if resubmitting)
    $(this).find('input[name="operation_ids[]"],input[name="operation_names[]"],input[name="task_ids[]"],input[name="task_names[]"],input[name="task_qtys[]"]').remove();

    // ops
    $("#opList .pick-op:checked").each(function(){
      const $r = $(this).closest('.row');
      $('<input>',{type:'hidden',name:'operation_ids[]', value:$r.data('id')}).appendTo("#projectForm");
      $('<input>',{type:'hidden',name:'operation_names[]', value:$r.data('name')}).appendTo("#projectForm");
    });

    // tasks
    let ok = true;
    $("#taskList .pick-task:checked").each(function(){
      const $r = $(this).closest('.row');
      const qty = parseInt($r.find('.task-qty').val(),10) || 1;
      if(qty < 1) ok = false;
      $('<input>',{type:'hidden',name:'task_ids[]', value:$r.data('id')}).appendTo("#projectForm");
      $('<input>',{type:'hidden',name:'task_names[]', value:$r.data('name')}).appendTo("#projectForm");
      $('<input>',{type:'hidden',name:'task_qtys[]', value:qty}).appendTo("#projectForm");
    });
    if(!ok){
      alert("Task quantity must be at least 1.");
      return false;
    }

    // ensure PHP sees a POST (we switched to REQUEST_METHOD anyway)
    this.submit();
  });
});
</script>
</body>
</html>

<?php
// --------- PHP save ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gather inputs
    $project_for    = mysqli_real_escape_string($connection, $_POST['project_for']);
    $project_name   = mysqli_real_escape_string($connection, $_POST['project_name']);

    $contact_id     = !empty($_POST['contact_id']) ? mysqli_real_escape_string($connection, $_POST['contact_id']) : NULL;
    $contact_person = !empty($_POST['contact_person']) ? mysqli_real_escape_string($connection, $_POST['contact_person']) : NULL;
    $contact_no     = !empty($_POST['contact_no']) ? mysqli_real_escape_string($connection, $_POST['contact_no']) : NULL;
    $report_freq    = !empty($_POST['report_frequency']) ? mysqli_real_escape_string($connection, $_POST['report_frequency']) : NULL;
    $next_report    = !empty($_POST['next_report_date']) ? mysqli_real_escape_string($connection, $_POST['next_report_date']) : NULL;
    $start_date     = mysqli_real_escape_string($connection, $_POST['start_date']);
    $end_date       = mysqli_real_escape_string($connection, $_POST['end_date']);

    $operation_group_id = (int)($_POST['operation_group_id'] ?? 0);
    $task_group_id      = (int)($_POST['task_group_id'] ?? 0);

    // Date guards
    if (strtotime($end_date) < strtotime($start_date)) {
        echo "<script>alert('End Date cannot be before Start Date!'); history.back();</script>"; exit();
    }
    if ($next_report && strtotime($next_report) < strtotime($start_date)) {
        echo "<script>alert('Next Project Report Date cannot be before Start Date!'); history.back();</script>"; exit();
    }

    // Selections
    $operation_ids   = $_POST['operation_ids'] ?? [];
    $operation_names = $_POST['operation_names'] ?? [];
    $task_ids        = $_POST['task_ids'] ?? [];
    $task_names      = $_POST['task_names'] ?? [];
    $task_qtys       = $_POST['task_qtys'] ?? [];

    if (count($operation_ids) === 0) { echo "<script>alert('Please select at least one Operation.'); history.back();</script>"; exit(); }
    if (count($task_ids) === 0) { echo "<script>alert('Please select at least one Task.'); history.back();</script>"; exit(); }

    // (Optional) basic array-length sanity checks
    if (count($operation_ids) !== count($operation_names)) {
        echo "<script>alert('Operation arrays mismatch.'); history.back();</script>"; exit();
    }
    if (count($task_ids) !== count($task_names) || count($task_ids) !== count($task_qtys)) {
        echo "<script>alert('Task arrays mismatch.'); history.back();</script>"; exit();
    }

    // Helper to compute next project_no for current year
    function compute_next_project_no(mysqli $conn): string {
        $yy = date('y');
        $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(project_no, '/', -1) AS UNSIGNED)) AS max_series
                FROM projects
                WHERE project_no LIKE 'PR/$yy/%'";
        $res = mysqli_query($conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        $seriesNum = isset($row['max_series']) && $row['max_series'] !== null ? ((int)$row['max_series'] + 1) : 1;
        $seriesPadded = str_pad($seriesNum, 4, '0', STR_PAD_LEFT);
        return "PR/$yy/$seriesPadded";
    }

    mysqli_begin_transaction($connection);
    try {
        // Insert project row with a safe project_no (retry on rare duplicate)
        // Requires: ALTER TABLE projects ADD COLUMN project_no VARCHAR(20) NOT NULL, and UNIQUE KEY on project_no
        $maxRetries = 3;
        $project_id = null;
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $project_no = compute_next_project_no($connection);

            $sqlP = "
              INSERT INTO projects
              (project_no, project_for, project_name, contact_id, contact_person, contact_no,
               project_report_submission, next_report_date, start_date, end_date,
               operation_group_id, task_group_id)
              VALUES (
                '$project_no',
                '$project_for',
                '$project_name',
                ".($contact_id ? "'$contact_id'" : "NULL").",
                ".($contact_person ? "'$contact_person'" : "NULL").",
                ".($contact_no ? "'$contact_no'" : "NULL").",
                ".($report_freq ? "'$report_freq'" : "NULL").",
                ".($next_report ? "'$next_report'" : "NULL").",
                '$start_date', '$end_date',
                ".($operation_group_id ?: "NULL").",
                ".($task_group_id ?: "NULL")."
              )
            ";

            if (!mysqli_query($connection, $sqlP)) {
                // 1062 = duplicate key (project_no unique). Retry by recomputing.
                if (mysqli_errno($connection) == 1062 && $attempt < $maxRetries - 1) {
                    continue;
                }
                throw new Exception("Project insert failed: ".mysqli_error($connection));
            }
            $project_id = mysqli_insert_id($connection);
            break;
        }

        if (!$project_id) {
            throw new Exception("Unable to generate a unique Project No. Please try again.");
        }

        // Insert selected operation items
        if (!empty($operation_ids)) {
            $stmt = mysqli_prepare(
                $connection,
                "INSERT INTO project_operation_items
                 (project_id, operation_group_id, operation_id, operation_name, position)
                 VALUES (?, ?, ?, ?, ?)"
            );
            if(!$stmt) throw new Exception("Prepare failed (operations): ".mysqli_error($connection));

            for ($i=0; $i<count($operation_ids); $i++){
                $op_id   = (int)$operation_ids[$i];
                $op_name = $operation_names[$i] ?? '';
                $pos     = $i + 1;

                mysqli_stmt_bind_param($stmt, "iiisi", $project_id, $operation_group_id, $op_id, $op_name, $pos);
                if(!mysqli_stmt_execute($stmt)) throw new Exception("Operation item insert failed: ".mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        }

        // Insert selected task items
        if (!empty($task_ids)) {
            $stmt2 = mysqli_prepare(
                $connection,
                "INSERT INTO project_task_items
                 (project_id, task_group_id, task_id, task_name, quantity, position)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            if(!$stmt2) throw new Exception("Prepare failed (tasks): ".mysqli_error($connection));

            for ($i=0; $i<count($task_ids); $i++){
                $t_id   = (int)$task_ids[$i];
                $t_name = $task_names[$i] ?? '';
                $qty    = (int)($task_qtys[$i] ?? 1);
                if ($qty < 1) $qty = 1;
                $pos    = $i + 1;

                mysqli_stmt_bind_param($stmt2, "iiisii", $project_id, $task_group_id, $t_id, $t_name, $qty, $pos);
                if(!mysqli_stmt_execute($stmt2)) throw new Exception("Task item insert failed: ".mysqli_stmt_error($stmt2));
            }
            mysqli_stmt_close($stmt2);
        }

        mysqli_commit($connection);

        if (ob_get_length()) { ob_end_clean(); }
        echo "<script>
          alert('Project created successfully!');
          window.location.replace('project_display.php');
        </script>";
        exit;

    } catch (Exception $ex) {
        mysqli_rollback($connection);
        if (ob_get_length()) { ob_end_clean(); }
        echo "<pre style='color:#b91c1c; white-space:pre-wrap;'>".$ex->getMessage()."</pre>";
        exit;
    }
}
?>
