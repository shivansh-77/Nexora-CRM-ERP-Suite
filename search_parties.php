<?php
include('connection.php');

if ($_POST['table'] && $_POST['query']) {
    $table = mysqli_real_escape_string($connection, $_POST['table']);
    $query = mysqli_real_escape_string($connection, $_POST['query']);

    $sql = "SELECT id, contact_person FROM $table WHERE contact_person LIKE '%$query%' LIMIT 10";
    $result = mysqli_query($connection, $sql);

    $parties = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $parties[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($parties);
}
?>
