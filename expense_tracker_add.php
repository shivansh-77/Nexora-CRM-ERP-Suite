<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense</title>
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
        <button class="close-btn" onclick="window.location.href='expense_display.php';">✖</button>
        <div class="card-header">Add Expense</div>
        <form method="POST" action="">
            <div class="form-group">
                <label for="expense">Expense:</label>
                <input type="text" id="expense" name="expense" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn">Add Expense</button>
        </form>
    </div>

    <?php
    // Database connection
    include('connection.php');

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $expense = $_POST['expense'];
        $description = $_POST['description'];

        // Use prepared statements to prevent SQL injection
        $query = "INSERT INTO expense_tracker (expense, description) VALUES (?, ?)";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("ss", $expense, $description);

        if ($stmt->execute()) {
            echo "<script>alert('Expense record added successfully!'); window.location.href='expense_display.php';</script>";
        } else {
            echo "<script>alert('Error adding expense record.');</script>";
        }

        $stmt->close();
    }
    ?>
</body>
</html>
