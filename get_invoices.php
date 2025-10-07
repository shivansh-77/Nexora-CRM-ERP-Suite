<?php
session_start();
include('connection.php');

header('Content-Type: application/json');

if ($_POST && isset($_POST['ledger_type']) && isset($_POST['party_no'])) {
    $ledger_type = $_POST['ledger_type'];
    $party_no = intval($_POST['party_no']);
    $invoices = [];

    try {
        if ($ledger_type == 'Customer Ledger') {
            // Fetch from invoices table using client_id with pending amounts > 0
            $query = "SELECT DISTINCT invoice_no, pending_amount, reference_invoice_no FROM invoices
                     WHERE client_id = ? AND invoice_no IS NOT NULL AND invoice_no != ''
                     AND pending_amount > 0
                     ORDER BY invoice_no DESC";
        } else if ($ledger_type == 'Vendor Ledger') {
            // Fetch from purchase_invoice table using vendor_id with pending amounts > 0
            $query = "SELECT DISTINCT invoice_no, pending_amount, reference_invoice_no FROM purchase_invoice
                     WHERE vendor_id = ? AND invoice_no IS NOT NULL AND invoice_no != ''
                     AND pending_amount > 0
                     ORDER BY invoice_no DESC";
        }

        if (isset($query)) {
            $stmt = mysqli_prepare($connection, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $party_no);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $invoices[] = [
                            'invoice_no' => $row['invoice_no'],
                            'pending_amount' => floatval($row['pending_amount']),
                            'reference_invoice_no' => $row['reference_invoice_no'] ?? null
                        ];
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }

        echo json_encode($invoices);

    } catch (Exception $e) {
        error_log("Error in get_invoices.php: " . $e->getMessage());
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>
