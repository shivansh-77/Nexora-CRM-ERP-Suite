<?php
// Database connection
include('connection.php');

// Check if the ID is provided
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $id = $_GET['id'];

    // Start a transaction to handle deletion and resetting
    $connection->begin_transaction();

    try {
        // Prepare and execute the delete query
        $stmt = $connection->prepare("DELETE FROM financial_years WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // Reset AUTO_INCREMENT to ensure continuous IDs
            $connection->query("SET @new_id = 0;");
            $connection->query("UPDATE financial_years SET id = (@new_id := @new_id + 1);");
            $connection->query("ALTER TABLE financial_years AUTO_INCREMENT = 1;");

            // Commit the transaction
            $connection->commit();

            // Redirect to the display page after successful deletion
            header("Location: financial_years_display.php?message=deleted");
            exit;
        } else {
            throw new Exception("Error: " . $stmt->error);
        }

        $stmt->close();
    } catch (Exception $e) {
        // Rollback the transaction in case of errors
        $connection->rollback();
        echo $e->getMessage();
    }
} else {
    echo "Invalid request.";
}

$connection->close();
?>
