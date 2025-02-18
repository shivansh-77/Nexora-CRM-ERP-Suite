<?php
// Include your database connection
include 'connection.php'; // Replace with your actual database connection file

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    // Retrieve the ID from the GET request
    $id = intval($_GET['id']); // Sanitize input to ensure it's an integer

    // Check if the ID is provided
    if (!empty($id)) {
        // Begin a transaction to ensure both deletions occur together
        $connection->begin_transaction();

        try {
            // First, delete all entries in the quotation_items table that match the quotation_id
            $delete_items_query = "DELETE FROM quotation_items WHERE quotation_id = ?";
            $stmt_items = $connection->prepare($delete_items_query);
            $stmt_items->bind_param("i", $id); // Bind the quotation ID
            $stmt_items->execute();

            // Then, delete the entry in the quotations table
            $delete_quotation_query = "DELETE FROM quotations WHERE id = ?";
            $stmt_quotation = $connection->prepare($delete_quotation_query);
            $stmt_quotation->bind_param("i", $id);
            $stmt_quotation->execute();

            // Commit the transaction
            $connection->commit();

            // Redirect to a success page or display a success message
            echo "<script>alert('Quotation deleted successfully.'); window.location.href='quotation_display.php';</script>";
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            $connection->rollback();

            // Error message
            echo "Error: " . $e->getMessage();
        }

        // Close prepared statements
        $stmt_items->close();
        $stmt_quotation->close();
    } else {
        echo "Error: Quotation ID is required.";
    }
} else {
    echo "Error: Invalid request method.";
}

// Close the database connection
$connection->close();
?>
