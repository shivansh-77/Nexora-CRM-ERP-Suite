<?php
session_start();
include('connection.php');

// ---------- Guard: project id ----------
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  echo "<script>alert('Invalid project ID'); window.location.href='project_display.php';</script>"; exit;
}
$project_id = (int)$_GET['id'];

// ---------- Load project ----------
$projRes = mysqli_query($connection, "SELECT * FROM projects WHERE id = $project_id");
if (!$projRes || mysqli_num_rows($projRes) === 0) {
  echo "<script>alert('Project not found'); window.location.href='project_display.php';</script>"; exit;
}
$project = mysqli_fetch_assoc($projRes);

// ---------- Load groups ----------
$op_groups   = mysqli_query($connection, "SELECT id, group_name FROM operation_group ORDER BY group_name");
$task_groups = mysqli_query($connection, "SELECT id, group_name FROM task_group ORDER BY group_name");

// ---------- Load existing selected items ----------
$selOps  = [];
$selOpRes = mysqli_query($connection, "SELECT operation_id, operation_name, position FROM project_operation_items WHERE project_id = $project_id ORDER BY position ASC");
if ($selOpRes) {
  while($r = mysqli_fetch_assoc($selOpRes)) {
    $selOps[] = ['id' => (int)$r['operation_id'], 'name' => $r['operation_name']];
  }
}

$selTasks = [];
$selTaskRes = mysqli_query($connection, "SELECT task_id, task_name, quantity, position FROM project_task_items WHERE project_id = $project_id ORDER BY position ASC");
if ($selTaskRes) {
  while($r = mysqli_fetch_assoc($selTaskRes)) {
    $selTasks[] = ['id' => (int)$r['task_id'], 'name' => $r['task_name'], 'qty' => (int)$r['quantity']];
  }
}

// ---------- Load current group items to render initial lists ----------
$initialOps = [];
if (!empty($project['operation_group_id'])) {
  $gid = (int)$project['operation_group_id'];
  $opsRes = mysqli_query($connection, "SELECT id, operation_name FROM operations WHERE operation_group_id = $gid ORDER BY operation_name");
  if ($opsRes) {
    while($r = mysqli_fetch_assoc($opsRes)) {
      $initialOps[] = ['id' => (int)$r['id'], 'name' => $r['operation_name']];
    }
  }
}

