<?php
// Include database connection
include('connection.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $status = $_POST['status'];

    // Insert the new record into the database
    $query = "INSERT INTO lead_sourc (name, status) VALUES ('$name', '$status')";
    if (mysqli_query($connection, $query)) {
        // Redirect to contact.php with the leadFor parameter (name from the inserted row)
        header("Location: contact.php?leadSource=" . urlencode($name));
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Source Entry</title>
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
        <h2>Enter Lead Source</h2>
        <form method="POST" action="lead_source_display.php">
            <div class="form-group">
                <label for="name">Lead Source Name</label>
                <input type="text" id="name" name="name" placeholder="Enter Lead Source" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit">Submit</button>
            </div>
        </form>
    </div>
</body>
</html>
