<?php
session_start();
include('connection.php');

// Initialize variables
$report_data = [];
$selected_items = [];
$selected_location = '';
$start_date = '';
$end_date = '';
$errors = [];

// Get form data
if ($_POST) {
    $selected_location = $_POST['location'] ?? '';
    $selected_items = $_POST['items'] ?? [];
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    // Validation
    if (empty($selected_location)) {
        $errors[] = "Please select a location";
    }
    if (empty($selected_items)) {
        $errors[] = "Please select at least one item";
    }
    if (empty($start_date)) {
        $errors[] = "Please select start date";
    }
    if (empty($end_date)) {
        $errors[] = "Please select end date";
    }
    if (!empty($start_date) && !empty($end_date) && $end_date < $start_date) {
        $errors[] = "End date must be greater than or equal to start date";
    }

    if (empty($errors)) {
        // Generate report data for each selected item
        foreach ($selected_items as $item_code) {
            // Get item details
            $item_query = "SELECT item_name, item_code, unit_of_measurement_code
                          FROM item
                          WHERE item_code = '" . mysqli_real_escape_string($connection, $item_code) . "'
                          AND block = 0 AND item_type = 'inventory'";
            $item_result = mysqli_query($connection, $item_query);
            $item_details = mysqli_fetch_assoc($item_result);

            if ($item_details) {
                // Calculate opening stock (before start date)
                $opening_query = "SELECT COALESCE(SUM(quantity), 0) as opening_stock
                                FROM item_ledger_history
                                WHERE product_id = '" . mysqli_real_escape_string($connection, $item_code) . "'
                                AND location = '" . mysqli_real_escape_string($connection, $selected_location) . "'
                                AND date < '" . mysqli_real_escape_string($connection, $start_date) . "'";
                $opening_result = mysqli_query($connection, $opening_query);
                $opening_stock = mysqli_fetch_assoc($opening_result)['opening_stock'];

                // Calculate transactions during period
                $period_query = "SELECT
                                COALESCE(SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END), 0) as total_increase,
                                COALESCE(SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END), 0) as total_decrease,
                                COALESCE(SUM(quantity), 0) as net_change
                                FROM item_ledger_history
                                WHERE product_id = '" . mysqli_real_escape_string($connection, $item_code) . "'
                                AND location = '" . mysqli_real_escape_string($connection, $selected_location) . "'
                                AND date BETWEEN '" . mysqli_real_escape_string($connection, $start_date) . "'
                                AND '" . mysqli_real_escape_string($connection, $end_date) . "'";
                $period_result = mysqli_query($connection, $period_query);
                $period_data = mysqli_fetch_assoc($period_result);

                // Calculate total stock (from beginning to end date)
                $total_query = "SELECT COALESCE(SUM(quantity), 0) as total_stock
                               FROM item_ledger_history
                               WHERE product_id = '" . mysqli_real_escape_string($connection, $item_code) . "'
                               AND location = '" . mysqli_real_escape_string($connection, $selected_location) . "'
                               AND date <= '" . mysqli_real_escape_string($connection, $end_date) . "'";
                $total_result = mysqli_query($connection, $total_query);
                $total_stock = mysqli_fetch_assoc($total_result)['total_stock'];

                $closing_stock = $opening_stock + $period_data['net_change'];

                $report_data[] = [
                    'item_name' => $item_details['item_name'],
                    'item_code' => $item_details['item_code'],
                    'unit' => $item_details['unit_of_measurement_code'],
                    'opening_stock' => $opening_stock,
                    'total_increase' => $period_data['total_increase'],
                    'total_decrease' => $period_data['total_decrease'],
                    'net_change' => $period_data['net_change'],
                    'closing_stock' => $closing_stock,
                    'total_stock' => $total_stock
                ];
            }
        }
    }
} else {
    $errors[] = "No data received. Please go back and fill the form.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #2c3e50;
        }

        .report-container {
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
        }

        .logo {
            max-width: 150px;
            height: auto;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            color: #2c3e50;
        }

        .report-info {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .report-info div {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .report-info p {
            margin: 0;
            font-size: 14px;
        }

        .report-info strong {
            color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }

        table th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        .increase {
            color: #27ae60;
            font-weight: bold;
        }

        .decrease {
            color: #e74c3c;
            font-weight: bold;
        }

        .stock {
            color: #2c3e50;
            font-weight: bold;
        }

        .summary-section {
            margin-top: 30px;
            border-top: 2px solid #2c3e50;
            padding-top: 20px;
        }

        .summary-row {
            margin-top: 30px;
        }

        .summary-left {
            width: 100%;
        }

        .error-container {
            text-align: center;
            padding: 50px;
            color: #e74c3c;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin: 20px 0;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #7f8c8d;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin: 20px 0;
        }

        .action-buttons {
            text-align: center;
            margin: 20px 0;
        }

        .btn {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-print {
            background-color: #3498db;
            color: white;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 14px;
            color: #7f8c8d;
        }

        /* Print Styles */
        @media print {
            body {
                background-color: #fff;
            }

            .report-container {
                border: none;
                margin: 0;
                padding: 0;
                width: 100%;
                box-shadow: none;
            }

            .action-buttons, .btn {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="header">
            <?php
            $query = "SELECT company_logo FROM company_card WHERE id = 1";
            $result = mysqli_query($connection, $query);
            $company = mysqli_fetch_assoc($result);
            $company_logo = !empty($company['company_logo']) ? $company['company_logo'] : 'uploads/default_logo.png';
            ?>
            <img src="<?php echo $company_logo; ?>" alt="Logo" class="logo" />
            <h1>Stock Report</h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-container">
                <h3>Error</h3>
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($report_data)): ?>
            <div class="report-info">
                <div>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($selected_location); ?></p>
                    <p><strong>Report Period:</strong> <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?></p>
                </div>
                <div>
                    <p><strong>Generated On:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    <p><strong>Total Items:</strong> <?php echo count($report_data); ?></p>
                </div>
            </div>

            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-print">🖨️ Print Report</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Name</th>
                        <th>Item Code</th>
                        <th>Unit</th>
                        <th>Opening Stock</th>
                        <th>Total Increase</th>
                        <th>Total Decrease</th>
                        <th>Net Change</th>
                        <th>Closing Stock</th>
                        <th>Total Stock Present</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($report_data as $index => $item):
                    ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td class="stock"><?php echo number_format($item['opening_stock'], 2); ?></td>
                            <td class="increase">+<?php echo number_format($item['total_increase'], 2); ?></td>
                            <td class="decrease">-<?php echo number_format($item['total_decrease'], 2); ?></td>
                            <td class="<?php echo $item['net_change'] >= 0 ? 'increase' : 'decrease'; ?>">
                                <?php echo number_format($item['net_change'], 2); ?>
                            </td>
                            <td class="stock"><?php echo number_format($item['closing_stock'], 2); ?></td>
                            <td class="stock"><?php echo number_format($item['total_stock'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="summary-section">
                <div class="summary-row">
                    <div class="summary-left">
                        <h4>Report Summary</h4>
                        <p>This report shows stock movement for <?php echo count($report_data); ?> items in location <strong><?php echo htmlspecialchars($selected_location); ?></strong> during the period from <strong><?php echo htmlspecialchars($start_date); ?></strong> to <strong><?php echo htmlspecialchars($end_date); ?></strong>.</p>
                        <br>
                        <p><strong>Key Metrics:</strong></p>
                        <ul>
                            <li>Opening Stock: Total stock at the beginning of the period</li>
                            <li>Total Increase: Sum of all positive stock movements</li>
                            <li>Total Decrease: Sum of all negative stock movements</li>
                            <li>Net Change: Overall change during the period</li>
                            <li>Closing Stock: Stock at the end of the period</li>
                            <li>Total Stock Present: Current total stock available</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="no-data">
                <h3>No data found for the selected criteria</h3>
                <p>Please try different filter combinations or check if data exists for the selected period.</p>
            </div>
        <?php endif; ?>

        <div class="footer">
            <p>Generated by Stock Management System | <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
