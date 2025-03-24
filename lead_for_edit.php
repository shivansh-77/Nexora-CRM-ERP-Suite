<?php
// Include database connection
include('connection.php');

// Fetch the existing record based on the ID from the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM lead_for WHERE id = $id";
    $result = mysqli_query($connection, $query);

    if ($result && $row = mysqli_fetch_assoc($result)) {
        $name = $row['name'];
        $status = $row['status'];
    } else {
        echo "<p style='color:red;'>Error: Record not found.</p>";
        exit();
    }
} else {
    echo "<p style='color:red;'>Error: No ID provided.</p>";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $status = $_POST['status'];

    // Update the existing record in the database
    $query = "UPDATE lead_for SET name = '$name', status = '$status' WHERE id = $id";
    if (mysqli_query($connection, $query)) {
        // Redirect to the lead source display page after successful update
        header("Location: lead_for_display.php");
        exit();
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($connection) . "</p>";
    }
}

// Close the database connection after the operation
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lead Source</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            width: 80%;
            max-width: 600px;
            background: #fff;
            border-radius: 10px;
            padding: 20px 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%; /* Ensure all fields take up the same width */
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box; /* Includes padding and border in the width */
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #007bff;
        }

        .form-actions {
            text-align: center;
            margin-top: 20px;
        }

        .form-actions button {
            padding: 10px 20px;
            font-size: 16px;
            background: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .form-actions button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit Lead Source</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Lead Source Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($status == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit">Update</button>
            </div>
        </form>
    </div>
</body>
</html>
