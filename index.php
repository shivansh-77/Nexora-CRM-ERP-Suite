<?php
session_start();
include('connection.php');
include('topbar.php');

// Function to get current financial year dates
function getFinancialYearDates($connection) {
    $fyQuery = "SELECT fy_code FROM financial_years WHERE is_current = 1 LIMIT 1";
    $fyResult = mysqli_query($connection, $fyQuery);
    $fyRow = mysqli_fetch_assoc($fyResult);

    if ($fyRow && isset($fyRow['fy_code'])) {
        $fyCode = $fyRow['fy_code'];
        $startYear = substr($fyCode, 0, 2);
        $endYear = substr($fyCode, 2, 2);

        // Convert 2-digit years to 4-digit (assuming 20xx)
        $startDate = "20{$startYear}-04-01";
        $endDate = "20{$endYear}-03-31";

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_year' => "20{$startYear}",
            'end_year' => "20{$endYear}"
        ];
    }

    // Fallback to current calendar year if financial year not found
    $currentYear = date('Y');
    return [
        'start_date' => "{$currentYear}-01-01",
        'end_date' => "{$currentYear}-12-31",
        'start_year' => $currentYear,
        'end_year' => $currentYear
    ];
}

// Get financial year dates
$fyDates = getFinancialYearDates($connection);

