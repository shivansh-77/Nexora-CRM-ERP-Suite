<?php
include('connection.php');

// Fetch Company Email & Passkey from company_card table
$query = "SELECT company_email_id, company_passkey FROM company_card LIMIT 1";
$result = mysqli_query($connection, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_USER', $row['company_email_id']);  // Dynamic Email
    define('SMTP_PASS', $row['company_passkey']);   // Dynamic Passkey
    define('SMTP_SECURE', 'tls');
    define('SMTP_PORT', 587);
} else {
    die("Error: Company email configuration not found in database.");
}
?>
