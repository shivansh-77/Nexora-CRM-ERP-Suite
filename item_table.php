<?php
include('connection.php');
// Fetch items from the database
$item = $connection->query("SELECT id, item_code, item_name FROM item");

// Fetch unit values from the database
$units = $connection->query("SELECT unit, description FROM unit_of_measurement");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Details</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        label { font-weight: bold; margin-top: 10px; display: block; }
        select, input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px; }
        button { margin-top: 15px; padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; }
        button:hover { background: #218838; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #2c3e50; color: white; }
        .action-btns button { margin-right: 5px; padding: 5px 10px; cursor: pointer; }
        .edit { background: #f39c12; color: white; }
        .delete { background: #e74c3c; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h2>Item Details</h2>

    <form id="itemForm">
        <!-- Item Number (Fetched) -->
        <label for="item_number">Item Number</label>
        <select id="item_number" required>
            <option value="" disabled selected>Select Item Number</option>
            <?php while ($row = $item->fetch_assoc()) { ?>
                <option value="<?= $row['id'] ?>" data-name="<?= $row['item_code'] ?>"><?= $row['item_code'] ?></option>
            <?php } ?>
        </select>

        <!-- Item Name (Auto-filled) -->
        <label for="item_name">Item Name</label>
        <input type="text" id="item_name" readonly>

        <!-- Unit (Fetched) -->
        <label for="unit">Unit</label>
        <select id="unit" required>
            <option value="" disabled selected>Select Unit</option>
            <?php while ($row = $unit->fetch_assoc()) { ?>
                <option value="<?= $row['unit'] ?>" data-value="<?= $row['value'] ?>"><?= $row['unit'] ?></option>
            <?php } ?>
        </select>

        <!-- Value (Auto-filled) -->
        <label for="value">Value</label>
        <input type="text" id="value" readonly>

        <!-- Add Button -->
        <button type="button" onclick="addItem()">Add</button>
    </form>

    <!-- Item Table -->
    <table id="itemTable">
        <thead>
            <tr>
                <th>Item Number</th>
                <th>Item Name</th>
                <th>Unit</th>
                <th>Value</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- Rows will be added dynamically -->
        </tbody>
    </table>
</div>

<script>
document.getElementById("item_number").addEventListener("change", function() {
    const selectedOption = this.options[this.selectedIndex];
    document.getElementById("item_name").value = selectedOption.dataset.name;
});

document.getElementById("unit").addEventListener("change", function() {
    const selectedOption = this.options[this.selectedIndex];
    document.getElementById("value").value = selectedOption.dataset.value;
});

function addItem() {
    const itemNumberSelect = document.getElementById("item_number");
    const itemNumber = itemNumberSelect.options[itemNumberSelect.selectedIndex].text;
    const itemName = document.getElementById("item_name").value;
    const unitSelect = document.getElementById("unit");
    const unit = unitSelect.options[unitSelect.selectedIndex].text;
    const value = document.getElementById("value").value;

    if (!itemNumber || !unit) {
        alert("Please select an item and a unit.");
        return;
    }

    const table = document.getElementById("itemTable").querySelector("tbody");
    const row = document.createElement("tr");

    row.innerHTML = `
        <td>${itemNumber}</td>
        <td>${itemName}</td>
        <td>${unit}</td>
        <td>${value}</td>
        <td class="action-btns">
            <button class="edit" onclick="editRow(this)">Edit</button>
            <button class="delete" onclick="deleteRow(this)">Delete</button>
        </td>
    `;

    table.appendChild(row);

    // Reset form fields
    document.getElementById("itemForm").reset();
}

function editRow(button) {
    const row = button.closest("tr");
    document.getElementById("item_number").value = row.cells[0].innerText;
    document.getElementById("item_name").value = row.cells[1].innerText;
    document.getElementById("unit").value = row.cells[2].innerText;
    document.getElementById("value").value = row.cells[3].innerText;

    row.remove();
}

function deleteRow(button) {
    button.closest("tr").remove();
}
</script>

</body>
</html>
