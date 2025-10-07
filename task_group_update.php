<?php
include('connection.php');

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid Task Group ID'); window.location.href='task_group_display.php';</script>";
    exit();
}

$id = intval($_GET['id']);

// Fetch existing data
$query = "SELECT * FROM task_group WHERE id = $id";
$result = mysqli_query($connection, $query);

if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Task Group not found'); window.location.href='task_group_display.php';</script>";
    exit();
}

$row = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_name = mysqli_real_escape_string($connection, $_POST['group_name']);

    $update_query = "UPDATE task_group SET group_name='$group_name' WHERE id=$id";
    if (mysqli_query($connection, $update_query)) {
        echo "<script>alert('Task Group updated successfully!'); window.location.href='task_group_display.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error updating task group.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Task Group</title>
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
        <button class="close-btn" onclick="window.location.href='task_group_display.php';">✖</button>
        <div class="card-header">Update Task Group</div>
        <form method="POST" action="">
            <div class="form-group">
                <label for="group_name">Task Group Name:</label>
                <input type="text" id="group_name" name="group_name" value="<?php echo htmlspecialchars($row['group_name']); ?>" required>
            </div>
            <button type="submit" class="btn">Update Task Group</button>
        </form>
    </div>
</body>
</html>
