<?php
// Database connection
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startYear = $_POST['start_year'];
    $endYear = $_POST['end_year'];
    $fycode  = $_POST['fy_code'];

    // Validate input
    if (ctype_digit($startYear) && ctype_digit($endYear) && strlen($startYear) === 4 && strlen($endYear) === 4 && $endYear == $startYear + 1) {
        // Calculate start and end dates
        $startDate = "$startYear-04-01"; // 1st April of the start year
        $endDate = "$endYear-03-31";    // 31st March of the end year

        // Update the existing entry with is_current set to 1 to 0
        $updateStmt = $connection->prepare("UPDATE financial_years SET is_current = 0 WHERE is_current = 1");
        if ($updateStmt->execute()) {
            // Insert into financial_years table with is_current set to 1 by default
            $stmt = $connection->prepare("INSERT INTO financial_years (start_date, end_date, fy_code, is_current) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("sss", $startDate, $endDate, $fycode);

            if ($stmt->execute()) {
                // Fetch all users from login_db
                $userQuery = $connection->query("SELECT id, name FROM login_db");

                // Check if users exist
                if ($userQuery->num_rows > 0) {
                    // Prepare statement for emp_fy_permission table
                    $permissionStmt = $connection->prepare("INSERT INTO emp_fy_permission (emp_id, emp_name, fy_code, permission) VALUES (?, ?, ?, 'Not Allowed')");

                    while ($row = $userQuery->fetch_assoc()) {
                        $empId = $row['id'];
                        $empName = $row['name'];

                        // Bind and execute for each user
                        $permissionStmt->bind_param("iss", $empId, $empName, $fycode);
                        $permissionStmt->execute();
                    }

                    $permissionStmt->close();
                }

                echo "<script>alert('New Financial Year Added Successfully'); window.location.href='financial_years_display.php';</script>";
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Error: " . $updateStmt->error;
        }

        $updateStmt->close();
    } else {
        echo "<script>alert('Error: The Financial years must be consecutive for example 2024 - 2025');</script>";
    }
}

$connection->close();
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Financial Year</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 400px;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        .card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .card form {
            display: flex;
            flex-direction: column;
        }
        .card label {
            text-align: left;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .card input {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .card button {
            background-color: #2c3e50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .card button:hover {
            background-color: #34495e;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            text-decoration: none;
            font-size: 20px;
            font-weight: bold;
            color: #333;
            cursor: pointer;
        }
        .close-btn:hover {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="card">
        <a href="financial_years_display.php" class="close-btn">&times;</a>
        <h2>Enter Financial Year</h2>
        <form action="" method="POST">
            <label for="start_year">Start Year</label>
            <input type="text" name="start_year" id="start_year" placeholder="Start Year(YYYY)" required>

            <label for="end_year">End Year</label>
            <input type="text" name="end_year" id="end_year" placeholder="End Year(YYYY)" required>

            <label for="fy_code">FY Code</label>
            <input type="text" name="fy_code" id="fy_code" placeholder="FY_Code" required>

            <button type="submit">Add Financial Year</button>
        </form>
    </div>
</body>
</html>
