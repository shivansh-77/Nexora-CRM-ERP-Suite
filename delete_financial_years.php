<?php
// Database connection
include('connection.php');

// Check if the ID is provided
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $id = $_GET['id'];

    // Start a transaction to handle deletion
    $connection->begin_transaction();

    try {
        // Prepare and execute the delete query
        $stmt = $connection->prepare("DELETE FROM financial_years WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // Commit the transaction
            $connection->commit();

            // Redirect to the display page after successful deletion
            header("Location: financial_years_display.php?message=deleted");
            exit;
        } else {
            throw new Exception("Error deleting record: " . $stmt->error);
        }

        $stmt->close();
    } catch (Exception $e) {
        // Rollback the transaction in case of errors
        $connection->rollback();
        echo "<script>alert('" . $e->getMessage() . "'); window.location.href='financial_years_display.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.location.href='financial_years_display.php';</script>";
}

$connection->close();
?>
