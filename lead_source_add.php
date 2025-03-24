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
        // Redirect to lead_source_display.php after successful insertion
        header("Location: lead_source_display.php");
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
    <title>Lead Source Entry</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            position: relative; /* Ensure the close button is positioned relative to this container */
            width: 50%;
            max-width: 500px;
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

        .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <button class="close-button" onclick="window.location.href='lead_source_display.php'">✖</button>
        <h2>Enter Lead Source</h2>
        <form method="POST" action="lead_source_add.php">
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