// Function to fetch data for all categories (removed contacts, followups, quotations, and invoices)
function fetchData($connection, $fyDates) {
    $queries = [
        'sales' => [
            'total' => "SELECT SUM(net_amount) AS total FROM invoices WHERE status = 'Finalized'",
            'yearly' => "SELECT SUM(net_amount) AS yearly FROM invoices WHERE status = 'Finalized' AND invoice_date BETWEEN '{$fyDates['start_date']}' AND '{$fyDates['end_date']}'",
            'monthly' => "SELECT SUM(net_amount) AS monthly FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(invoice_date) AS month, SUM(net_amount) AS total FROM invoices WHERE status = 'Finalized' AND invoice_date BETWEEN '{$fyDates['start_date']}' AND '{$fyDates['end_date']}' GROUP BY MONTH(invoice_date)",
            'monthly_data' => "SELECT DAY(invoice_date) AS day, SUM(net_amount) AS total FROM invoices WHERE status = 'Finalized' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE()) GROUP BY DAY(invoice_date)",
            'total_yearly_data' => "SELECT
                CASE
                    WHEN MONTH(invoice_date) >= 4 THEN YEAR(invoice_date)
                    ELSE YEAR(invoice_date) - 1
                END AS fy_year,
                SUM(net_amount) AS total
                FROM invoices
                WHERE status = 'Finalized' AND invoice_date IS NOT NULL
                GROUP BY
                CASE
                    WHEN MONTH(invoice_date) >= 4 THEN YEAR(invoice_date)
                    ELSE YEAR(invoice_date) - 1
                END
                ORDER BY fy_year"
        ],
        'expenses' => [
            'total' => "SELECT SUM(amount) AS total FROM expense",
            'yearly' => "SELECT SUM(amount) AS yearly FROM expense WHERE date BETWEEN '{$fyDates['start_date']}' AND '{$fyDates['end_date']}'",
            'monthly' => "SELECT SUM(amount) AS monthly FROM expense WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(date) AS month, SUM(amount) AS total FROM expense WHERE date BETWEEN '{$fyDates['start_date']}' AND '{$fyDates['end_date']}' GROUP BY MONTH(date)",
            'monthly_data' => "SELECT DAY(date) AS day, SUM(amount) AS total FROM expense WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) GROUP BY DAY(date)",
            'total_yearly_data' => "SELECT
                CASE
                    WHEN MONTH(date) >= 4 THEN YEAR(date)
                    ELSE YEAR(date) - 1
                END AS fy_year,
                SUM(amount) AS total
                FROM expense
                WHERE date IS NOT NULL
                GROUP BY
                CASE
                    WHEN MONTH(date) >= 4 THEN YEAR(date)
                    ELSE YEAR(date) - 1
                END
                ORDER BY fy_year"
        ],
        'salary' => [
            'total' => "SELECT SUM(total_amount) AS total FROM salary WHERE status = 'Approved'",
            'yearly' => "SELECT SUM(total_amount) AS yearly FROM salary WHERE status = 'Approved' AND DATE(created_at) BETWEEN '{$fyDates['start_date']}' AND '{$fyDates['end_date']}'",
            'monthly' => "SELECT SUM(total_amount) AS monthly FROM salary WHERE status = 'Approved' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(created_at) AS month, SUM(total_amount) AS total FROM salary WHERE status = 'Approved' AND DATE(created_at) BETWEEN '{$fyDates['start_date']}' AND '{$fyDates['end_date']}' GROUP BY MONTH(created_at)",
            'monthly_data' => "SELECT DAY(created_at) AS day, SUM(total_amount) AS total FROM salary WHERE status = 'Approved' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) GROUP BY DAY(created_at)",
            'total_yearly_data' => "SELECT
                CASE
                    WHEN MONTH(created_at) >= 4 THEN YEAR(created_at)
                    ELSE YEAR(created_at) - 1
                END AS fy_year,
                SUM(total_amount) AS total
                FROM salary
                WHERE status = 'Approved' AND created_at IS NOT NULL
                GROUP BY
                CASE
                    WHEN MONTH(created_at) >= 4 THEN YEAR(created_at)
                    ELSE YEAR(created_at) - 1
                END
                ORDER BY fy_year"
        ],
        'transactions' => [
            'total' => "SELECT ABS(SUM(amount)) AS total FROM party_ledger WHERE document_type = 'Payment Received'",
            'yearly' => "SELECT ABS(SUM(amount)) AS yearly FROM party_ledger WHERE document_type = 'Payment Received' AND date BETWEEN '{$fyDates['start_date']}' AND '{$fyDates['end_date']}'",
            'monthly' => "SELECT ABS(SUM(amount)) AS monthly FROM party_ledger WHERE document_type = 'Payment Received' AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())",
            'yearly_data' => "SELECT MONTH(date) AS month, ABS(SUM(amount)) AS total FROM party_ledger WHERE document_type = 'Payment Received' AND date BETWEEN '{$fyDates['start_date']}' AND '{$fyDates['end_date']}' GROUP BY MONTH(date)",
            'monthly_data' => "SELECT DAY(date) AS day, ABS(SUM(amount)) AS total FROM party_ledger WHERE document_type = 'Payment Received' AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) GROUP BY DAY(date)",
            'total_yearly_data' => "SELECT
                CASE
                    WHEN MONTH(date) >= 4 THEN YEAR(date)
                    ELSE YEAR(date) - 1
                END AS fy_year,
                ABS(SUM(amount)) AS total
                FROM party_ledger
                WHERE document_type = 'Payment Received' AND date IS NOT NULL
                GROUP BY
                CASE
                    WHEN MONTH(date) >= 4 THEN YEAR(date)
                    ELSE YEAR(date) - 1
                END
                ORDER BY fy_year"
        ]
    ];

    // Additional single queries (added back today_followups for first container only)
    $singleQueries = [
        'today_followups' => "SELECT COUNT(*) AS count FROM followup WHERE lead_status = 'Open' AND followup_date_nxt = CURDATE()",
        'amc_dues_today' => "SELECT COUNT(*) AS count FROM invoice_items WHERE amc_due_date = CURDATE()",
        'sales_today' => "SELECT SUM(net_amount) AS sales_today FROM invoices WHERE status = 'Finalized' AND DATE(invoice_date) = CURDATE()",
        'pending_amount' => "SELECT SUM(pending_amount) AS pending_amount FROM invoices WHERE status = 'Finalized'",
        'pending_leaves' => "SELECT COUNT(*) AS count FROM user_leave WHERE approver_id = ? AND status = 'Pending'",
        'expenses_today' => "SELECT SUM(amount) AS expenses_today FROM expense WHERE DATE(date) = CURDATE()",
        'salary_today' => "SELECT SUM(total_amount) AS salary_today FROM salary WHERE status = 'Approved' AND DATE(created_at) = CURDATE()"
    ];

    $data = [];

    // Process main category queries
    foreach ($queries as $category => $categoryQueries) {
        foreach ($categoryQueries as $type => $sql) {
            $result = mysqli_query($connection, $sql);
            if ($type === 'yearly_data' || $type === 'monthly_data' || $type === 'total_yearly_data') {
                $data[$category][$type] = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    if ($type === 'total_yearly_data') {
                        $data[$category][$type][$row['fy_year']] = $row['count'] ?? $row['total'];
                    } else {
                        $data[$category][$type][$row['month'] ?? $row['day']] = $row['count'] ?? $row['total'];
                    }
                }
            } else {
                $row = mysqli_fetch_assoc($result);
                $data[$category][$type] = $row[$type] ?? 0;
            }
        }
    }

    // Process single queries
    foreach ($singleQueries as $key => $query) {
        if ($key === 'pending_leaves') {
            $stmt = $connection->prepare($query);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = mysqli_fetch_assoc($result);
            $data[$key] = $row['count'] ?? 0;
            $stmt->close();
        } else {
            $result = mysqli_query($connection, $query);
            $row = mysqli_fetch_assoc($result);
            if ($key === 'pending_amount') {
                $data[$key] = $row['pending_amount'] ?? 0;
            } elseif ($key === 'sales_today') {
                $data[$key] = $row['sales_today'] ?? 0;
            } elseif ($key === 'expenses_today') {
                $data[$key] = $row['expenses_today'] ?? 0;
            } elseif ($key === 'salary_today') {
                $data[$key] = $row['salary_today'] ?? 0;
            } else {
                $data[$key] = $row['count'] ?? 0;
            }
        }
    }

    return $data;
}

