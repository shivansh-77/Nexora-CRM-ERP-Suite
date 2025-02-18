<?php
// Enable error reporting for debugging (optional in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Include your database connection
include 'connection.php'; // Ensure the correct path

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize input to ensure it's an integer

    if (!empty($id)) {
        // Start transaction
        $connection->begin_transaction();

        try {
            // Delete from invoice_items first
            $delete_items_query = "DELETE FROM invoice_items WHERE invoice_id = ?";
            $stmt_items = $connection->prepare($delete_items_query);
            $stmt_items->bind_param("i", $id);
            $stmt_items->execute();
            $stmt_items->close(); // Close statement after execution

            // Delete from invoices
            $delete_quotation_query = "DELETE FROM invoices WHERE id = ?";
            $stmt_quotation = $connection->prepare($delete_quotation_query);
            $stmt_quotation->bind_param("i", $id);
            $stmt_quotation->execute();
            $stmt_quotation->close();

            // Commit transaction
            $connection->commit();

            // Redirect with success message
            echo "<script>alert('Invoice deleted successfully.'); window.location.href='invoice_display.php';</script>";
        } catch (Exception $e) {
            // Rollback transaction on error
            $connection->rollback();
            echo "<script>alert('Error deleting invoice: " . addslashes($e->getMessage()) . "');</script>";
        }
    } else {
        echo "<script>alert('Error: Invoice ID is required.');</script>";
    }
} else {
    echo "<script>alert('Error: Invalid request method.');</script>";
}

// Close the database connection
$connection->close();
?>
