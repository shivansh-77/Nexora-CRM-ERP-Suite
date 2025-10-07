<?php
session_start();
include('connection.php');
header('Content-Type: application/json');
if ($_POST && isset($_POST['ledger_type'])) {
    $ledger_type = $_POST['ledger_type'];
    $parties = [];
    if ($ledger_type == 'Customer Ledger') {
        // Fetch from contact table
        $query = "SELECT id, contact_person as name, company_name FROM contact WHERE contact_person IS NOT NULL AND contact_person != '' ORDER BY contact_person";
    } else if ($ledger_type == 'Vendor Ledger') {
        // Fetch from contact_vendor table
        $query = "SELECT id, contact_person as name, company_name FROM contact_vendor WHERE contact_person IS NOT NULL AND contact_person != '' ORDER BY contact_person";
    }
    if (isset($query)) {
        $result = mysqli_query($connection, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $parties[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'company' => $row['company_name'] ?? ''
                ];
            }
        }
    }
    echo json_encode($parties);
} else {
    echo json_encode([]);
}
?>
