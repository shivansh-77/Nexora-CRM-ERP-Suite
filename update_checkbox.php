<?php
// Assuming you have already established a database connection

// Get the JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['field']) && isset($data['value']) && isset($data['id'])) {
    $field = $conn->real_escape_string($data['field']); // Field name (e.g., lot_tracking)
    $value = (int)$data['value']; // Value (0 or 1)
    $id = (int)$data['id']; // Record ID

    // Update the database
    $sql = "UPDATE item SET $field = $value WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "message" => "Checkbox updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update checkbox"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
}
?>
