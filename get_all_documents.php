<?php
include('connection.php');

if ($_POST && isset($_POST['ledger_type'])) {
    $ledger_type = $_POST['ledger_type'];
    $documents = [];

    if ($ledger_type === 'Customer Ledger') {
        // Get all invoice numbers from invoices table
        $query = "SELECT DISTINCT invoice_no FROM invoices WHERE invoice_no IS NOT NULL AND invoice_no != '' ORDER BY invoice_no ASC";
    } else if ($ledger_type === 'Vendor Ledger') {
        // Get all invoice numbers from purchase_invoice table
        $query = "SELECT DISTINCT invoice_no FROM purchase_invoice WHERE invoice_no IS NOT NULL AND invoice_no != '' ORDER BY invoice_no ASC";
    }

    if (isset($query)) {
        $result = mysqli_query($connection, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $documents[] = [
                    'id' => $row['invoice_no'],
                    'name' => $row['invoice_no']
                ];
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($documents);
}
?>
