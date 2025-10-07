<?php
// invoice_lookup.php
header('Content-Type: application/json');
require 'connection.php';

// q = user's search text, by = 'invoice' | 'receiver'
$q  = trim($_GET['q']  ?? '');
$by = trim($_GET['by'] ?? 'invoice'); // default invoice

if ($q === '' || !in_array($by, ['invoice','receiver'], true)) {
    echo json_encode(['ok'=>false,'message'=>'Bad request','data'=>[]]); exit;
}

// Build WHERE and bind
if ($by === 'invoice') {
    $sql = "SELECT
              invoice_no,
              client_company_name,
              DATE_FORMAT(invoice_date, '%Y-%m-%d') AS invoice_date,
              client_gstno,
              net_amount,
              client_state,
              COALESCE(rate, gst_rate, tax_rate, 18) AS rate
            FROM invoices
            WHERE status = 'Finalized'
              AND invoice_no LIKE CONCAT('%', ?, '%')
            ORDER BY invoice_date DESC
            LIMIT 20";
} else {
    $sql = "SELECT
              invoice_no,
              client_company_name,
              DATE_FORMAT(invoice_date, '%Y-%m-%d') AS invoice_date,
              client_gstno,
              net_amount,
              client_state,
              COALESCE(rate, gst_rate, tax_rate, 18) AS rate
            FROM invoices
            WHERE status = 'Finalized'
              AND client_company_name LIKE CONCAT('%', ?, '%')
            ORDER BY invoice_date DESC
            LIMIT 20";
}

$stmt = $connection->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok'=>false,'message'=>'Prepare failed','data'=>[]]); exit;
}
$stmt->bind_param('s', $q);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;

echo json_encode(['ok'=>true,'data'=>$out]);
