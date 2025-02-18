<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "lead_management") or die("Connection failed");

// Delete lead
$id = $_GET['id'];
$delete_query = "DELETE FROM lead_sourc WHERE id = $id";
mysqli_query($conn, $delete_query) or die("Error deleting lead: " . mysqli_error($conn));

// Reset AUTO_INCREMENT
$sql_check = "SELECT MAX(id) AS max_id FROM lead_sourc";
$result_check = mysqli_query($conn, $sql_check);
$row = mysqli_fetch_assoc($result_check);

if ($row['max_id']) {
    $next_id = $row['max_id'] + 1;
    $sql_reset = "ALTER TABLE lead_sourc AUTO_INCREMENT = {$next_id}";
} else {
    $sql_reset = "ALTER TABLE lead_sourc AUTO_INCREMENT = 1";
}
mysqli_query($conn, $sql_reset) or die("Error resetting AUTO_INCREMENT: " . mysqli_error($conn));

echo "Lead deleted successfully and AUTO_INCREMENT reset.";
mysqli_close($conn);
header("Location: http://localhost/Log/Source_Lead.php");
header("Location: Source_Lead.php?message=Lead deleted successfully");

?>
