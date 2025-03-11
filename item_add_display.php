<?php
// Include the connection file
include 'connection.php';

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Fetch item details based on ID from the URL
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($itemId <= 0) {
    echo "Invalid Item ID.";
    exit;
}

$item_query = "SELECT item_code, item_name FROM item WHERE id = $itemId";
$item_result = $connection->query($item_query);
$item_row = $item_result->fetch_assoc();

if (!$item_row) {
    echo "Item not found.";
    exit;
}

// Fetch all units from the unit_of_measurement table
$units = [];
$unit_query = "SELECT id, unit FROM unit_of_measurement";
$unit_result = $connection->query($unit_query);

while ($row = $unit_result->fetch_assoc()) {
    $units[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_code = $connection->real_escape_string($_POST['item_code'] ?? '');
    $item_name = $connection->real_escape_string($_POST['item_name'] ?? '');
    $unit_id = intval($_POST['unit_id'] ?? 0);
    $value = $connection->real_escape_string($_POST['value'] ?? '');
    $base_value = $connection->real_escape_string($_POST['base_value'] ?? '');

    // Validate inputs
    if (empty($item_code) || empty($item_name) || $unit_id <= 0 || empty($base_value)) {
        echo "<script>alert('All fields are required.');</script>";
    } else {
        // Fetch the unit name based on the selected unit ID
        $unit_name_query = "SELECT unit FROM unit_of_measurement WHERE id = $unit_id";
        $unit_name_result = $connection->query($unit_name_query);
        $unit_name_row = $unit_name_result->fetch_assoc();

        if ($unit_name_row) {
            $unit_name = $unit_name_row['unit'];

            // Check for active base price
            if ($base_value === 'Active') {
                $check_active_query = "SELECT * FROM item_add WHERE item_code = '$item_code' AND base_value = 'Active'";
                $check_active_result = $connection->query($check_active_query);

                if ($check_active_result->num_rows > 0) {
                    echo "<script>alert('An Active Base Price already exists for this item. Please choose Inactive.');</script>";
                } else {
                    // Insert data with Active base value
                    $insert_query = "INSERT INTO item_add (item_code, item_name, unit, unit_id, value, base_value)
                                     VALUES ('$item_code', '$item_name', '$unit_name', $unit_id, '$value', '$base_value')";
                    if ($connection->query($insert_query) === TRUE) {
                        echo "<script>
                            alert('Item record added successfully!');
                            window.location.href = 'item_add_display.php?id=$itemId';
                        </script>";
                    } else {
                        echo "Error: " . $connection->error;
                    }
                }
            } else {
                // Handle Inactive base value
                if ($value == 1) {
                    echo "<script>alert('The value can only be given to the Active unit. Please set Base Value to Active.');</script>";
                } else {
                    $insert_query = "INSERT INTO item_add (item_code, item_name, unit, unit_name, value, base_value)
                                     VALUES ('$item_code', '$item_name', '$unit_id' , '$unit_name', '$value', '$base_value')";
                    if ($connection->query($insert_query) === TRUE) {
                        echo "<script>
                            alert('Item record added successfully!');
                            window.location.href = 'item_add_display.php?id=$itemId';
                        </script>";
                    } else {
                        echo "Error: " . $connection->error;
                    }
                }
            }
        } else {
            echo "<script>alert('Invalid unit selected.');</script>";
        }
    }
}

// Fetch entries from item_add table based on item_code
$item_code_query = "SELECT * FROM item_add WHERE item_code = '{$item_row['item_code']}'";
$item_code_result = $connection->query($item_code_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #2c3e50;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background-color: #f9f9f9;
            padding: 15px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            margin-bottom: 20px;
        }
        .form-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
        }
        select, input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .action-button {
            background-color: #4CAF50; /* Green */
            color: white;
            border: none;
            padding: 8px 12px;
            text-align: center;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
</head>
<body>
  <div class="container">
      <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
       <h2 style="margin-left: 350px;">Item Unit Of Measurement</h2>
       <?php
// Fetch the `id` from the query parameter to ensure redirection to the correct edit page.
$id = isset($_GET['id']) ? $_GET['id'] : '';
?>
<button type="button"
    onclick="window.location.href='item_edit.php?id=<?php echo $id; ?>'"
    style="background: none; border: none; font-size: 24px; color: red; cursor: pointer;">
    &times;
</button>

   </div>
          <form method="POST" action="">
              <div class="form-container">
                  <div class="form-group">
                      <label for="item_name">Item Name:</label>
                      <input type="text" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item_row['item_name']); ?>" readonly>
                  </div>
                  <div class="form-group">
                      <label for="item_code">Item Code:</label>
                      <input type="text" id="item_code" name="item_code" value="<?php echo htmlspecialchars($item_row['item_code']); ?>" readonly>
                  </div>
                            <div class="form-group">
          <label for="unit">Unit:</label>
          <select id="unit" name="unit_id" required>
              <option value="">Select Unit</option>
              <?php foreach ($units as $unit): ?>
                  <option value="<?php echo htmlspecialchars($unit['id']); ?>">
                      <?php echo htmlspecialchars($unit['unit']); ?>
                  </option>
              <?php endforeach; ?>
          </select>
          </div>

                  <div class="form-group">
                      <label for="value">Value:</label>
                      <input type="text" id="value" name="value">
                  </div>
                  <div class="form-group">
                      <label for="base_value">Base Value:</label>
                      <select id="base_value" name="base_value">
                          <option value="Inactive">Inactive</option>
                          <option value="Active">Active</option>
                      </select>
                  </div>
              </div>
              <button type="submit" name="add_item" style="display: block; margin: auto;">Add</button>
          </form>

          <table>
              <thead>
                  <tr>
                      <th>ID</th>
                      <th>Item Code</th>
                      <th>Item Name</th>
                      <th>Unit</th>
                      <th>Value</th>
                      <th>Base Price</th>
                      <th>Action</th>
                  </tr>
              </thead>
              <tbody>
                  <?php if ($item_code_result->num_rows > 0): ?>
                      <?php while ($row = $item_code_result->fetch_assoc()): ?>
                          <tr>
                              <td><?php echo htmlspecialchars($row['id']); ?></td>
                              <td><?php echo htmlspecialchars($row['item_code']); ?></td>
                              <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                              <td><?php echo htmlspecialchars($row['unit_name']); ?></td>
                              <td><?php echo htmlspecialchars($row['value']); ?></td>
                              <td><?php echo htmlspecialchars($row['base_value']); ?></td>
                              <td>
                                  <a style="text-decoration:none;" href="item_add_delete.php?id=<?php echo $row['id']; ?>" class="action-button" onclick="return confirm('Are you sure you want to delete this entry?');">🗑️</a>
                              </td>
                          </tr>
                      <?php endwhile; ?>
                  <?php else: ?>
                      <tr>
                         echo "<tr><td colspan='7' style='text-align: center;'>No records found</td></tr>";
                      </tr>
                  <?php endif; ?>
              </tbody>
          </table>
      </div>
  </div>

</body>
</html>

<?php
$connection->close();
?>
