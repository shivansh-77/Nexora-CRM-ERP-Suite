<?php
session_start();
include('connection.php');

// Get the fy_code from URL parameter
$fy_code = isset($_GET['fy_code']) ? $_GET['fy_code'] : '';

// Validate fy_code
if(empty($fy_code)) {
    die("Financial Year code is required");
}

// Fetch the selected financial year details (using the provided fy_code, not just current)
$fy_query = "SELECT * FROM financial_years WHERE fy_code = ? LIMIT 1";
$fy_stmt = $connection->prepare($fy_query);
$fy_stmt->bind_param("s", $fy_code);
$fy_stmt->execute();
$fy_result = $fy_stmt->get_result();
$fy_data = $fy_result->fetch_assoc();

if(!$fy_data) {
    die("Financial Year not found");
}

// Process month selection
if(isset($_GET['month'])) {
    $month = $_GET['month'];
    $month_yr = $_GET['month_yr'];
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];

    // Check if month is completed (current date is after end_date)
    $current_date = date('Y-m-d');
    if($current_date <= $end_date) {
        echo "<script>alert('Cannot create salary sheet for this month as it is not yet completed!'); window.location.href='salary_fy_months.php?fy_code=$fy_code';</script>";
        exit();
    }

    // Check if month already exists
    $check_query = "SELECT id FROM salary_sheet_months WHERE fy_code = ? AND month = ?";
    $check_stmt = $connection->prepare($check_query);
    $check_stmt->bind_param("ss", $fy_code, $month);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if($check_result->num_rows > 0) {
        echo "<script>alert('This month already exists!'); window.location.href='salary_fy_months.php?fy_code=$fy_code';</script>";
        exit();
    }

    // Insert new month
    $insert_query = "INSERT INTO salary_sheet_months (fy_code, month, month_yr, start_date, end_date, status)
                    VALUES (?, ?, ?, ?, ?, 'pending')";
    $insert_stmt = $connection->prepare($insert_query);
    $insert_stmt->bind_param("sssss", $fy_code, $month, $month_yr, $start_date, $end_date);

    if($insert_stmt->execute()) {
        header("Location: salary_fy_months.php?fy_code=$fy_code");
        exit();
    } else {
        echo "<script>alert('Error adding month!'); window.location.href='salary_fy_months.php?fy_code=$fy_code';</script>";
    }
    exit();
}

// Generate all months for the selected financial year
$months = [];
$start_year = date('Y', strtotime($fy_data['start_date']));
$end_year = date('Y', strtotime($fy_data['end_date']));
$current_date = date('Y-m-d');

// April to December of start year
for($i = 4; $i <= 12; $i++) {
    $month_name = date('F', mktime(0, 0, 0, $i, 1));
    $end_date = date('Y-m-t', strtotime($start_year . '-' . sprintf('%02d', $i) . '-01'));
    $is_completed = $current_date > $end_date;

    $months[] = [
        'name' => $month_name . ' ' . $start_year,
        'month_yr' => $start_year . '-' . sprintf('%02d', $i),
        'start_date' => $start_year . '-' . sprintf('%02d', $i) . '-01',
        'end_date' => $end_date,
        'is_completed' => $is_completed
    ];
}

// January to March of end year
for($i = 1; $i <= 3; $i++) {
    $month_name = date('F', mktime(0, 0, 0, $i, 1));
    $end_date = date('Y-m-t', strtotime($end_year . '-' . sprintf('%02d', $i) . '-01'));
    $is_completed = $current_date > $end_date;

    $months[] = [
        'name' => $month_name . ' ' . $end_year,
        'month_yr' => sprintf('%02d', $i) . '_' . $end_year,
        'start_date' => $end_year . '-' . sprintf('%02d', $i) . '-01',
        'end_date' => $end_date,
        'is_completed' => $is_completed
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Salary Month - <?php echo htmlspecialchars($fy_code); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-height: 700px;
        }
        h2 {
            color: #2c3e50;
            margin-top: 0;
        }
        .month-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        .month-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
        }
        .month-card.available {
            background-color: #e8f5e9;
            cursor: pointer;
        }
        .month-card.available:hover {
            background-color: #c8e6c9;
        }
        .month-card.exists {
            background-color: #e0f7fa;
        }
        .month-card.future {
            background-color: #ffebee;
            opacity: 0.7;
        }
        .status-indicator {
            font-size: 12px;
            margin-top: 5px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Select Month to Add</h2>
        <p>Financial Year: <?php echo htmlspecialchars($fy_data['fy_code']); ?>
        (<?php echo date('d M Y', strtotime($fy_data['start_date'])); ?> to <?php echo date('d M Y', strtotime($fy_data['end_date'])); ?>)</p>

        <div class="month-grid">
            <?php foreach($months as $month):
                // Check if month already exists
                $exists_query = "SELECT id FROM salary_sheet_months WHERE fy_code = ? AND month = ?";
                $exists_stmt = $connection->prepare($exists_query);
                $exists_stmt->bind_param("ss", $fy_code, $month['name']);
                $exists_stmt->execute();
                $exists_result = $exists_stmt->get_result();
                $already_exists = $exists_result->num_rows > 0;

                $card_class = '';
                $onclick = '';
                $status_text = '';

                if($already_exists) {
                    $card_class = 'exists';
                    $status_text = 'Already added';
                    $onclick = "alert('This month already exists!')";
                } elseif($month['is_completed']) {
                    $card_class = 'available';
                    $status_text = 'Available to add';
                    $onclick = "window.location.href='salary_month_add.php?fy_code=$fy_code&month=".urlencode($month['name'])."&month_yr=".$month['month_yr']."&start_date=".$month['start_date']."&end_date=".$month['end_date']."'";
                } else {
                    $card_class = 'future';
                    $status_text = 'Not yet completed';
                    $onclick = "alert('Cannot create salary sheet for this month as it is not yet completed!')";
                }
            ?>
            <div class="month-card <?php echo $card_class; ?>" onclick="<?php echo $onclick; ?>">
                <h3><?php echo htmlspecialchars($month['name']); ?></h3>
                <p><?php echo date('d M', strtotime($month['start_date'])); ?> - <?php echo date('d M Y', strtotime($month['end_date'])); ?></p>
                <div class="status-indicator"><?php echo $status_text; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
