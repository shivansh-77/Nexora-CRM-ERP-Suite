<?php
include('connection.php');

header('Content-Type: application/json');

if (!isset($_GET['query']) || strlen(trim($_GET['query'])) < 1) {
    echo json_encode([]);
    exit;
}

$query = trim($_GET['query']);
$type = $_GET['type'] ?? 'both'; // 'invoice_no', 'company', or 'both'

// Build the SQL query based on search type
$sql = "SELECT invoice_no, client_company_name, invoice_date, client_gstno, net_amount, client_state
        FROM invoices
        WHERE status = 'Finalized' AND (";

$params = [];
$types = "";

if ($type === 'invoice_no' || $type === 'both') {
    $sql .= "invoice_no LIKE ? OR ";
    $params[] = "%$query%";
    $types .= "s";
}

if ($type === 'company' || $type === 'both') {
    $sql .= "client_company_name LIKE ? OR ";
    $params[] = "%$query%";
    $types .= "s";
}

// Remove the last " OR "
$sql = rtrim($sql, " OR ");
$sql .= ") ORDER BY invoice_date DESC LIMIT 10";

$stmt = $connection->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        $invoices[] = [
            'invoice_no' => $row['invoice_no'],
            'client_company_name' => $row['client_company_name'],
            'invoice_date' => $row['invoice_date'],
            'client_gstno' => $row['client_gstno'],
            'net_amount' => $row['net_amount'],
            'client_state' => $row['client_state']
        ];
    }

    echo json_encode($invoices);
    $stmt->close();
} else {
    echo json_encode([]);
}

$connection->close();
?>
