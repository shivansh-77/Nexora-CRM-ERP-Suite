<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Department</title>
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

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
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
        <button class="close-btn" onclick="window.location.href='designation_display.php';">✖</button>
        <div class="card-header">Add Department</div>
        <form method="POST" action="">
            <div class="form-group">
                <label for="designation">Designation Name:</label>
                <input type="text" id="designation" name="designation" required>
            </div>
            <button type="submit" class="btn">Add Designation</button>
        </form>
    </div>

    <?php
    // Database connection
    include('connection.php');

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $designation = $_POST['designation'];

        $query = "INSERT INTO designation (designation) VALUES ('$designation')";
        if (mysqli_query($connection, $query)) {
            echo "<script>alert('Designation added successfully!'); window.location.href='designation_display.php';</script>";
        } else {
            echo "<script>alert('Error adding designation.');</script>";
        }
    }
    ?>
</body>
</html>
