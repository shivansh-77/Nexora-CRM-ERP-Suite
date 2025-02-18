<?php
// Database connection
$servername = "localhost"; // Change if necessary
$username = "root"; // Change to your database username
$password = ""; // Change to your database password
$dbname = "Lead_"; // Change to your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the query from the request
$query = isset($_GET['query']) ? $_GET['query'] : '';

// Prepare and execute SQL statement
$sql = "SELECT contact_person, address, mobile_no, email_id, gstin FROM contact WHERE contact_person LIKE ?";
$stmt = $conn->prepare($sql);
$search = "%" . $query . "%";
$stmt->bind_param('s', $search);
$stmt->execute();
$result = $stmt->get_result();

// Fetch results
$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

// Return results as JSON
header('Content-Type: application/json');
echo json_encode($customers);

$stmt->close();
$conn->close();
?>
