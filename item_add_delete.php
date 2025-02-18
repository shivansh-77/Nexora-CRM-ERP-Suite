<?php
// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'lead_management';
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if an ID is passed in the URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Delete query
    $delete_query = "DELETE FROM item_add WHERE id = $id";

    if ($conn->query($delete_query) === TRUE) {
        echo "<script>
            alert('Record deleted successfully!');
            window.location.href = document.referrer; // Redirect to the previous page
        </script>";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>
