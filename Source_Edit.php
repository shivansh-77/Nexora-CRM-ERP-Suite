<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "lead_management");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle POST request for updating the lead
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $status = $_POST['status'];

    // Update query
    $query = "UPDATE lead_sourc SET name=?, status=? WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $name, $status, $id);

    if ($stmt->execute()) {
        header("Location: lead_for.php?message=Lead Updated Successfully");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    // Handle GET request to fetch the lead details
    $id = $_GET['id'] ?? 0;
    $query = "SELECT * FROM lead_sourc WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        echo "Lead not found.";
        exit;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lead</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h2 {
            margin-bottom: 20px;
        }
        form {
            max-width: 400px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
        }
        label {
            margin: 10px 0 5px;
            font-weight: bold;
        }
        input, select, button {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h2>Edit Lead</h2>
    <form action="Source_Edit.php" method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">

        <label for="name">Name:</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>

        <label for="status">Status:</label>
        <select name="status" id="status" required>
            <option value="Active" <?php echo $row['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
            <option value="Inactive" <?php echo $row['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>

        <button type="submit">Update</button>
    </form>
</body>
</html>
