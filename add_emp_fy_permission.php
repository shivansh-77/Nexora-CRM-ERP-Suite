<?php
// Include your database connection
include('connection.php');
$fy_code = 'YOUR_FY_CODE_HERE'; // Ensure this variable is defined

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the selected employee ID and fiscal year code
    $emp_id = mysqli_real_escape_string($connection, $_POST['emp_id']);
    $fy_code = mysqli_real_escape_string($connection, $_POST['fy_code']);

    // Fetch employee name from login_db table
    $query = "SELECT name FROM login_db WHERE id = '$emp_id'";
    $result = mysqli_query($connection, $query);
    $emp_name = '';

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $emp_name = $row['name'];
    }

    // Check if the employee ID already exists in emp_fy_permission
    $check_query = "SELECT * FROM emp_fy_permission WHERE emp_id = '$emp_id' AND fy_code = '$fy_code'";
    $check_result = mysqli_query($connection, $check_query);

    if (mysqli_num_rows($check_result) == 0) {
        // Insert new entry into emp_fy_permission
        $insert_query = "INSERT INTO emp_fy_permission (emp_id, emp_name, fy_code, permission) VALUES ('$emp_id', '$emp_name', '$fy_code', 1)";

        if (mysqli_query($connection, $insert_query)) {
            echo "<script>alert('Employee added successfully!');</script>";
        } else {
            echo "<script>alert('Error adding employee: " . mysqli_error($connection) . "');</script>";
        }
    } else {
        echo "<script>alert('This employee already has permission for the selected FY.');</script>";
    }
}

// Fetch all employee IDs and names from the login_db table for the select dropdown
$query = "SELECT id, name FROM login_db";
$result = mysqli_query($connection, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
<link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee FY Permission</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Full viewport height */
            background-color: #2c3e50; /* Light background color */
        }
        .card {
            width: 500px; /* Set card width */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Soft shadow */
        }
        .card-header {
            background-color: #2c3e50; /* Bootstrap primary color */
            color: white; /* White text color */

        }
        .btn-primary {
            width: 100%; /* Full width buttons */
            background-color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="card">
      <div class="card-header text-center" style="position: relative;">
    <h2 style="">Add Employee to FY Permission</h2>
    <button
    style="position: absolute; right: 25px; top: 5px; border: none; background: none; font-size: 20px; cursor: pointer; color: white;"
    onclick="window.location.href='financial_years_display.php';">
    &times;
</button>


</div>

        <div class="card-body">
            <form method="POST" action="add_emp_fy_permission.php">
                <div class="form-group">
                    <label for="emp_id">Select Employee</label>
                    <select class="form-control" id="emp_id" name="emp_id" required>
                        <option value="" disabled selected>Select an employee</option>
                        <?php
                        // Include your database connection
                        include('connection.php');

                        // Populate the dropdown with employee IDs and names
                        $query = "SELECT id, name FROM login_db";
                        $result = mysqli_query($connection, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<option value='" . $row['id'] . "'>" . $row['name'] . " (ID: " . $row['id'] . ")</option>";
                        }
                        ?>
                    </select>
                </div>
                <input type="hidden" name="fy_code" value="<?php echo isset($_GET['fy_code']) ? $_GET['fy_code'] : 1; ?>">
                <button type="submit" class="btn btn-primary">Add Employee</button>

            </form>
        </div>
    </div>
</body>
</html>
