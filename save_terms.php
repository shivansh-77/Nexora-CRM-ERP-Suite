<?php
include 'connection.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Treat quotation_id as invoice_id
    $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
    $document_no = $_POST['document_no'] ?? '';
    $type = $_POST['type'] ?? '';
    $terms_and_conditions = $_POST['terms_and_conditions'] ?? '';
    // Store the referring page URL
    $referer = $_SERVER['HTTP_REFERER'];

    try {
        // Check if terms and conditions already exist for this invoice
        $check_query = "SELECT * FROM invoice_terms_conditions WHERE invoice_id = ? AND type = ?";
        $stmt_check = $connection->prepare($check_query);
        $stmt_check->bind_param("is", $invoice_id, $type);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Update existing terms and conditions
            $update_query = "UPDATE invoice_terms_conditions SET terms_and_conditions = ?, document_no = ? WHERE invoice_id = ? AND type = ?";
            $stmt_update = $connection->prepare($update_query);
            $stmt_update->bind_param("ssis", $terms_and_conditions, $document_no, $invoice_id, $type);
            $stmt_update->execute();
            $message = "Terms and conditions updated successfully!";
        } else {
            // Insert new terms and conditions
            $insert_query = "INSERT INTO invoice_terms_conditions (invoice_id, document_no, type, terms_and_conditions) VALUES (?, ?, ?, ?)";
            $stmt_insert = $connection->prepare($insert_query);
            $stmt_insert->bind_param("isss", $invoice_id, $document_no, $type, $terms_and_conditions);
            $stmt_insert->execute();
            $message = "Terms and conditions saved successfully!";
        }

        // Close statements
        if (isset($stmt_check)) $stmt_check->close();
        if (isset($stmt_update)) $stmt_update->close();
        if (isset($stmt_insert)) $stmt_insert->close();

        // JavaScript for alert and redirect
        echo "<script>
            alert('$message');
            window.location.href = '$referer';
        </script>";

    } catch (Exception $e) {
        // Error handling
        $error_message = "Error: " . $e->getMessage();
        echo "<script>
            alert('$error_message');
            window.location.href = '$referer';
        </script>";
    }

    $connection->close();
    exit(); // Ensure no further output is sent
} else {
    // JavaScript for invalid request method
    echo "<script>
        alert('Invalid request method.');
        window.history.back();
    </script>";
    exit();
}
?>
