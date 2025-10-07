<?php
session_start();
include('connection.php');

if ($_POST['action'] === 'get_custom_data') {
    $category = $_POST['category'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];

    // Define table and column mappings (removed contacts, followups, quotations, and invoices)
    $categoryConfig = [
        'sales' => [
            'table' => 'invoices',
            'date_column' => 'invoice_date',
            'value_column' => 'net_amount',
            'aggregate' => 'SUM',
            'extra_condition' => "status = 'Finalized'"
        ],
        'expenses' => [
            'table' => 'expense',
            'date_column' => 'date',
            'value_column' => 'amount',
            'aggregate' => 'SUM',
            'extra_condition' => ''
        ],
        'salary' => [
            'table' => 'salary',
            'date_column' => 'created_at',
            'value_column' => 'total_amount',
            'aggregate' => 'SUM',
            'extra_condition' => "status = 'Approved'"
        ],
        'transactions' => [
            'table' => 'party_ledger',
            'date_column' => 'date',
            'value_column' => 'amount',
            'aggregate' => 'SUM',
            'extra_condition' => "document_type = 'Payment Received'"
        ]
    ];

    // Handle Sales vs Expense comparison (now includes salary as separate data)
    if ($category === 'sales-vs-expense') {
        $salesConfig = $categoryConfig['sales'];
        $expensesConfig = $categoryConfig['expenses'];
        $salaryConfig = $categoryConfig['salary'];

        // Calculate the date range to determine grouping strategy
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        $totalDays = $interval->days;

        $labels = [];
        $salesData = [];
        $expensesData = [];
        $salaryData = [];

        if ($totalDays <= 31) {
            // For ranges <= 31 days, show daily data

            // Sales query
            $salesWhereClause = "{$salesConfig['date_column']} BETWEEN '$startDate' AND '$endDate'";
            if (!empty($salesConfig['extra_condition'])) {
                $salesWhereClause .= " AND {$salesConfig['extra_condition']}";
            }

            $salesQuery = "SELECT DATE({$salesConfig['date_column']}) as date, {$salesConfig['aggregate']}({$salesConfig['value_column']}) as value
                          FROM {$salesConfig['table']}
                          WHERE $salesWhereClause
                          GROUP BY DATE({$salesConfig['date_column']})
                          ORDER BY DATE({$salesConfig['date_column']})";

            // Expenses query
            $expensesWhereClause = "{$expensesConfig['date_column']} BETWEEN '$startDate' AND '$endDate'";
            if (!empty($expensesConfig['extra_condition'])) {
                $expensesWhereClause .= " AND {$expensesConfig['extra_condition']}";
            }

            $expensesQuery = "SELECT DATE({$expensesConfig['date_column']}) as date, {$expensesConfig['aggregate']}({$expensesConfig['value_column']}) as value
                             FROM {$expensesConfig['table']}
                             WHERE $expensesWhereClause
                             GROUP BY DATE({$expensesConfig['date_column']})
                             ORDER BY DATE({$expensesConfig['date_column']})";

            // Salary query
            $salaryWhereClause = "DATE({$salaryConfig['date_column']}) BETWEEN '$startDate' AND '$endDate'";
            if (!empty($salaryConfig['extra_condition'])) {
                $salaryWhereClause .= " AND {$salaryConfig['extra_condition']}";
            }

            $salaryQuery = "SELECT DATE({$salaryConfig['date_column']}) as date, {$salaryConfig['aggregate']}({$salaryConfig['value_column']}) as value
                           FROM {$salaryConfig['table']}
                           WHERE $salaryWhereClause
                           GROUP BY DATE({$salaryConfig['date_column']})
                           ORDER BY DATE({$salaryConfig['date_column']})";

            // Get all dates in range
            $period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));
            $allDates = [];
            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $allDates[$dateStr] = 0;
                $labels[] = $date->format('M d, Y');
            }

            // Fill sales data
            $salesResult = mysqli_query($connection, $salesQuery);
            $salesDataMap = $allDates;
            while ($row = mysqli_fetch_assoc($salesResult)) {
                $salesDataMap[$row['date']] = floatval($row['value']);
            }

            // Fill expenses data
            $expensesResult = mysqli_query($connection, $expensesQuery);
            $expensesDataMap = $allDates;
            while ($row = mysqli_fetch_assoc($expensesResult)) {
                $expensesDataMap[$row['date']] = floatval($row['value']);
            }

            // Fill salary data
            $salaryResult = mysqli_query($connection, $salaryQuery);
            $salaryDataMap = $allDates;
            while ($row = mysqli_fetch_assoc($salaryResult)) {
                $salaryDataMap[$row['date']] = floatval($row['value']);
            }

            $salesData = array_values($salesDataMap);
            $expensesData = array_values($expensesDataMap);
            $salaryData = array_values($salaryDataMap);

        } else if ($totalDays <= 365) {
            // For ranges > 31 days but <= 1 year, show monthly data

            // Sales query
            $salesWhereClause = "{$salesConfig['date_column']} BETWEEN '$startDate' AND '$endDate'";
            if (!empty($salesConfig['extra_condition'])) {
                $salesWhereClause .= " AND {$salesConfig['extra_condition']}";
            }

            $salesQuery = "SELECT YEAR({$salesConfig['date_column']}) as year, MONTH({$salesConfig['date_column']}) as month, {$salesConfig['aggregate']}({$salesConfig['value_column']}) as value
                          FROM {$salesConfig['table']}
                          WHERE $salesWhereClause
                          GROUP BY YEAR({$salesConfig['date_column']}), MONTH({$salesConfig['date_column']})
                          ORDER BY YEAR({$salesConfig['date_column']}), MONTH({$salesConfig['date_column']})";

            // Expenses query
            $expensesWhereClause = "{$expensesConfig['date_column']} BETWEEN '$startDate' AND '$endDate'";
            if (!empty($expensesConfig['extra_condition'])) {
                $expensesWhereClause .= " AND {$expensesConfig['extra_condition']}";
            }

            $expensesQuery = "SELECT YEAR({$expensesConfig['date_column']}) as year, MONTH({$expensesConfig['date_column']}) as month, {$expensesConfig['aggregate']}({$expensesConfig['value_column']}) as value
                             FROM {$expensesConfig['table']}
                             WHERE $expensesWhereClause
                             GROUP BY YEAR({$expensesConfig['date_column']}), MONTH({$expensesConfig['date_column']})
                             ORDER BY YEAR({$expensesConfig['date_column']}), MONTH({$expensesConfig['date_column']})";

            // Salary query
            $salaryWhereClause = "{$salaryConfig['date_column']} BETWEEN '$startDate' AND '$endDate'";
            if (!empty($salaryConfig['extra_condition'])) {
                $salaryWhereClause .= " AND {$salaryConfig['extra_condition']}";
            }

            $salaryQuery = "SELECT YEAR({$salaryConfig['date_column']}) as year, MONTH({$salaryConfig['date_column']}) as month, {$salaryConfig['aggregate']}({$salaryConfig['value_column']}) as value
                           FROM {$salaryConfig['table']}
                           WHERE $salaryWhereClause
                           GROUP BY YEAR({$salaryConfig['date_column']}), MONTH({$salaryConfig['date_column']})
                           ORDER BY YEAR({$salaryConfig['date_column']}), MONTH({$salaryConfig['date_column']})";

            // Get all months in range
            $current = clone $start;
            $current->modify('first day of this month');
            $endMonth = clone $end;
            $endMonth->modify('first day of this month');

            $allMonths = [];
            while ($current <= $endMonth) {
                $monthKey = $current->format('Y-n');
                $allMonths[$monthKey] = 0;
                $labels[] = $current->format('M Y');
                $current->modify('+1 month');
            }

            // Fill sales data
            $salesResult = mysqli_query($connection, $salesQuery);
            $salesDataMap = $allMonths;
            while ($row = mysqli_fetch_assoc($salesResult)) {
                $monthKey = $row['year'] . '-' . $row['month'];
                if (isset($salesDataMap[$monthKey])) {
                    $salesDataMap[$monthKey] = floatval($row['value']);
                }
            }

            // Fill expenses data
            $expensesResult = mysqli_query($connection, $expensesQuery);
            $expensesDataMap = $allMonths;
            while ($row = mysqli_fetch_assoc($expensesResult)) {
                $monthKey = $row['year'] . '-' . $row['month'];
                if (isset($expensesDataMap[$monthKey])) {
                    $expensesDataMap[$monthKey] = floatval($row['value']);
                }
            }

            // Fill salary data
            $salaryResult = mysqli_query($connection, $salaryQuery);
            $salaryDataMap = $allMonths;
            while ($row = mysqli_fetch_assoc($salaryResult)) {
                $monthKey = $row['year'] . '-' . $row['month'];
                if (isset($salaryDataMap[$monthKey])) {
                    $salaryDataMap[$monthKey] = floatval($row['value']);
                }
            }

            $salesData = array_values($salesDataMap);
            $expensesData = array_values($expensesDataMap);
            $salaryData = array_values($salaryDataMap);

        } else {
            // For ranges > 1 year, show yearly data

            // Sales query
            $salesWhereClause = "{$salesConfig['date_column']} BETWEEN '$startDate' AND '$endDate'";
            if (!empty($salesConfig['extra_condition'])) {
                $salesWhereClause .= " AND {$salesConfig['extra_condition']}";
            }

            $salesQuery = "SELECT YEAR({$salesConfig['date_column']}) as year, {$salesConfig['aggregate']}({$salesConfig['value_column']}) as value
                          FROM {$salesConfig['table']}
                          WHERE $salesWhereClause
                          GROUP BY YEAR({$salesConfig['date_column']})
                          ORDER BY YEAR({$salesConfig['date_column']})";

            // Expenses query
            $expensesWhereClause = "{$expensesConfig['date_column']} BETWEEN '$startDate' AND '$endDate'";
            if (!empty($expensesConfig['extra_condition'])) {
                $expensesWhereClause .= " AND {$expensesConfig['extra_condition']}";
            }

            $expensesQuery = "SELECT YEAR({$expensesConfig['date_column']}) as year, {$expensesConfig['aggregate']}({$expensesConfig['value_column']}) as value
                             FROM {$expensesConfig['table']}
                             WHERE $expensesWhereClause
                             GROUP BY YEAR({$expensesConfig['date_column']})
                             ORDER BY YEAR({$expensesConfig['date_column']})";

            // Salary query
            $salaryWhereClause = "{$salaryConfig['date_column']} BETWEEN '$startDate' AND '$endDate'";
            if (!empty($salaryConfig['extra_condition'])) {
                $salaryWhereClause .= " AND {$salaryConfig['extra_condition']}";
            }

            $salaryQuery = "SELECT YEAR({$salaryConfig['date_column']}) as year, {$salaryConfig['aggregate']}({$salaryConfig['value_column']}) as value
                           FROM {$salaryConfig['table']}
                           WHERE $salaryWhereClause
                           GROUP BY YEAR({$salaryConfig['date_column']})
                           ORDER BY YEAR({$salaryConfig['date_column']})";

            // Get all years in range
            $startYear = intval($start->format('Y'));
            $endYear = intval($end->format('Y'));

            $allYears = [];
            for ($year = $startYear; $year <= $endYear; $year++) {
                $allYears[$year] = 0;
                $labels[] = strval($year);
            }

            // Fill sales data
            $salesResult = mysqli_query($connection, $salesQuery);
            $salesDataMap = $allYears;
            while ($row = mysqli_fetch_assoc($salesResult)) {
                $salesDataMap[intval($row['year'])] = floatval($row['value']);
            }

            // Fill expenses data
            $expensesResult = mysqli_query($connection, $expensesQuery);
            $expensesDataMap = $allYears;
            while ($row = mysqli_fetch_assoc($expensesResult)) {
                $expensesDataMap[intval($row['year'])] = floatval($row['value']);
            }

            // Fill salary data
            $salaryResult = mysqli_query($connection, $salaryQuery);
            $salaryDataMap = $allYears;
            while ($row = mysqli_fetch_assoc($salaryResult)) {
                $salaryDataMap[intval($row['year'])] = floatval($row['value']);
            }

            $salesData = array_values($salesDataMap);
            $expensesData = array_values($expensesDataMap);
            $salaryData = array_values($salaryDataMap);
        }

        echo json_encode([
            'labels' => $labels,
            'sales_data' => $salesData,
            'expense_data' => $expensesData,
            'salary_data' => $salaryData,
            'type' => 'comparison'
        ]);
        exit;
    }

    // Handle regular single categories
    if (!isset($categoryConfig[$category])) {
        echo json_encode(['error' => 'Invalid category']);
        exit;
    }

    $config = $categoryConfig[$category];

    // Calculate the date range to determine grouping strategy
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end);
    $totalDays = $interval->days;

    $labels = [];
    $data = [];

    // Handle regular categories with standard queries
    $whereClause = "{$config['date_column']} BETWEEN '$startDate' AND '$endDate'";
    if (!empty($config['extra_condition'])) {
        $whereClause .= " AND {$config['extra_condition']}";
    }

    // For salary, we need to handle the DATE() function for created_at
    if ($category === 'salary') {
        $whereClause = "DATE({$config['date_column']}) BETWEEN '$startDate' AND '$endDate'";
        if (!empty($config['extra_condition'])) {
            $whereClause .= " AND {$config['extra_condition']}";
        }
    }

    // Special handling for transactions aggregate function
    $aggregateFunction = $config['aggregate'];
    if ($category === 'transactions') {
        $aggregateFunction = "ABS(SUM";
    }

    if ($totalDays <= 31) {
        // For ranges <= 31 days, show daily data
        if ($category === 'salary') {
            $query = "SELECT DATE({$config['date_column']}) as date, {$aggregateFunction}({$config['value_column']})) as value
                      FROM {$config['table']}
                      WHERE $whereClause
                      GROUP BY DATE({$config['date_column']})
                      ORDER BY DATE({$config['date_column']})";
        } else if ($category === 'transactions') {
            $query = "SELECT DATE({$config['date_column']}) as date, {$aggregateFunction}({$config['value_column']})) as value
                      FROM {$config['table']}
                      WHERE $whereClause
                      GROUP BY DATE({$config['date_column']})
                      ORDER BY DATE({$config['date_column']})";
        } else {
            $query = "SELECT DATE({$config['date_column']}) as date, {$config['aggregate']}({$config['value_column']}) as value
                      FROM {$config['table']}
                      WHERE $whereClause
                      GROUP BY DATE({$config['date_column']})
                      ORDER BY DATE({$config['date_column']})";
        }

        $result = mysqli_query($connection, $query);

        while ($row = mysqli_fetch_assoc($result)) {
            $labels[] = date('M d, Y', strtotime($row['date']));
            $data[] = floatval($row['value']);
        }
    } else if ($totalDays <= 365) {
        // For ranges > 31 days but <= 1 year, show monthly data
        if ($category === 'transactions') {
            $query = "SELECT YEAR({$config['date_column']}) as year, MONTH({$config['date_column']}) as month, {$aggregateFunction}({$config['value_column']})) as value
                      FROM {$config['table']}
                      WHERE $whereClause
                      GROUP BY YEAR({$config['date_column']}), MONTH({$config['date_column']})
                      ORDER BY YEAR({$config['date_column']}), MONTH({$config['date_column']})";
        } else {
            $query = "SELECT YEAR({$config['date_column']}) as year, MONTH({$config['date_column']}) as month, {$config['aggregate']}({$config['value_column']}) as value
                      FROM {$config['table']}
                      WHERE $whereClause
                      GROUP BY YEAR({$config['date_column']}), MONTH({$config['date_column']})
                      ORDER BY YEAR({$config['date_column']}), MONTH({$config['date_column']})";
        }

        $result = mysqli_query($connection, $query);

        while ($row = mysqli_fetch_assoc($result)) {
            $monthName = date('M', mktime(0, 0, 0, $row['month'], 1));
            $labels[] = $monthName . ' ' . $row['year'];
            $data[] = floatval($row['value']);
        }
    } else {
        // For ranges > 1 year, show yearly data
        if ($category === 'transactions') {
            $query = "SELECT YEAR({$config['date_column']}) as year, {$aggregateFunction}({$config['value_column']})) as value
                      FROM {$config['table']}
                      WHERE $whereClause
                      GROUP BY YEAR({$config['date_column']})
                      ORDER BY YEAR({$config['date_column']})";
        } else {
            $query = "SELECT YEAR({$config['date_column']}) as year, {$config['aggregate']}({$config['value_column']}) as value
                      FROM {$config['table']}
                      WHERE $whereClause
                      GROUP BY YEAR({$config['date_column']})
                      ORDER BY YEAR({$config['date_column']})";
        }

        $result = mysqli_query($connection, $query);

        while ($row = mysqli_fetch_assoc($result)) {
            $labels[] = $row['year'];
            $data[] = floatval($row['value']);
        }
    }

    echo json_encode([
        'labels' => $labels,
        'data' => $data,
        'type' => 'date_range'
    ]);
}
?>
