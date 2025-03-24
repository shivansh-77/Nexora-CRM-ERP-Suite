<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Unit Measurement</title>
    <style>
        body {
            background-color: #2c3e50;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .card {
            background-color: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            width: 400px;
            position: relative;
        }

        .card-header {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: transparent;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
        }

        .form-group textarea {
            resize: none;
            height: 80px;
        }

        .btn {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .btn:hover {
            background-color: #34495e;
        }
    </style>
</head>
<body>
    <div class="card">
        <button class="close-btn" onclick="window.location.href='unit_measurement_display.php';">✖</button>
        <div class="card-header">Edit Unit Measurement</div>
        <?php
        // Database connection
        include('connection.php');

        // Check if ID is passed via GET
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $id = $_GET['id'];

            // Fetch the data for the given ID
            $query = "SELECT * FROM unit_of_measurement WHERE id = $id";
            $result = mysqli_query($connection, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
            } else {
                echo "<script>alert('Record not found!'); window.location.href='unit_measurement_display.php';</script>";
            }
        } else {
            echo "<script>alert('Invalid request!'); window.location.href='unit_measurement_display.php';</script>";
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $unit = $_POST['unit'];
            $description = $_POST['description'];

            // Update the record in the database
            $updateQuery = "UPDATE unit_of_measurement SET unit = '$unit', description = '$description' WHERE id = $id";
            if (mysqli_query($connection, $updateQuery)) {
                echo "<script>alert('Unit Measurement updated successfully!'); window.location.href='unit_measurement_display.php';</script>";
            } else {
                echo "<script>alert('Error updating Unit Measurement.');</script>";
            }
        }
        ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="unit">Unit:</label>
                <input type="text" id="unit" name="unit" value="<?php echo $row['unit']; ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description"><?php echo $row['description']; ?></textarea>
            </div>
            <button type="submit" class="btn">Update Unit Measurement</button>
        </form>
    </div>
</body>
</html>
