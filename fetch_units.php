<?php
// fetch_units.php
include 'connection.php'; // Include your database connection

if (isset($_POST['item_code'])) {
    $item_code = $_POST['item_code'];

    // Query to fetch unit names and values based on item_code
    $query = "SELECT unit_name, value FROM item_add WHERE item_code = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $item_code);
    $stmt->execute();
    $result = $stmt->get_result();

    $units = [];
    while ($row = $result->fetch_assoc()) {
        $units[] = [
            'unit_name' => $row['unit_name'],
            'value' => $row['value']
        ];
    }

    // Return the unit names and values as a JSON response
    echo json_encode($units);
}
?>
