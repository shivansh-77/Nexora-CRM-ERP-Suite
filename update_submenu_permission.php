<?php
session_start();
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submenu = $_POST['submenu'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $menu = $_POST['menu'] ?? null;
    $has_access = $_POST['has_access'] ?? null;

    if ($submenu && $user_id && $menu && isset($has_access)) {
        if ($has_access == 0) {
            // Delete the record when access is revoked
            $query = "DELETE FROM user_menu_permission WHERE user_id = ? AND menu_name = ? AND submenu_name = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("iss", $user_id, $menu, $submenu);
        } else {
            // Check if the permission exists
            $checkQuery = "SELECT 1 FROM user_menu_permission WHERE user_id = ? AND menu_name = ? AND submenu_name = ?";
            $checkStmt = $connection->prepare($checkQuery);
            $checkStmt->bind_param("iss", $user_id, $menu, $submenu);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                // Permission exists, update it
                $query = "UPDATE user_menu_permission SET has_access = ? WHERE user_id = ? AND menu_name = ? AND submenu_name = ?";
                $stmt = $connection->prepare($query);
                $stmt->bind_param("iiss", $has_access, $user_id, $menu, $submenu);
            } else {
                // Permission does not exist, insert it
                $query = "INSERT INTO user_menu_permission (user_id, menu_name, submenu_name, has_access, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $connection->prepare($query);
                $stmt->bind_param("issi", $user_id, $menu, $submenu, $has_access);
            }
            $checkStmt->close();
        }

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
