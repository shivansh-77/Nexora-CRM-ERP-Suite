<?php
include('connection.php');

// Check if an item_code is provided in the URL
if (isset($_GET['id'])) {
    $item_code = $_GET['id'];

    // Fetch the item details
    $query = "SELECT * FROM item WHERE id = '$item_code'";
    $result = $connection->query($query);
    $item = $result->fetch_assoc();

    if (!$item) {
        echo "Item not found.";
        exit;
    }
} else {
    echo "No item code provided.";
    exit;
}

// Handle form submission for updating
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $item_name = $_POST['item_name'];
    $item_category = $_POST['item_category'];
    $vendor_item_name = $_POST['vendor_item_name'];
    $unit_of_measure = $_POST['unit_of_measure'];
    $gst = $_POST['gst'];
    $hsn_sac = $_POST['hsn_sac'];
    $sales_price = $_POST['sales_price'];
    $location = $_POST['location'];
    $lot_tracking = isset($_POST['lot_tracking']) ? 1 : 0;
    $item_type = $_POST['item_type'];
    $expiration_tracking = isset($_POST['expiration_tracking']) ? 1 : 0;
    $block = isset($_POST['block']) ? 1 : 0;
    $amc_tracking = isset($_POST['amc_tracking']) ? 1 : 0;

    // Prepare and execute update statement
    $update_query = "UPDATE item SET item_name = '$item_name', item_category = '$item_category', vendor_item_name = '$vendor_item_name',
                     unit_of_measurement_code = '$unit_of_measure', gst_code = '$gst', hsn_sac_code = '$hsn_sac',
                     sales_price = '$sales_price', location = '$location', lot_tracking = '$lot_tracking',
                     item_type = '$item_type', expiration_tracking = '$expiration_tracking', block = '$block',
                     amc_tracking = '$amc_tracking' WHERE id = '$item_code'";

    if ($connection->query($update_query)) {
        echo "<script>alert('Item updated successfully!'); window.location.href='item_display.php';</script>";
    } else {
        echo "Error updating item: " . $connection->error;
    }
}

// Fetch options for dropdowns (reuse your existing fetchOptions function)
$unitOfMeasureOptions = fetchOptions($connection, 'unit_of_measurement', 'unit');
$gstOptions = fetchOptions($connection, 'gst', 'percentage');
$hsnSacOptions = fetchOptions($connection, 'hsn_sac', 'code');
$locationOptions = fetchOptions($connection, 'location_card', 'location_code');
$itemOptions = fetchOptions($connection, 'item_category', 'code');

function fetchOptions($connection, $table, $column) {
    $options = [];
    $query = "SELECT $column FROM $table";
    $result = $connection->query($query);

    while ($row = $result->fetch_assoc()) {
        $options[] = $row[$column];
    }

    return $options;
}

$connection->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            /* height: 70vh; */
        }

        .card-container {
            position: relative;
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 70vw;
            max-height: 610px;
            margin-top: 50px;
        }

        .close-btn {
            position: absolute;
            top: 5px;
            right: 10px;
            font-size: 18px;
            color: #2c3e50;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-btn:hover {
            color: #ff0000;
        }

        h2 {
            margin-left: 380px;
        }

        .form-container {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Two equal columns */
            gap: 20px; /* Spacing between items */

        }

        .form-group {
            display: flex;
            flex-direction: column; /* Stack elements vertically */
        }

        .form-group label {
            margin-bottom: 5px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            padding: 4px;
            border: 1px solid #ccc;
            border-radius: 4px;
            height: 40px; /* Set a uniform height */
            font-size: 14px;
            width: 100%; /* Ensure the fields take the full width of the container */
            box-sizing: border-box; /* Include padding in width calculation */
        }

        .form-group input[type="checkbox"] {
            height: 38px; /* Match height of other input fields */
            width: 30px; /* Ensure uniform width */
            margin-right: 10px; /* Space between checkbox and label */
            cursor: pointer;
        }

        /* .form-group label span {
            color: red;
        } */



        .btn-container {
            text-align: center;
            margin-top: 20px;
        }

        .btn-container .btn {
            background-color: #2c3e50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            margin-bottom: 190px;
        }

        .btn-container .btn:hover {
            background-color: #0056b3;
        }

        /* #lot_tracking{
          margin-top: 20px;

        } */


       .form-group input[type="checkbox"] {
           height: 20px; /* Consistent height */
           width: 20px; /* Consistent width */
           margin-right: 10px;
            margin-Top: 30px; /* Space between checkbox and label text */
           cursor: pointer;
       }

    </style>
