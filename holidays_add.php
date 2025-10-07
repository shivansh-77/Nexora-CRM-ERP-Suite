<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Holiday</title>
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

        .form-group .readonly-input {
            background-color: #f2f2f2;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="card">
        <button class="close-btn" onclick="window.location.href='holidays_display.php';">✖</button>
        <div class="card-header">Add Holiday</div>
        <form method="POST" action="">
            <div class="form-group">
                <label for="holiday_name">Holiday Name:</label>
                <input type="text" id="holiday_name" name="holiday_name" required>
            </div>
            <div class="form-group">
                <label for="start_date">Holiday Date:</label>
                <input type="date" id="start_date" name="start_date" required onchange="updateEndDate()">
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" class="readonly-input" readonly>
            </div>
            <button type="submit" class="btn">Add Holiday</button>
        </form>
    </div>

    <script>
        function updateEndDate() {
            const startDate = document.getElementById('start_date').value;
            document.getElementById('end_date').value = startDate;
        }

        // Initialize end date when page loads
        window.onload = function() {
            updateEndDate();
        };
    </script>

    <?php
    // Database connection
    include('connection.php');

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $holiday_name = $_POST['holiday_name'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['start_date']; // Same as start_date
        $total_days = 1; // Always 1 day for holidays

        // Use prepared statements to prevent SQL injection
        $query = "INSERT INTO holidays (holiday_name, start_date, end_date, total_days) VALUES (?, ?, ?, ?)";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("sssi", $holiday_name, $start_date, $end_date, $total_days);

        if ($stmt->execute()) {
            echo "<script>alert('Holiday record added successfully!'); window.location.href='holidays_display.php';</script>";
        } else {
            echo "<script>alert('Error adding holiday record.');</script>";
        }

        $stmt->close();
    }
    ?>
</body>
</html>
