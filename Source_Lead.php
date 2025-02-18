<?php
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    echo "<script>alert('$message');</script>";
}
?>
<?php
session_start();
include('topbar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lead For</title>

  <style>
    /* General styles */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 63px;
    }
    table th, table td {
      padding: 10px;
      border: 1px solid #ddd;
      text-align: left;
    }
    table th {
      background-color: #2c3e50;
    }
    table th:last-child, table td:last-child {
    width: 100px; /* Adjust the width as needed */
    text-align: center;
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
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }
    .modal-content {
      background: white;
      width: 90%;
      max-width: 500px;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    .modal-header, .modal-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .modal-header {
      border-bottom: 1px solid #ddd;
    }
    .modal-footer {
      border-top: 1px solid #ddd;
      margin-top: 10px;
      padding-top: 10px;
    }
    .form-control {
      width: 100%;
      padding: 8px;
      margin: 5px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
    }
    .alert {
      color: green;
      margin: 10px 0;
    }
    .leadforhead {
    position: fixed;
    width: 74%;
    height: 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #2c3e50;
    color: white;
    padding: 0 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
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
.lead-actions {
    display: flex;
    align-items: center;
    gap: 10px;
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

.btn-search {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 0; /* Remove the rounded corner to align with the search bar */
    cursor: pointer;
    font-size: 14px;
}

  </style>
</head>
<body>

  <div class="content">
    <div class="leadforhead">
        <h2 class="leadfor">Lead Source</h2>
          <div class="lead-actions">
              <div class="search-bar">
      <input type="text" id="searchInput" class="search-input" placeholder="Search...">
      <button class="btn-search" id="searchButton">🔍</button>
    </div>
    <button class="btn-primary" id="openModal" data-mode="add">➕</button>
  </div>
</div>
    <?php
    $conn = mysqli_connect("localhost", "root", "", "lead_management") or die("Connection failed");
    $query = "SELECT * FROM lead_sourc";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
    ?>


    <table id="mytable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        while ($rows = mysqli_fetch_assoc($result)) {
        ?>
        <tr>
          <td><?php echo $rows['id']; ?></td>
          <td><?php echo $rows['name']; ?></td>
          <td><?php echo $rows['status']; ?></td>
          <td>
                        <button class="btn-warning edit-btn"
                                data-id="<?php echo $rows['id']; ?>"
                                data-name="<?php echo $rows['name']; ?>"
                                data-status="<?php echo $rows['status']; ?>">✏️</button>
                        <a href="Source_Delete.php?id=<?php echo $rows['id']; ?>"
                           onclick="return confirm('Are you sure you want to delete this record?');">
                            <button class="btn-danger">🗑️</button>
                        </a>
            </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
    <?php } ?>
  </div>

  <!-- Modal -->
  <div class="modal" id="leadSourceModal">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="modalTitle">Add Lead Source</h5>
        <button class="btn-close" id="closeModal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="leadSourceForm" method="POST" action="">
          <input type="hidden" id="editId" name="id">
          <label for="name">Lead Source Name</label>
          <input type="text" id="name" name="name" class="form-control" required>
          <label for="status">Status</label>
          <select id="status" name="status" class="form-control" required>
            <option value="Active">Select an Option</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
          <input class="submit btn-primary" type="submit" value="Save">
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" id="cancelModal">Cancel</button>
      </div>
    </div>
  </div>


  <script>
  document.addEventListener("DOMContentLoaded", () => {
const modal = document.getElementById("leadSourceModal");
const openModalButton = document.getElementById("openModal");
const closeModalButton = document.getElementById("closeModal");
const cancelModalButton = document.getElementById("cancelModal");
const nameInput = document.getElementById("name");
const editIdInput = document.getElementById("editId");
const statusSelect = document.getElementById("status");
const leadSourceForm = document.getElementById("leadSourceForm");
const modalTitle = document.getElementById("modalTitle");

// Ensure modal doesn't open automatically
modal.style.display = "none";

// Open Modal in Add or Edit Mode
function openModal(mode, data = {}) {
  modal.style.display = "flex";
  modalTitle.textContent = mode === "edit" ? "Edit Lead Source" : "Add Lead Source";
  leadSourceForm.action = mode === "edit" ? "Source_Edit.php" : "Source_insert.php"; // Set action based on mode
  editIdInput.value = data.id || "";
  nameInput.value = data.name || "";
  statusSelect.value = data.status || "Active";
  nameInput.focus();
}

openModalButton.addEventListener("click", () => {
  openModal("add");
});

document.querySelectorAll(".edit-btn").forEach(button => {
  button.addEventListener("click", () => {
    const data = {
      id: button.dataset.id,
      name: button.dataset.name,
      status: button.dataset.status,
    };
    openModal("edit", data);
  });
});

// Close Modal
[closeModalButton, cancelModalButton].forEach(button => {
  button.addEventListener("click", () => {
    modal.style.display = "none";
  });
});

window.addEventListener("click", (event) => {
  if (event.target === modal) {
    modal.style.display = "none";
  }
});
});

//Search Input

document.addEventListener("DOMContentLoaded", () => {
const searchInput = document.getElementById("searchInput");
const table = document.getElementById("mytable");
const rows = table ? table.getElementsByTagName("tr") : [];

searchInput.addEventListener("keyup", () => {
  const query = searchInput.value.toLowerCase().trim();

  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    const cells = row.getElementsByTagName("td");
    let match = Array.from(cells).some((cell) =>
      cell.textContent.toLowerCase().includes(query)
    );
    row.style.display = match ? "" : "none";
  }
});
});
  </script>

</body>
</html>
