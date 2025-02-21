<?php
session_start();
include('connection.php');

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve data from POST request
    $submenu = $_POST['submenu'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $menu = $_POST['menu'] ?? null;

    // Validate input
    if ($submenu && $user_id && $menu) {
        // Check if the permission exists
        $checkQuery = "SELECT 1 FROM user_menu_permission WHERE user_id = ? AND menu_name = ? AND submenu_name = ?";
        $checkStmt = $connection->prepare($checkQuery);
        $checkStmt->bind_param("iss", $user_id, $menu, $submenu);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // Permission exists, delete it
            $query = "DELETE FROM user_menu_permission WHERE user_id = ? AND menu_name = ? AND submenu_name = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("iss", $user_id, $menu, $submenu);
        } else {
            // Permission does not exist, insert it with the next available ID
            // Find the next available ID
            $idQuery = "SELECT MAX(id) AS max_id FROM user_menu_permission";
            $idStmt = $connection->prepare($idQuery);
            $idStmt->execute();
            $idResult = $idStmt->get_result();
            $nextId = $idResult->fetch_assoc()['max_id'] + 1;
            $idStmt->close();

            // Insert the new permission with the next available ID
            $query = "INSERT INTO user_menu_permission (id, user_id, menu_name, submenu_name, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("iiss", $nextId, $user_id, $menu, $submenu);
        }

        $checkStmt->close();

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            error_log('Error executing query: ' . $stmt->error);
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
