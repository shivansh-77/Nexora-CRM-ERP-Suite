<?php
session_start();
include('connection.php');
include('topbar.php');

// Function to fetch data for all categories
function fetchData($connection) {
    $queries = [
        'contacts' => [
            'total' => "SELECT COUNT(*) AS total FROM contact",
            'yearly' => "SELECT COUNT(*) AS yearly FROM contact WHERE YEAR(followupdate) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM contact WHERE YEAR(followupdate) = YEAR(CURDATE()) AND MONTH(followupdate) = MONTH(CURDATE())"
        ],
        'followups' => [
            'total' => "SELECT COUNT(*) AS total FROM followup WHERE lead_status = 'Open'",
            'yearly' => "SELECT COUNT(*) AS yearly FROM followup WHERE lead_status = 'Open' AND YEAR(followup_date_nxt) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM followup WHERE lead_status = 'Open' AND YEAR(followup_date_nxt) = YEAR(CURDATE()) AND MONTH(followup_date_nxt) = MONTH(CURDATE())"
        ],
        'sales' => [
            'total' => "SELECT SUM(net_amount) AS total FROM invoices WHERE status = 'Finalized'",
            'yearly' => "SELECT SUM(net_amount) AS yearly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE())",
            'monthly' => "SELECT SUM(net_amount) AS monthly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())"
        ],
        'today_followups' => "SELECT COUNT(*) AS count FROM followup WHERE lead_status = 'Open' AND followup_date_nxt = CURDATE()",
        'amc_dues_today' => "SELECT COUNT(*) AS count FROM invoice_items WHERE amc_due_date = CURDATE()",
        'pending_amount' => "SELECT SUM(amount) AS pending FROM party_ledger", // Updated query
        'invoices' => [
            'total' => "SELECT COUNT(*) AS total FROM invoices WHERE status = 'Finalized'",
            'yearly' => "SELECT COUNT(*) AS yearly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())"
        ],
        'quotations' => [
            'total' => "SELECT COUNT(*) AS total FROM quotations",
            'yearly' => "SELECT COUNT(*) AS yearly FROM quotations WHERE YEAR(quotation_date) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM quotations WHERE YEAR(quotation_date) = YEAR(CURDATE()) AND MONTH(quotation_date) = MONTH(CURDATE())"
        ],
        'transactions' => [
            'total' => "SELECT COUNT(*) AS total FROM party_ledger",
            'yearly' => "SELECT COUNT(*) AS yearly FROM party_ledger WHERE YEAR(date) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM party_ledger WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())"
        ]
    ];

    $data = [];
    foreach ($queries as $category => $query) {
        if (is_array($query)) {
            foreach ($query as $type => $sql) {
                $result = mysqli_query($connection, $sql);
                $data[$category][$type] = mysqli_fetch_assoc($result)[$type] ?? 0;
            }
        } else {
            $result = mysqli_query($connection, $query);
            $data[$category] = mysqli_fetch_assoc($result)['count'] ?? mysqli_fetch_assoc($result)['pending'] ?? 0;
        }
    }

    return $data;
}

$data = fetchData($connection);
echo json_encode($data);
?>