</head>
<body>
    <div class="card-container">
      <a href="item_add_display.php?id=<?php echo $item['id']; ?>" style="text-decoration: none; margin-right: 10px; padding: 5px 10px; background-color: #2c3e50; color: white; border-radius: 5px;">Unit</a>
        <a style="text-decoration:None;" href="item_display.php" class="close-btn">&times;</a>
        <h2>Edit Item</h2>
        <form action="" method="POST">
            <div class="form-container">
                <div class="form-group">
                    <label for="item_code">Item Code:</label>
                    <input type="text" id="item_code" name="item_code" value="<?php echo htmlspecialchars($item['item_code']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="item_name">Item Name<span>*</span>:</label>
                    <input type="text" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="item_category">Item Category</label>
                    <select id="item_category" name="item_category">
                        <option value="">Select</option>
                        <?php foreach ($itemOptions as $category) : ?>
                            <option value="<?= htmlspecialchars($category) ?>" <?php echo ($item['item_category'] == $category) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($category) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Repeat for other form fields, populating with $item['field_name'] -->
                <div class="form-group">
      <label for="item_type">Item Type<span>*</span>:</label>
      <select id="item_type" name="item_type" required>
          <option value="Service" <?php echo ($item['item_type'] == 'Service') ? 'selected' : ''; ?>>Service</option>
          <option value="Inventory" <?php echo ($item['item_type'] == 'Inventory') ? 'selected' : ''; ?>>Inventory</option>
      </select>
  </div>
                <!-- <div class="form-group">
                    <label for="vendor_item_name">Vendor Item Name:</label>
                    <input type="text" id="vendor_item_name" name="vendor_item_name">
                </div>
                <div class="form-group">
                    <label for="customer_item_name">Customer Item Name:</label>
                    <input type="text" id="customer_item_name" name="customer_item_name">
                </div> -->

                <div class="form-group">
      <label for="unit_of_measure">Unit of Measure<span>*</span>:</label>
      <select id="unit_of_measure" name="unit_of_measure" required>
          <option value="">Select</option>
          <?php foreach ($unitOfMeasureOptions as $unit) : ?>
              <option value="<?= htmlspecialchars($unit) ?>" <?php echo ($item['unit_of_measurement_code'] == $unit) ? 'selected' : ''; ?>>
                  <?= htmlspecialchars($unit) ?>
              </option>
          <?php endforeach; ?>
      </select>
  </div>

  <div class="form-group">
    <label for="gst">GST%<span>*</span>:</label>
    <select id="gst" name="gst" required>
        <option value="">Select</option>
        <?php foreach ($gstOptions as $gst) : ?>
            <option value="<?= htmlspecialchars($gst) ?>" <?php echo ($item['gst_code'] == $gst) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($gst) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


  <div class="form-group">
      <label for="hsn_sac">HSN/SAC<span>*</span>:</label>
      <select id="hsn_sac" name="hsn_sac" required>
          <option value="">Select</option>
          <?php foreach ($hsnSacOptions as $hsnSac) : ?>
              <option value="<?= htmlspecialchars($hsnSac) ?>" <?php echo ($item['hsn_sac_code'] == $hsnSac) ? 'selected' : ''; ?>>
                  <?= htmlspecialchars($hsnSac) ?>
              </option>
          <?php endforeach; ?>
      </select>
  </div>


                <div class="form-group">
    <label for="sales_price">Sales Price:</label>
    <input type="number" step="0.01" id="sales_price" name="sales_price" value="<?php echo htmlspecialchars($item['sales_price']); ?>">
</div>

<div class="form-group">
    <label for="location">Location Code</label>
    <select id="location" name="location">
        <option value="">Select</option>
        <?php foreach ($locationOptions as $loc) : ?>
            <option value="<?= htmlspecialchars($loc) ?>" <?php echo (isset($item['location']) && $item['location'] == $loc) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($loc) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>



                <div class="form-group" style="display:flex; flex-direction:row; justify-content:space-between;">
                    <label for="lot_tracking">
                        <input type="checkbox" id="lot_tracking" name="lot_tracking" value="1" <?php echo $item['lot_tracking'] ? 'checked' : ''; ?>>
                        Lot-ID
                    </label>
                    <label for="expiration_tracking">
                        <input type="checkbox" id="expiration_tracking" name="expiration_tracking" value="1" <?php echo $item['expiration_tracking'] ? 'checked' : ''; ?>>
                        Expiration
                    </label>
                    <label for="amc_tracking">
                        <input type="checkbox" id="amc_tracking" name="amc_tracking" value="1" <?php echo $item['amc_tracking'] ? 'checked' : ''; ?>>
                        AMC
                    </label>
                    <label for="block">
                        <input type="checkbox" id="block" name="block" value="1" <?php echo $item['block'] ? 'checked' : ''; ?>>
                        Block
                    </label>
                </div>
                <div class="form-group" style="margin-bottom:10px;">
    <label for="vendor_item_name">Barcode:</label>
    <input type="text" id="vendor_item_name" name="vendor_item_name" value="<?php echo htmlspecialchars($item['vendor_item_name']); ?>">
</div>

            </div>
            <div class="btn-container">
                <button type="submit" class="btn">Update Item</button>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
      // Add event listeners to all checkboxes
      const checkboxes = document.querySelectorAll('input[type="checkbox"]');
      checkboxes.forEach(checkbox => {
          checkbox.addEventListener('change', function () {
              const isChecked = this.checked ? 1 : 0; // Convert to 1 or 0
              const fieldName = this.name; // Get the name of the checkbox
              const recordId = this.dataset.id; // Get the record ID from a data attribute

              // Send an AJAX request to update the database
              fetch("update_checkbox.php", {
                  method: "POST",
                  headers: {
                      "Content-Type": "application/json"
                  },
                  body: JSON.stringify({
                      field: fieldName,
                      value: isChecked,
                      id: recordId // Include the record ID
                  })
              })
              .then(response => response.json())
              .then(data => {
                  if (data.success) {
                      console.log(`${fieldName} updated to ${isChecked} for ID ${recordId}`);
                  } else {
                      console.error(data.message);
                  }
              })
              .catch(error => {
                  console.error("Error:", error);
              });
          });
      });
    });
    </script>
</body>
</html>
