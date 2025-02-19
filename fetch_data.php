<?php
session_start();
include('connection.php');
include('topbar.php');

// Function to fetch data for all categories
function fetchData($connection) {
    $queries = [
        'contacts' => [
            'total' => "SELECT COUNT(*) AS total FROM contact",
            'yearly' => "SELECT COUNT(*) AS yearly FROM contact WHERE YEAR(created_at) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM contact WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(created_at) AS month, COUNT(*) AS count FROM contact WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY MONTH(created_at)",
            'monthly_data' => "SELECT DAY(created_at) AS day, COUNT(*) AS count FROM contact WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) GROUP BY DAY(created_at)"
        ],
        'followups' => [
            'total' => "SELECT COUNT(*) AS total FROM followup WHERE lead_status = 'Open'",
            'yearly' => "SELECT COUNT(*) AS yearly FROM followup WHERE lead_status = 'Open' AND YEAR(followup_date_nxt) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM followup WHERE lead_status = 'Open' AND YEAR(followup_date_nxt) = YEAR(CURDATE()) AND MONTH(followup_date_nxt) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(followup_date_nxt) AS month, COUNT(*) AS count FROM followup WHERE lead_status = 'Open' AND YEAR(followup_date_nxt) = YEAR(CURDATE()) GROUP BY MONTH(followup_date_nxt)",
            'monthly_data' => "SELECT DAY(followup_date_nxt) AS day, COUNT(*) AS count FROM followup WHERE lead_status = 'Open' AND YEAR(followup_date_nxt) = YEAR(CURDATE()) AND MONTH(followup_date_nxt) = MONTH(CURDATE()) GROUP BY DAY(followup_date_nxt)"
        ],
        'sales' => [
            'total' => "SELECT SUM(net_amount) AS total FROM invoices WHERE status = 'Finalized'",
            'yearly' => "SELECT SUM(net_amount) AS yearly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE())",
            'monthly' => "SELECT SUM(net_amount) AS monthly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(invoice_date) AS month, SUM(net_amount) AS total FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) GROUP BY MONTH(invoice_date)",
            'monthly_data' => "SELECT DAY(invoice_date) AS day, SUM(net_amount) AS total FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE()) GROUP BY DAY(invoice_date)"
        ],
        'invoices' => [
            'total' => "SELECT COUNT(*) AS total FROM invoices WHERE status = 'Finalized'",
            'yearly' => "SELECT COUNT(*) AS yearly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(invoice_date) AS month, COUNT(*) AS count FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) GROUP BY MONTH(invoice_date)",
            'monthly_data' => "SELECT DAY(invoice_date) AS day, COUNT(*) AS count FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE()) GROUP BY DAY(invoice_date)"
        ],
        'quotations' => [
            'total' => "SELECT COUNT(*) AS total FROM quotations",
            'yearly' => "SELECT COUNT(*) AS yearly FROM quotations WHERE YEAR(quotation_date) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM quotations WHERE YEAR(quotation_date) = YEAR(CURDATE()) AND MONTH(quotation_date) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(quotation_date) AS month, COUNT(*) AS count FROM quotations WHERE YEAR(quotation_date) = YEAR(CURDATE()) GROUP BY MONTH(quotation_date)",
            'monthly_data' => "SELECT DAY(quotation_date) AS day, COUNT(*) AS count FROM quotations WHERE YEAR(quotation_date) = YEAR(CURDATE()) AND MONTH(quotation_date) = MONTH(CURDATE()) GROUP BY DAY(quotation_date)"
        ],
        'transactions' => [
            'total' => "SELECT COUNT(*) AS total FROM party_ledger",
            'yearly' => "SELECT COUNT(*) AS yearly FROM party_ledger WHERE YEAR(date) = YEAR(CURDATE())",
            'monthly' => "SELECT COUNT(*) AS monthly FROM party_ledger WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(date) AS month, COUNT(*) AS count FROM party_ledger WHERE YEAR(date) = YEAR(CURDATE()) GROUP BY MONTH(date)",
            'monthly_data' => "SELECT DAY(date) AS day, COUNT(*) AS count FROM party_ledger WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) GROUP BY DAY(date)"
        ],
        'today_followups' => "SELECT COUNT(*) AS count FROM followup WHERE lead_status = 'Open' AND followup_date_nxt = CURDATE()",
        'amc_dues_today' => "SELECT COUNT(*) AS count FROM invoice_items WHERE amc_due_date = CURDATE()",
        'sales_today' => "SELECT SUM(net_amount) AS sales_today FROM invoices WHERE status = 'Finalized' AND DATE(invoice_date) = CURDATE()",
        'pending_amount' => "SELECT SUM(net_amount) AS pending_amount FROM invoices WHERE status = 'Finalized'"
    ];

    $data = [];
    foreach ($queries as $category => $query) {
        if (is_array($query)) {
            foreach ($query as $type => $sql) {
                $result = mysqli_query($connection, $sql);
                if ($type === 'yearly_data' || $type === 'monthly_data') {
                    $data[$category][$type] = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        $data[$category][$type][$row['month'] ?? $row['day']] = $row['count'] ?? $row['total'];
                    }
                } else {
                    $data[$category][$type] = mysqli_fetch_assoc($result)[$type] ?? 0;
                }
            }
        } else {
            $result = mysqli_query($connection, $query);
            $row = mysqli_fetch_assoc($result);
            if ($category === 'pending_amount') {
                $data[$category] = $row['pending_amount'] ?? 0;
            } elseif ($category === 'sales_today') {
                $data[$category] = $row['sales_today'] ?? 0;
            } else {
                $data[$category] = $row['count'] ?? 0;
            }
        }
    }

    return $data;
}

$data = fetchData($connection);

// You can now use $data to access all the fetched information
?>
