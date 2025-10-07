<?php
session_start();

// ✅ Add access control
if (!isset($_SESSION['user_id']) || ($_SESSION['user_id'] != 1 && $_SESSION['user_role'] !== 'Admin')) {
    die("❌ Unauthorized access.");

}

$hostname = "localhost";
$username = "root";
$password = "";
$dbname   = "u766296854_crm";

$date = date("Y-m-d_H-i-s");
$filename = "backup_{$dbname}_{$date}.sql";

// ✅ Set headers to trigger download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// ✅ Use passthru to directly stream mysqldump to browser
$command = "C:\\xampp\\mysql\\bin\\mysqldump -u {$username} " .
           ($password !== "" ? "-p{$password} " : "") .
           "{$dbname}";

passthru($command); // directly outputs the result to the browser
exit;
?>
