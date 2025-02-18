<?php
// Database connection
include('connection.php');

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = $_POST['code'];
    $description = $_POST['description'];
    $type = $_POST['type'];

    // Insert query
    $query = "INSERT INTO hsn_sac (code, description, type) VALUES ('$code', '$description', '$type')";

    if (mysqli_query($connection, $query)) {
        echo "<script>alert('HSN/SAC record added successfully!'); window.location.href='hsn_sac_display.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($connection) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add HSN/SAC</title>
    <style>
        body {
            background-color: #2c3e50;
            font-family: Arial, sans-serif;
        }
        .card {
            width: 400px;
            margin: 100px auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            position: relative;
        }
        .card h2 {
            text-align: center;
            color: #2c3e50;
        }
        .card form {
            display: flex;
            flex-direction: column;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            color: #2c3e50;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .btn {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #34495e;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 18px;
            text-decoration: none;
            color: #2c3e50;
        }
        .close-btn:hover {
            color: red;
        }
    </style>
</head>
<body>
    <div class="card">
        <a href="hsn_sac_display.php" class="close-btn">✖</a>
        <h2>Add HSN/SAC</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="code">Code</label>
                <input type="text" id="code" name="code" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="2" required></textarea>
            </div>
            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" name="type" required>
                    <option value="" disabled selected>Select Type</option>
                    <option value="HSN">HSN</option>
                    <option value="SAC">SAC</option>
                </select>
            </div>
            <button type="submit" class="btn">Add HSN/SAC</button>
        </form>
    </div>
</body>
</html>