$initialTasks = [];
if (!empty($project['task_group_id'])) {
  $tgid = (int)$project['task_group_id'];
  $tasksRes = mysqli_query($connection, "SELECT id, task_name FROM tasks WHERE task_group_id = $tgid ORDER BY task_name");
  if ($tasksRes) {
    while($r = mysqli_fetch_assoc($tasksRes)) {
      $initialTasks[] = ['id' => (int)$r['id'], 'name' => $r['task_name']];
    }
  }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Project</title>
<style>
  :root {
    --ink:#2c3e50; --muted:#6b7280; --line:#e5e7eb; --panel:#ffffff; --bg:#2c3e50;
    --accent:#14a44d; --danger:#dc3545;
  }
  *{ box-sizing:border-box; }
  body{ margin:0; background:var(--bg); font-family:"Segoe UI",system-ui,-apple-system,Arial,sans-serif; color:#111827; }

  .shell{ max-width:1200px; margin:32px auto 60px; padding:22px 20px 26px; background:#fff; border:1px solid var(--line);
          border-radius:14px; box-shadow:0 10px 30px rgba(0,0,0,.12); }
  .shell-header{ display:grid; grid-template-columns:1fr auto 1fr; align-items:center; margin-bottom:14px; }
  .shell-header h1{ grid-column:2; margin:0; text-align:center; font-size:28px; font-weight:800; color:var(--ink); letter-spacing:.3px; }
  .close-btn{ grid-column:3; justify-self:end; text-decoration:none; color:#9aa3af; font-size:26px; line-height:1; padding:4px 8px; border-radius:8px; }
  .close-btn:hover{ color:#6b7280; background:#f3f4f6; }

  .form-card{ background:var(--panel); border:1px solid var(--line); border-radius:12px; padding:18px 18px 8px; }
  .form-grid10{ display:grid; grid-template-columns:repeat(5,1fr); gap:12px 14px; align-items:end; }
  .field label{ display:block; font-size:13px; font-weight:600; color:#374151; margin:4px 0 6px; }
  .required{ color:#ef4444; }
  .input,.select{ width:100%; height:38px; padding:0 10px; border:1px solid #d1d5db; border-radius:8px; outline:none; background:#fff; font-size:14px; }
  .input:focus,.select:focus{ border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.08); }

  .btn{ border:none; cursor:pointer; border-radius:10px; padding:10px 18px; font-weight:600; font-size:15px; }
  .btn-primary{ background:var(--accent); color:#fff; }
  .btn-danger{ background:var(--danger); color:#fff; }

  .gap-strong{ height:22px; }
  .lists-shell{ display:grid; grid-template-columns:1fr 1fr; gap:20px; }
  @media(max-width:1000px){ .lists-shell{ grid-template-columns:1fr; } }

  .panel{ background:var(--panel); border:1px solid var(--line); border-radius:12px; padding:12px; }
  .panel h2{ margin:2px 4px 10px; font-size:26px; font-weight:800; color:#374151; letter-spacing:.2px; display:flex; align-items:center; justify-content:space-between; }

  .search-wrap{ position:relative; }
  .search{ height:34px; width:220px; padding:0 34px 0 10px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; }
  .search-clear{ position:absolute; right:6px; top:50%; transform:translateY(-50%); width:24px; height:24px; border-radius:6px; border:1px solid #e5e7eb;
                 background:#f8fafc; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:14px; color:#475569; }
  .search-clear:hover{ background:#eef2f7; }

  .listbox{ margin-top:8px; border:1px solid var(--line); border-radius:10px; background:#fff; max-height:420px; overflow:auto; }
  .row{ display:flex; gap:10px; align-items:center; padding:10px 12px; border-bottom:1px solid #f3f4f6; }
  .row:last-child{ border-bottom:0; }
  .row:hover{ background:#fafafa; }
  .row.selected{ background:#f5f7fb; }
  .name{ flex:1 1 auto; }
  .qty input{ width:76px; height:34px; border:1px solid #d1d5db; border-radius:8px; padding:0 8px; text-align:center; }
  .muted{ color:var(--muted); font-size:12px; padding:6px 4px; }

  #projectList{ position:absolute; z-index:50; background:#fff; border:1px solid var(--line); width:100%; max-height:220px; overflow-y:auto; display:none; border-radius:8px; }
  .project-item{ padding:8px 10px; cursor:pointer; }
  .project-item:hover{ background:#f3f4f6; }

  .submit-row{ text-align:center; padding:24px 0 6px; }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
<div class="shell">
  <div class="shell-header">
    <div></div>
    <h1>Update Project</h1>
    <a href="project_display.php" class="close-btn" title="Back">×</a>
  </div>

  <!-- Top Form -->
  <form class="form-card" action="" method="post" id="projectForm" novalidate>
    <div class="form-grid10">
      <!-- Project No. (read-only) -->
      <div class="field">
        <label>Project No.</label>
        <input type="text" class="input" value="<?= htmlspecialchars($project['project_no']) ?>" readonly>
      </div>

      <div class="field" style="grid-column: span 1; position: relative;">
        <label>Project For <span class="required">*</span></label>
        <input type="text" name="project_for" id="project_for" class="input" required autocomplete="off"
               value="<?= htmlspecialchars($project['project_for']) ?>" placeholder="Start typing company/person…">
        <div id="projectList"></div>
      </div>

      <div class="field">
        <label>Project Name <span class="required">*</span></label>
        <input type="text" name="project_name" id="project_name" class="input" required
               value="<?= htmlspecialchars($project['project_name']) ?>" placeholder="Enter project name">
      </div>

      <div class="field">
        <label>Client Name</label>
        <input type="text" name="contact_person" id="contact_person" class="input"
               value="<?= htmlspecialchars($project['contact_person'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Project Report Submission</label>
        <select name="report_frequency" id="report_frequency" class="select">
          <?php
          $freqs = ["", "Daily", "Weekly", "Monthly", "Yearly"];
          foreach($freqs as $f){
            $sel = ($project['project_report_submission'] === $f) ? 'selected' : '';
            $label = $f ?: 'Select';
            $val = $f ?: '';
            echo "<option value=\"$val\" $sel>$label</option>";
          }
          ?>
        </select>
      </div>

      <!-- Row 2 -->
      <div class="field">
        <label>Next Project Report Date</label>
        <input type="date" name="next_report_date" id="next_report_date" class="input"
               value="<?= htmlspecialchars($project['next_report_date'] ?? '') ?>">
      </div>

      <div class="field">
        <label>Start Date <span class="required">*</span></label>
        <input type="date" name="start_date" id="start_date" class="input" required
               value="<?= htmlspecialchars($project['start_date']) ?>">
      </div>

      <div class="field">
        <label>End Date <span class="required">*</span></label>
        <input type="date" name="end_date" id="end_date" class="input" required
               value="<?= htmlspecialchars($project['end_date']) ?>">
      </div>

      <div class="field">
        <label>Operation Group <span class="required">*</span></label>
        <select name="operation_group_id" id="operation_group_id" class="select" required>
          <option value="">Select Operation Group</option>
          <?php while($g = mysqli_fetch_assoc($op_groups)): ?>
            <option value="<?= (int)$g['id'] ?>" <?= ((int)$project['operation_group_id'] === (int)$g['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($g['group_name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="field">
        <label>Task Group <span class="required">*</span></label>
        <select name="task_group_id" id="task_group_id" class="select" required>
          <option value="">Select Task Group</option>
          <?php while($tg = mysqli_fetch_assoc($task_groups)): ?>
            <option value="<?= (int)$tg['id'] ?>" <?= ((int)$project['task_group_id'] === (int)$tg['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($tg['group_name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Hidden fields -->
      <input type="hidden" name="contact_id" id="contact_id" value="<?= htmlspecialchars($project['contact_id'] ?? '') ?>">
      <input type="hidden" name="contact_no" id="contact_no" value="<?= htmlspecialchars($project['contact_no'] ?? '') ?>">
    </div>
  </form>

  <div class="gap-strong"></div>

  <!-- Lists -->
  <div class="lists-shell">
    <!-- Operations -->
    <div class="panel">
      <h2>
        Operations
        <span class="search-wrap">
          <input type="text" id="opSearch" class="search" placeholder="Search operations…">
          <button type="button" class="search-clear" id="opSearchClear" title="Clear">✕</button>
        </span>
      </h2>
      <div class="listbox" id="opList">
        <div class="muted">Loading operations…</div>
      </div>
      <div class="muted">Checked items float to the top automatically.</div>
    </div>

    <!-- Tasks -->
    <div class="panel">
      <h2>
        Tasks
        <span class="search-wrap">
          <input type="text" id="taskSearch" class="search" placeholder="Search tasks…">
          <button type="button" class="search-clear" id="taskSearchClear" title="Clear">✕</button>
        </span>
      </h2>
      <div class="listbox" id="taskList">
        <div class="muted">Loading tasks…</div>
      </div>
      <div class="muted">Quantity is enabled when checked. Selected tasks float to the top.</div>
    </div>
  </div>

  <!-- Buttons at the very bottom -->
  <div class="submit-row">
    <button type="submit" form="projectForm" name="update" class="btn btn-primary">Update Project</button>
    <button type="button" class="btn btn-danger" onclick="window.history.back();">Cancel</button>
  </div>
</div>

<script>
$(function(){

  // ---- PHP -> JS bootstrap data ----
  const initialOps     = <?= json_encode($initialOps, JSON_UNESCAPED_UNICODE) ?>;
  const initialTasks   = <?= json_encode($initialTasks, JSON_UNESCAPED_UNICODE) ?>;
  const selectedOps    = <?= json_encode($selOps, JSON_UNESCAPED_UNICODE) ?>;
  const selectedTasks  = <?= json_encode($selTasks, JSON_UNESCAPED_UNICODE) ?>;

  // ------- Project For autocomplete -------
  $("#project_for").on("input", function(){
    const query = $(this).val().trim();
    if(query.length === 0){ $("#projectList").hide().empty(); return; }
    $.post("project_autocomplete.php",{query},function(html){
      const hasItems = /class=[\"']project-item[\"']/.test(html);
      if (hasItems) { $("#projectList").html(html).show(); }
      else { $("#projectList").hide().empty(); }
    }).fail(function(){ $("#projectList").hide().empty(); });
  });

  // Click to select from suggestions
  $(document).on("click",".project-item",function(){
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
  $(document).on("click",function(e){
    if(!$(e.target).closest("#project_for,#projectList").length){ $("#projectList").hide().empty(); }
  });

  // ------- Auto next report date -------
  $("#report_frequency").on("change", function(){
    const freq = $(this).val(); if(!freq) return;
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

  // ------- Render helpers -------
  function opRow(item, checked){
    const $r = $(`
      <div class="row" data-type="op" data-id="${item.id}" data-name="${$('<div>').text(item.name).html()}">
        <input type="checkbox" class="pick-op" ${checked ? 'checked' : ''}>
        <div class="name">${$('<div>').text(item.name).html()}</div>
      </div>
    `);
    return $r;
  }
  function taskRow(item, checked, qtyVal){
    const qty = Number.isInteger(qtyVal) && qtyVal > 0 ? qtyVal : 1;
    const $r = $(`
      <div class="row" data-type="task" data-id="${item.id}" data-name="${$('<div>').text(item.name).html()}">
        <input type="checkbox" class="pick-task" ${checked ? 'checked' : ''}>
        <div class="name">${$('<div>').text(item.name).html()}</div>
        <div class="qty"><input type="number" class="task-qty" value="${qty}" min="1" step="1" ${checked ? '' : 'disabled'}></div>
      </div>
    `);
    if (checked) $r.addClass('selected');
    return $r;
  }

  function toMap(arr){ const m = {}; (arr||[]).forEach(a => m[a.id] = a); return m; }

  function renderOps($box, list, selectedList){
    $box.empty();
    if(!list || !list.length){ $box.append('<div class="muted">No items found in this group.</div>'); return; }
    const selMap = toMap(selectedList);
    // First append selected (to float at top), then unselected
    list.filter(it => selMap[it.id]).forEach(it => $box.append(opRow(it, true)).children().first().addClass('selected'));
    list.filter(it => !selMap[it.id]).forEach(it => $box.append(opRow(it, false)));
  }
  function renderTasks($box, list, selectedList){
    $box.empty();
    if(!list || !list.length){ $box.append('<div class="muted">No items found in this group.</div>'); return; }
    const selMap = {}; (selectedList||[]).forEach(t => selMap[t.id] = t.qty || 1);
    list.filter(it => selMap[it.id] !== undefined).forEach(it => $box.append(taskRow(it, true, selMap[it.id])));
    list.filter(it => selMap[it.id] === undefined).forEach(it => $box.append(taskRow(it, false, 1)));
  }

  // Initial render using server data
  renderOps($("#opList"), initialOps, selectedOps);
  renderTasks($("#taskList"), initialTasks, selectedTasks);

  // ------- Group change loading -------
  let lastOpIndex = $("#operation_group_id")[0].selectedIndex;
  $("#operation_group_id").on("change", function(){
    if($("#opList .row").length){
      if(!confirm("Changing Operation Group will clear current selections. Continue?")){
        this.selectedIndex = lastOpIndex; return;
      }
    }
    lastOpIndex = this.selectedIndex;
    $("#opList").html('<div class="muted">Loading operations…</div>');
    const gid = $(this).val();
    if(!gid){ $("#opList").html('<div class="muted">Select a group first.</div>'); return; }
    $.post('fetch_operations_by_group.php', { operation_group_id: gid }, function(list){
      renderOps($("#opList"), list || [], []); // clear selections on group change
      $("#opSearch").val('').trigger('input');
    }, 'json').fail(function(){ $("#opList").html('<div class="muted">Error loading operations.</div>'); });
  });

  let lastTaskIndex = $("#task_group_id")[0].selectedIndex;
  $("#task_group_id").on("change", function(){
    if($("#taskList .row").length){
      if(!confirm("Changing Task Group will clear current selections. Continue?")){
        this.selectedIndex = lastTaskIndex; return;
      }
    }
    lastTaskIndex = this.selectedIndex;
    $("#taskList").html('<div class="muted">Loading tasks…</div>');
    const gid = $(this).val();
    if(!gid){ $("#taskList").html('<div class="muted">Select a group first.</div>'); return; }
    $.post('fetch_tasks_by_group.php', { task_group_id: gid }, function(list){
      renderTasks($("#taskList"), list || [], []); // clear selections on group change
      $("#taskSearch").val('').trigger('input');
    }, 'json').fail(function(){ $("#taskList").html('<div class="muted">Error loading tasks.</div>'); });
  });

  // ------- Selection behavior -------
  $(document).on("change",".pick-op",function(){
    const $row = $(this).closest('.row'); const $box = $row.parent();
    if(this.checked){ $row.addClass('selected').prependTo($box); } else { $row.removeClass('selected').appendTo($box); }
  });
  $(document).on("change",".pick-task",function(){
    const $row = $(this).closest('.row'); const $box = $row.parent(); const $qty = $row.find(".task-qty");
    if(this.checked){ $row.addClass('selected').prependTo($box); $qty.prop('disabled', false); }
    else { $row.removeClass('selected').appendTo($box); $qty.prop('disabled', true); }
  });

  // ------- Search + clear -------
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

  // ------- Submit: validate + confirm + pack hidden fields -------
  $("#projectForm").on("submit", function(e){
    e.preventDefault();

    // Require basics
    if(!$("#project_for").val().trim()){ alert("Please fill Project For."); return false; }
    if(!$("#project_name").val().trim()){ alert("Please fill Project Name."); return false; }

    if($("#opList .pick-op:checked").length === 0){ alert("Please select at least one Operation."); return false; }
    if($("#taskList .pick-task:checked").length === 0){ alert("Please select at least one Task."); return false; }

    const startVal = $("#start_date").val(), endVal = $("#end_date").val();
    if(startVal && endVal && new Date(endVal) < new Date(startVal)){ alert("End Date cannot be before Start Date!"); return false; }
    const nextVal = $("#next_report_date").val();
    if(nextVal && startVal && new Date(nextVal) < new Date(startVal)){ alert("Next Project Report Date cannot be before Start Date!"); return false; }

    if(!confirm("Do you want to update this project?")) return false;

    // Remove previous packs if any
    $(this).find('input[name="operation_ids[]"],input[name="operation_names[]"],input[name="task_ids[]"],input[name="task_names[]"],input[name="task_qtys[]"]').remove();

    // Pack operations
    $("#opList .pick-op:checked").each(function(){
      const $r = $(this).closest('.row');
      $('<input>',{type:'hidden',name:'operation_ids[]', value:$r.data('id')}).appendTo("#projectForm");
      $('<input>',{type:'hidden',name:'operation_names[]', value:$r.data('name')}).appendTo("#projectForm");
    });

    // Pack tasks
    let ok = true;
    $("#taskList .pick-task:checked").each(function(){
      const $r = $(this).closest('.row');
      const qty = parseInt($r.find('.task-qty').val(),10) || 1;
      if(qty < 1) ok = false;
      $('<input>',{type:'hidden',name:'task_ids[]', value:$r.data('id')}).appendTo("#projectForm");
      $('<input>',{type:'hidden',name:'task_names[]', value:$r.data('name')}).appendTo("#projectForm");
      $('<input>',{type:'hidden',name:'task_qtys[]', value:qty}).appendTo("#projectForm");
    });
    if(!ok){ alert("Task quantity must be at least 1."); return false; }

    // Mark update (optional if backend uses REQUEST_METHOD)
    if(!$('#projectForm input[name="update"]').length){
      $('<input>',{type:'hidden',name:'update',value:'1'}).appendTo("#projectForm");
    }

    this.submit();
  });
});
</script>
</body>
</html>

<?php
// ---------- UPDATE SAVE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_for     = mysqli_real_escape_string($connection, $_POST['project_for']);
    $project_name    = mysqli_real_escape_string($connection, $_POST['project_name']);
    $contact_id      = !empty($_POST['contact_id']) ? mysqli_real_escape_string($connection, $_POST['contact_id']) : NULL;
    $contact_person  = !empty($_POST['contact_person']) ? mysqli_real_escape_string($connection, $_POST['contact_person']) : NULL;
    $contact_no      = !empty($_POST['contact_no']) ? mysqli_real_escape_string($connection, $_POST['contact_no']) : NULL;
    $report_freq     = !empty($_POST['report_frequency']) ? mysqli_real_escape_string($connection, $_POST['report_frequency']) : NULL;
    $next_report     = !empty($_POST['next_report_date']) ? mysqli_real_escape_string($connection, $_POST['next_report_date']) : NULL;
    $start_date      = mysqli_real_escape_string($connection, $_POST['start_date']);
    $end_date        = mysqli_real_escape_string($connection, $_POST['end_date']);
    $operation_group_id = (int)($_POST['operation_group_id'] ?? 0);
    $task_group_id      = (int)($_POST['task_group_id'] ?? 0);

    if (strtotime($end_date) < strtotime($start_date)) { echo "<script>alert('End Date cannot be before Start Date!'); history.back();</script>"; exit(); }
    if ($next_report && strtotime($next_report) < strtotime($start_date)) { echo "<script>alert('Next Project Report Date cannot be before Start Date!'); history.back();</script>"; exit(); }

    $operation_ids   = $_POST['operation_ids'] ?? [];
    $operation_names = $_POST['operation_names'] ?? [];
    $task_ids        = $_POST['task_ids'] ?? [];
    $task_names      = $_POST['task_names'] ?? [];
    $task_qtys       = $_POST['task_qtys'] ?? [];

    if (count($operation_ids) === 0) { echo "<script>alert('Please select at least one Operation.'); history.back();</script>"; exit(); }
    if (count($task_ids) === 0) { echo "<script>alert('Please select at least one Task.'); history.back();</script>"; exit(); }

    if (count($operation_ids) !== count($operation_names)) { echo "<script>alert('Operation arrays mismatch.'); history.back();</script>"; exit(); }
    if (count($task_ids) !== count($task_names) || count($task_ids) !== count($task_qtys)) { echo "<script>alert('Task arrays mismatch.'); history.back();</script>"; exit(); }

    mysqli_begin_transaction($connection);
    try {
        // Update projects (project_no stays untouched)
        $sqlU = "
          UPDATE projects SET
            project_for = '$project_for',
            project_name = '$project_name',
            contact_id = ".($contact_id ? "'$contact_id'" : "NULL").",
            contact_person = ".($contact_person ? "'$contact_person'" : "NULL").",
            contact_no = ".($contact_no ? "'$contact_no'" : "NULL").",
            project_report_submission = ".($report_freq ? "'$report_freq'" : "NULL").",
            next_report_date = ".($next_report ? "'$next_report'" : "NULL").",
            start_date = '$start_date',
            end_date = '$end_date',
            operation_group_id = ".($operation_group_id ?: "NULL").",
            task_group_id = ".($task_group_id ?: "NULL")."
          WHERE id = $project_id
        ";
        if (!mysqli_query($connection, $sqlU)) throw new Exception("Project update failed: ".mysqli_error($connection));

        // Replace items: delete then insert
        if (!mysqli_query($connection, "DELETE FROM project_operation_items WHERE project_id = $project_id"))
          throw new Exception("Failed clearing operation items: ".mysqli_error($connection));
        if (!mysqli_query($connection, "DELETE FROM project_task_items WHERE project_id = $project_id"))
          throw new Exception("Failed clearing task items: ".mysqli_error($connection));

        if (!empty($operation_ids)) {
            $stmt = mysqli_prepare($connection, "INSERT INTO project_operation_items (project_id, operation_group_id, operation_id, operation_name, position) VALUES (?, ?, ?, ?, ?)");
            if(!$stmt) throw new Exception("Prepare failed (operations): ".mysqli_error($connection));
            for ($i=0; $i<count($operation_ids); $i++){
                $op_id = (int)$operation_ids[$i];
                $op_name = $operation_names[$i] ?? '';
                $pos = $i+1;
                mysqli_stmt_bind_param($stmt, "iiisi", $project_id, $operation_group_id, $op_id, $op_name, $pos);
                if(!mysqli_stmt_execute($stmt)) throw new Exception("Operation item insert failed: ".mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        }

        if (!empty($task_ids)) {
            $stmt2 = mysqli_prepare($connection, "INSERT INTO project_task_items (project_id, task_group_id, task_id, task_name, quantity, position) VALUES (?, ?, ?, ?, ?, ?)");
            if(!$stmt2) throw new Exception("Prepare failed (tasks): ".mysqli_error($connection));
            for ($i=0; $i<count($task_ids); $i++){
                $t_id = (int)$task_ids[$i];
                $t_name = $task_names[$i] ?? '';
                $qty = (int)($task_qtys[$i] ?? 1); if ($qty < 1) $qty = 1;
                $pos = $i+1;
                mysqli_stmt_bind_param($stmt2, "iiisii", $project_id, $task_group_id, $t_id, $t_name, $qty, $pos);
                if(!mysqli_stmt_execute($stmt2)) throw new Exception("Task item insert failed: ".mysqli_stmt_error($stmt2));
            }
            mysqli_stmt_close($stmt2);
        }

        mysqli_commit($connection);
        if (ob_get_length()) { ob_end_clean(); }
        echo "<script>alert('Project updated successfully!'); window.location.replace('project_display.php');</script>";
        exit;

    } catch (Exception $ex) {
        mysqli_rollback($connection);
        if (ob_get_length()) { ob_end_clean(); }
        echo "<pre style='color:#b91c1c; white-space:pre-wrap;'>".$ex->getMessage()."</pre>";
        exit;
    }
}
?>
