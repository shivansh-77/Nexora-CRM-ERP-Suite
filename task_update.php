<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Task</title>
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
    <?php
    // Database connection
    include('connection.php');

    // Get task details for update
    $id = $_GET['id'] ?? null;
    $task_name = '';
    $task_group_id = '';

    if ($id) {
        $query = "SELECT * FROM tasks WHERE id = $id";
        $result = mysqli_query($connection, $query);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $task_name = $row['task_name'];
            $task_group_id = $row['task_group_id'];
        }
    }
    ?>

    <div class="card">
        <button class="close-btn" onclick="window.location.href='task_display.php';">✖</button>
        <div class="card-header">Update Task</div>
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <div class="form-group">
                <label for="task_name">Task Name:</label>
                <input type="text" id="task_name" name="task_name" value="<?php echo $task_name; ?>" required>
            </div>
            <div class="form-group">
                <label for="task_group_id">Task Group:</label>
                <select id="task_group_id" name="task_group_id" required>
                    <option value="">Select Task Group</option>
                    <?php
                    // Fetch task groups for dropdown
                    $group_query = "SELECT * FROM task_group ORDER BY group_name";
                    $group_result = mysqli_query($connection, $group_query);

                    if (mysqli_num_rows($group_result) > 0) {
                        while ($group_row = mysqli_fetch_assoc($group_result)) {
                            $selected = ($group_row['id'] == $task_group_id) ? 'selected' : '';
                            echo "<option value='" . $group_row['id'] . "' $selected>" . $group_row['group_name'] . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn">Update Task</button>
        </form>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = mysqli_real_escape_string($connection, $_POST['id']);
        $task_name = mysqli_real_escape_string($connection, $_POST['task_name']);
        $task_group_id = mysqli_real_escape_string($connection, $_POST['task_group_id']);

        $query = "UPDATE tasks SET task_name = '$task_name', task_group_id = '$task_group_id' WHERE id = $id";
        if (mysqli_query($connection, $query)) {
            echo "<script>alert('Task updated successfully!'); window.location.href='task_display.php';</script>";
        } else {
            echo "<script>alert('Error updating task.');</script>";
        }
    }
    ?>
</body>
</html>
