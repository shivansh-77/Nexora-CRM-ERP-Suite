<?php
// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'lead_management';
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch item details based on ID from the URL
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($itemId > 0) {
    $item_query = "SELECT item_code, item_name FROM item WHERE id = ?";
    $stmt = $conn->prepare($item_query);
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $item_result = $stmt->get_result();
    $item_row = $item_result->fetch_assoc();

    if (!$item_row) {
        echo "Item not found.";
        exit;
    }

    // Fetch entries from item_add based on item_code
    $item_code_query = "SELECT * FROM item_add WHERE item_code = ?";
    $stmt = $conn->prepare($item_code_query);
    $stmt->bind_param("s", $item_row['item_code']);
    $stmt->execute();
    $item_code_result = $stmt->get_result();
} else {
    echo "Invalid ID.";
    exit;
}

// Handle update request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_item'])) {
    $id = $conn->real_escape_string($_POST['id']);
    $unit = $conn->real_escape_string($_POST['unit']);
    $value = $conn->real_escape_string($_POST['value']);
    $base_value = $conn->real_escape_string($_POST['base_value']);

    $update_query = "UPDATE item_add SET unit = ?, value = ?, base_value = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $unit, $value, $base_value, $id);

    if ($stmt->execute()) {
        echo "<script>
            alert('Item updated successfully!');
            window.location.href = 'edit_item.php?id=$itemId';
        </script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<h1>Edit Item: <?php echo htmlspecialchars($item_row['item_name']); ?></h1>
<table border="1" cellpadding="10">
    <thead>
        <tr>
            <th>Unit</th>
            <th>Value</th>
            <th>Base Value</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $item_code_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['unit']); ?></td>
                <td><?php echo htmlspecialchars($row['value']); ?></td>
                <td><?php echo htmlspecialchars($row['base_value']); ?></td>
                <td>
                    <button onclick="openEditCard(<?php echo htmlspecialchars($row['id']); ?>)">Edit</button>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Editing Modal -->
<div class="overlay" id="editOverlay" style="display:none;">
    <div class="card">
        <form method="POST" id="editForm">
            <input type="hidden" name="id" id="editId">
            <input type="text" name="unit" id="editUnit" placeholder="Unit" required>
            <input type="text" name="value" id="editValue" placeholder="Value" required>
            <input type="text" name="base_value" id="editBaseValue" placeholder="Base Value" required>
            <button type="submit" name="update_item">Update</button>
            <button type="button" onclick="closeEditCard()">Cancel</button>
        </form>
    </div>
</div>

<script>
    function openEditCard(itemId) {
        // Fetch the current data for the selected item_add entry
        fetch(`get_item_add.php?id=${itemId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('editId').value = data.id;
                document.getElementById('editUnit').value = data.unit;
                document.getElementById('editValue').value = data.value;
                document.getElementById('editBaseValue').value = data.base_value;
                document.getElementById('editOverlay').style.display = 'flex'; // Show the modal
            })
            .catch(error => console.error('Error fetching item:', error));
    }

    function closeEditCard() {
        document.getElementById('editOverlay').style.display = 'none'; // Hide the modal
    }
</script>
