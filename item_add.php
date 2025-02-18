<?php
include('connection.php');

// Function to fetch the last item code
function getLastItemCode($connection) {
    $query = "SELECT item_code FROM item ORDER BY item_code DESC LIMIT 1";
    $result = $connection->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['item_code'];
    }
    return null; // Return null if no items are found
}

// Get the last item code
$lastItemCode = getLastItemCode($connection);

// Generate the new item code
$newItemCode = '';
if ($lastItemCode) {
    // Extract the numeric part and increment it
    preg_match('/_(\d+)$/', $lastItemCode, $matches);
    if ($matches) {
        $lastNumber = (int)$matches[1];
        $newItemCode = 'item_' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $newItemCode = 'item_0001'; // Default if no valid item code found
    }
} else {
    $newItemCode = 'item_0001'; // Default if no items exist
}

// Fetch data for dropdowns
$unitOptions = fetchOptions($connection, 'unit_of_measurement', 'unit');
$gstOptions = fetchOptions($connection, 'gst', 'percentage'); // Assuming you want the GST code here
$hsnOptions = fetchOptions($connection, 'hsn_sac', 'code');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_code = $_POST['item_code'];
    $item_name = $_POST['item_name'];
    $item_category = $_POST['item_category'];
    $vendor_item_name = $_POST['vendor_item_name'];
    // $customer_item_name = $_POST['customer_item_name'];
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

    // Prepare statement for item table
    $stmt = $connection->prepare("INSERT INTO item (item_code, item_name, vendor_item_name ,item_category, unit_of_measurement_code, gst_code, hsn_sac_code, sales_price, location, lot_tracking, item_type, expiration_tracking, block , amc_tracking) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? , ?)");

    // Bind parameters
    $stmt->bind_param("sssssssdsissis", $item_code, $item_name, $vendor_item_name, $item_category, $unit_of_measure, $gst, $hsn_sac, $sales_price, $location, $lot_tracking, $item_type, $expiration_tracking, $block ,$amc_tracking);

    // Execute and check for errors
    if ($stmt->execute()) {
        // After successfully inserting into the item table, insert into item_add table
        $value = 1.00; // Set value as 1.00
        $base_value = 'Active'; // Set base_value as 'Active'
        // Fetch unit id based on unit name
        $unitQuery = "SELECT id FROM unit_of_measurement WHERE unit = '$unit_of_measure' LIMIT 1";
        $unitResult = $connection->query($unitQuery);

        if ($unitRow = $unitResult->fetch_assoc()) {
            $unit_id = $unitRow['id'];
        } else {
            $unit_id = null; // Handle cases where no match is found
        }

        // Prepare statement for item_add table
        $stmt_add = $connection->prepare("INSERT INTO item_add (item_code, item_name,unit, unit_name, value, base_value) VALUES (?, ?, ?, ?, ? , ?)");

        // Bind parameters
        $stmt_add->bind_param("ssssss", $item_code, $item_name,$unit_id, $unit_of_measure, $value, $base_value);

        // Execute and check for errors
        if ($stmt_add->execute()) {
            echo "<script>alert('Record added successfully!'); window.location.href='item_display.php';</script>";
        } else {
            echo "Error adding to item_add: " . $stmt_add->error;
        }

        $stmt_add->close();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Function to fetch options for dropdowns
function fetchOptions($connection, $table, $column) {
    $options = [];
    $query = "SELECT $column FROM $table";
    $result = $connection->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $options[] = $row[$column];
        }
    }
    return $options;
}

// Fetch data for each select input
$unitOfMeasureOptions = fetchOptions($connection, 'unit_of_measurement', 'unit');
$gstOptions = fetchOptions($connection, 'gst', 'percentage'); // Fetching GST codes
$hsnSacOptions = fetchOptions($connection, 'hsn_sac', 'code');
$locationOptions = fetchOptions($connection, 'location_card', 'location_code'); // Fetch location codes
$itemOptions = fetchOptions($connection, 'item_category', 'code'); // Fetch item category codes

// Close the connection
$connection->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Card</title>
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


    <div class="card-container">
          <a href="item_add_display.php?item_code=<?php echo $newItemCode; ?>" style="text-decoration: none; margin-right: 10px; padding: 5px 10px; background-color: #2c3e50; color: white; border-radius: 5px;">Unit</a>
       <a style="text-decoration:None;"href="item_display.php" class="close-btn">&times;</a>
        <h2>Item Entry Card</h2>
        <form action="" method="POST">
            <div class="form-container">
                <!-- Column 1 -->
                <div class="form-group">
                  <label for="item_code">Item Code:</label>
        <input type="text" id="item_code" name="item_code" value="<?php echo $newItemCode; ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="item_name">Item Name<span>*</span>:</label>
                    <input type="text" id="item_name" name="item_name" required>
                </div>
                <div class="form-group">
                    <label for="item_category">Item Category</label>
                    <select id="item_category" name="item_category">
                        <option value="">Select</option>
                        <?php foreach ($itemOptions as $item_category) : ?>
                            <option value="<?= htmlspecialchars($item_category) ?>"><?= htmlspecialchars($item_category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="item_type">Item Type<span>*</span>:</label>
                    <select id="item_type" name="item_type" required>
                        <option value="Service">Service</option>
                        <option value="Inventory">Inventory</option>
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
                            <option value="<?= htmlspecialchars($unit) ?>"><?= htmlspecialchars($unit) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="gst">GST%<span>*</span>:</label>
                    <select id="gst" name="gst" required>
                        <option value="">Select</option>
                        <?php foreach ($gstOptions as $gst) : ?>
                            <option value="<?= htmlspecialchars($gst) ?>"><?= htmlspecialchars($gst) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Column 2 -->
                <div class="form-group">
                    <label for="hsn_sac">HSN/SAC<span>*</span>:</label>
                    <select id="hsn_sac" name="hsn_sac" required>
                        <option value="">Select</option>
                        <?php foreach ($hsnSacOptions as $hsnSac) : ?>
                            <option value="<?= htmlspecialchars($hsnSac) ?>"><?= htmlspecialchars($hsnSac) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sales_price">Sales Price:</label>
                    <input type="number" step="0.01" id="sales_price" name="sales_price">
                </div>
                <div class="form-group">
                    <label for="location">Location Code</label>
                    <select id="location" name="location">
                        <option value="">Select</option>
                        <?php foreach ($locationOptions as $location) : ?>
                            <option value="<?= htmlspecialchars($location) ?>"><?= htmlspecialchars($location) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="display:flex; flex-direction:row; justify-content:space-between;">
             <label for="lot_tracking">
                 <input type="checkbox" id="lot_tracking" name="lot_tracking" value="1" data-id="your_record_id">
                 Lot-ID
             </label>
             <label for="expiration_tracking">
                 <input type="checkbox" id="expiration_tracking" name="expiration_tracking" value="1" data-id="your_record_id">
                 Expiration
             </label>
             <label for="amc_tracking">
                 <input type="checkbox" id="amc_tracking" name="amc_tracking" value="1" data-id="your_record_id">
                 AMC
             </label>
             <label for="block">
                 <input type="checkbox" id="block" name="block" value="1" data-id="your_record_id">
                 Block
             </label>
         </div>
      <div class="form-group" style="margin-bottom:10px;">
          <label for="vendor_item_name">Barcode:</label>
          <input type="text" id="vendor_item_name" name="vendor_item_name">
      </div>
            </div>
            <div class="btn-container">
                <button type="submit" class="btn">Submit</button>
            </div>
        </form>
    </div>

</body>

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

</html>
