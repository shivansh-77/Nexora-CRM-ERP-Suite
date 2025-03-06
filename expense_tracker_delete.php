<?php
session_start();
include('connection.php');

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if ID is passed via GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request.");
}

$id = intval($_GET['id']);

// Fetch the existing record
$query = "SELECT * FROM expense_tracker WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    die("Record not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['expense']) || !isset($_POST['description'])) {
        die("Invalid input data.");
    }

    $expense = trim($_POST['expense']);
    $description = trim($_POST['description']);

    // Update the record
    $updateQuery = "UPDATE expense_tracker SET expense = ?, description = ? WHERE id = ?";
    $stmt = $connection->prepare($updateQuery);
    $stmt->bind_param("ssi", $expense, $description, $id);

    if ($stmt->execute()) {
        echo "Expense record updated successfully.";
    } else {
        echo "Error updating expense record: " . $stmt->error;
    }

    $stmt->close();
}

mysqli_close($connection);
?>
