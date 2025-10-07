<?php
include('connection.php');

if ($_POST['table'] && $_POST['query'] && $_POST['party_field'] && $_POST['party_id']) {
    $table = mysqli_real_escape_string($connection, $_POST['table']);
    $query = mysqli_real_escape_string($connection, $_POST['query']);
    $partyField = mysqli_real_escape_string($connection, $_POST['party_field']);
    $partyId = mysqli_real_escape_string($connection, $_POST['party_id']);

    $sql = "SELECT invoice_no FROM $table WHERE invoice_no LIKE '%$query%' AND $partyField = '$partyId' LIMIT 10";
    $result = mysqli_query($connection, $sql);

    $invoices = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $invoices[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($invoices);
}
?>