// Fetch data
$data = fetchData($connection, $fyDates);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Splendid Infotech Dashboard</title>
    <link rel="stylesheet" href="styles.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            background: #ffffff; /* Changed to white */
            margin: 0;
            padding: 0;
            color: #333;
            min-height: 100vh;
        }

        .leadforhead {
            position: fixed;
            width: calc(100% - 290px);
            height: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 0 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            overflow: visible;
            margin-left: 260px;
            margin-top: 80px;
            backdrop-filter: blur(10px);
        }

        .leadforhead h2 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .content {
            margin-top: 100px;
            padding: 30px;
        }

        .hidden {
            margin-bottom: 30px;
        }

        /* Enhanced Card Styles for Top Section */
        .card-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-top: 20px;
            padding: 30px;
            background: #5a738c;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .card {
            background: #2c3e50;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2), 0 1px 8px rgba(0, 0, 0, 0.3);
            padding: 30px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .card:hover::before {
            left: 100%;
        }

        .card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 5px 15px rgba(0, 0, 0, 0.5);
        }

        .card h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card p {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .card p span {
            color: #ecf0f1;
            font-weight: 600;
            font-size: 16px;
        }

        /* Button Container Styles - Updated for 5 buttons in one row */
        .button-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin: 40px 0;
            padding: 30px;
            background: #5a738c;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .chart-button {
            padding: 20px 15px;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 700;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            background: #2c3e50;
            text-align: center;
        }

        .chart-button.active {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }

        .chart-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .chart-button:hover::before {
            left: 100%;
        }

        .chart-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        /* Chart Container Styles */
        .chart-container {
            margin: 40px 0;
            padding: 0;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .chart-container.show {
            max-height: 1200px;
            opacity: 1;
            padding: 30px;
        }

        .chart-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .chart-title {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Date Filter Styles */
        .date-filter {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .date-input-group {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .date-input-group label {
            font-size: 12px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .date-input-group input {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: border-color 0.3s ease;
        }

        .date-input-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-btn {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            margin-top: 20px;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.3);
        }

        /* Total Display Styles */
        .total-display {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .total-value {
            font-size: 48px;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .total-label {
            font-size: 18px;
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .chart-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .control-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .control-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .control-btn.active {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(231, 76, 60, 0.4);
        }

        .chart-types {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .chart-type-btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .chart-type-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .chart-type-btn.active {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }

        .single-chart {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            height: 600px;
            position: relative;
        }

        /* Chart canvas styling */
        #mainChart {
            height: 500px !important;
            width: 100% !important;
            position: relative;
        }

        .card h3, .card p {
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .fade-out {
            opacity: 0;
            transform: translateY(-10px);
        }

        .fade-in {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive design for smaller screens */
        @media (max-width: 1200px) {
            .button-container {
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }
            .chart-button {
                font-size: 14px;
                padding: 15px 10px;
            }
        }

        @media (max-width: 768px) {
            .button-container {
                grid-template-columns: repeat(2, 1fr);
            }
            .date-filter {
                flex-direction: column;
            }
            .chart-controls {
                flex-direction: column;
                align-items: center;
            }
            .single-chart {
                height: 400px;
                padding: 20px;
            }
            #mainChart {
                height: 300px !important;
            }
        }

        @media (max-width: 480px) {
            .button-container {
                grid-template-columns: 1fr;
            }
        }

        .backup-btn {
    text-decoration: none;
    background-color: #1abc9c;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    transition: background-color 0.3s ease, transform 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
}

.backup-btn:hover {
    background-color: #16a085;
    transform: scale(1.05);
}

.backup-btn i {
    font-size: 18px;
}

    </style>
</head>

<body>
  <div class="leadforhead">
  <h2>DASHBOARD</h2>
  <a href="backup.php" class="backup-btn" title="Download Backup">
      <i class="fas fa-download"></i> Backup
  </a>
</div>


    <!-- Main Content -->
    <div class="content">
        <!-- Top Priority Cards -->
        <div id="additionalCards" class="card-container hidden">
            <a href="today_followup.php" class="card-link" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <h3>Today's Followups</h3>
                    <p><span>Total:</span> <?php echo $data['today_followups']; ?></p>
                </div>
            </a>
            <a href="invoice_display.php" class="card-link" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <h3>Pending Amount</h3>
                    <p><span>Total:</span> ₹<?php echo number_format($data['pending_amount'], 2); ?></p>
                </div>
            </a>
            <a href="leave_approval_display.php?id=<?php echo $_SESSION['user_id']; ?>" class="card-link" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <h3>Pending Leaves</h3>
                    <p><span>Total:</span> <?php echo $data['pending_leaves']; ?></p>
                </div>
            </a>
        </div>
        <h2 style="font-size: 24px; font-weight: 700; color: #2c3e50; text-align: center; margin-top: 30px;">
        📅 Current Month Status
        </h2>

        <!-- Chart Buttons (5 buttons in one row) -->
        <div class="button-container">
            <button class="chart-button btn-sales" onclick="showChart('sales')">
                <span>💰 Sales</span><br>
                <small id="salesValue">₹<?php echo number_format($data['sales']['monthly'], 2); ?></small>
            </button>
            <button class="chart-button btn-transactions" onclick="showChart('transactions')">
                <span>💳 Payment</span><br>
                <small id="transactionsValue">₹<?php echo number_format($data['transactions']['monthly'], 2); ?></small>
            </button>
            <button class="chart-button btn-expenses" onclick="showChart('expenses')">
                <span>💸 Expenses</span><br>
                <small id="expensesValue">₹<?php echo number_format($data['expenses']['monthly'], 2); ?></small>
            </button>
            <button class="chart-button btn-salary" onclick="showChart('salary')">
                <span>💼 Salary</span><br>
                <small id="salaryValue">₹<?php echo number_format($data['salary']['monthly'], 2); ?></small>
            </button>
            <button class="chart-button btn-sales-vs-expense" onclick="showChart('sales-vs-expense')">
                <span>📊 Sales vs Exp.</span><br>
                <small id="salesVsExpenseValue">Analysis</small>
            </button>
        </div>

        <!-- Chart Container -->
        <div id="chartContainer" class="chart-container">
            <div class="chart-header">
                <h2 id="chartTitle" class="chart-title">Analytics</h2>

                <!-- Date Filter -->
                <div class="date-filter">
                    <div class="date-input-group">
                        <label for="startDate">Start Date</label>
                        <input type="date" id="startDate" />
                    </div>
                    <div class="date-input-group">
                        <label for="endDate">End Date</label>
                        <input type="date" id="endDate" />
                    </div>
                    <button class="filter-btn" onclick="applyDateFilter()">Apply Filter</button>
                </div>

                <!-- Data Period Controls -->
                <div class="chart-controls">
                    <button class="control-btn active" onclick="updateChart('total')">Year-wise</button>
                    <button class="control-btn" onclick="updateChart('yearly')">This Year</button>
                    <button class="control-btn" onclick="updateChart('monthly')">This Month</button>
                    <button class="control-btn" onclick="updateChart('custom')">Custom Range</button>
                </div>

                <!-- Total Display -->
                <div id="totalDisplay" class="total-display">
                    <div id="totalValue" class="total-value">0</div>
                    <div id="totalLabel" class="total-label">Total</div>
                </div>

                <!-- Chart Type Controls -->
                <div class="chart-types">
                    <button class="chart-type-btn active" onclick="changeChartType('bar')">Bar</button>
                    <button class="chart-type-btn" onclick="changeChartType('line')">Line</button>
                    <button class="chart-type-btn" onclick="changeChartType('pie')">Pie</button>
                    <button class="chart-type-btn" onclick="changeChartType('doughnut')">Doughnut</button>
                </div>
            </div>

            <!-- Charts Display Area -->
            <div id="chartsDisplay">
                <div class="single-chart">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentState = 'total';
        let cardData = <?php echo json_encode($data); ?>;
        let currentCard = '';
        let currentChartType = 'bar';
        let currentDataType = 'total';
        let mainChart = null;
        let customData = null;

        console.log('Card Data:', cardData); // Debug log

        // Function to show chart
        function showChart(category) {
            console.log('Showing chart for:', category); // Debug log
            currentCard = category;

            // Update active button
            document.querySelectorAll('.chart-button').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`.btn-${category}`).classList.add('active');

            // Update chart title
            if (category === 'sales-vs-expense') {
                document.getElementById('chartTitle').textContent = 'Sales vs Expense Comparison';
            } else if (category === 'transactions') {
                document.getElementById('chartTitle').textContent = 'Payment Analytics';
            } else {
                document.getElementById('chartTitle').textContent = category.charAt(0).toUpperCase() + category.slice(1) + ' Analytics';
            }

            // Show chart container with animation
            const container = document.getElementById('chartContainer');
            container.classList.add('show');

            // Scroll to chart
            setTimeout(() => {
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);

            // Update chart
            updateChart(currentDataType);
        }

        // Function to update chart based on data type
        function updateChart(type) {
            console.log('Updating chart with type:', type); // Debug log
            currentDataType = type;

            // Update active control button
            document.querySelectorAll('.control-btn').forEach(btn => btn.classList.remove('active'));
            const buttons = document.querySelectorAll('.control-btn');
            if (type === 'total') buttons[0].classList.add('active');
            else if (type === 'yearly') buttons[1].classList.add('active');
            else if (type === 'monthly') buttons[2].classList.add('active');
            else if (type === 'custom') buttons[3].classList.add('active');

            renderChart();
        }

        // Function to change chart type
        function changeChartType(type) {
            console.log('Changing chart type to:', type); // Debug log
            currentChartType = type;

            // Update active chart type button
            document.querySelectorAll('.chart-type-btn').forEach(btn => btn.classList.remove('active'));
            const buttons = document.querySelectorAll('.chart-type-btn');
            if (type === 'bar') buttons[0].classList.add('active');
            else if (type === 'line') buttons[1].classList.add('active');
            else if (type === 'pie') buttons[2].classList.add('active');
            else if (type === 'doughnut') buttons[3].classList.add('active');

            renderChart();
        }

        // Function to apply date filter
        function applyDateFilter() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (!startDate || !endDate) {
                alert('Please select both start and end dates');
                return;
            }

            if (startDate > endDate) {
                alert('Start date cannot be later than end date');
                return;
            }

            // Fetch custom data via AJAX
            fetchCustomData(startDate, endDate);
        }

        // Function to fetch custom date range data
        function fetchCustomData(startDate, endDate) {
            const formData = new FormData();
            formData.append('action', 'get_custom_data');
            formData.append('category', currentCard);
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);

            fetch('get_custom_data.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Custom data received:', data); // Debug log
                customData = data;
                currentDataType = 'custom';

                // Update active button
                document.querySelectorAll('.control-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelector('.control-btn:last-child').classList.add('active');

                renderChart();
            })
            .catch(error => {
                console.error('Error fetching custom data:', error);
                alert('Error fetching data. Please try again.');
            });
        }

        // Function to calculate total from data points
        function calculateTotal(dataPoints) {
            return dataPoints.reduce((sum, value) => sum + (parseFloat(value) || 0), 0);
        }

        // Function to update total display
        function updateTotalDisplay(total, label) {
            const totalValueElement = document.getElementById('totalValue');
            const totalLabelElement = document.getElementById('totalLabel');

            if (currentCard === 'sales' || currentCard === 'expenses' || currentCard === 'salary' || currentCard === 'transactions' || currentCard === 'sales-vs-expense') {
                totalValueElement.textContent = '₹' + total.toLocaleString('en-IN', {
                    maximumFractionDigits: 2,
                    minimumFractionDigits: 2
                });
            } else {
                totalValueElement.textContent = Math.round(total).toLocaleString('en-IN');
            }

            totalLabelElement.textContent = label;
        }

        // Function to render chart
        function renderChart() {
            if (!currentCard) {
                console.log('No current card selected');
                return;
            }

            console.log('Rendering chart for:', currentCard, 'Type:', currentDataType, 'Chart:', currentChartType);

            const ctx = document.getElementById('mainChart').getContext('2d');
            let labels = [];
            let dataPoints = [];
            let salesDataPoints = [];
            let expenseDataPoints = [];
            let salaryDataPoints = [];
            let totalLabel = '';

            // Handle Sales vs Expense comparison (now includes salary as part of total expenses for calculation)
            if (currentCard === 'sales-vs-expense') {
                if (currentDataType === 'custom' && customData) {
                    labels = customData.labels || [];
                    salesDataPoints = customData.sales_data || [];
                    expenseDataPoints = customData.expense_data || [];
                    salaryDataPoints = customData.salary_data || [];
                    totalLabel = `Custom Range Comparison`;
                } else if (currentDataType === 'total') {
                    // Show year-wise totals for sales, expenses, and salary
                    labels = [];
                    salesDataPoints = [];
                    expenseDataPoints = [];
                    salaryDataPoints = [];

                    // Get all years from sales, expenses, and salary
                    const allYears = new Set();
                    if (cardData['sales']['total_yearly_data']) {
                        Object.keys(cardData['sales']['total_yearly_data']).forEach(year => allYears.add(year));
                    }
                    if (cardData['expenses']['total_yearly_data']) {
                        Object.keys(cardData['expenses']['total_yearly_data']).forEach(year => allYears.add(year));
                    }
                    if (cardData['salary']['total_yearly_data']) {
                        Object.keys(cardData['salary']['total_yearly_data']).forEach(year => allYears.add(year));
                    }

                    // Sort years and create data arrays
                    const sortedYears = Array.from(allYears).sort();
                    sortedYears.forEach(year => {
                        labels.push(year);
                        salesDataPoints.push(cardData['sales']['total_yearly_data'][year] || 0);
                        expenseDataPoints.push(cardData['expenses']['total_yearly_data'][year] || 0);
                        salaryDataPoints.push(cardData['salary']['total_yearly_data'][year] || 0);
                    });

                    totalLabel = 'All Years Comparison';
                } else if (currentDataType === 'yearly') {
                    const monthNames = ["Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec", "Jan", "Feb", "Mar"];
                    labels = monthNames;
                    salesDataPoints = Array(12).fill(0);
                    expenseDataPoints = Array(12).fill(0);
                    salaryDataPoints = Array(12).fill(0);

                    // Fill sales data
                    if (cardData['sales'][`${currentDataType}_data`]) {
                        for (const [month, value] of Object.entries(cardData['sales'][`${currentDataType}_data`])) {
                            let adjustedMonth = month - 4;
                            if (adjustedMonth < 0) adjustedMonth += 12;
                            salesDataPoints[adjustedMonth] = value;
                        }
                    }

                    // Fill expense data
                    if (cardData['expenses'][`${currentDataType}_data`]) {
                        for (const [month, value] of Object.entries(cardData['expenses'][`${currentDataType}_data`])) {
                            let adjustedMonth = month - 4;
                            if (adjustedMonth < 0) adjustedMonth += 12;
                            expenseDataPoints[adjustedMonth] = value;
                        }
                    }

                    // Fill salary data
                    if (cardData['salary'][`${currentDataType}_data`]) {
                        for (const [month, value] of Object.entries(cardData['salary'][`${currentDataType}_data`])) {
                            let adjustedMonth = month - 4;
                            if (adjustedMonth < 0) adjustedMonth += 12;
                            salaryDataPoints[adjustedMonth] = value;
                        }
                    }

                    totalLabel = 'This Year Comparison';
                } else if (currentDataType === 'monthly') {
                    labels = Array.from({ length: 31 }, (_, i) => `Day ${i + 1}`);
                    salesDataPoints = Array(31).fill(0);
                    expenseDataPoints = Array(31).fill(0);
                    salaryDataPoints = Array(31).fill(0);

                    // Fill sales data
                    if (cardData['sales'][`${currentDataType}_data`]) {
                        for (const [day, value] of Object.entries(cardData['sales'][`${currentDataType}_data`])) {
                            salesDataPoints[day - 1] = value;
                        }
                    }

                    // Fill expense data
                    if (cardData['expenses'][`${currentDataType}_data`]) {
                        for (const [day, value] of Object.entries(cardData['expenses'][`${currentDataType}_data`])) {
                            expenseDataPoints[day - 1] = value;
                        }
                    }

                    // Fill salary data
                    if (cardData['salary'][`${currentDataType}_data`]) {
                        for (const [day, value] of Object.entries(cardData['salary'][`${currentDataType}_data`])) {
                            salaryDataPoints[day - 1] = value;
                        }
                    }

                    totalLabel = 'This Month Comparison';
                }

                // Calculate net profit/loss for total display (salary is treated as negative like expenses for calculation)
                const totalSales = calculateTotal(salesDataPoints);
                const totalExpenses = calculateTotal(expenseDataPoints);
                const totalSalary = calculateTotal(salaryDataPoints);
                const netAmount = totalSales - totalExpenses - totalSalary; // Salary is subtracted (treated as expense)

                updateTotalDisplay(netAmount, `Net ${netAmount >= 0 ? 'Profit' : 'Loss'} - ${totalLabel}`);
            } else {
                // Handle regular single category charts
                if (currentDataType === 'custom' && customData) {
                    labels = customData.labels || [];
                    dataPoints = customData.data || [];
                    totalLabel = `Custom Range Total`;
                } else if (currentDataType === 'total') {
                    // Show year-wise totals instead of single total
                    labels = [];
                    dataPoints = [];

                    if (cardData[currentCard]['total_yearly_data']) {
                        for (const [year, value] of Object.entries(cardData[currentCard]['total_yearly_data'])) {
                            labels.push(year);
                            dataPoints.push(value);
                        }
                    }

                    // If no yearly data, create a fallback with current year
                    if (labels.length === 0) {
                        labels = [new Date().getFullYear().toString()];
                        dataPoints = [cardData[currentCard]['total']];
                    }

                    totalLabel = 'All Years Total';
                } else if (currentDataType === 'yearly') {
                    const monthNames = ["Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec", "Jan", "Feb", "Mar"];
                    labels = monthNames;
                    dataPoints = Array(12).fill(0);

                    if (cardData[currentCard][`${currentDataType}_data`]) {
                        for (const [month, value] of Object.entries(cardData[currentCard][`${currentDataType}_data`])) {
                            let adjustedMonth = month - 4;
                            if (adjustedMonth < 0) adjustedMonth += 12;
                            dataPoints[adjustedMonth] = value;
                        }
                    }

                    totalLabel = 'This Year Total';
                } else if (currentDataType === 'monthly') {
                    labels = Array.from({ length: 31 }, (_, i) => `Day ${i + 1}`);
                    dataPoints = Array(31).fill(0);

                    if (cardData[currentCard][`${currentDataType}_data`]) {
                        for (const [day, value] of Object.entries(cardData[currentCard][`${currentDataType}_data`])) {
                            dataPoints[day - 1] = value;
                        }
                    }

                    totalLabel = 'This Month Total';
                }

                // Calculate and display total for single category
                const total = calculateTotal(dataPoints);
                updateTotalDisplay(total, totalLabel);
            }

            console.log('Chart data prepared:', { labels, dataPoints, salesDataPoints, expenseDataPoints, salaryDataPoints });

            if (mainChart) {
                mainChart.destroy();
            }

            // Color schemes for different categories (removed invoices)
            const colorSchemes = {
                sales: ['rgba(79, 172, 254, 0.8)', 'rgba(0, 242, 254, 0.8)'],
                transactions: ['rgba(168, 237, 234, 0.8)', 'rgba(254, 214, 227, 0.8)'],
                expenses: ['rgba(255, 154, 158, 0.8)', 'rgba(254, 207, 239, 0.8)'],
                salary: ['rgba(255, 236, 210, 0.8)', 'rgba(252, 182, 159, 0.8)'],
                'sales-vs-expense': ['rgba(79, 172, 254, 0.8)', 'rgba(255, 154, 158, 0.8)', 'rgba(255, 236, 210, 0.8)']
            };

            const colors = colorSchemes[currentCard] || ['rgba(54, 162, 235, 0.8)', 'rgba(255, 99, 132, 0.8)'];

            let chartConfig = {
                type: currentChartType,
                data: {
                    labels: labels,
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    elements: {
                        point: {
                            hoverRadius: 8,
                            hitRadius: 10
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: currentCard === 'sales-vs-expense' ?
                                `Sales vs Expense - ${totalLabel}` :
                                currentCard === 'transactions' ?
                                `Payment - ${totalLabel}` :
                                `${currentCard.charAt(0).toUpperCase() + currentCard.slice(1)} - ${totalLabel}`,
                            font: {
                                size: 18,
                                weight: 'bold'
                            }
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            position: 'nearest',
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    let value = context.parsed.y || context.parsed;
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (currentCard === 'sales' || currentCard === 'expenses' || currentCard === 'salary' || currentCard === 'transactions' || currentCard === 'sales-vs-expense') {
                                        label += '₹' + parseFloat(value).toLocaleString('en-IN', {
                                            maximumFractionDigits: 2,
                                            minimumFractionDigits: 2
                                        });
                                    } else {
                                        label += parseFloat(value).toLocaleString('en-IN');
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: (currentChartType === 'pie' || currentChartType === 'doughnut') ? {} : {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (currentCard === 'sales' || currentCard === 'expenses' || currentCard === 'salary' || currentCard === 'transactions' || currentCard === 'sales-vs-expense') {
                                        return '₹' + value.toLocaleString('en-IN');
                                    }
                                    return value.toLocaleString('en-IN');
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 0
                            }
                        }
                    }
                }
            };

            // Configure datasets based on category
            if (currentCard === 'sales-vs-expense') {
                if (currentChartType === 'pie' || currentChartType === 'doughnut') {
                    // For pie/doughnut, show total sales vs total expenses vs total salary
                    const totalSales = calculateTotal(salesDataPoints);
                    const totalExpenses = calculateTotal(expenseDataPoints);
                    const totalSalary = calculateTotal(salaryDataPoints);

                    chartConfig.data.labels = ['Sales', 'Expenses', 'Salary'];
                    chartConfig.data.datasets = [{
                        data: [totalSales, totalExpenses, totalSalary],
                        backgroundColor: colors,
                        borderWidth: 2
                    }];
                } else {
                    chartConfig.data.datasets = [
                        {
                            label: 'Sales',
                            data: salesDataPoints,
                            backgroundColor: colors[0],
                            borderColor: colors[0].replace('0.8', '1'),
                            borderWidth: 2
                        },
                        {
                            label: 'Expenses',
                            data: expenseDataPoints,
                            backgroundColor: colors[1],
                            borderColor: colors[1].replace('0.8', '1'),
                            borderWidth: 2
                        },
                        {
                            label: 'Salary',
                            data: salaryDataPoints,
                            backgroundColor: colors[2],
                            borderColor: colors[2].replace('0.8', '1'),
                            borderWidth: 2
                        }
                    ];
                }
            } else {
                if (currentChartType === 'pie' || currentChartType === 'doughnut') {
                    // For pie/doughnut, use labels as categories and dataPoints as values
                    chartConfig.data.datasets = [{
                        data: dataPoints,
                        backgroundColor: labels.map((_, index) =>
                            `hsl(${(index * 360 / labels.length)}, 70%, 60%)`
                        ),
                        borderWidth: 2
                    }];
                } else {
                    chartConfig.data.datasets = [{
                        label: currentCard === 'transactions' ? 'Payment' : currentCard.charAt(0).toUpperCase() + currentCard.slice(1),
                        data: dataPoints,
                        backgroundColor: colors[0],
                        borderColor: colors[0].replace('0.8', '1'),
                        borderWidth: 2,
                        fill: currentChartType === 'line' ? false : true
                    }];
                }
            }

            console.log('Final chart config:', chartConfig);

            try {
                mainChart = new Chart(ctx, chartConfig);
                console.log('Chart created successfully');
            } catch (error) {
                console.error('Error creating chart:', error);
            }
        }

        // Initialize with default data
        console.log('Dashboard initialized');
    </script>
</body>
</html>
