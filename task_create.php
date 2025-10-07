<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Task</title>
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

        .form-group input, .form-group select {
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
        <button class="close-btn" onclick="window.location.href='task_display.php';">✖</button>
        <div class="card-header">Add Task</div>
        <form method="POST" action="">
            <div class="form-group">
                <label for="task_name">Task Name:</label>
                <input type="text" id="task_name" name="task_name" required>
            </div>
            <div class="form-group">
                <label for="task_group_id">Task Group:</label>
                <select id="task_group_id" name="task_group_id" required>
                    <option value="">Select Task Group</option>
                    <?php
                    // Database connection
                    include('connection.php');

                    // Fetch task groups for dropdown
                    $group_query = "SELECT * FROM task_group ORDER BY group_name";
                    $group_result = mysqli_query($connection, $group_query);

                    if (mysqli_num_rows($group_result) > 0) {
                        while ($group_row = mysqli_fetch_assoc($group_result)) {
                            echo "<option value='" . $group_row['id'] . "'>" . $group_row['group_name'] . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn">Add Task</button>
        </form>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $task_name = mysqli_real_escape_string($connection, $_POST['task_name']);
        $task_group_id = mysqli_real_escape_string($connection, $_POST['task_group_id']);

        $query = "INSERT INTO tasks (task_name, task_group_id) VALUES ('$task_name', '$task_group_id')";
        if (mysqli_query($connection, $query)) {
            echo "<script>alert('Task added successfully!'); window.location.href='task_display.php';</script>";
        } else {
            echo "<script>alert('Error adding task.');</script>";
        }
    }
    ?>
</body>
</html>
