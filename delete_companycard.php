<?php
include('connection.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if ID is provided in the URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize the ID

    // Prepare the SQL query to delete the company card entry
    $sql = "DELETE FROM company_card WHERE id = ?";
    $stmt = $connection->prepare($sql);

    if (!$stmt) {
        die("Preparation failed: " . $connection->error);
    }

    // Bind the ID parameter
    $stmt->bind_param("i", $id);

    // Execute the delete query
    if ($stmt->execute()) {
        // Reorder IDs after deletion (not recommended in most cases)
        $connection->query("SET @count = 0");
        $connection->query("UPDATE company_card SET id = @count := @count + 1");
        $connection->query("ALTER TABLE company_card AUTO_INCREMENT = 1"); // Corrected line

        // Redirect to company card page with a success message
        echo "<script>alert('Company card deleted successfully.'); window.location.href='companycard.php';</script>";
    } else {
        echo "Error deleting company card entry: " . $stmt->error;
    }
} else {
    die("ID not provided.");
}

// Close the prepared statement
$stmt->close();

// Close the connection
$connection->close();
?>
