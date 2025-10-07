<?php
session_start();
include("connection.php");

// Fetch company checkout time
$query = $connection->query("SELECT checkout_time FROM company_card WHERE id = 1");
if ($query && $query->num_rows > 0) {
    $row = $query->fetch_assoc();
    echo json_encode([
        "status" => "success",
        "checkout_time" => $row['checkout_time']
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Could not retrieve company checkout time"
    ]);
}
?>
