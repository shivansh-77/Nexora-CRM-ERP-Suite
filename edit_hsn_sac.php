<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit HSN/SAC</title>
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
        .form-group select,
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
        <button class="close-btn" onclick="window.location.href='hsn_sac_display.php';">✖</button>
        <div class="card-header">Edit HSN/SAC</div>
        <?php
        // Database connection
        include('connection.php');

        // Check if ID is passed via GET
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $id = $_GET['id'];

            // Fetch the data for the given ID
            $query = "SELECT * FROM hsn_sac WHERE id = $id";
            $result = mysqli_query($connection, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
            } else {
                echo "<script>alert('Record not found!'); window.location.href='hsn_sac_display.php';</script>";
            }
        } else {
            echo "<script>alert('Invalid request!'); window.location.href='hsn_sac_display.php';</script>";
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $code = $_POST['code'];
            $description = $_POST['description'];
            $type = $_POST['type'];

            // Update the record in the database
            $updateQuery = "UPDATE hsn_sac SET code = '$code', description = '$description', type = '$type' WHERE id = $id";
            if (mysqli_query($connection, $updateQuery)) {
                echo "<script>alert('HSN/SAC updated successfully!'); window.location.href='hsn_sac_display.php';</script>";
            } else {
                echo "<script>alert('Error updating HSN/SAC.');</script>";
            }
        }
        ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="code">Code:</label>
                <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($row['code']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($row['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="type">Type:</label>
                <select id="type" name="type" required>
                    <option value="" disabled>Select Type</option>
                    <option value="HSN" <?php echo ($row['type'] == 'HSN') ? 'selected' : ''; ?>>HSN</option>
                    <option value="SAC" <?php echo ($row['type'] == 'SAC') ? 'selected' : ''; ?>>SAC</option>
                </select>
            </div>
            <button type="submit" class="btn">Update HSN/SAC</button>
        </form>
    </div>
</body>
</html>
